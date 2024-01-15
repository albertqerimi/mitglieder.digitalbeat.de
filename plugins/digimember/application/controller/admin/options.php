<?php

/** @var ncore_LoaderCore $load */
$load->controllerBaseClass( 'admin/form' );

class digimember_AdminOptionsController extends ncore_AdminFormController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    protected function tabs()
    {
        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $tabs = [
            'basic' => _digi('Setup'),
            'advanced' => _digi('Options'),
            'api_keys' => [
                'label' => _digi('Api Keys'),
                'url' => $linkLogic->adminPage('', ['api_keys' => 'all']),
            ],
        ];

        return $tabs;
    }

    protected function pageHeadline()
    {
        return _ncore( 'Settings' );
    }

    protected function inputMetas()
    {
        switch ($this->currentTab()) {

            case 'advanced': return $this->_inputMetasAdvanced();

            case 'basic':
            default: return $this->_intputMetasBasic();
        }
    }

    private function _intputMetasBasic()
    {
        $api = $this->api;
        $api->load->model( 'data/product' );

        $api->load->library( 'payment_handler' );
        /** @var ncore_LicenseValidator_PluginBase $license_lib */
        $license_lib = $api->loadLicenseLib();



        $no_smtp_hoster_labels = '1&1';

        $smtp_tooltip_inner = _digi( 'Using a real email account for sending may improve delivery rates - especially if you have specified an email sender address above.|Without an email account, the mail are likely to be considered as spam.|Ask your webhoster for the details concerning your email web account.|Some webhoster (like %s) forbid using this options for their mail accounts. For these web hosters just disable the checkbox. This will also work fine.', $no_smtp_hoster_labels );
        $smtp_tooltip = '<p>' . str_replace('|','</p><p>',$smtp_tooltip_inner) . '</p>';

        /** @var digimember_LinkLogic $link */
        $link = $this->api->load->model( 'logic/link' );

        $license_valid = $license_lib->licenseStatus() == NCORE_LICENSE_VALID;

        $lic_url = $this->api->edition() == 'US'
            ?   'https://digimember.com/licenseagreement/'
            :   'https://digimember.de/lizenzvertrag';

        $lic_msg = _digi( 'By using %s you accept our <a>license agreement</a>.', $this->api->pluginDisplayName() );

        $buy_msg = _digi('Get your license code now!' );
        $buy_url = $link->buyUrl();

        $license_code_hint = $license_valid
                           ? ncore_linkReplace( $lic_msg, $lic_url )
                           : "<a href='$buy_url' target='_blank'>$buy_msg</a>";

        $license_code_tt = $license_valid
                         ? _ncore( 'Click on this button to clear the license key.<p>You then may use the license key for another domain.' )
                         : _ncore('Enter your license key we have sent you by email after purchasing the product.' );

        $metas = array();

        /** @var digimember_DigistoreConnectorLogic $ds24 */
        $ds24 = $this->api->load->model( 'logic/digistore_connector' );
        list( $type, $message, $button ) = $ds24->renderStatusNotice( 'seperate', 'auto' );
        $ds24name = $this->api->Digistore24DisplayName();

        if ($type == NCORE_NOTIFY_ERROR) {
            $html = ncore_htmlAlert('info', $message, 'info', '', $button);
        }
        else {
            $html = ncore_htmlAlert('success', $message, 'success', '', $button);
        }

        $metas[] = array(
                    'label' => _digi( 'Connection to %s', $ds24name ),
                    'type' => 'html',
                    'html' => $html,
                    'section' => 'setup',
         );

        if ($license_lib->licenseCheckEnabled())
        {
            if ($license_valid)
            {
                $this->api->load->helper( 'html_input' );

                $attr = array();
                $attr['class' ] = 'dm-btn dm-btn-success dm-btn-outlined';
                $attr['confirm' ] = _ncore( 'The license will now be reset.|You then may use the license key for a new domain.|Continue?' );

                $reset_button = ncore_htmlButtonMinor( 'reset_license', _ncore( 'Reset license key' ), $attr );

                $metas[] = array(
                    'name'    => 'license_code',
                    'section' => 'setup',
                    'type'    => 'html',
                    'label'   => _ncore('License Code' ),
                    'tooltip' => $license_code_tt,
                    'html'    => ncore_htmlAlert('success', $license_lib->getLicense()->license_key, 'success', $license_code_hint, $reset_button)
                );
            }
            else {
                $reset_button = ncore_htmlButton('update_license', _ncore('Ok'), ['class' => 'dm-input-button', 'type' => 'submit']);

                $metas[] = [
                    'name' => 'license_code',
                    'section' => 'setup',
                    'type' => 'text',
                    'label' => _ncore('License Code'),
                    'rules' => "defaults|licensekey",
                    'class' => 'ncore_code',
                    'hint' => $license_code_hint,
                    'tooltip' => $license_code_tt,
                    'button' => $reset_button,
                ];
            }

            /*
                $reset_license_tt = _ncore( 'Click on this button to clear the license key.<p>You then may use the license key for another domain.' );

            $metas[] =  array(
                    'name' => 'reset_license',
                    'section' => 'setup',
                    'type' => 'action_button',
                    'class' => 'button-minor',
                    'label' => '&nbsp;',
                    'action_label' => _ncore( 'Reset license key' ),
                    'instructions' => '', // _digi3( 'Send configuration update email to Shareit support.' ),
                    'tooltip' => $reset_license_tt,
                    'confirm' => _ncore( 'The license will now be reset.<p>You then may use the license key for a new domain.<p>Continue?' ),
                    'hide' => !$license_valid,
                );
                */
        }

        if (NCORE_TESTING && $license_lib->licenseCheckEnabled())
        {
             $metas[] = array(
                'name' => 'testing',
                'section' => 'setup',
                'type' => 'html',
                'label' => _ncore('Important' ),
                'html'  => ncore_icon( 'warning' ) . _ncore( 'This is the test version of the %s plugin!', $this->api->pluginDisplayName() ),
                'tooltip' => _ncore( 'This test version receives updates earlier then the productive version. These update may not be stable and are not suited for productive environments.' ),
            );
        }

        $options = array(
                        'sie' => _digi( 'Sie (formal)' ),
                        'du'  => _digi( 'du (lower case)' ),
                        'Du'  => _digi( 'Du (upper case)' ),
                   );

        $options[ 'custom' ]= _digi( 'Your own texts' );

        if (dm_api()->edition() != 'US')
        {
            $metas[] = array(
                        'name'    => 'lang_personal',
                        'section' => 'setup',
                        'type'    => 'select',
                        'options' => $options,
                        'label'   => _digi('How to address user' ),
                        'hint'    => str_replace( '[LOCALE]', 'de_DE', _digi('Only for German language (if locale is set to: [LOCALE]). Only for texts in the user area.' ) ),
            );
        }


        $lang = get_locale();

        $mo_file  = 'digimember_you_custom_' . $lang . '.mo';
        $po_file  = 'digimember_you_custom_' . $lang . '.po';

        $wp_content = defined( 'WP_CONTENT_FOLDERNAME' )
                    ? WP_CONTENT_FOLDERNAME
                    : 'wp-content';

        $po_start = 'digimember_you-de_DE.po';
        $src = $wp_content . '/plugins/digimember/language';
        $dst = $wp_content . '/plugins';

        $find = array( '[MO]', '[PO]', '[START]', '[SRC]', '[DST]', '[PLUGIN]' );
        $repl = array( "<strong>$mo_file</strong>", "<strong>$po_file</strong>", "<strong>$po_start</strong>", $src, $dst, $this->api->pluginDisplayName() );

        $po_edit_url = 'https://poedit.net/';

        $msg = _digi( "Follow these steps to create a language file named [MO] using <a>Poedit</a>.|- You may use [START] as a starting point. See folder: [SRC]|- Copy the file to your computer and rename it to [PO].|- Edit it with Poedit and save it. You'll then get the file [MO].|- Copy this file to your server into the folder [DST]. The file will survive a [PLUGIN] update.|- Keep the file [PO] safe just in case you want to make changes in the future." );

        $msg = str_replace( $find, $repl, $msg );

        if (dm_api()->edition() != 'US') {
            $metas[] = [
                'section' => 'setup',
                'type' => 'html',
                'depends_on' => ['lang_personal' => 'custom'],
                'label' => '',
                'html' => ncore_paragraphs(ncore_linkReplace($msg, $po_edit_url)),
            ];
        }

        $metas[] = array(
                'name' => 'mail_sender_name',
                'section' => 'email',
                'type' => 'text',
                'label' => _digi('Sender name' ),
                'rules' => 'defaults',
                'size' => 50,
            );


        $metas[] = array(
                'name' => 'mail_sender_email',
                'section' => 'email',
                'type' => 'email',
                'label' => _digi('Sender email address' ),
                'rules' => 'defaults|email',
            );

        $metas[] = array(
                'name' => 'mail_reply_email',
                'section' => 'email',
                'type' => 'email',
                'label' => _digi('Reply to address' ),
                'tooltip' => _digi( 'If not empty, the receiver of the email will reply to this email address.' ),
                'rules' => 'email',
                'hint' => _digi( 'Optional' ),
            );


        $metas[] = array(
                'name' => 'use_smtp_mail',
                'section' => 'email',
                'type' => 'checkbox',
                'label' => _digi('Send via email account' ),
                'rules' => 'defaults',
                'tooltip' => $smtp_tooltip,
            );


        $metas[] = array(
                'name' => 'smtp_host',
                'section' => 'email',
                'type' => 'text',
                'label' => _digi('SMTP host' ),
                'rules' => 'defaults|required|domain',
                'size' => 35,
                'depends_on' => array(
                    'use_smtp_mail' => '1'
                ),
            );


        $is_port_25_blocked = defined( 'DB_IS_PORT_25_BLOCKED_FOR_SMTP' ) && DB_IS_PORT_25_BLOCKED_FOR_SMTP;

        $metas[] = array(
                'name' => 'smtp_secure_type',
                'section' => 'email',
                'type' => 'select',
                'options' => array( '' => _digi('none'), 'ssl' => 'SSL/TLS', 'tls' => 'STARTTTLS' ),
                'label' => _digi('Connection security' ),
                'rules' => 'defaults',
                'depends_on' => array(
                    'use_smtp_mail' => '1'
                ),

                'hint' => $is_port_25_blocked
                          ? _digi( 'Please use a secure connection like %s.', 'SSL/TLS' )
                          : '',
            );

        $hint = _digi( 'Default for for connection security SSL/TLS is 465.%s Default for STARTTTLS is 587.', '<br />', '<br />' )
              . '<br />';

        $hint .= $is_port_25_blocked
              ? _digi( 'For security reasons port 25 is NOT possible.', 465 )
              : _digi( 'Default for no security is 25.' );

        $rules = 'defaults|required';
        if ($is_port_25_blocked) {
            $rules .= "|not_equal[25]";
        }



        $metas[] = array(
                'name' => 'smtp_port',
                'section' => 'email',
                'type' => 'int',
                'label' => _digi('SMTP port' ),
                'rules' => $rules,
                'default' => $is_port_25_blocked ? 465 : 25,
                'depends_on' => array(
                    'use_smtp_mail' => '1'
                ),
                'hint' => $hint
            );

        $metas[] = array(
                'name' => 'smtp_user_name',
                'section' => 'email',
                'type' => 'text',
                'label' => _digi('SMTP username' ),
                'rules' => 'defaults|required',
                'size' => 50,
                'depends_on' => array(
                    'use_smtp_mail' => '1',
                ),
            );

        $metas[] = array(
                'name' => 'smtp_user_password',
                'section' => 'email',
                'type' => 'password',
                'label' => _digi('SMTP password' ),
                'rules' => 'defaults|required',
                'depends_on' => array(
                    'use_smtp_mail' => '1',
                ),
            );


        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model( 'logic/features' );

        $can_use_facebook = $model->canUseFacebook();

        if (!ncore_hasFacebookApp())
        {
            // empty
        }
        elseif ($can_use_facebook)
        {
            $metas[] = array(
                'name' => 'facebook_enabled',
                'section' => 'facebook',
                'type' => 'checkbox',
                'label' => _digi('Facebook integration' ),
            );

            /** @var digimember_FacebookConnectorLib $lib */
            $lib = $this->api->load->library( 'facebook_connector' );

            $metas[] = array(
                    'name' => 'facebook_app_id',
                    'section' => 'facebook',
                    'type' => 'int',
                    'label' => _digi('Facebook app id' ),
                    'depends_on'=> array( 'facebook_enabled' => '1' ),
                    'rules' => 'required|numeric',
                    'size' => 20,
                    'display_zero_as' => '',
                    'hint' => $lib->renderFbAppSetupHint(),
                );
            $metas[] = array(
                    'name' => 'facebook_app_secrect',
                    'section' => 'facebook',
                    'type' => 'text',
                    'label' => _digi('Facebook app secret' ),
                    'depends_on'=> array( 'facebook_enabled' => '1' ),
                    'rules' => 'required',
                    'size' => 32,
                );

//            $metas[] = array(
//                    'name' => 'facebook_use_extended_permissions',
//                    'section' => 'facebook',
//                    'type' => 'checkbox',
//                    'label' => _digi('Use extended permissions' ),
//                    'depends_on'=> array( 'facebook_enabled' => '1' ),
//                    'hint' => _digi( 'If enabled, %s will request permission to post on the Facebook user\'s wall. This requires you to ask Facebook for approval.', $this->api->pluginDisplayName() ),
//                );

        }
        else
        {
            /** @var digimember_LinkLogic $model */
            $model = $this->api->load->model( 'logic/link' );
            $html = ncore_htmlAlert('info', _digi( 'Use the %s Facebook connector with Facebook logins.', $this->api->pluginDisplayName() ), 'info', '', $model->upgradeButton());

            $metas[] = array(
                    'section' => 'facebook',
                    'type' => 'html',
                    'label' => _digi('Facebook integration' ),
                    'html'=> $html,
                );
        }

        $supportButtonJs = 'var supportinfo = document.getElementById("support_info"); supportinfo.select(); document.execCommand("copy"); alert("'._digi('Copied support info to clipboard').'");';

        $metas[] = array(
            'type' => 'onclick',
            'section' => 'support',
            'label' => _digi('Support info'),
            'button_label' => _digi('Copy support info'),
            'primary' => true,
            'javascript' => $supportButtonJs,
            'width' => '50%',
            'tooltip' => _digi('With this button, technical information of your website (e.g. the DigiMember version used) is placed in the clipboard. So you can easily add this information to your support request to support@digimember.com.')
        );

        $supportModel = $this->api->load->model( 'logic/support' );

        $metas[] = array(
            'name' => 'support_info',
            'section' => 'support',
            'type' => 'function',
            'label' => 'none',
            'function' => array($supportModel,'getSupportInfo'),
        );

        return $metas;
    }

    private function _inputMetasAdvanced()
    {
        $api = $this->api;
        $api->load->model( 'data/product' );

        $metas = array();

        $metas[] = array(
                'name' => 'loginurl',
                'section' => 'general',
                'type' => 'url',
                'label' => _digi('Login URL' ),
                'tooltip' => _digi( 'This URL is used in emails as parameter %%loginurl%%.')
                           . '|'
                           . _digi( 'When using Digistore24, the login URL is also displayed together with the username and password in the Digistore24 mail confirmation mail and on the Digistore24 receipt page.' ),
                'hint' => _digi( 'If left blank, the default is used: %s', ncore_siteUrl() ),
            );

        $metas[] = array(
                'name' => 'disable_login_data_in_ds24',
                'section' => 'general',
                'type' => 'checkbox',
                'label' => _digi('Hide user login data in Digistore24' ),
                'tooltip' => _digi( 'By default, the username and the password of new users are transferred to Digistore24. Then in Digistore24 the login data are show to the user. This makes it easy for the user to get his login data. If this option is enabled, Digistore24 will not get and display the login data.' ),
            );

        $download_url = 'https://www.digistore24.com/download/package/wordpress_plugin';
        $metas[] = array(
                'name' => 'thankyou_data_policy_in_ds24',
                'section' => 'general',
                'type' => 'select',
                'label' => _digi('Digistore24 transfers thankyou URL data ...' ),
                'options' =>  array(
                    'plain'     => _digi( '... as PLAIN TEXT to DigiMember' ), // DEFAULT VALUE
                    'encrypted' => _digi( '... ENCRYPTED to DigiMember' ),
                    'hidden'    => _digi( '... NEVER to DigiMember' ),
                ),
                'hint'    => _digi( 'Only for products, that are synchronised with Digistore24.' ),
        );

        $metas[] = array(
            'name' => 'nickname_policy_in_dm',
            'section' => 'general',
            'type' => 'select',
            'label' => _digi('Wordpress nickname configuration' ),
            'options' =>  array(
                'email'     => _digi( 'Complete E-Mail (standard)' ), // DEFAULT VALUE
                'email_pre_at' => _digi( 'The part of the E-Mail before the @ sign' ),
                'firstname_lastname' => _digi( 'Firstname + lastname' ),
                'firstname-lastname' => _digi( 'Firstname-lastname' ),
                'random'    => _digi( 'Random string (12 signs)' ),
            ),
            'tooltip' => _digi('When DigiMember creates a new User for an order, a -nickname- needs to be set for the user. In default setting for this, the E-Mail adress will be used. The nickname of an user you can see in the user overview. If the wordpress user is already created, the nickname will stay unchanged on an order. If you set this on -firstname + lastname- and both are not provided, the e-mail address will be used as fallback.'),
            'default' => 'email',
        );

        $metas[] = array(
                'section' => 'general',
                'type' => 'html',
                'label' => '',
                'depends_on' => array( 'thankyou_data_policy_in_ds24' => 'encrypted' ),
                'hint' => ncore_linkReplace( _digi( '<a>Digistore24 Wordpress plugin</a> is required for decryption.' ), $download_url ),
        );

        $metas[] = array(
                'name' => 'disable_admin_navbar',
                'section' => 'admin',
                'type' => 'checkbox',
                'label' => _digi('Disable Navbar for Non-Admins' ),
                'tooltip' => _digi( 'By default, logged in members see a navigation at the top of the page.|If checked, for non admin members the navigation bar will be hidden.' ),
            );

        $url = ncore_siteUrl( 'wp-admin' );
        $metas[] = array(
                'name' => 'disable_admin_area',
                'section' => 'admin',
                'type' => 'checkbox',
                'label' => _digi('Disable access to admin area for Non-Admins' ),
                'tooltip' => _digi( 'By default, logged in members may access %s.|If checked, they may not access it and will be redirected.', $url ),
            );
        $metas[] = array(
                'name' => 'disable_admin_area_url',
                'section' => 'admin',
                'type' => 'url',
                'label' => _digi('Admin area redirect URL' ),
                'tooltip' => _digi( 'If a non-admin logs in and access the Wordpress admin area, he is redirected to this URL.' ),
                'depends_on' => array( 'disable_admin_area' => 1 ),
            );


        $label1 = _digi('First login redirect' );
        $label2 = _digi('Login redirect' );
        $label3 = _digi('Link for products short code' );
        $labelD = _digi('Download page');
        $metas[] = array(
                'name' => 'use_free_url_for_login_page',
                'section' => 'admin',
                'type' => 'yes_no_bit',
                'label' => _digi('Use free form URL for login page' ),
                'tooltip' => _digi( 'If set to yes, you may use any URL for these product settings:') . "<ul><li>$label1</li><li>$label2</li><li>$label3</li><li>$labelD</li></ul>",
                'hint' => _digi( 'Important: if you change this settings, your current product settings for %s, %s and %s as well as for %s may be lost.', "<i>$label1</i>", "<i>$label2</i>", "<i>$label3</i>", "<i>$labelD</i>" ),
                'default_value' => 'N',
            );
        $metas[] = array(
            'name' => 'use_error_handling_prioritization',
            'section' => 'admin',
            'type' => 'yes_no_bit',
            'label' => _digi('Prioritization of Errorpages if multible products are assigned' ),
            'hint' => _digi('If this setting is active, you are able to set which error handling will be prioritized if the content is connected to more than one membership products.'),
            'default_value' => 'N',
        );

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        if ($model->canAffiliateFooterLinkBeDisabled())
        {
            $affiliate_digistore_id_depend_on = array(
                    'show_affiliate_link' => '1'
                );

            $have_affiliate_link = true;
        }
        else
        {
            $affiliate_digistore_id_depend_on = false;
            $have_affiliate_link = false;
        }

        if ($have_affiliate_link)
        {
            $metas[] = array(
                    'name' => 'show_affiliate_link',
                    'section' => 'recommend',
                    'type' => 'checkbox',
                    'label' => _digi('Affiliate footer link' ),
                    'rules' => 'defaults',
                    'tooltip' => _digi( 'Do you want to make money for nothing?|If checked, in the footer of your blog your affiliate link is displayed.|If an interested merchant checks out your blog, he may click on your affiliate link. If he buys, you get a provision.|Just enter your Digistore24 id in the text field below.'),
                );
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $signup_url = $model->digistoreReferenceUrl();

        $metas[] = array(
                'name' => 'affiliate_digistore_id',
                'section' => 'recommend',
                'type' => 'text',
                'label' => _digi('Your Digistore24 id' ),
                'rules' => 'defaults',
                'hint' => str_replace( '<a>', '<a href="'.$signup_url.'" target="_blank">', _digi( 'Enter your Digistore24 id (i.e. your Digistore24 login name). Not registered? <a>Click here!</a>') ),
                'tooltip' => _digi( 'Enter your Digistore24 id. It will be added to your affiliate link in the page footer. So you may receive a commission for each sale generated by your affiliate link.'),
                'depends_on' => $affiliate_digistore_id_depend_on,
        );

        $access_limit_options = array(
                '0' => _digi('Unlimited IPs'),
                '5' => _digi( 'Five IPs' ),
               '10' => _digi( 'Ten IPs' ),
           'manual' => _digi( 'Enter value' ),
        );


        $metas[] = array(
                'name' => 'ip_access_limit_default',
                'section' => 'accesslimit',
                'options' => $access_limit_options,
                'type' => 'select',
                'label' => _digi('Daily access limit' ),
                'rules' => 'defaults',
                'tooltip' => _digi( 'You can limit the number of IP addresses a user may access your site from. This is useful to prevent users from sharing their login data with other people.|Set this number to 10 to allow each user to access your content via ten IPs (e.g. pc, smartphone, tablet, ...).|IP addresses change from time to time (e.g. if the user disconnects from the internet and reconnects). The IP changes at least once per day, sometimes more often.|So don\'t make the number too low or legitimate users may be blocked.|We suggest a value of ten IPs.' ),
            );

        $metas[] = array(
                'name' => 'ip_access_limit_manual',
                'section' => 'accesslimit',
                'type' => 'int',
                'unit' => _digi( 'IPs' ),
                'label' => '',
                'colon' => false,
                'rules' => 'required|greater_equal[1]',
                'depends_on' => array(
                    'ip_access_limit_default' => 'manual'
                ),
        );

        $metas[] = array(
                'name' => 'limit_login_enabled',
                'section' => 'accesslimit',
                'type' => 'yes_no_bit',
                'label' => _digi( 'Limit login attempts' ),
        );
        $metas[] = array(
                'name' => 'limit_login_count',
                'section' => 'accesslimit',
                'type' => 'select',
                'label' => _digi( 'Number of login tries' ),
                'options' => array(
                    3 => 3,
                    5 => 5,
                   10 => 10,
                   25 => 25,
                  100 => 100,
                ),
                'depends_on' => array(
                    'limit_login_enabled' => 'Y'
                ),
        );
        $metas[] = array(
                'name' => 'limit_login_waittime',
                'section' => 'accesslimit',
                'type' => 'select',
                'label' => _digi( 'Wait time' ),
                'options' => array(
                    '1' => _digi( '%s minute',   1 ),
                    '3' => _digi( '%s minutes',  3 ),
                   '10' => _digi( '%s minutes', 10 ),
                   '30' => _digi( '%s minutes', 30 ),
                   '60' => _digi( '%s minutes', 60 ),
                ),
                'depends_on' => array(
                    'limit_login_enabled' => 'Y'
                ),         );

        $metas[] = array(
            'name' => 'limit_login_remove_email',
            'section' => 'accesslimit',
            'type' => 'text',
            'label' => _digi('Remove IP block for a single user'),
            'rules' => 'defaults',
            'hint' => _digi('Please enter the email address of the blocked account'),
            'tooltip' => _digi('With this field you can reset a IP blocked user account'),
        );

        $cron_service = _digi("<a target='_blank' href='http://www.cronjob.de'>cronjob.de</a>");

        $cron_url = ncore_siteUrl( 'wp-cron.php' );

        $metas[] = array(
                'name' => 'have_external_wp_cron_call',
                'section' => 'system',
                'type' => 'checkbox',
                'label' => _digi('Cronjobs called externally' ),
            );

        $this->api->load->helper( 'html_input' );
        $metas[] = array(
                'section' => 'system',
                'type' => 'html',
                'rules' => 'readonly',
                'label' => _digi('Cronjob URL' ),
                'hint' => _digi('Make sure, this URL is called every 5 minutes (better: every minute) - e.g. using a service like %s. This will speedup the payment process (if autoresponders are setup) and some page views (if pageview actions are enabled).', $cron_service ),
                'html' => ncore_htmlTextInputCode($cron_url),
                'depends_on' => array( 'have_external_wp_cron_call' => '1' ),
            );

        /** @var digimember_DigistoreConnectorLogic $ds24 */
        $ds24 = $this->api->load->model( 'logic/digistore_connector' );
        list( $is_connected ) = $ds24->connectionStatus( 'default', 'auto' );
        $ds24_name = $this->api->Digistore24DisplayName( false );
        $ds24_link = $this->api->Digistore24DisplayName( true );
        $row_label    = _digi( '%s import', $ds24_link );
        $button_label = _digi( 'Import your %s products', $ds24_name );
        if ($is_connected)
        {
            $info = $ds24->connectionInfo();
            $user_name = $info[ 'username' ];

            $find = array( '[DS24]', '[DM]', '[USER]' );
            $repl = array( $ds24_name, $this->api->pluginDisplayName(), $user_name );

            $msg_template = _digi( 'Your [DS24] products will be imported to [DM] from the account [USER].|If a [DS24] product is already assigned to a [DM] product (in the [DM] product editor in the tab [DS24]), it will not be imported again.' );

            $confirm_msg = str_replace( $find, $repl, $msg_template );

            $metas[] = array(
                    'section' => 'system',
                    'name' => 'import_ds24_products',
                    'type' => 'action_button',
                    'instructions' => _digi('Click on the button to start importing your %s products.', $ds24_link),
                    'label' => $row_label,
                    'action_label' => $button_label,
                    'confirm' => $confirm_msg,
            );
        }
        else
        {
             $metas[] = array(
                    'section' => 'system',
                    'name' => 'dummy',
                    'type' => 'action_button',
                    'label' => $row_label,
                    'action_label' => $button_label,
                    'instructions' => _digi( 'To import your %s products, first establish a connection to %s.', $ds24_link, $ds24_link ),
                    'disabled' => true,
            );
        }

        return $metas;
    }

    protected function sectionMetas()
    {
        $metas = array(
            'setup' =>  array(
                            'headline' => _ncore('Setup'),
                            'instructions' => '',
                          ),
            'general' =>  array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
                          ),
            'admin' =>  array(
                            'headline' => _digi('Wordpress admin area'),
                            'instructions' => '',
                          ),
            'email' =>  array(
                            'headline' => _ncore('Email communication'),
                            'instructions' => '',
                          ),
            'recommend' =>  array(
                            'headline' => _ncore('Recommend'),
                            'instructions' => '',
                          ),
            'support' =>  array(
                            'headline' => _ncore('Support'),
                            'instructions' => '',
                          ),
            'accesslimit' =>  array(
                            'headline' => _digi('IP access limit'),
                            'instructions' => '',
                          ),
            'facebook' => array(
                            'headline' => _digi('Facebook'),
                            'instructions' => '',
                          ),
            'system' => array(
                            'headline' => _digi('System'),
                            'instructions' => '',
                          ),

        );

        return $metas;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        if ($this->currentTab() == 'basic')
        {

            $form_id = $this->formId();

            $metas[] = array(
                    'type' => 'ajax',
                    'label' => _ncore('Send test email'),
                    'ajax_meta' => array(
                                'type' => 'form',
                                'cb_form_id' => $form_id,
                                'message' => _ncore( 'Enter an email address:' ),
                                'title' => _ncore( 'Send test email' ),
                                'width' => '600px',
                                'modal' => false,
                                'form_sections' => array(
                                ),
                                'form_inputs' => array(
                                    array(
                                        'name' => 'test_email',
                                        'type' => 'text',
                                        'label' => _ncore('Email' ),
                                        'rules' => 'defaults|email',
                                        'default' => $this->_getTestDefaults(),
                                        'full_width' => true,
                                    ),
                             ),
                        ),
                    );
        }

        return $metas;
    }

    protected function editedElementIds()
    {
        $dummy_element_id='';

        return array( $dummy_element_id );
    }


    protected function handleRequest()
    {
        parent::handleRequest();

        $test_email            = ncore_retrieve( $_POST, 'ncore_test_email' );
        $reset_license        = !empty( $_POST[ 'reset_license' ] );
        $import_ds24_products = !empty( $_POST[ 'import_ds24_products' ] );

        $removeIpBlockEmail = ncore_retrieve( $_POST, 'ncore_limit_login_remove_email', false );


        if ($removeIpBlockEmail) {
            $removeUserId = ncore_getUserIdByEmail($removeIpBlockEmail);
            if ($removeUserId) {
                $model = $this->api->load->model( 'logic/access' );
                $block_reason = $model->blockAccessReason( $removeUserId );
                $model->unBlockAccess($removeUserId);
                $test = '';
            }
        }


        $do_test = (bool) $test_email;
        if ($do_test)
        {
            $this->sendTestMail( $test_email );
        }

        if ($reset_license)
        {
            $this->resetLicenseKey();
        }

        if ($import_ds24_products)
        {
            try
            {
                /** @var digimember_DigistoreSyncLogic $model */
                $model = $this->api->load->model( 'logic/digistore_sync' );

                $count = $model->importProducts();


                $ds24_name = $this->api->Digistore24DisplayName( false );
                switch ($count)
                {
                    case 0:  $msg = _digi( 'No new %s products found.', $ds24_name ); break;
                    case 1:  $msg = _digi( 'One %s product has been imported.', $ds24_name ); break;
                    default: $msg = _digi( '%s %s products have been imported.', $count, $ds24_name );
                }

                $this->formSuccess( $msg );
            }
            catch (Exception $e)
            {
                $this->formError( $e->getMessage() );
            }
        }

    }


    protected function getData( $dummy_element_id )
    {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );

        $data     = $model->getAll();


        /** @var ncore_SupportLogic $model */
        $model = $this->api->load->model( 'logic/support' );
        $data['support_info_url'] = $model->supportInfoUrl();

        $pw_len = mb_strlen( ncore_retrieve( $data, 'smtp_user_password' ) );
        $data[ 'smtp_user_password' ] = str_pad( '', $pw_len, '*' );

        return $data;
    }

    protected function setData( $dummy_element_id, $data )
    {
        $is_password_set = (bool) str_replace( '*', '', ncore_retrieve( $data, 'smtp_user_password' ) );
        if (!$is_password_set) {
            unset( $data[ 'smtp_user_password' ] );
        }


        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );

        $modified = $model->setAll( $data );

        $license_key = ncore_retrieve( $data, 'license_code' );
        $this->updateLicense( $license_key );

        $leadpages_apikey_file = ncore_retrieve( $data, 'leadpages_apikey_file' );
        if ($leadpages_apikey_file) {
            $this->updateLeadPagesApiKey( $leadpages_apikey_file );
        }

        return $modified;
    }

    private function _getTestDefaults()
    {
        /** @var ncore_UserSettingsData $model */
        $model = $this->api->load->model( 'data/user_settings' );

        $email = $model->get( 'test_settings_email' );

        if (!$email)
        {
            /** @var ncore_MailerLib $lib */
            $lib = $this->api->load->library( 'mailer' );
            $email = $lib->defaultTestEmailAddress();
        }

        return $email;
    }

    private function _setTestDefaults( $email )
    {
        /** @var ncore_UserSettingsData $model */
        $model = $this->api->load->model( 'data/user_settings' );

        $model->set( 'test_settings_email', $email );
    }


   private function sendTestMail( $test_email )
    {
        $this->_setTestDefaults( $test_email );

        /** @var ncore_RuleValidatorLib $rules */
        $rules = $this->api->load->library( 'rule_validator' );

        $error_msg = $rules->validate( _ncore('Email'), $test_email, 'email' );

        if (is_string( $error_msg ))
        {
            $this->formError( $error_msg );
            return;
        }

        /** @var digimember_MailHookLogic $model */
        $model = $this->api->load->model( 'logic/mail_hook' );
        $success = $model->sendMail( $test_email, NCORE_MAIL_HOOK_TESTMAIL  );

        if ($success)
        {
            $this->formSuccess( _ncore('A test email has been sent to %s.', $test_email ));
        }
        else
        {
            $this->formError( _ncore('The email to %s could not be send. Please validate the email address and your email settings.', $test_email ) . ' (' . $model->lastMailError() . ')' );
        }
    }

    private function updateLicense( $license_key )
    {
        if (!$license_key)
        {
            return;
        }

        try
        {
            $lib = $this->api->loadLicenseLib();
            // $license =$lib->getLicense();

            $lib->fetchLicense( $license_key );

            $errormsg = $lib->getLicenseErrors( true );

            if ($errormsg)
            {
                $this->formError( $errormsg );
            }
            else
            {
                $this->formSuccess( _ncore( 'The license is valid and activated.') );

                /** @var digimember_LinkLogic $model */
                $model = $this->api->load->model( 'logic/link' );
                $url   = $model->adminPage();
                ncore_redirect( $url );
            }
        }
        catch (Exception $e)
        {
            $this->formError( $e->getMessage() );
        }


    }


    private function resetLicenseKey()
    {
        $lib = $this->api->loadLicenseLib();

        try
        {
            $lib->clearLicense();
        }
        catch (Exception $e)
        {
        }

        $data = array( 'license_code' => '' );
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $model->setAll( $data );

        $this->formSuccess( _ncore( 'The license key has been resetted. You may now use the license for another domain.') );

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $url   = $model->adminPage();
        ncore_redirect( $url );

    }

    private function updateLeadPagesApiKey( $leadpages_apikey_file )
    {
        $leadpages_apikey_file = trim( str_replace( "'", '"', $leadpages_apikey_file ) );

        if (!$leadpages_apikey_file) {
            return;
        }

        // mandatory: define("PRIVATE_LEADPAGES_API_KEY", "MTJlMmJmYjQ2NjM5YzU6MzNRYjhaRHFPMVlPdUVZMDFhRmk1VHA3RmY2bkxqdXc=");
        // optional:  define("PRIVATE_LEADPAGES_API_URL", "https://my.leadpages.net/api/");

        $api_key = false;
        $api_url = false;

        if (preg_match( '|PRIVATE_LEADPAGES_API_KEY".*"(.*)"|', $leadpages_apikey_file, $matches )) {
            $api_key = $matches[1];
        }
        if (preg_match( '|PRIVATE_LEADPAGES_API_URL".*"(.*)"|', $leadpages_apikey_file, $matches )) {
            $api_url = $matches[1];
        }

        $is_valid = (bool) $api_key;

        if ($is_valid)
        {
            $modified = $api_key != get_option('leadpages_private_api_key', false)
                     || $api_url != get_option('leadpages_private_api_url', false);

            if ($modified)
            {
                update_option('leadpages_private_api_key', $api_key);
                update_option('leadpages_private_api_url', $api_url);

                /** @noinspection PhpUndefinedFunctionInspection */
                $this->formSuccess( _dbiz( 'The Leadpages API key has been stored!') );
            }
        }
        else
        {
            /** @noinspection PhpUndefinedFunctionInspection */
            $this->formError( _dbiz( 'This is not a valid contents of the file api_key.php.') );
        }

    }


}