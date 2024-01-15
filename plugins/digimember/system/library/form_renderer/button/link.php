<?php
  class ncore_Formrenderer_ButtonLink extends ncore_Formrenderer_ButtonBase
  {
      function label()
      {
          $label = $this->meta('label', _ncore('Back') );
          return $label;
      }

      protected function onClickJs()
      {
          $url = $this->meta('url', NCORE_ARG_REQUIRED );

          $as_popup = $this->meta( 'as_popup', false );

          return $as_popup
                 ? "window.open( '$url' ); return false;"
                 : "location.href='$url'; return false;";
      }
  }