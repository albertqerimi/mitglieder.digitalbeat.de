<?php
/** @noinspection PhpIncludeInspection */


/**
 * Class ncore_ApiCore
 *
 * @property-read digimember_ProductData $product_data
 * @property-read digimember_LinkLogic $link_logic
 * @property-read digimember_ActionLogic $action_logic
 * @property-read ncore_ActionData $action_data
 * @property-read ncore_AutoresponderData $autoresponder_data
 * @property-read digimember_ExamCertificateData $exam_certificate_data
 * @property-read digimember_PaymentHandlerLib $payment_handler_lib
 * @property-read digimember_MailHookLogic $mail_hook_logic
 * @property-read digimember_PageProductData $page_product_data
 * @property-read digimember_AccessLogic $access_logic
 * @property-read digimember_DigistoreConnectorLogic $digistore_connector_logic
 * @property-read ncore_FormVisibilityLib $form_visibility_lib
 * @property-read digimember_ExamAnswerData $exam_answer_data
 * @property-read digimember_ExamData $exam_data
 * @property-read digimember_UserData $user_data
 * @property-read digimember_BlogConfigLogic $blog_config_logic
 * @property-read digimember_CourseLogic $course_logic
 * @property-read digimember_MailTextData $mail_text_data
 * @property-read ncore_RuleValidatorLib $rule_validator_lib
 * @property-read digimember_UserProductData $user_product_data
 * @property-read digimember_FeaturesLogic $features_logic
 * @property-read digimember_WebhookData $webhook_data
 * @property-read digimember_WebhookLogic $webhook_logic
 * @property-read digimember_WebpushMessageData $webpush_message_data
 * @property-read digimember_WebpushSubscriptionData $webpush_subscription_data
 * @property-read digimember_WebpushQueue $webpush_queue
 * @property-read digimember_WebpushLogic $webpush_logic
 * @property-read digimember_GdprLogic $gdpr_logic
 * @property-read ncore_SessionLogic $session_logic
 */
class ncore_ApiCore
{
	public $load;

	public static function getInstance( $plugin_name, $root_dir = false )
	{
        global $blog_id;

        $cachekey = empty( $blog_id )
                  ? $plugin_name
                  : $blog_id.$plugin_name;

        $root_dir = rtrim( $root_dir, '/ ' );

		$instance =& self::$instances[ $cachekey ];
		if ( !isset( $instance ) )
		{
			if ( !$root_dir )
			{
				trigger_error( 'Need $root_dir when creating instance' );
			}

            $plugin_class_file = "$root_dir/application/core/api.php";
            if (file_exists($plugin_class_file)) {
                /** @noinspection PhpIncludeInspection */
                require_once $plugin_class_file;

                $class = $plugin_name . '_ApiCore';
            }
            else {
                $class = 'ncore_ApiCore';
            }

			$instance = new $class( $plugin_name, $root_dir );
		}

		return $instance;
	}

	public function __construct( $plugin_name, $root_dir )
	{
		$this->plugin_name = $plugin_name;
		$this->root_dir    = $root_dir;
		$this->sys_dir     = dirname( dirname( dirname( __FILE__ ) ) ) . '/system';

		$sys_dir = $this->sys_dir;

		require_once "$sys_dir/config/environment/environment.php";
		require_once "$sys_dir/config/constants.php";

        $file = "$root_dir/application/config/constants.php";
        if (file_exists($file)) {
            require_once $file;
        }

		require_once $this->sys_dir . "/core/loader.php";
		$this->load = new ncore_LoaderCore( $this, $plugin_name, $root_dir, $sys_dir );
	}

	public function init()
	{
		if (!isset($this->init))
		{
			require_once $this->sysDir() . '/core/init.php';

			$app_dir = $this->appDir() . '/core/init.php';

			$have_app_initializer = file_exists( $app_dir );
			if ($have_app_initializer)
			{
				require_once $app_dir;
				$plugin_name = $this->pluginName();
				$class = $plugin_name . '_InitCore';
			}
			else
			{
				$class = 'ncore_InitCore';
			}

            if (!class_exists($class))
            {
                trigger_error( "Class not loaded: $class (appdir is: $app_dir)" );
                return false;

            }

            /** @var ncore_InitCore $init */
			$init = new $class( $this, 'init', 'core' );

			$init->init();

			$this->init = $init;
		}

		return $this->init;
	}

