<?php

class digimember_PageProductData extends ncore_BaseData
{
    private $postTypes = array();

    private $IGNORED_POST_TYPES = array( 'attachment', 'revision', 'nav_menu_item' );

    public function __construct( ncore_ApiCore $api, $file='', $dir='' )
    {
        parent::__construct( $api, $file, $dir );
    }

    public function isProtected( $page_id )
    {
        $where = array( 'post_id' => $page_id, 'is_active' => 'Y' );

        $all = $this->getAll( $where );

        $this->api->load->model( 'data/product' );

        foreach ($all as $one) {
            $product = $this->api->product_data->get( $one->product_id );
            $is_valid = $product && !$product->deleted && $product->type == 'membership';
            if ($is_valid) {
                return true;
            }
        }


        return false;
    }

    public function getCurrent( $post_type='page')
    {
        $page_id = ncore_getCurrentPageId();

        $candidates = $this->getForPage($post_type, $page_id, $active_only=true );

        if (!$candidates)
        {
            return false;
        }
        elseif (count($candidates)==1)
        {
            $page_product = $candidates[0];

            $this->_storeCurrentPageProduct( $page_product );

            return $page_product;
        }
        else
        {
            $product_id = $this->_getCurrentPageProduct( $page_id );

            $found_products = ncore_elementsWithKey( $candidates, 'product_id', $product_id );
            if ($found_products)
            {
                $found_entry = ncore_findByKey( $candidates, 'product_id', $product_id );
                if ($found_entry)
                {
                    $page_product = $found_entry;
                }
                else
                {
                    $page_product = $found_products[0];
                }
            }
            else
            {
                $page_product = $candidates[0];
            }

            $this->_storeCurrentPageProduct( $page_product );

            return $page_product;
        }
    }

    private function _storeCurrentPageProduct( $page_product )
    {
        $product_id = $page_product->product_id;
    }

    private function _getCurrentPageProduct( $page_id )
    {
        $product_id = false;

        return $product_id;
    }

    public function getForPage( $post_type, $page_id, $active_only=false  )
    {
        $page_products =& $this->pageCache[ $post_type ][ $page_id ];
        if (!isset($page_products))
        {
            $where = array(
                'post_id' => $page_id,
                'post_type' => $post_type,
            );

            $limit = false;

            $order_by = 'id ASC';

            $page_products = $this->getAll( $where, $limit, $order_by );
        }

        if ($active_only)
        {
            $this->api->load->helper( 'array' );
            return ncore_elementsWithKey( $page_products, 'is_active', 'Y' );
        }
        else
        {
            return $page_products;
        }

    }

    public function getAll( $where=array(), $limit=false, $order_by='' )
    {
        $all = parent::getAll( $where, $limit, $order_by );

        $this->api->load->model( 'data/product' );
        $products = $this->api->product_data->options( 'all' );

        $modified = false;

        foreach ($all as $index => $one)
        {
            $have_product = isset( $products[ $one->product_id ] );
            if (!$have_product) {
                unset( $all[ $index ] );
                $modified = true;
            }
        }

        if ($modified) {
            $all = array_values( $all );
        }

        return $all;
    }

    /**
     * @param $product_id
     * @return array
     */
    public function getLectureMenu( $product_id )
    {
        $where = array(
            'product_id' => $product_id,
            'is_active'  => 'Y',
        );

        $limit = false;
        $order_by = 'position ASC, id ASC';
        $page_products = $this->getAll( $where, $limit, $order_by );
        $post = get_post();
        $selected_post_id = $post
            ? $post->ID
            : false;

        /** @var digimember_AccessLogic $accessLogic */
        $accessLogic = $this->api->load->model('logic/access');

        $modified = false;
        foreach ($page_products as $index => $one) {
            if (get_post_status($one->post_id) != 'publish' || $accessLogic->accessType($one->post_type, $one->post_id)[0] == DIGI_ACCESS_NONE) {
                unset($page_products[$index]);
                $modified = true;
            }
        }
        if ($modified) {
            $page_products = array_values($page_products);
        }

        return $this->_getLectureMenu( $page_products, $product_id, $selected_post_id );
    }

