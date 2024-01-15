<?php

class digimember_CourseLogic extends ncore_BaseLogic
{
    public function renderLectureMenu( $menu_product_obj_or_id='current', $omit_ul_countainer=false )
    {
        $is_menu = is_array( $menu_product_obj_or_id );
        $menu    = $is_menu
                 ? $menu_product_obj_or_id
                 : $this->getLectureMenu( $menu_product_obj_or_id );


        if (!$menu) {
            return '';
        }

        return $this->_renderMenuExec( $menu, $level=0, 'menu', $omit_ul_countainer );
    }

    public function getCourseProgress( $product_obj_or_id='current' )
    {
        $menu = $this->getLectureMenu( $product_obj_or_id );

        $module_completed = 0;
        $module_total     = 0;

        $course_completed  = 0;
        $course_total      = 0;

        if ($menu)
        {
            $is_before_current_selection = true;

            $module_index = ncore_retrieve( $menu[0]['selected_index_by_level'], $level=0, $default=0 );

            $this->_getLectureProgress( $menu, $level=0, $module_index, $is_selected_module=false, $is_before_current_selection, $module_completed, $module_total, $course_completed, $course_total );
        }

        return [
            'module' => array(
                'completed_count'  => $module_completed,
                'total_count'      => $module_total,
                'progress_rate'    => ($module_total ? round( 100*$module_completed/$module_total ) : 0),
            ),
            'course' => array(
                'completed_count'  => $course_completed,
                'total_count'      => $course_total,
                'progress_rate'    => ($course_total ? round( 100*$course_completed/$course_total ) : 0),
            ),
        ];
    }

    /**
     * @param string | stdClass $product_obj_or_id
     * @return array|bool|mixed
     */
    public function getLectureMenu( $product_obj_or_id='current')
    {
        static $cache;

        $menu =& $cache[ is_object( $product_obj_or_id )
                         ? $product_obj_or_id->id
                         : $product_obj_or_id ];

        if (isset($menu)) {
            return $menu;
        }

        $this->api->load->model( 'logic/access' );
        $product_id = $this->api->access_logic->resolveCurrentCourseProductId( $product_obj_or_id );
        if (!$product_id) {
            $menu = false;
        }
        else
        {
            $this->api->load->model( 'data/page_product' );
            $menu = $this->api->page_product_data->getLectureMenu( $product_id );
        }

        return $menu;
    }

