<?php
  class ncore_Formrenderer_ButtonDisabled extends ncore_Formrenderer_ButtonBase
  {
      protected function onClickJs()
      {
          $msg = $this->meta( 'hint' );

          $msg = str_replace( "'", "\\'", $msg );

          $js = $msg
              ? "alert( '$msg' );"
              : '';

          return "$js return false;";
      }

     protected function cssClass()
      {
          $class = parent::cssClass();

          $class .= " ncore_disabled";

          return $class;
      }
  }