    public function getPostsForProduct( $product_id, $post_type, $accessOnly = true)
    {
        $api = $this->api;
        $api->load->helper( 'array' );

        $where = array(
            'product_id' => $product_id,
            'is_active'  => 'Y',
        );

        $limit = false;
        $order_by = 'position ASC, unlock_day ASC, id ASC';

        $page_products = $this->getAll( $where, $limit, $order_by );

        $pages = $this->getAllPosts( $post_type, $accessOnly);

        $options = array();

        $numbers = array( 0 );

        $list_stack = array();

        $list_stack[] =& $options;

        foreach ($page_products as $page_product)
        {
            $page = ncore_findByKey( $pages, 'ID', $page_product->post_id );
            if (!$page || $page->post_type != $page_product->post_type) {
                continue;
            }

            $option = array();

            $parent_index = $page->post_parent
                ? ncore_indexOfByKey( $options, 'parent_id', $page->post_parent )
                : false;

            $option['post_id']      = $page->ID;
            $option['title']        = $page->post_title;
            $option['parent_id']    = $page->post_parent;
            $option['parent_index'] = $parent_index;

            $page_depth = 1+intval($page_product->level);
            $cur_depth  = count($numbers);

            $must_inc_depth = $page_depth > $cur_depth;
            $must_dec_depth = $page_depth < $cur_depth;

            $is_same_level =  !$must_inc_depth && !$must_dec_depth;

            if ($must_inc_depth) {
                $numbers[] = 1;
            }
            if ($must_dec_depth)
            {
                while ($page_depth < count($numbers))
                {
                    array_pop( $numbers );
                }

                $count = count($numbers);
                $numbers[ $count-1 ]++;
            }

            if ($is_same_level) {
                $count = count($numbers);
                $numbers[ $count-1 ]++;
            }

            $number_as_text = implode( '.', $numbers );
            $must_update_number = $number_as_text != $page_product->lecture_number;
            if ($must_update_number) {
                $data = array( 'lecture_number' => $number_as_text );
                $this->update( $page_product, $data );
            }

            $option['is_active']  = $page_product->is_active;
            $option['unlock_day'] = intval($page_product->unlock_day);
            $option['level']      = count($numbers) - 1;
            $option['number']     = $this->renderLectureNumber( $numbers );
            $option['position']   = $page_product->position;

            $option[ 'subpages' ] = array();

            if ($is_same_level)
            {
                $count = count( $list_stack );
                $list = &$list_stack[ $count-1 ];
                $list[] = $option;
            }
            elseif ($must_inc_depth)
            {
                $count     = count( $list_stack );
                $last_list = &$list_stack[ $count-1 ];

                $count     = count( $last_list );
                $last_elem = &$last_list[ $count-1 ];;

                $list = &$last_elem[ 'subpages' ];
                $list_stack[] = &$list;
                $list[] = $option;
            }
            else
            {
                array_pop( $list_stack );
                $count = count( $list_stack );
                $list = &$list_stack[ $count-1 ];
                $list[] = $option;
            }
        }

        $sanitized_options = array();

        $this->_sanitizeProductPostList( $sanitized_options, $options );

        return $sanitized_options;

    }

    public function storePostsForProduct( $product_id, $post_type, $post_recs )
    {
        $api = $this->api;
        $api->load->helper( 'array' );

        $where = array(
            'post_type' => $post_type,
            'product_id' => $product_id,
        );

        $all_entries = $this->getAll( $where );

        $position = 1;

        $numbers = array();

        foreach ($post_recs as $rec)
        {
            $one_post_id     = (int) ncore_retrieve( $rec, array( 'post_id', 'id' ) );
            $one_unlock_day  = (int) ncore_retrieve( $rec, 'unlock_day' );
            $level           = (int) ncore_retrieve( $rec, 'level', 0 );

            if (!$one_post_id)
            {
                continue;
            }

            $index = ncore_indexOfByKey( $all_entries, 'post_id', $one_post_id );

            $have_entry = $index !== false;

            $count = count($numbers);
            $must_add_level = $count <= $level;
            $must_rem_level = max( 0, $count - ($level+1) );

            if ($must_add_level) {
                $numbers[] = 1;
            }
            else
            {
                while ($must_rem_level-->0) {
                    array_pop($numbers);
                }

                $numbers[count($numbers)-1]++;
            }
            $number_as_text = implode( '.', $numbers );

            if ($have_entry)
            {
                $entry = $all_entries[ $index ];

                $data = array(
                    'is_active'      => 'Y',
                    'unlock_day'     => $one_unlock_day,
                    'lecture_number' => $number_as_text,
                    'level'          => $level,
                    'position'       => $position++,
                );

                $this->update( $entry->id, $data );

                unset( $all_entries[ $index ] );
            }
            else
            {
                $data = array(
                    'is_active'      => 'Y',
                    'unlock_day'     => $one_unlock_day,
                    'lecture_number' => $number_as_text,
                    'post_id'        => $one_post_id,
                    'post_type'      => $post_type,
                    'product_id'     => $product_id,
                    'level'          => $level,
                    'position'       => $position++,
                );

                $this->create( $data );

            }

        }

        foreach ($all_entries as $entry)
        {
            $this->delete( $entry->id );
        }
    }

