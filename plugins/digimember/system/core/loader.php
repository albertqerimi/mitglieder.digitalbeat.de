<?php /** @noinspection PhpIncludeInspection */

/** @noinspection PhpInconsistentReturnPointsInspection */

class ncore_LoaderCore
{
    private $app_dir = '';

    public function __construct( ncore_ApiCore $api, $plugin_name, $root_dir, $sys_dir )
    {
        $this->api = $api;
        $this->root_dir = $root_dir;
        $this->sys_dir = $sys_dir;
        $this->app_dir = $root_dir . '/application';

        $this->plugin_name = $plugin_name;

        $required_helpers = array( 'features', 'required', 'wordpress' );

        foreach ($required_helpers as $one)
        {
            require_once "$this->sys_dir/helper/$one.php";

            if (file_exists("$this->app_dir/helper/$one.php")) {
                require_once "$this->app_dir/helper/$one.php";
            }
        }



        require_once $this->sys_dir . '/base/class.php';
        require_once $this->sys_dir . '/base/library.php';
        require_once $this->sys_dir . '/base/plugin.php';
        require_once $this->sys_dir . '/base/model.php';
        require_once $this->sys_dir . '/base/controller.php';
    }

    public function controller($file, $settings=array() )
    {
        if (empty($settings))
        {
            $settings = array();
        } elseif (!is_array($settings))
        {
            $settings = array( $settings );
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $controller = $this->loadFile( 'controller', 'controller', $file, $settings );

        if ($controller->is_new_instance) {
            /** @noinspection PhpUndefinedMethodInspection */
            $controller->init( $settings );
        }

        return $controller;
    }

    public function controllerBaseClass( $file )
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $load = $this;
        require_once $this->sys_dir . "/controller/$file.php";
    }

    public function model($model_path )
    {
        list( $type, $file ) = explode( '/', $model_path );

        $base_class_loaded = in_array( $type, $this->loaded_base_models );
        if (!$base_class_loaded)
        {
            $this->loaded_base_models[] = $type;
            $this->loadClass( $type, "model/$type", 'base' );
        }

        try {
            return $this->loadFile($type, "model/$type", $file);
        } catch (Exception $e) {
            return null;
        }
    }

    public function modelClass( $model_path )
    {
        list( $type, $file ) = explode( '/', $model_path );

        return $this->loadClass( $type, "model/$type", $file  );
    }

    public function miscClass( $class )
    {
        return $this->loadClass( $type='class', "class", $file=$class  );
    }

    public function allModels( $app_or_apps, $dir_or_dirs )
    {
        $apps = is_array($app_or_apps)
              ? $app_or_apps
              : array( $app_or_apps);

        $dirs = is_array($dir_or_dirs )
              ? $dir_or_dirs
              : array( $dir_or_dirs );

        $models = array();

        foreach ($apps as $app)
        {
            foreach ($dirs as $dir)
            {
                $path = $app == 'system'
                      ? $this->sys_dir."/model/$dir/"
                      : $this->root_dir."/application/model/$dir/";

                $function = 'model';

                $some = $this->_load_all_files( $path, $function, "$dir/FILE" );

                $models = array_merge( $models, $some );
            }
        }

        return $models;
    }

    public function helper( $file )
    {
        $system_helper_path = $this->sys_dir."/helper/$file.php";
        $system_helper_path_user = $this->sys_dir."/helper/user/$file.php";
        $app_helper_path = $this->root_dir."/application/helper/$file.php";
        $app_helper_path_user = $this->root_dir."/application/helper/user/$file.php";

        if (!ncore_isAdminArea() && file_exists($system_helper_path_user)) {
            require_once $system_helper_path_user;
        } else if (file_exists($system_helper_path))
        {
            require_once $system_helper_path;
        }

        if (!ncore_isAdminArea() && file_exists($app_helper_path_user)) {
            require_once $app_helper_path_user;
        } if (file_exists($app_helper_path))
        {
            require_once $app_helper_path;
        }
    }

