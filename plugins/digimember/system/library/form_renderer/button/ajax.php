<?php
  class ncore_Formrenderer_ButtonAjax extends ncore_Formrenderer_ButtonBase
  {
      protected function onClickJs()
      {
          $ajax_meta = $this->meta('ajax_meta', NCORE_ARG_REQUIRED );

          $ajax = $this->api->load->library( 'ajax' );

          $dialog = $ajax->dialog( $ajax_meta );

          $javascript = $dialog->showDialogJs();

          return "$javascript; return false;";
      }
  }