<?php

  class ncore_TableRendererPaginator
  {
      const max_page_size = 200;
      const min_page_size = 1;
      const def_page_size = 20;

      public function __construct($api, $url, $row_count, $settings)
      {
          $get_params_to_remove = array( 'n', 'jump_to_page' );

          $this->api = $api;
          $this->url = ncore_removeArgs($url, $get_params_to_remove, '&', false);
          $this->row_count = $row_count;
          $this->page_size = min(self::max_page_size, max(self::min_page_size, ncore_retrieve($settings, 'page_size', self::def_page_size)));
          $this->page_count = ceil($this->row_count / $this->page_size);

          $this->row_count_message_1 = ncore_retrieve($settings, 'row_count_message_1', _ncore('1 element'));
          $this->row_count_message_N = ncore_retrieve($settings, 'row_count_message_N', _ncore('%s elements'));
      }

      public function sqlLimit()
      {
          $page_size = $this->page_size;
          $page = $this->page();

          $first = ($page-1) * $page_size;

          $limit = "$first, $page_size";

          return $limit;
      }

      public function render($have_search=false)
      {
          $html = '<div class="dm-pagination"><div class="dm-pagination-label">';
          $html .= $this->renderElementCount();
          $html .= '</div>';

          if (!$this->havePagination()) {
              $html .= '</div>';
              return $html;
          }

          $page = $this->page();
          $previous_page = max(1, $page-1);
          $next_page = min($this->page_count, $page+1);
          $last_page = $this->page_count;

          $first = $this->pagiLink(1, '<span><<</span>', 'dm-btn dm-btn-form dm-pagination-btn', _ncore('Go to first page'));
          $previous = $this->pagiLink($previous_page, '<span><</span>', 'dm-btn dm-btn-form dm-pagination-btn', _ncore('Go to previous page'));
          $next = $this->pagiLink($next_page, '<span>></span>', 'dm-btn dm-btn-form dm-pagination-btn', _ncore('Go to next page'));
          $last = $this->pagiLink($last_page, '<span>>></span>', 'dm-btn dm-btn-form dm-pagination-btn', _ncore('Go to last page'));

          $input_title = _ncore('Current page');

          $current_and_total = _ncore('%s of %s', '', $this->page_count);

          $current = $have_search
               ? '
<div class="dm-input-group dm-input-dense">
    <input class="dm-input" type="text" value="' . $page . '" name="jump_to_page" title="' . $input_title . '" />
    <button class="dm-btn dm-btn-form dm-btn-outlined dm-input-button">' . $current_and_total . '</button>
</div>'
               : '<button class="dm-btn dm-btn-form dm-pagination-btn dm-btn-outlined selected" disabled><span>' . $page . '</span></button>';


          $html .= '
<div class="dm-pagination-buttonpane">
    '. $first . $previous . $current . $next . $last . '
</div>';
          $html .= '</div>';

          return $html;
      }

      public function rowCount()
      {
          return $this->row_count;
      }

      public function firstRowNo()
      {
          $page_size = $this->page_size;
          $page = $this->page();

          $first = 1+($page-1) * $page_size;

          return $first;
      }

      private $api;
      private $url;
      private $row_count = 0;
      private $page_size = 0;
      private $page = false;

      private $row_count_message_1 = '1 element';
      private $row_count_message_N = '%s elements';

      private function pagiLink($page, $label, $css, $title)
      {
          $disabled = $page == $this->page();
          $attr = '';
          if ($disabled) {
              $css .= ' disabled';
              $attr = 'disabled';
          }

          $url = ncore_addArgs($this->url, array( 'n' => $page), '&', false);

          return "<button class='$css' type='button'><a href='$url' $attr>$label</a></button>";
      }

      private function page()
      {
          if ($this->page === false) {
              if ($this->havePagination()) {
                  $page = ncore_retrieve($_GET, 'jump_to_page', false);
                  if ($page===false) {
                      $page = ncore_retrieve($_POST, 'jump_to_page', false);
                  }

                  if ($page===false) {
                      $page = ncore_retrieve($_POST, 'n', false);
                  }

                  if ($page===false) {
                      $page = ncore_retrieve($_GET, 'n', false);
                  }

                  if ($page===false) {
                      $page = 1;
                  }

                  if ($page*$this->page_size > $this->row_count) {
                      $page = ceil($this->row_count / $this->page_size);
                  }
                  if ($page < 1) {
                      $page = 1;
                  }

                  $this->page = intval($page);
              } else {
                  $this->page = 1;
              }
          }

          return $this->page;
      }

      private function havePagination()
      {
          $have_pagination = $this->row_count > $this->page_size;
          return $have_pagination;
      }

      /**
       * @return string
       */
      private function renderElementCount()
      {
          return $this->row_count == 1
                              ? $this->row_count_message_1
                              : sprintf($this->row_count_message_N, $this->row_count);
      }
  }
