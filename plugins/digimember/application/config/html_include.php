<?php

$config['user_css'] = array(
);

$config['user_js'] = array(
	'user',
);

$config['user_js_handles'] = array(
    'jquery',
);

$config['user_packages'] = array(
    'dm-frontend.js',
    'dm-frontend-styles.css',
);

$config['admin_css'] = array(
);

$config['admin_js'] = array(
	'admin',
	'user',
);

$config['admin_packages'] = array(
    'dm-ui.js',
    'dm-frontend-styles.css',
    'dm-ui-styles.css',
    'gutenberg.js',
    'dm-frontend.js',
);

$config['admin_js_handles'] = array(
    'jquery',
);



$config['translation']['tinymce_helper'] = array(
	'tinymce_button_tooltip' => _digi( 'Add a %s shortcode', ncore_api()->pluginDisplayName() ),
	'tinymce_error_url' => _digi( 'Please enter a valid URL like http://example.com/?page=123.' ),
	'tinymce_error_product_required' => _digi( 'Please select at least one product.' ),
    'tinymce_autojoin_hint'  => _digi( 'Add your content here (only visible for the new user).' ),
    'tinymce_label_username' => _digi( 'Your user name:' ),
    'tinymce_label_password' => _digi( 'Your password:' ),
    'tinymce_if_hint'        => _digi( 'Add your content here.' ),
    'gutenberg_preview'      => _digi('Preview'),
    'gutenberg_select_shortcode' => 'Bitte einen Shortcode auswählen.',

);

$config['dm-blocks'] = array(
    'ifcontent' => array(
        'name' => 'ifcontent',
        'type' => 'ifcontent',
        'active' => true,
        'attributes' => array(
            'dm_ifcontent' => array('type' => 'string'),
            'products_loaded' => array('type' => 'boolean'),
            'products' => array('type' => 'array'),
            'product_whitelist' => array('type' => 'array'),
            'product_blacklist' => array('type' => 'array'),
            'loginactive' => array('type' => 'string'),
            'filter' => array('type' => 'string'),
            'blockconfig' => array('type' => 'object'),
            'blockconfig_loaded' => array('type' => 'boolean'),
        ),
    )
);
