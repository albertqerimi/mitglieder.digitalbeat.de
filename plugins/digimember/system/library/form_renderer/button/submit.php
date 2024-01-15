<?php
  class ncore_Formrenderer_ButtonSubmit extends ncore_Formrenderer_ButtonBase
  {
      protected function onClickJs()
      {
          $onclickjs = $this->meta('onclick', '');
          
          $confirm = $this->meta('confirm');

          if ($confirm)
          {
              $confirm = str_replace( array( '"', "'", '<p>', '|', '<br>' ), array( '&quot;', "\\'", "\\n\\n", "\\n\\n", "\\n" ), $confirm );
              return "$onclickjs ; return confirm('$confirm');";
          }

          return $onclickjs;
      }
  }