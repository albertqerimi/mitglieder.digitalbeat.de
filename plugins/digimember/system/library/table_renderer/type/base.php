<?php

abstract class ncore_TableRenderer_TypeBase extends ncore_Plugin
{
    public final function __construct( $parent, $meta )
    {
        $type = $meta['type'];

        if (empty( $meta['column'] ) )
        {
            if (!empty($meta['name'])) {
                $meta['column' ] = $meta['name'];
            }
        }

        if (empty( $meta['column'] ) )
        {
            $meta['column'] = 'id';
            $meta['sortable'] = false;
        }

        foreach ($this->metaDefaults() as $k => $v)
        {
            if (!isset($meta[$k])) {
                $meta[$k]= $v;
            }
        }

        parent::__construct( $parent, $type, $meta );


        $this->column = $meta['column'];

        $this->init();
    }

    public function column()
    {
        return $this->column;
    }

    public function label()
    {
        return $this->meta( 'label' );
    }

    public function headerCss()
    {
        $type = $this->meta( 'type' );
        $column = $this->column();

        $extra_css = $this->meta( 'css' );

        return array( 'manage-column', "column-$column type-$type $extra_css" );
    }

    public function cellCss()
    {
        $type = $this->meta( 'type' );
        $column = $this->column();

        $extra_css = $this->meta( 'css' );

        return array( "column-$column type-$type $extra_css" );
    }

    public function isInSameCellAsNext() {
        return $this->meta( 'is_in_cell_with_next', false );
    }

    public function render( $row )
    {
        $inner_html = $this->matchesCondition( $row )
                    ? $this->renderInner( $row )
                    : '';

        // $inner_html = $this->applyFilters( $inner_html );

        $html = "<span class='contents'>$inner_html</span>";

        $actions = $this->renderActions( $row );

        if ($actions)
        {
            $html .= "<div class='row-actions'>$actions</div>";
        }

        return $html;
    }

    public function renderWhere( $search_for )
    {
        $column = $this->column;
        $compare = $this->meta( 'compare', 'equal' );

        $where = array();

        $is_like = $compare == 'like';
        if ($is_like)
        {
            $where[ "$column LIKE"] = $search_for;
        }
         else
         {
            $where[ $column ] = $search_for;
         }

         return $where;
    }

    public function searchType()
    {
        return $this->meta( 'search', false );
    }

    public function sorting()
    {
        $is_sortable = $this->meta( 'sortable', false );

        if ($is_sortable)
        {
            $order_by = $this->column();

            $is_down = $is_sortable === 'desc';

            $order_dir = $is_down ? 'desc' : 'asc';

            return array( $order_by, $order_dir );
        }

        return array( $order_by='', 'asc' );
    }



    protected $column;

    protected function init()
    {
    }

    protected function metaDefaults()
    {
        return array();
    }

    protected function value( $row )
    {
        $value = ncore_retrieve( $row, $this->column );
        return $value;
    }

    protected abstract function renderInner( $row );

    protected function renderActions( $row )
    {
        $actions = $this->getActions( $row );

        $html = '';

        foreach ($actions as $index => $one)
        {
            $is_last = $index == count($actions) - 1;

            $html .= $this->renderOneAction( $one, $row, $is_last );
        }

        return $html;
    }

    protected function renderOneAction( $meta, $row, $is_last )
    {
        $action   = ncore_retrieve( $meta, 'action' );
        $as_popup = ncore_retrieve( $meta, 'as_popup', false );

        $css = $action . ' ' . ncore_retrieve( $meta, 'css' );

        $url = $this->actionUrl( $meta, $row );
        $label = ncore_retrieve( $meta, 'label' );

        $attr = array();
        if ($as_popup)
        {
            $attr[ 'as_popup' ] = $as_popup;
        }


        $link = ncore_htmlLink( $url, $label, $attr );

        $html = "<span class='$css'>$link";

        if (!$is_last)
        {
            $html .= ' | ';
        }

        $html .= '</span>';

        return $html;
    }

    //
    // private
    //

//    private function applyFilters( $cell_content_html )
//    {
//        $filter = $this->meta( 'filter' );
//        if (empty($filter)) {
//            return $cell_content_html;
//        }
//
//        $is_array = is_array( $filter )
//                 && !is_object( $filter[0] );
//
//        if ($is_array) {
//            foreach ($filter as $one) {
//                $cell_content_html = call_user_func( $one, $cell_content_html );
//            }
//        }
//        else {
//            $cell_content_html = call_user_func( $filter, $cell_content_html );
//        }
//
//        return $cell_content_html;
//    }




    private function getActions( $row )
    {
        $result = array();

        $actions = $this->meta( 'actions', array() );

        foreach ($actions as $one)
        {
            $depends_on = ncore_retrieve( $one, 'depends_on', array() );

            foreach ($depends_on as $key => $values)
            {
                if (!is_array($values))
                {
                    $values = array( $values );
                }

                $value = ncore_retrieve( $row, $key );

                $matches = in_array( $value, $values );

                if (!$matches)
                {
                    continue 2;
                }
            }

            $result[] = $one;
        }

        return $result;
    }

    private function actionUrl( $one, $row )
    {
        $id = $row->id;

        $url = $one['url'];

        $find = '_ID_';
        $repl = $id;

        return str_replace( $find, $repl, $url );
    }

    private function matchesCondition( $row )
    {
        $condition = $this->meta( 'condition', array() );
        if (!$condition) {
            return true;
        }

        foreach ($condition as $column_operator => $compare_to )
        {
            list( $column, $operator ) = ncore_retrieveList( ' ', $column_operator );

            $value = ncore_retrieve( $row, $column );

            switch ($operator) {
                case '!=':
                    if ($value == $compare_to) {
                        return false;
                    }
                    break;

                case '>':
                    if ($value <= $compare_to) {
                        return false;
                    }
                    break;

                case '>=':
                    if ($value < $compare_to) {
                        return false;
                    }
                    break;

                case '<':
                    if ($value >= $compare_to) {
                        return false;
                    }
                    break;

                case '<=':
                    if ($value > $compare_to) {
                        return false;
                    }
                    break;

                case 'IN':
                    $is_match = in_array( $value, $compare_to );
                    if (!$is_match) {
                        return false;
                    }
                    break;

                case '==':
                case '=':
                default:
                    if ($value != $compare_to) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}