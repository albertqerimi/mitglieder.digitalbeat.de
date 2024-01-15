<?php
  class ncore_Formrenderer_InputOnclick extends ncore_FormRenderer_InputBase
  {
      protected function renderInnerWritable() {
          $html = ncore_htmlButton($this->postname(),$this->meta('button_label'),array(
              'style'=>'width: '.$this->setWidth().';',
              'onclick'=> $this->onClickJs(),
          ));

          return $html;
      }
      protected function onClickJs()
      {
          $javascript = $this->meta('javascript', NCORE_ARG_REQUIRED );

          $javascript = ncore_minifyJs( $javascript );

          return "$javascript; return false;";
      }
      protected function setWidth() {
          $width = $this->meta('width', '100%' );
          return $width;
      }
  }