    public function getAllPages($accessOnly = true)
    {
        return $this->getAllPosts( 'page', $accessOnly );
    }

    public function getAllPosts( $post_type, $accessOnly = true )
    {
        if ($accessOnly) {
            $posts = ncore_getPages( $post_type );
            return $posts;
        }
        else {
            $posts = ncore_getAllPages( $post_type );
            return $posts;
        }

    }

    public function savePageProduct( $rec )
    {
        $post_id    = ncore_retrieve( $rec, 'post_id' );
        $post_type  = ncore_retrieve( $rec, 'post_type' );
        $product_id = ncore_retrieve( $rec, 'product_id' );
        $unlock_day = ncore_retrieve( $rec, 'unlock_day' );
        $is_active  = ncore_retrieve( $rec, 'is_active' );

        $where = array(
            'post_id'    => $post_id,
            'post_type'  => $post_type,
            'product_id' => $product_id,
        );

        $data = array();
        $data['unlock_day'] = $unlock_day;
        $data['is_active'] = ($is_active?'Y':'N');

        $all = $this->getAll( $where );

        $is_first = true;
        foreach ($all as $one)
        {
            if ($is_first)
            {
                $is_first = false;
            }
            else
            {
                $this->delete( $one->id );
            }
        }

        $have_entry = (bool) $all;

        if ($have_entry)
        {
            $id = $all[0]->id;
            $this->update( $id, $data );
        }
        else
        {
            $data = array_merge( $data, $where );
            $this->create( $data );
        }

        /** @var ncore_EventSubscriberLogic $model */
        $model = $this->api->load->model( 'logic/event_subscriber' );
        $model->call( 'dm_page_product_changed', $post_id );
    }

    public function postTypes()
    {
        $postTypesChanged = false;
        if (count($this->postTypes) < 1) {
            $this->postTypes = get_post_types();
        }
        else {
            $newPostTypes = get_post_types();
            $diff = array_diff($newPostTypes, $this->postTypes);
            if ( is_array($diff) && count($diff) > 0 ) {
                $this->postTypes = $newPostTypes;
                $postTypesChanged = true;
            }
        }
        return array_keys( $this->postTypeOptions($postTypesChanged) );
    }

    public function postTypeOptions($forceRebuild = false)
    {
        static $options;
        if (!isset($options) || $forceRebuild) {
            $options = array(
                'page' => _digi('Pages'),
                'post' => _digi('Posts'),
            );

            $post_types_to_skip = $this->IGNORED_POST_TYPES;
            $post_types_to_skip[] = 'page';
            $post_types_to_skip[] = 'post';

            foreach ($this->postTypes as $type => $label)
            {
                $is_skipped = in_array( $type, $post_types_to_skip );
                if (!$is_skipped) {
                    $options[ $type ] = ncore_camelCase( $label, ncore_wordGlue() );
                }
            }
        }

        return $options;
    }


    public function isPostTypeHandled( $post_type )
    {
        static $cache;

        $handled =& $cache[ $post_type ];

        if (isset($handled)) {
            return $handled;
        }
        $handled = in_array( $post_type, $this->postTypes() );

        return $handled;
    }

