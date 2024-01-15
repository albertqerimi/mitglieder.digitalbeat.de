<?php
  abstract class ncore_Formrenderer_ButtonBase extends ncore_Formrenderer_ElementBase
  {
      public function render()
      {
          $button = $this->renderInner();

          return array( $button, $hints='', $css='' );
      }

      public function postName( $dummy_field_name='' )
      {
        $name = $this->meta('name');
        return $name;
      }

      public function label()
      {
            $label = $this->meta('label', NCORE_ARG_REQUIRED );
            return $label;
      }

      protected function onClickJs()
      {
        return '';
      }

      protected function cssClass()
      {
          $is_primary = $this->meta( 'primary', false );

          $css = $this->meta( 'class' );

          if (ncore_isAdminArea()) {
              $baseClasses = $is_primary ? 'dm-btn dm-btn-primary' : 'dm-btn dm-btn-secondary dm-btn-outlined';
          } else {
              $baseClasses = $is_primary ? 'button button-primary' : 'button';
          }
          if ($this->meta('disabled', false)) {
              $baseClasses .= ' dm-btn-disabled';
          }

          return $baseClasses . ' ' . $css;
      }

     public function renderInner()
      {
          $url   = $this->meta( array( 'img', 'image_url'), '' );

          $have_image_button = (bool) $url;

          $id       = $this->meta( 'id' );
          $label    = $this->label();
          $disabled = $this->meta('disabled', false) ? ' disabled' : '';
          $name     = $this->postName();
          $css      = $this->cssClass();
          $js       = $this->onClickJs();
          $js_attr  = $js
                   ? "onclick=\"$js\""
                   : '';

          $img_tag = "<img src=\"$url\" alt=\"$label\"/>";

          $html = $this->form()->popHiddenInput();

          $id_attr = ncore_attribute( 'id', $id );
          
          $title      = $this->meta( 'title' );
          $title_attr = ncore_attribute( 'title', $title );

          $html .= $have_image_button
                 ? "<button $id_attr $title_attr class='ncore_button' $js_attr name='$name' class='$css' title=\"$label\" $disabled>$img_tag</button>"
                 : "<input $id_attr $title_attr type='submit' $js_attr name='$name' class='$css' value=\"$label\" />";

          return $html;
      }


  }