    public function getLectureNavLinks( $product_obj_or_id='current')
    {
        $menu = $this->getLectureMenu( $product_obj_or_id );

        if (!$menu || empty($menu[0]['selected_index_by_level'])) {
            return array( $start_of_course=false, $prev_module=false, $prev_lecture=false, $next_lecture=false, $next_module=false, $end_of_course=false );
        }

        $can_have_module_links = count($menu) >= 2;

        $selected_index_by_level = $menu[0][ 'selected_index_by_level' ];

        //
        // Link to first page of course
        //
        $is_beginning_selected = count($selected_index_by_level) == 1
                              && $selected_index_by_level[0] == 0;

        $start_of_course = $is_beginning_selected
                           ? $this->_builtLectureNavDisabledRec( _digi( 'First lecture' ),_dgyou( 'You are at the beginning of the course.' ) )
                           : $this->_builtLectureNavLinkRec( $menu[0], _digi( 'First lecture' ) );



        //
        // Link to last page or course
        //
        $end_item        = $menu[ count($menu)-1 ];
        $is_end_selected = $selected_index_by_level && $selected_index_by_level[0] == count($menu)-1;

        $tmp_selected_indexes = $selected_index_by_level;
        while (!empty($end_item['sub_menu']))
        {
            $last_index = count($end_item['sub_menu'])-1;

            if ($is_end_selected) {
                array_shift( $tmp_selected_indexes );
                $is_end_selected = $tmp_selected_indexes && $tmp_selected_indexes[0] == $last_index;
            }

            $end_item = $end_item['sub_menu'][ $last_index ];
        }
        $end_of_course = $is_end_selected
                       ? $this->_builtLectureNavDisabledRec( _digi( 'Last lecture' ), _dgyou( 'You are at the end of the course.' ) )
                       : $this->_builtLectureNavLinkRec( $end_item, _digi( 'Last lecture' ) );


        //
        // Link to next/prev module/lecture
        //
        $have_selected_modul       = count($selected_index_by_level) >= 1;
        $have_selected_lecture     = count($selected_index_by_level) >= 2;
        $have_selected_sub_lecture = count($selected_index_by_level) >= 3;

        $prev_module  = false;
        $prev_lecture = false;

        if ($is_beginning_selected || !$have_selected_modul)
        {
            $prev_module  = $start_of_course;
            $next_module  = empty($menu[1])
                          ? $this->_builtLectureNavDisabledRec( _digi( 'Next lecture' ), _dgyou( 'The course has only one module.' ) )
                          : $this->_builtLectureNavLinkRec( $menu[1], _digi( 'Next lecture' ) );
        }
        else
        {
            $module_index  = $selected_index_by_level[0];

            $is_lecture_of_module_selected = $have_selected_lecture;

            $prev_module = $is_lecture_of_module_selected
                         ? $this->_builtLectureNavLinkRec( $menu[ $module_index ], _digi( 'Start of this module' ) )
                         : ($module_index>0
                            ? $this->_builtLectureNavLinkRec( $menu[ $module_index-1 ], _digi( 'Previous module' ) )
                            : $this->_cloneLectureNavLinkRec( $start_of_course, _digi( 'Previous module' ) ) );

            $next_module = $module_index < count($menu)-1
                         ? $this->_builtLectureNavLinkRec( $menu[ $module_index+1 ], _digi( 'Next module' ) )
                         : $this->_cloneLectureNavLinkRec( $end_of_course, _digi( 'Next module' ) );
        }


        if ($is_end_selected || !$have_selected_modul)
        {
            $next_module  = $end_of_course;
        }

        $next_lecture = $this->_lectureLink_next_lecture( $menu, $selected_index_by_level, $next_module );
        $prev_lecture = $this->_lectureLink_prev_lecture( $menu, $selected_index_by_level, $prev_module );

        if (!$can_have_module_links) {
            $prev_module = false;
            $next_module = false;
        }

        return array( $start_of_course, $prev_module, $prev_lecture, $next_lecture, $next_module, $end_of_course );
    }

    private function _builtLectureNavLinkRec( $menu_item, $label )
    {
        return array(
            'label' => $label,
            'title' => $menu_item['title'],
            'url'   => $menu_item['url'],
        );
    }

    private function _builtLectureNavDisabledRec( $label, $description )
    {
        return array(
            'label' => $label,
            'title' => $description,
            'url'   => false,
        );
    }

    private function _cloneLectureNavLinkRec( $rec, $label )
    {
        $rec[ 'label' ] = $label;
        return $rec;
    }

    private function _getMenuItem( $menu, $nested_index_list, $label='[LABEL]', $description='' )
    {
        $item = $menu[ $nested_index_list[0] ];
        array_shift( $nested_index_list );

        while ($nested_index_list)
        {
            $index = array_shift( $nested_index_list );
            $item = $item['sub_menu'][ $index ];
        }

        $find = '[LABEL]';
        $repl = $item['title'];

        return array(
            'label'    => str_replace( $find, $repl, $label ),
            'title'    => str_replace( $find, $repl, $description ),
            'url'      => $item[ 'url' ],
            'sub_menu' => (empty( $item[ 'sub_menu' ] ) ? array() : $item[ 'sub_menu' ]),
        );
    }

    private function _getLectureProgress( $menu, $level, $module_index, $is_selected_module, &$is_before_current_selection, &$module_completed, &$module_total, &$course_completed, &$course_total )
    {
        $course_total += count($menu);

        if ($is_selected_module) {
            $module_total += count($menu);
        }

        foreach ($menu as $index => $item)
        {
            if ($level == 0)
            {
                $is_selected_module = $module_index == $index;
                if ($is_selected_module) {
                    $module_total++;
                }
            }

            if ($is_before_current_selection) {
                $course_completed++;

                if ($is_selected_module) {
                    $module_completed++;
                }
            }

            if ($item['is_selected']) {
                $is_before_current_selection = false;
            }

            $has_submenu = !empty( $item['sub_menu'] );
            if ($has_submenu) {
                $this->_getLectureProgress( $item['sub_menu'], $level+1, $module_index, $is_selected_module, $is_before_current_selection, $module_completed, $module_total, $course_completed, $course_total );
            }
        }
    }