    public function setupChecklistDone()
    {
        $this->api->load->helper( 'array' );

        $model = $this->api->load->model( 'data/product' );
        $where = array( 'published !=' => null );
        $product_objs = $model->getAll( $where );

        $published_product_ids = array_keys( ncore_listToArray( $product_objs, 'id', 'name' ) );

        $where = array( 'is_active' => 'Y' );
        $all = $this->getAll( $where );
        foreach ($all as $one)
        {
            $is_active = in_array( $one->product_id, $published_product_ids );
            if ($is_active)
            {
                return true;
            }
        }

        return false;
    }


    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'page_product';
    }

    protected function sqlTableMeta()
    {
       $columns = array(
        'post_id'         => 'id',
        'post_type'       => 'string[80]',
        'product_id'      => 'id',
        'is_active'       => 'yes_no_bit',
        'unlock_day'      => 'int',
        'position'        => 'int',
        'level'           => 'int',
        'lecture_number'  => 'string[15]',
       );

       $indexes = array( 'post_id', 'product_id' );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function defaultOrder()
    {
        return 'position ASC, product_id ASC, id ASC';
    }

    protected function buildObject( $object )
    {
        parent::buildObject( $object );

        if (!$object->unlock_day)
        {
            $object->unlock_day = '';
        }
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values['is_active'] = 'Y';

        return $values;
    }

    //
    // private section
    //
    private $pageCache = array();

    private function _sortProductPostList( &$options )
    {
        $sort_unlock_day = array();
        $sort_position   = array();
        $sort_page_name  = array();

        foreach ($options as $index => $one)
        {
            if (empty($one[ 'title']))
            {
                $sort_page_name[]  = 'aaaaa';
                $sort_unlock_day[] = 0;
                $sort_position[]   = 0;
            }
            else
            {
                $sort_page_name[]  = strtolower( $one[ 'title'] );
                $sort_unlock_day[] = $one[ 'unlock_day' ];
                $sort_position[]   = $one[ 'position' ];
            }
        }

        array_multisort( $sort_position, $sort_unlock_day, $sort_page_name, $options );
    }

    private function _sanitizeProductPostList( &$sanitized_options, $options )
    {
        if (empty($options)) {
            return;
        }

        $this->_sortProductPostList( $options );

        foreach ($options as $one)
        {
            $sublist = ncore_retrieveAndUnset( $one, 'subpages', array() );

            $sanitized_options[] = $one;

            $this->_sanitizeProductPostList( $sanitized_options, $sublist );
        }
    }

    private function renderLectureNumber( $numbers_array_or_text )
    {
        $numbers = is_string($numbers_array_or_text)
                 ? explode( ',', str_replace( '.', ',', $numbers_array_or_text ) )
                 : $numbers_array_or_text;

        return implode( '.', $numbers );
    }

    /**
     * Intern function to generate a menu structure of pages/post that is associated to a product, calls private function buildTree(array &$elements, $product_id, $selected_post_id, $parentId = 0)
     * @param $page_products
     * @param int $product_id
     * @param int $selected_post_id
     * @return array
     */
    private function _getLectureMenu($page_products, $product_id, $selected_post_id)
    {
        foreach ($page_products as $i => $product) {
            $count = $i;
            do {
                $prev = empty( $page_products[ $count - 1 ] )
                    ? false
                    : $page_products[ $count - 1 ];

                if ($prev && $prev->level < $product->level) {
                    $product->parent_id = $prev->id;
                    break;
                }
                else {
                    $product->parent_id = false;
                }
                $count--;
            } while($prev);
        }
        $productsById = [];
        foreach ($page_products as $value) {
            $productsById[$value->id] = $value;
        }

        return $this->_buildLectureMenuTree($productsById, $product_id, $selected_post_id,0);
    }

    /**
     * Function that recursively builds the correct menu structure of Pages/Post that are associated to a product. Original idea behind that structure is a non-balanced but sorted"tree"
     * @param array $elements
     * @param int $product_id
     * @param int $selected_post_id
     * @param int $parentId
     * @return array
     */
    private function _buildLectureMenuTree(array &$elements, $product_id, $selected_post_id, $parentId = 0) {

        $branch = array();

        foreach ($elements as $element) {

            if ($element->parent_id == $parentId) {
                $children = $this->_buildLectureMenuTree($elements, $product_id, $selected_post_id, $element->id);

                if ($children) {
                    $element->children = $children;
                }

                $post_list = ncore_getPages($element->post_type);

                $page_titles = [];
                foreach ($post_list as $single_page) {
                    $page_titles[$single_page->ID] = $single_page->post_title;
                }
                $title = ncore_retrieve($page_titles, $element->post_id);
                $url = ncore_addArgs(get_permalink($element->post_id), ['digimember_product_id' => $product_id]);
                $is_selected = $selected_post_id == $element->post_id;

                $menu_entry = [
                    'title' => $title,
                    'url' => $url,
                    'level' => $element->level,
                    'product_id' => $product_id,
                    'is_selected' => $is_selected,
                    'sub_menu' => $children,
                ];

                $zaehler=count($branch);
                $branch[$zaehler] = $menu_entry;

                if ($is_selected) {
                    $current_index = count($branch)-1;
                    $branch[0]['selected_index_by_level'] = [$current_index];
                } else if (count($children) && count(ncore_retrieve($children[0], 'selected_index_by_level', []))) {
                    $current_index = count($branch)-1;
                    $branch[0]['selected_index_by_level'] = array_merge([$current_index], $children[0]['selected_index_by_level']);
                } else if (!isset($branch[0]['selected_index_by_level'])) {
                    $branch[0]['selected_index_by_level'] = [];
                }
                unset($elements[$element->id]);
            }
        }
        return $branch;
    }
}