	public function edition()
    {
        $have_edition = NCORE_DEBUG && defined( 'DIGIMEMBER_DEBUG_EDITION' );
	    if ($have_edition) {
            /** @noinspection PhpUndefinedConstantInspection */
            return DIGIMEMBER_DEBUG_EDITION;
        }

        $have_edition = defined( 'DIGIMEMBER_EDITION' ) && substr( DIGIMEMBER_EDITION, 0, 1) != '{';
	    if ($have_edition) {
	        return DIGIMEMBER_EDITION;
        }

	    $url = $this->licenseServerBaseUrl();

	    $is_us = strpos( $url, 'digimember.com' ) !== false;

	    return $is_us
               ? 'US'
               : 'DE';
    }

	public function licenseServerBaseUrl() {

	    $have_url = defined( 'DIGIMEMBER_LICENSE_SERVER_URL' ) && substr( DIGIMEMBER_LICENSE_SERVER_URL, 0, 1) != '{';
	    if ($have_url) {
	        return DIGIMEMBER_LICENSE_SERVER_URL;
        }

        $is_server = defined('NCORE_IS_LICENSE_SERVER') && NCORE_IS_LICENSE_SERVER;
	    if ($is_server) {
            $url = rtrim(site_url(), '/');
            return $url;
        }

        return 'https://digimember.de';
    }

	public function isPluginSetup()
	{
	    /** @var ncore_ConfigStoreData $model */
		$model = $this->load->model( 'data/config_store' );
		$is_setup = $model->sqlTableExists();

		return $is_setup;
	}

    public function getPluginActivationObstacles()
	{
	    /** @var ncore_ConfigStoreData $model */
		$model = $this->load->model( 'data/config_store' );
		$obstacles = $model->validateFrameworkSetup();
		if ($obstacles)
		{
			return $obstacles;
		}

		return false;
	}

    public function pluginIsActive()
    {
        $plugin_main_file = $this->pluginMainFile();

        $must_include_wp_include_file = !function_exists( 'is_plugin_active' );
        if ($must_include_wp_include_file)
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active( $plugin_main_file );
    }

    public function havePlugin( $name )
    {
        $name_constant_map = array(
            'digimember'  => 'DIGIMEMBER_DIR',
        );

        $constant = ncore_retrieve( $name_constant_map, $name );
        if (!$constant) {
            return false;
        }

        return defined( $constant );
    }


    /** @var bool | ncore_LicenseValidator_PluginBase */
    private $license_lib = false;
    public function loadLicenseLib()
    {
        if (!$this->license_lib)
        {
            $settings = array( 'package' => $this->pluginName() );

            $this->license_lib = $this->load->library( 'license_validator', $settings );
        }

        return $this->license_lib;
    }

    public function pluginNameFree( $format='html' )
    {
        /** @var ncore_ConfigLib $config */
        $config       = $this->load->config( 'general' );
        $name = $config->get( 'plugin_name_free' );

        return $this->_formatName( $name, $format, 'ncore_free_name' );
    }

    public function pluginNamePro( $format='html' )
    {
        $config = $this->load->config( 'general' );
        $name = $config->get( 'plugin_name_pro' );

        return $this->_formatName( $name, $format, 'ncore_pro_name' );
    }

    public function isFreePluginVersionAvailable()
    {
        $config       = $this->load->config( 'general' );
        $is_avialable = (bool) $config->get( 'license_is_free_available' );

        return $is_avialable;
    }

	public function error( $msg )
	{
		$trace = ncoreDebugTrace();
		trigger_error( $this->plugin_name . ": $msg\n\n\n$trace" );

		if (NCORE_DEBUG)
		{
			$msg .= "\n\n\n$trace";
		}

		if (NCORE_LOG_FILE)
		{
			$date = date( "Y-m-d H:i:s" );

			$old_level = error_reporting(0);

			$fp = fopen( NCORE_LOG_FILE, 'a' );
			fwrite( $fp, "\n\n----------------------------------------------------\n\n\n$date: $msg\n\n----------------------------------------------------\n\n\n" );
			fclose( $fp );

			error_reporting( $old_level );

		}

		die( $this->plugin_name . ': ' . $msg );
	}

	public function registerInstance( $dir, $file, $instance )
	{
		$name        = $file . '_' . $dir;
		$this->$name = $instance;
	}

	public function className( $application, $file, $dir = '' )
	{
		$prefix = $application == 'system' ? 'ncore' : $application;

		$name = ncore_camelCase( $file . '_' . $dir );

		return $prefix . '_' . $name;
	}

