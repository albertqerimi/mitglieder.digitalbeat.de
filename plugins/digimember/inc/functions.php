<?php

if (!function_exists( 'ncore_api' ) )
{
	function ncore_api( $plugin='digimember', $plugin_root_dir='' )
	{
		static $instances;

        $blog_id = function_exists( 'get_current_blog_id' )
                 ? get_current_blog_id()
                 : 0;

        if ($plugin === 'all')
        {
            return ncore_retrieve( $instances, $blog_id, array() );
        }

        $instance =& $instances[ $blog_id ][ $plugin ];

        if (empty($instance))
		{
            if (!$plugin_root_dir) {
                $plugin_root_dir = $plugin === 'digimember'
                                 ? DIGIMEMBER_DIR
                                 : dirname( DIGIMEMBER_DIR ) . DIRECTORY_SEPARATOR . $plugin;
            }
			if (!class_exists( 'ncore_ApiCore' ) )
			{
				require_once DIGIMEMBER_DIR . '/system/core/api.php';
                define( 'NCORE_SYSTEM_OWNER', 'digimember' );
			}

			$instance = ncore_ApiCore::getInstance( $plugin, $plugin_root_dir );
            $instance->init();
		}

		return $instance;
	}
}

function dm_api() {
    return ncore_api( 'digimember', DIGIMEMBER_DIR );
}

function _dgyou( $string, $var1=false, $var2=false, $var3=false )
{
    static $domain;
    if (!$domain)
    {
        $model = dm_api()->load->model( 'logic/blog_config' );
        $du = $model->get( 'lang_personal' );

        $locale = get_locale();

        $is_de = $locale[0] == 'd' && $locale[1] == 'e';

        $maybe_have_DU = $is_de || $du == 'custom';

        if ($maybe_have_DU)
        {
            $model = dm_api()->load->model( 'logic/blog_config' );
            $du = $model->get( 'lang_personal' );
            switch ($du)
            {
                case 'Du':
                    $domain = 'digimember_you_du_upper';
                    $folder = 'digimember/languages';
                    break;
                case 'du':
                    $domain = 'digimember_you_du_lower';
                    $folder = 'digimember/languages';
                    break;
                case 'custom':
                    $domain = 'digimember_you_custom';
                    $folder = ''; // The file will be located in the plugins folder, because this makes it survice the updates
                    break;
                default:
                    $domain = 'digimember_you';
                    $folder = 'digimember/languages';
            }
        }
        else
        {
            $domain = 'digimember_you';
            $folder = 'digimember/languages';
        }

        load_plugin_textdomain( $domain, false, $folder );
    }

    return $var1===false
           ? __( $string, $domain )
           : sprintf( __( $string, $domain ), $var1, $var2, $var3 );
}



function _digi( $string, $var1=false, $var2=false, $var3=false, $var4=false )
{
	static $initialized;
	if (!$initialized)
	{
		$initialized = true;
		load_plugin_textdomain( 'digimember', false, 'digimember/languages' );
	}

	return $var1===false
		   ? __( $string, 'digimember' )
		   : sprintf( __( $string, 'digimember' ), $var1, $var2, $var3, $var4 );
}

function _digie( $string, $var1=false, $var2=false, $var3=false )
{
	echo _digi( $string, $var1, $var2, $var3 );
}

function _digi3( $string, $var1=false, $var2=false, $var3=false, $var4=false )
{
	static $initialized;
	if (!$initialized)
	{
		$initialized = true;
		load_plugin_textdomain( 'digimember-3rd-party', false, 'digimember/languages' );
	}

	return $var1===false
		   ? __( $string, 'digimember-3rd-party' )
		   : sprintf( __( $string, 'digimember-3rd-party' ), $var1, $var2, $var3, $var4 );
}



