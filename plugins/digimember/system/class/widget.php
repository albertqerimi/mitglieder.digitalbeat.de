<?php

abstract class ncore_WidgetClass extends WP_Widget
{
    private static $shortcode_controller_for_new_widgets = false;
    public static function setShortCodeController( $controller )
    {
        self::$shortcode_controller_for_new_widgets = $controller;
    }

    public function __construct()
    {
        $shortcode = $this->shortcode();

        /** @var digimember_ShortCodeController $shortcode_controller */
        $shortcode_controller = self::$shortcode_controller_for_new_widgets;

        $pluginName = $shortcode_controller->api()->pluginDisplayName();

        $id_base         = "ncore_${shortcode}_";
        $admin_label     = $pluginName . ' - ' . $shortcode_controller->widgetLabel( $shortcode );

        $description     = self::widgetDescription( $shortcode, $pluginName );

        $widget_options  = array(
            'dm_is_digimember_widget' => true,
            'dm_shortcode'            => $shortcode,
            'classname'               => 'ncore_WidgetClass',
            'description'             => $description,

        );

        $control_options = array();

        parent::__construct( $id_base, $admin_label, $widget_options, $control_options );

        $this->shortcode            = $shortcode;
        $this->shortcode_controller = $shortcode_controller;

        $this->api = $shortcode_controller->api();
    }

    public static function widgetDescription( $shortcode, $pluginName )
    {
        $map = array(
            'login' => _ncore( 'Shows a login area (like the %s %s shortcode)', $pluginName, $shortcode ),
            'account' => _digi('Allows a user to edit his display name and his password.') . ' '. _digi( '(like the %s %s shortcode)', $pluginName, $shortcode ),
            'lecture_buttons' => _digi('Create a navigation bar to move between the lectures of the current course.') . ' '. _digi( '(like the %s %s shortcode)', $pluginName, $shortcode ),
            'lecture_progress' => _digi('Display a progress bar to show the completed percentage of a course.') . ' '. _digi( '(like the %s %s shortcode)', $pluginName, $shortcode ),
            'menu' => _digi('Shows a menu inside the content area.') . ' '. _digi( '(like the %s %s shortcode)', $pluginName, $shortcode ),
            'signup' => _digi('Signup form - new users get a product.') . ' '. _digi( '(like the %s %s shortcode)', $pluginName, $shortcode ),
            'webpush' => _digi('Allows the user to optin to notifications - see %s, Tab %s', $pluginName . ' - ' . _digi( 'Push Notifications' ), _ncore( 'Settings' ) ) . ' '. _digi( '(like the %s %s shortcode)', $pluginName, $shortcode ),
        );

        if (!empty( $map[ $shortcode] ))
        {
            return $map[ $shortcode];
        }

        return '&nbsp;';
    }


    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {

        $inner_html = $this->shortcode_controller->renderShortcode( $this->shortcode, $instance );
        if ($inner_html === false)
        {
            return;
        }

        echo $args['before_widget'];

        $title = trim( ncore_retrieveAndUnset( $instance, 'title' ) );
        if ($title) {
            echo "<h3 class='widget-title'>$title</h3>";
        }

        echo $inner_html;

        echo $args['after_widget'];

    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {

        $section_metas = $this->sectionMetas();
        $input_metas   = $this->inputMetas();
        $button_metas  = array();

        $form_settings = array(
            'layout'          => 'widget_editor',
            'plain_postnames' => true,
            // 'details_open'    => $this->isModified(),
        );

        /** @var ncore_FormRendererLib $lib */
        $lib = $this->api->load->library('form_renderer');

        $form = $lib->createForm(  $section_metas, $input_metas, $button_metas, $form_settings );

        foreach ($input_metas as $meta)
        {
            if (empty($meta['name'])) {
                continue;
            }

            $name = $meta['name'];

            $have_default = !empty( $meta['default'] );
            $need_default = empty( $instance[ $name ] );

            if ($have_default && $need_default)
            {
                $instance[ $name ] = $meta['default'];
            }
        }


        $form->setData( 0, $instance );

        $form->render();

        echo '<script>
            window.dm_widget_id_counter = 0;
            if (typeof ncoreJQ.fn.dmInit !== "undefined") {
                if (typeof window.dm_has_widget_callback === "undefined") {
                    ncoreJQ(document).on("widget-updated widget-added", function(e, $addedContainer) {
                        $addedContainer.dmInit(true);
                        window.dm_widget_id_counter++;
                        $addedContainer.find("label[for]").each(function() {
                            var newId = ncoreJQ(this).attr("for") + window.dm_widget_id_counter;
                            $addedContainer.find("#" + ncoreJQ(this).attr("for")).attr("id", newId);
                            ncoreJQ(this).attr("for", newId);
                        });
                    });
                    window.dm_has_widget_callback = true;
                }
            }
        </script>';
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     * @return array
     */
    public function update( $new_instance, $old_instance ) {

        $instance = array();

        $input_metas   = $this->inputMetas();

        $modified = false;

        foreach ($input_metas as $meta)
        {
            $name = ncore_retrieve( $meta, 'name' );
            if ($name) {
                $new_value = ncore_retrieve( $_POST,    $name );
                $old_value = ncore_retrieve( $instance, $name );

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

    abstract protected function shortcode();


    /** @var digimember_ShortCodeController */
    private $shortcode_controller;
    /** @var ncore_ApiCore */
    private $api;
    private $shortcode;
    /** @var bool */
    private $modified;

    private function inputMetas()
    {
        $common_metas = array(
            array(
                'label'       => _digi( 'Title' ),
                'name'        => 'title',
                'type'        => 'text',
            ),
        );

        $short_code_metas = ncore_retrieve( $this->shortcode_controller->getShortcodeMetas( $this->shortcode ), 'args', array() );

        $metas = array_merge(
                    $common_metas,
                    $short_code_metas
        );

        foreach ($metas as $index => $meta)
        {
            $is_hidden = !empty( $meta[ 'is_only_for' ] )
                      && $meta[ 'is_only_for' ] !== 'widget';

            if ($is_hidden) {
                unset( $metas[ $index ] );
            }
        }

        $this->shortcode_controller->prepareWidgetInputMetas( $this->shortcode, $metas );

        return $metas;
    }


    private function sectionMetas()
    {
        return array(
            'general' =>  array(
                            'headline' => '',
                            'instructions' => '',
            )
        );
    }

}