    public function library($file, $settings=array() )
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->loadFile( 'lib', 'library', $file, $settings );
    }

    public function config($file )
    {
        /** @var ncore_ConfigLib $lib */
        /** @noinspection PhpUnhandledExceptionInspection */
        $lib = $this->library( 'config' );

        if ($lib->isLoaded( $file ))
        {
            $lib->setDomain( $file );
        }
        else
        {
            $sys_config = $this->sys_dir."/config/$file.php";
            $app_config = $this->app_dir."/config/$file.php";

            if (file_exists($sys_config))
            {
                require $sys_config;
            }
            if (file_exists($app_config))
            {
                require $app_config;
            }

            if (empty($config)) {
                $config = array();
            }

            $lib->setConfig( $file, $config );

        }

        return $lib;
    }

    /**
     * @throws Exception
     */
    public function autoLoad()
    {
        $models    = $this->requiredModels();
        $helpers   = $this->requiredHelpers();
        $libraries = $this->requiredLibraries();

        foreach ($models as $one)
        {
            $this->model( $one );
        }

        foreach ($helpers as $one)
        {
            $this->helper( $one );
        }

        foreach ($libraries as $one)
        {
            $this->library( $one );
        }

    }

    private $api;
    private $loaded_files;
    private $plugin_name = '';
    private $root_dir = '';
    private $sys_dir = '';
    private $loaded_base_models = array();

    private function loadFile($name_suffix, $dir, $file, $settings=array()  )
    {
        $is_new_instance = false;

        $instance_key = $settings
                      ? md5( serialize( $settings ) )
                      : 'default';

        $instance =& $this->loaded_files[ $dir ][ $file ][ $instance_key ];

        $file_is_loaded = isset( $instance );
        if (!$file_is_loaded)
        {
            $class = $this->loadClass( $name_suffix, $dir, $file );

            if (!class_exists($class))
            {
                $dont_instance = ncore_retrieve( $settings, 'dont_instance', false );
                if ($dont_instance)
                {
                    return false;
                }
                else
                {
                    $file = __FILE__ . ':' . __LINE__;
                    /** @noinspection PhpUnhandledExceptionInspection */
                    throw new Exception( "Class $class not found in $file" );
                }
            }

            $instance = new $class( $this->api, $file, $dir );

            $is_new_instance = true;
        }

        $this->api->registerInstance( $name_suffix, $file, $instance );

        $instance->is_new_instance = $is_new_instance;

        return $instance;
    }

    private function loadClass( $name_suffix, $dir, $file )
    {
        $plugin_name = $this->plugin_name;

        $className = '';

        $path = $this->sys_dir . "/$dir/$file.php";
        if (file_exists($path))
        {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $load = $this;

            require_once $path;

            $className = $this->api->className( 'system', $file, $name_suffix );
        }
        else
        {

            $path = $this->sys_dir . "/$dir/$file/$file.php";
            if (file_exists($path ))
            {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $load = $this;

                require_once $path ;

                $className = $this->api->className( 'system', $file, $name_suffix );
            }
        }

        $path = $this->root_dir . "/application/$dir/$file.php";
        if (file_exists($path ))
        {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $load = $this;

            require_once $path ;

            $className = $this->api->className( $plugin_name, $file, $name_suffix );
        }
        else
        {
            $path = $this->root_dir . "/application/$dir/$file/$file.php";
            if (file_exists($path ))
            {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $load = $this;

                require_once $path ;

                $className = $this->api->className( $plugin_name, $file, $name_suffix );
            }
        }

        if ($className)
        {
            return $className;
        }

        $this->api->error( "Cannot load class file for $dir/$file" );
    }

    private function requiredModels()
    {
        return array( 'logic/link', 'logic/html' );
    }

    private function requiredHelpers()
    {
        return array( 'html', 'url' );
    }

    private function requiredLibraries()
    {
        return array( 'config' );
    }


    private function _load_all_files( $path, $function, $filename_template='FILE' )
    {
        $entries = @scandir( $path );
        if (empty($entries)) {
            return array();
        }

        $objects = array();

        foreach ($entries as $one)
        {
            if (!preg_match( '/^(.*)\.php$/', $one, $matches ))
            {
                continue;
            }

            $file = $matches[1];

            $is_base = substr( $file, 0, 4 ) == 'base';
            if ($is_base)
            {
                continue;
            }

            $filename = str_replace( 'FILE', $file, $filename_template );

            $objects[] = $this->$function( $filename );
        }

        return $objects;
    }
}