<?php

abstract class ncore_WidgetBaseEditController extends ncore_Controller
{
    public function renderForm( &$widget_instance, &$return, $instance ) {

        $input_metas   = $this->inputMetas();
        $section_metas = $this->sectionMetas();
        $button_metas  = array();

        $all_settings = $widget_instance->get_settings();

        $number = ncore_retrieve( $widget_instance, 'number' );

        $settings = ncore_retrieve( $all_settings, $number );

        foreach ($input_metas as $index => $meta)
        {
            if (!empty($meta['depends_on'])) {
                trigger_error( 'depends_on not implemented for widget settings forms' );
            }

            $only_for = ncore_retrieve( $meta, 'only_for' );
            if ($only_for && !in_array( get_class($widget_instance), $only_for )) {
                $input_metas[$index]['hide'] = true;
                continue;
            }

            $name = ncore_retrieve( $meta, 'name' );
            if ($name) {
                $input_metas[$index][ 'name' ] = $widget_instance->get_field_name( $name );

                $default = ncore_retrieve( $settings, $name );
                if ($default) {
                    $input_metas[$index][ 'default' ] = $default;
                }
            }
        }

        $form_settings = array(
            'layout'          => 'widget_editor',
            'plain_postnames' => true,
            'details_open'    => $this->isModified(),
        );

        /** @var ncore_FormRendererLib $lib */
        $lib = $this->api->load->library('form_renderer');

        $form = $lib->createForm(  $section_metas, $input_metas, $button_metas, $form_settings );

        $contentId = ncore_id();
//        echo '<div id="' . $contentId . '">';
        $form->render();
//        echo '</div>';

        if (ncore_isAjax())
        {
            echo '<script>ncore_setupJsForAllInputTypes();</script>';
        }
//        echo '<script>
//            if (typeof ncoreJQ.fn.dmInit !== "undefined") {
//                var applyDmInputs = function() {
//                console.log("#' . $contentId . '");
//                    ncoreJQ("#' . $contentId . '").dmInit(true);
//                };
//                ncoreJQ(document)
//                    .on("widget-updated widget-added", applyDmInputs);
//            }
//        </script>';
    }

    public function saveData( $instance, $new_instance, $old_instance, $wp_widget_object )
    {
        $modified = false;

        $input_metas = $this->inputMetas();
        foreach ($input_metas as $meta)
        {
            $only_for = ncore_retrieve( $meta, 'only_for' );
            if ($only_for && !in_array( get_class($wp_widget_object), $only_for )) {
                continue;
            }

            $name = ncore_retrieve( $meta, 'name' );
            if ($name) {
                $new_value = ncore_retrieve( $new_instance, $name );
                $old_value = ncore_retrieve( $instance,     $name );

                $instance[ $name ] = $new_value;

                if ($new_value != $old_value) {
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $this->modified = true;
        }

        return $instance;
    }

    abstract protected function inputMetas();

    protected function sectionMetas()
    {
        return array(
            'general' =>  array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
            ),
        );
    }

    protected function isModified()
    {
        return $this->modified;
    }

    private $modified = false;



}