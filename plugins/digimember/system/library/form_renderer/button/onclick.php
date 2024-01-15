<?php
  class ncore_Formrenderer_ButtonOnclick extends ncore_Formrenderer_ButtonBase
  {
      protected function onClickJs()
      {
          $javascript = $this->meta('javascript', NCORE_ARG_REQUIRED );

          $javascript = ncore_minifyJs( $javascript );

          return "$javascript; return false;";
      }
  }