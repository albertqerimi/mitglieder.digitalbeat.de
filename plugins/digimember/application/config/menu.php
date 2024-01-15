<?php

$model = ncore_api()->load->model( 'logic/features' );
$payment_providers_hidden = !$model->canUseOtherPaymentProviders();

$config['page_meta'] = array(
	array(
		'controller' => 'post/products',
		'headline' => _digi('[PLUGIN] Products'),
		'position' => 'side',
		'priority' => 'default',
		'callback' => 'cbMetaBox',
        'post_types_callback' => array( 'api' => 'dm_api', 'model' => 'data/page_product', 'method' => 'postTypes' ),
        // 'post_types' => array( 'page', 'post' );
	),
);

$config['admin_pages'] = array(

	'digimember' => array(
		'menu_title' => '[PLUGIN]',
		'page_title' => _ncore('[PLUGIN] Settings'),
		'menu_entry' => _ncore('Settings'),
		'capabilities' => 'manage_options',
		'controller' => 'admin/options',
		 'subpages' => array(
			'id' => 'admin/product_edit',
            'api_keys' => 'admin/api_key_list',
            'api_key_id' => 'admin/api_key_edit',
		),
		'icon_url' => 'div',

		'submenu' => array(

			'products' => array(
				'page_title' => _digi('Products'),
				'menu_entry' => _digi('Products'),
				'controller' => 'admin/product_list',

				'subpages' => array(
					'id'    => 'admin/product_edit',
				),
			),

  		    'content' => array(
				'page_title' => _digi('Content'),
				'menu_entry' => _digi('Content'),
				'controller' => 'admin/content',
			),

			'payment' => array(
				'page_title' => _digi('Payment'),
				'menu_entry' => _digi('Payment'),
				'controller' => 'admin/payment_list',
                'hide' => $payment_providers_hidden,

				'subpages' => array(
					'id' => 'admin/payment_edit',
				),
			),

			'payment_edit' => array(
				'page_title' => _digi('Edit payment provider'),
				'controller' => 'admin/payment_edit',
			),
            
            'orders' => array(
                'page_title' => _digi('Orders'),
                'menu_entry' => _digi('Orders'),
                'controller' => 'admin/order_list',
                'subpages' => array(
                    'id'         => 'admin/order_edit',
                    'masscreate' => 'admin/order_masscreate',
                ),
            ),

			'mails' => array(
				'page_title' => _digi('Email texts'),
				'menu_entry' => _digi('Email texts'),
				'controller' => 'admin/mail_text',
			),

			'newsletter' => array(
				'page_title' => _digi('Autoresponder'),
				'menu_entry' => _digi('Autoresponder'),
				'controller' => 'admin/newsletter_list',
				'subpages' => array(
					'id' => 'admin/newsletter_edit',
				),
			),

            'actions' => array(
                'page_title' => _digi('Actions'),
                'menu_entry' => _digi('Actions'),
                'controller' => 'admin/action_list',
                'subpages' => array(
                    'id'  => 'admin/action_edit',
                    'log' => 'admin/action_log',
                    'cfg' => 'admin/action_settings',
                ),
            ),

            'exam' => array(
                'page_title' => _digi('Exams'),
                'menu_entry' => _digi('Exams'),
                'controller' => 'admin/exam_list',
                'subpages' => array(
                    'id'  => 'admin/exam_edit',
                    //'stats' => 'admin/exam_stats',
                ),
            ),

            'certificates' => array(
                'page_title' => _digi('Exam certificates'),
                'menu_entry' => _digi('Exam certificates'),
                'controller' => 'admin/certificate_list',
                'subpages' => array(
                    'id'      => 'admin/certificate_edit',
                    'select'  => 'admin/certificate_select',
                ),
            ),


            'webpush' => array(
                'page_title' => _digi('Web push notifications'),
                'menu_entry' => _digi('Push notifications'),
                'controller' => 'admin/webpush_message_list',
                'subpages' => array(
                    'id'            => 'admin/webpush_message_edit',
                    'subscriptions' => 'admin/webpush_subscription_list',
                    'settings'      => 'admin/webpush_settings',
                ),
            ),
            
            'webhooks' => array(
                'page_title' => _digi('Webhooks'),
                'menu_entry' => _digi('Webhooks'),
                'controller' => 'admin/webhook_list',
                'subpages' => array(
                    'id'         => 'admin/webhook_edit',
                ),            
            ),            

            'signupform' => array(
                'page_title' => _digi('Signup form'),
                'menu_entry' => _digi('Signup form'),
                'controller' => 'admin/signup_form_generator',
            ),

            'shortcode_designer' => array(
                'page_title' => _digi('Shortcode Designer'),
                'menu_entry' => _digi('Shortcode Designer'),
                'controller' => 'admin/shortcode_designer',
            ),

			'shortcode' => array(
				'page_title' => _digi('Shortcodes'),
				'menu_entry' => _digi('Shortcodes'),
				'controller' => 'admin/shortcode',
			),

			'csvimport' => array(
				'page_title' => _digi('CSV Import'),
				'menu_entry' => _digi('CSV Import'),
				'controller' => 'admin/csv_import',
			),

            'customfields' => array(
                'page_title' => _digi('Custom Fields'),
                'menu_entry' => _digi('Custom Fields'),
                'controller' => 'admin/customfield_list',
                'subpages' => array(
                    'id' => 'admin/customfield_edit',
                ),
            ),
            'advancedforms' => array(
                'page_title' => _ncore('Forms'),
                'menu_entry' => _ncore('Forms'),
                'controller' => 'admin/advanced_forms_list',
                'subpages' => array(
                    //'id' => 'admin/advanced_signup_edit',
                    'id' => 'admin/advanced_forms_edit',
                ),
            ),

            'log' => array(
                'page_title' => _digi('Log'),
                'menu_entry' => _digi('Log'),
                'controller' => 'admin/log',
            ),
	  ),

	),

);

