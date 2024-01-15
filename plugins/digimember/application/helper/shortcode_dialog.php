<?php

/**
 * Appends all resources necessary to use the global
 * Javascript function _digimember_shortcode_dialog(onConfirm, onCancel)
 * im Frontend
 */
function dm_append_shortcode_dialog()
{
  $api = dm_api();
  $api->load->helper('html_input');
  /** @var ncore_HtmlLogic $htmlLogic */
  $htmlLogic = $api->load->model('logic/html');

  $loadData = dm_get_shortcode_dialog_resources();

  // Append WP resources
  foreach ($loadData['wp_enqueue'] as $enq) {
    wp_enqueue_script($enq);
    wp_enqueue_style($enq);
  }
  ncore_htmlImageUploaderInit();

  $htmlLogic->jsOnLoad($loadData['code']);

  // Append custom scripts
  foreach ($loadData['packages'] as $url) {
    $htmlLogic->loadPackage($url);
  }
}

/**
 * Returns all necessary resources URLs as an array
 * @return array
 */
function dm_get_shortcode_dialog_resources()
{
  global $wp_scripts;
  global $wp_styles;

  $api = dm_api();
  /** @var digimember_LinkLogic $linkLogic */
  $linkLogic = $api->load->model('logic/link');
  /** @var ncore_HtmlLogic $htmlLogic */
  $htmlLogic = $api->load->model('logic/html');

  // Generate necessary URLS
  $ajaxUrl              = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_shortcode_metas');
  $pagesUrl             = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_pages');
  $productsUrl          = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_products');
  $menusUrl             = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_menus');
  $examsUrl             = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_exams');
  $examCertificatesUrl  = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_exam_certificates');
  $lectureOrMenusUrl    = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_lecture_or_menus');
  $optionsUrl           = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_options');
  $pageUrlUrl           = $linkLogic->ajaxUrl('ajax/shortcode_dialog', 'get_page_url');
  $urls = [
    'metas' => $ajaxUrl,
    'pages' => $pagesUrl,
    'products' => $productsUrl,
    'menus' => $menusUrl,
    'exams' => $examsUrl,
    'exam_certificates' => $examCertificatesUrl,
    'lecture_or_menus' => $lectureOrMenusUrl,
    'options' => $optionsUrl,
    'page_url' => $pageUrlUrl,
  ];
  $code = 'window._digimember_shortcode_urls = {';
  foreach ($urls as $key => $url) {
    $code .= $key . ': "' . $url . '",';
  }
  $code .= '};';

  $wp_enqueue = [ 'wp-color-picker' ];
  $wp_urls = [];
  foreach ($wp_enqueue as $handle) {
    if (in_array($handle, ncore_retrieve($wp_scripts, 'queue', []))) {
      $wp_urls[] = $wp_scripts->registered[$handle]->src;
    }
    if (in_array($handle, ncore_retrieve($wp_styles, 'queue', []))) {
      $wp_urls[] = $wp_styles->registered[$handle]->src;
    }
  }

  return [
    'wp_enqueue'  => $wp_enqueue,
    'wp_urls'     => $wp_urls,
    'urls'        => $urls,
    'code'        => $code,
    'packages'    => [ 'dm-ui-styles.css', 'shortcode-dialog.js' ],
    'package_urls'=> [ $htmlLogic->getPackageUrl('dm-ui-styles.css'), $htmlLogic->getPackageUrl('shortcode-dialog.js') ],
  ];
}

function dm_print_shortcode_dialog()
{
  $api = dm_api();
  $api->load->helper('html_input');
  $loadData = dm_get_shortcode_dialog_resources();
  ncore_htmlImageUploaderInit();
 
  foreach (array_merge($loadData['wp_urls'], $loadData['package_urls']) as $url) {
    if (strpos($url, '.css') === false) {
      ?>
      <script src="<?php echo $url; ?>" defer></script>
      <?php
    }
    else {
      ?>
      <link rel="stylesheet" type="text/css" href="<?php echo $url; ?>" />
      <?php
    }
  }
  ?>
  <script>
    <?php echo $loadData['code']; ?>
  </script>
  <?php
}