	public function pluginName()
	{
		return $this->plugin_name;
	}

    public function Digistore24DisplayName($asLink = true)
    {

        $label = 'Digistore24';

        /** @var digimember_LinkLogic $model */
        $model = $this->load->model('logic/link');
        $signup_url = $model->digistoreReferenceUrl();

        return $asLink
            ? "<a target=\"_blank\" href=\"$signup_url\">$label</a>"
            : "$label";
    }

    public function pluginMainFile( $full_path = false )
	{
		$plugin    = $this->plugin_name;
		$main_file = $full_path
					 ? $this->root_dir
					 : $plugin;

		$main_file .= "/$plugin.php";

		return $main_file;
	}

    public function pluginBaseName()
    {
        return dirname( plugin_basename( $this->pluginMainFile() ) );
    }

	public function pluginVersion()
	{
		if (!$this->plugin_version)
		{
			$plugin_file = $this->pluginMainFile( $full_path = true);
			$contents = file_get_contents( $plugin_file );

			preg_match( '/Version: *([0-9\.]*)/i', $contents, $matches );

			$this->plugin_version = $matches[1];

		}

		return $this->plugin_version;

	}

	public function pluginDisplayName()
	{
		if (!$this->plugin_display_name)
		{
            $plugin_name = $this->pluginName();

            $display_name = $this->_camelcase( $plugin_name );

            $this->plugin_display_name = apply_filters( 'ncore_plugin_displayname', $display_name, $plugin_name );
		}

		return $this->plugin_display_name;
	}

    public function pluginLogName()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        return $this->plugin_name == 'digibizz'
               ? dbizz_pluginDisplayName()
               : $this->_camelcase( $this->plugin_name );
    }


	public function sysDir()
	{
		return $this->sys_dir;
	}

	public function appDir()
	{
		return $this->root_dir.'/application';
	}

	public function rootDir()
	{
		return $this->root_dir;
	}

	public function pluginUrl( $file='', $stripHost=false )
	{
		if ($this->root_url === false)
		{
			$this->root_url = plugins_url( "/", $this->pluginMainFile() );

			$this->root_url = rtrim( $this->root_url, '/' ) . '/';
		}

		if ($stripHost && $this->root_url_without_host === false)
		{
			$proto_pos = strpos( $this->root_url, '://' );

			$path_pos = strpos( $this->root_url, '/', $proto_pos+3 );

			if ($proto_pos !== false && $path_pos !== false)
			{
				$this->root_url_without_host = substr( $this->root_url, $path_pos );
			}
			else
			{
				$this->root_url_without_host = $this->root_url;
			}
		}



		$url = $stripHost
			   ? $this->root_url_without_host
			   : $this->root_url;

		if ($file)
		{
			$file = ltrim( $file, '/' );

			$url .= $file;
		}

		return $url;
	}

	public function logError( $section, $message_templ, $arg1='', $arg2='', $arg3='' )
	{
		$this->log( $section, $message_templ, $arg1, $arg2, $arg3, 'error' );
	}

	public function log( $section, $message_templ, $arg1='', $arg2='', $arg3='', $level='info' )
	{
	    /** @var ncore_LogData $model */
		$model = $this->load->model( 'data/log' );

		$model->log( $level, $section, $message_templ, $arg1, $arg2, $arg3 );
	}

	public function language()
	{
		if ($this->language === false)
		{
			$locale = get_locale();
			list( $this->language, ) = explode( '_', $locale );

			if (empty($this->language))
			{
				$this->language = 'en';
			}
		}

		return $this->language;
	}

	private static $instances;
	private $plugin_name = '';
	private $plugin_display_name = '';
	private $sys_dir = '';
	private $root_dir = '';
	private $root_url = false;
	private $root_url_without_host = false;
	private $plugin_version = '';
	private $init;

	private $language = false;



    private function _camelcase( $name ) {

        if (ncore_stringStartsWith( $name, 'digi' )) {

            $camecased = 'Digi' . ucfirst( substr( $name, 4 ) );
        }
        else {
            $camecased = ucfirst( $name );
        }

        return $camecased;
    }

    private function _formatName( $name, $format, $css )
    {
        switch ($format) {
            case 'html':
                $css .= ' ' . $this->pluginName();
                return "<span class='$css'>$name</span>";
            case 'text':
            default:
                return $name;
        }
    }
}
