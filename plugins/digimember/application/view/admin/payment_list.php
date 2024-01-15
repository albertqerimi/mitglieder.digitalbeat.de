
<h3>
    <?=_digi3('To connect %s to you Digistore24-Account perform these steps:', $plugin_name); ?>
</h3>

<?php

    $pay_code_input = ncore_htmlTextInputCode( $pay_code );

    $steps = array(
        _digi3( 'Copy the following pay code to the clipboard: %s', $pay_code_input ),
        ncore_linkReplace( _digi3( 'If you have no Digistore24-Account, <a>signup with Digistore24</a> now.'), $digistore_signup_url, $asPopup=true ),
        _digi3( 'In Digistore24, goto <a>Settings -> IPN</a>.', $digistore_settings_url ),
        _digi3( 'Click on <em>Add new connection</em>.' ),
        _digi3( 'For <em>Type</em>, select %s.', $digistore_ipn_type ),
        _digi3( 'Follow the instructions there.' ),
    );

    echo '<ol>';

    foreach ($steps as $one)
    {
        echo "<li>$one</li>";
    }

    echo '</ol>';

?>