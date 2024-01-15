<?php
$load->loadPluginClass( 'date' );
class ncore_TableRenderer_TypeShortcodeCopy extends ncore_TableRenderer_TypeDate
{
    protected function renderInner( $row )
    {
        $shortcode = '[ds_forms id="'.$row->id.'"]';
        return "<a data-dm-tooltip=\""._ncore('Copy to clipboard')."\" href=\"#\" class=\"dm-tooltip-simple\" style='color: rgb(58,65,73); text-decoration: none;' id='ncore_shortcode_".$row->id."'><code class=\"dm-code\" onClick=\"ncore_copyShortcodeToClipboard('forms', '".$row->id."'); var el = document.querySelector('#ncore_shortcode_'+".$row->id."); ncore_switchElementAttribute(el,'data-dm-tooltip','"._ncore('Copied...')."','"._ncore('In to clipboard')."', 1000);\">".$shortcode."</code></a>";
    }
}
