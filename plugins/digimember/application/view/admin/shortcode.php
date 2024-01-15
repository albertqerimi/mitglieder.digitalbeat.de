<?php
/** @var ncore_Controller $this */

/**
 * @param array $usageArray
 * @return string
 */
function _short_code_usage($usageArray)
{
    $html = '';
    foreach ($usageArray as $tag => $description) {
        $html .= '
<div class="dm-formbox-item dm-row dm-middle-xs">
    <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-12 dm-color-coral">
        <tt>' . $tag . '</tt>
    </div>
    <div class="dm-col-md-8 dm-col-sm-8 dm-col-xs-10 dm-col-xs-offset-1 dm-col-sm-offset-0">
        <p class="dm-text">
            ' . $description . '
        </p>
    </div>
</div>
';
    }

    return '
<div class="dm-formbox-headline">
    ' . _digi('Usage') . '
</div>
<div class="dm-formbox-content">
    ' . $html . '
</div>
';
}

/**
 * @param array $contents
 * @return string
 */
function _short_code_preview($contents)
{
    $html = '';
    foreach ($contents as $description => $content) {
        $html .= '
<div class="dm-formbox-item dm-row dm-middle-xs">
    <div class="dm-col-md-3 dm-col-xs-12">
        <label>' . $description . '</label>
    </div>
    <div class="dm-col-md-9 dm-col-xs-12 dm-shortcode-preview">
        <iframe style="width: 100%; height: 100%; border: 0; min-height: 400px;" src="' . home_url('/?dm_shortcode_preview=' . $content) . '"></iframe>
    </div>
</div>
';
    }
    return '
<div class="dm-formbox-headline">
    ' . _digi('Preview') . '
</div>
<div class="dm-formbox-content">
    ' . $html . '
</div>
';
}

function _short_code($tag, $args = [])
{
    $html_args = '';
    foreach ($args as $key => $value) {
        $html_args .= " $key" . ($value ? "=$value" : '');
    }

    /** @var digimember_ShortCodeController $ncore_admin_shortcode_renderer */
    global $ncore_admin_shortcode_renderer;
    return $ncore_admin_shortcode_renderer->renderTag("$tag$html_args");
}

function _short_code_hl($tag)
{
    global $top_link;

    $tags = is_array($tag)
        ? $tag
        : [$tag];

    $html = '<div class="dm-row dm-middle-xs">';

    foreach ($tags as $tag) {
        $html .= "<a name='$tag'></a>";
    }

    foreach ($tags as $tag) {
        $html .= '<div class="dm-col-xs-11"><tt>[' . $tag . ']</tt></div><div class="dm-col-xs-1">' . $top_link . '</div>';
    }
    $html .= '</div>';

    return $html;
}

function _max_length($descriptions)
{
    $length = 20;

    foreach ($descriptions as $key => $value) {
        $length = max($length, strlen($key) + 2);
    }

    return $length;
}

/** @var array $descriptions */
/** @var digimember_ShortCodeController $renderer */
global $ncore_admin_shortcode_renderer;
$ncore_admin_shortcode_renderer = $renderer;

global $top_link;
ncore_api()->load->helper('html');
$top_title = _digi('To the top of the page');
$top_link = '
<a class="dm-btn dm-btn-primary dm-btn-icon" href="#top" title="' . $top_title . '">
  <span class="dm-icon icon-up-open dm-color-white"></span>
</a>
';

$bottom_icon = ncore_icon('bottom_link');

global $code_input_attributes;
$code_input_attributes = ['size' => _max_length($descriptions)];

list($lang,$country) = explode('_',get_locale());
if ($lang == 'de') {
    $shortcodeHelpUrl = 'https://digimember-hilfe.de/docs/handbuch/hb-shortcodes/';
    $label = 'hier';
}
else {
    $shortcodeHelpUrl = 'https://docs.digimember.com/docs/reference-book/rb-shortcodes/';
    $label = 'here';
}

//$html = ncore_linkReplace(_digi( 'To get further information about shortcodes and there usage click <a>here</a>.', "<strong>$label</strong>"), $shortcodeHelpUrl );

?>

<div class="dm-tabs-content dm-form-instructions">
    <div class="dm-tabs-tab visible">
        <p class="dm-text">
            <?=_digi('Via Shortcodes %s provides various possibilities to individualize your website even more. Just add the shortcode of your choice to the specific location.', $plugin)?>
        </p>
        <p class="dm-text">
            <?=ncore_linkReplace(_digi( 'To get further information about shortcodes and there usage click <a>here</a>.', "<strong>$label</strong>"), $shortcodeHelpUrl, true )?>
        </p>
        <p class="dm-text">
            <?=_digi('On this page you can easily generate and copy shortcodes. The last ten shortcodes you generated are shown in the list below. So, you are able to use them over and over again without generating them from scratch everytime.')?>
        </p>
    </div>
</div>
<div class="dm-tabs-content dm-form-instructions">
    <div class="dm-tabs-tab visible">
        <div class="dm-formbox-item dm-row dm-middle-xs">
            <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-11">
                <input id="show-shortcodes-dialog" type="submit" class="dm-btn dm-btn-primary dm-input-button dm-bulk-action-button" value="<?=_digi('Add Shortcode')?>" style="min-width: 100px;">
            </div>
        </div>
        <div class="dm-formbox-item dm-row dm-middle-xs">
        </div>

        <div class="dm-formbox">
            <div class="dm-formbox-headline">
                <?=_digi( 'Shortcodes' )?>
            </div>
            <div id="shortcodes-recent-list" class="dm-formbox-content">
                <div class="dm-formbox-item dm-row dm-middle-xs">
                    <div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-11">
                        <?=_digi( 'No shortcodes added until now.' )?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>