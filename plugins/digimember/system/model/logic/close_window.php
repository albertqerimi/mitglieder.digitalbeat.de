<?php

class ncore_CloseWindowLogic extends ncore_BaseLogic
{
    public function renderCloseWindowJs( $window, $closed=1 )
    {
        $plugin = $this->api->pluginName();

        /** @var digimember_LinkLogic $link */
        $link = $this->api->load->model( 'logic/link' );

        $closed = intval( $closed );
        $window = ncore_washText( $window );

        $args = array(
                'domain' => $plugin,
                'closed' => $closed,
                'window' => $window,
                );

        $url = $link->ajaxUrl( 'ajax/info', 'model_close_window', $args );

        $js = "dmDialogAjax_FetchUrl( '$url', true );";

        if ($window == 'welcome_panel')
        {
            $js .= "ncoreJQ( '#wp_welcome_panel-hide' ).prop('checked', false); ncoreJQ('#welcome-panel').addClass('hidden'); ";
        }

        return $js;
    }

    public function attachCloseButton( $window )
    {
        $label = _ncore( 'Dismiss' );

        $js = $this->renderCloseWindowJs( $window );
        $js .= "ncoreJQ(this).parents('.dm-alert').slideUp();";

        return [$label, $js];
    }


    public function isWindowClosed( $window )
    {
        if ($window === 'welcome_panel' ) {
            $user_id = ncore_userId();
            $option = get_user_meta( $user_id, 'show_welcome_panel', true );

            return !$option;
        }

        $plugin = $this->api->pluginName();

        $key = ncore_washText( "closed-$plugin-$window" );

        $model = $this->api->load->model( 'data/user_settings' );

        $closed = $model->get( $key, 0 );

        return $closed;
    }

    public function setClosedWindow( $window, $closed, $plugin='' )
    {
        if ($window === 'welcome_panel' ) {
            $user_id = ncore_userId();
            $option = get_user_meta( $user_id, 'show_welcome_panel', true );
            $show = $closed ? 0 : 1;
            update_user_meta( $user_id, 'show_welcome_panel', $show, $option );
            return;
        }

        if (!$plugin) {
            $plugin = $this->api->pluginName();
        }

        $closed = $closed
                ? time()
                : 0;

        $key = ncore_washText( "closed-$plugin-$window" );

        /** @var ncore_UserSettingsData $model */
        $model = $this->api->load->model( 'data/user_settings' );
        $model->set( $key, $closed );
    }


}