    private function _renderMenuExec( $menu, $current_level, $class, $omit_ul_countainer=false )
    {
        if (!$menu) {
            return false;
        }

        $html = '';
        
        if (!$omit_ul_countainer) {
            $html .= "<ul class='$class digimember-depth-$current_level'>";
        }

        foreach ($menu as $item)
        {
//            if (!$item['title']) {
//                continue;
//            }
            $have_submenu = !empty( $item['sub_menu'] );

            $css = $item[ 'is_selected' ]
                 ? 'current-menu-item digimember_selected_menu'
                 : '';

            if ($have_submenu) {
                $css .= ' menu-item-has-children';
            }

            $html .= "<li class='menu-item menu-item-type-post_type menu-item-object-page $css'><a href='${item['url']}'>${item['title']}</a>";


            if ($have_submenu)
            {
                $html .= $this->_renderMenuExec( $item['sub_menu'], $current_level+1, 'sub-menu' );
            }

            $html .= "</li>";
        }

        if (!$omit_ul_countainer) {
            $html .= "</ul>";
        }

        return $html;
    }

    private function _lectureLink_next_lecture( $menu, $selected_index_by_level, $fallback_link )
    {
        $next_lecture           = false;
        $current_menu_to_check  = $menu;
        $current_index_by_level = $selected_index_by_level;
        $current_next_menu      = false;

        while (true)
        {
            $current_index = $selected_index_by_level
                           ? array_shift( $selected_index_by_level )
                           : false;

            if ($current_index !== false && !empty( $current_menu_to_check[ $current_index+1 ]))
            {
                $current_next_menu = $current_menu_to_check[ $current_index+1 ];
            }

            if ($current_index === false)
            {
                $next_lecture = empty( $current_menu_to_check )
                              ? ($current_next_menu
                                 ? $this->_builtLectureNavLinkRec( $current_next_menu, _digi( 'Next lecture' ) )
                                 : $fallback_link )
                              : $this->_builtLectureNavLinkRec( $current_menu_to_check[0], _digi( 'Next lecture' ) );
                break;
            }
            elseif (empty( $current_menu_to_check[ $current_index ]['sub_menu'] ))
            {
                $next_lecture = ($current_next_menu
                                 ? $this->_builtLectureNavLinkRec( $current_next_menu, _digi( 'Next lecture' ) )
                                 : $fallback_link );
                break;
            }
            else
            {
                $current_menu_to_check = $current_menu_to_check[ $current_index ]['sub_menu'];
            }
        }

        return $next_lecture;
    }


    private function _lectureLink_prev_lecture( $menu, $selected_index_by_level, $fallback_link )
    {
        if (!$selected_index_by_level) {
            return $fallback_link;
        }

        $is_first_modul = count($selected_index_by_level) == 1 && $selected_index_by_level[0] == 0;
        if ($is_first_modul) {
            return $fallback_link;
        }

        $current_level = count( $selected_index_by_level ) -1 ;

        $can_step_back_on_same_level = $selected_index_by_level[ $current_level ] > 0;

        if ($can_step_back_on_same_level)
        {
            $selected_index_by_level[ $current_level ]--;

            $current_menu = $menu;
            for ($i=0; $i<count($selected_index_by_level)-1; $i++)
            {
                $current_menu = $current_menu[ $selected_index_by_level[$i] ]['sub_menu'];
            }

            $current_entry = $current_menu[ end($selected_index_by_level) ];

            return empty($current_entry['sub_menu'])
                   ? $this->_builtLectureNavLinkRec( $current_entry, _digi( 'Previous lecture' ) )
                   : $this->_builtLectureNavLinkRec( end($current_entry['sub_menu']), _digi( 'Previous lecture' ) );

        }
        else
        {
            array_pop( $selected_index_by_level );

            $current_menu = $menu;
            for ($i=0; $i<count($selected_index_by_level)-1; $i++)
            {
                $current_menu = $current_menu[ $selected_index_by_level[$i] ]['sub_menu'];
            }

            $current_entry = $current_menu[ end($selected_index_by_level) ];

            return $this->_builtLectureNavLinkRec( $current_entry, _digi( 'Previous lecture' ) );
        }




    }
}