function ncore_retrieve( $array_or_object, $key_or_keys, $default = '' )
{
    if ( is_array( $key_or_keys ) )
    {
        $keys = $key_or_keys;
        foreach ( $keys as $one )
        {
            $result = ncore_retrieve( $array_or_object, $one, null );
            if ( isset( $result ) )
            {
                return $result;
            }
        }
        return $default;
    }

    $key = $key_or_keys;

    if ( is_array( $array_or_object ) )
    {
        $value = isset( $array_or_object[ $key ] ) ? $array_or_object[ $key ] : $default;
    }
    elseif ( is_object( $array_or_object ) )
    {
        $value = isset( $array_or_object->$key ) ? $array_or_object->$key : $default;
    }
    else
    {
        $value = $default;
    }

    $is_void = $value === '' || $value === false;
    if ( $is_void )
    {
        $value = $default;
    }

    return $value;
}

function ncore_retrieveAndUnset( &$array_or_object, $key_or_keys, $default = '' )
{
    $value = ncore_retrieve( $array_or_object, $key_or_keys, $default  );

    $keys = is_array( $key_or_keys)
          ? $key_or_keys
          : array( $key_or_keys );

    foreach ($keys as $key)
    {
        if (is_array($array_or_object)) {
            unset( $array_or_object[$key] );
        }
        elseif (is_object($array_or_object)) {
            unset( $array_or_object->$key );
        }
    }

    return $value;
}

function ncore_retrieveAndSet( &$array_or_object, $key, $setTo = '' )
{
    $value = ncore_retrieve( $array_or_object, $key, $setTo  );

    if (is_array($array_or_object)) {
        $array_or_object[$key] = $setTo;
    }
    elseif (is_object($array_or_object)) {
        $array_or_object->$key = $setTo;
    }

    return $value;
}

function ncore_retrieveList( $seperator, $string, $min_list_size = 5, $use_exact_size=false )
{
    $list = explode($seperator, $string);

    while (count($list) < $min_list_size)
    {
        $list[] = '';
    }
    if ($use_exact_size) {
        $suffix = '';
        while (count($list) > $min_list_size) {
            $suffix .= $seperator;
            $suffix .= array_pop( $list );
        }
        $keys = array_keys( $list );
        $last_key = end( $keys );
        $list[ $last_key ] .= $suffix;
    }

    return $list;
}

function ncore_serializeField(&$data, $key) {
    if ($fieldData = ncore_retrieve($data,$key,false)) {
        if ( is_array( $data ) ) {
            $data[$key] = json_encode($fieldData);
        }
        else {
            $data->$key = json_encode($fieldData);
        }
    }
}

function ncore_deserializeField (&$data, $key) {
    if ($fieldData = ncore_retrieve($data,$key,false)) {
        if ( is_array( $data ) ) {
            $data[$key] = json_decode($fieldData);
        }
        else {
            $data->$key = json_decode($fieldData);
        }
    }
}


function ncore_washInt( $var )
{
    if (empty($var))
    {
        return 0;
    }

    if (is_numeric( $var ))
    {
        return $var;
    }

    return (int) $var;
}

function ncore_washText( $text, $allowed_chars = "", $forbidden_chars='' )
{
    $user_forbidden_chars = $forbidden_chars;

    // allowed are single dots ('.'), pipes ('|'), and underscores ('_')

    $forbidden_chars = array(
         '\\',
        '\r',
        '\n',
        '\t',
        '#',
        '%',
        '&',
        ';',
        '....',
        '...',
        '..',
        '"',
        "'",
        '/',
        ',',
        '*',
        '?',
        ' '
    );

    if ($user_forbidden_chars) {
        $forbidden_chars = array_merge( $forbidden_chars, str_split( $user_forbidden_chars ) );
    }



    $len = strlen( $allowed_chars );
    for ( $i = 0; $i < $len; $i++ )
    {
        $pos = array_search( $allowed_chars[ $i ], $forbidden_chars );
        if ( $pos !== false )
        {
            unset( $forbidden_chars[ $pos ] );
        }
    }

    return str_replace( $forbidden_chars, '', $text );
}

function ncore_findInArrayOfObjects($arrayOfObjects, $property, $value) {
    foreach ($arrayOfObjects as $object) {
        if ($object->$property == $value) {
            return $object;
        }
    }
    return false;
}
