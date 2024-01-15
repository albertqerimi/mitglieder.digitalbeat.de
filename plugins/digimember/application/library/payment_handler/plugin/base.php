<?php

define( 'DIGIMEMBER_IPN_PER_PRODUCT', 'per_product' );
define( 'DIGIMEMBER_IPN_PER_PROVIDER', 'per_provider' );

abstract class digimember_PaymentHandler_PluginBase extends ncore_Plugin
{
	public function __construct( digimember_PaymentHandlerLib $parent, $meta )
	{
		$this->payment_provider_id = ncore_retrieve( $meta, 'id', 0 );

		$type = $meta['engine'];
		parent::__construct( $parent, $type, $meta );
	}

	public function reportSuccess( $type, $user_product_id )
	{
		echo 'OK';
	}

	public function reportError( $msg )
	{
		echo "ERROR: $msg";
	}

	public function isActive( )
	{
		return $this->meta( 'is_active' ) == 'Y';
	}

	public function ipnType()
	{
		return DIGIMEMBER_IPN_PER_PROVIDER;
	}

	public function initRequest()
	{
	    /** @var digimember_IpnInLogData $model */
		$model = $this->api->load->model( 'data/ipn_in_log' );
		$model->storeIpnCall( $this->payment_provider_id );
	}

    /**
     * @throws Exception
     */
	public function validateRequestParams()
	{
		$this->validateCallbackPassword();
	}

	public function label()
	{
		$type = $this->type();

		$providers = $this->parent()->getProviders();

		$label = ncore_retrieve( $providers, $type, ucfirst( $type ) );

		return $label;
	}

    public function type()
    {
        return parent::type();
    }

	public function instructions()
	{
		return array(
		);
	}

	public function id()
	{
		return $this->meta( 'id' );
	}

	public function getEventType()
	{
		return $this->getParam('event_type', $required = false );
	}

	public function getProductIds()
	{
		list( $mapped_product_ids, $unmapped_product_ids ) = $this->getParam('product_code', $required = true, $array_allowed=true, $do_split_mapped_and_unmapped_value=true );

        $quantities = $this->getParam('quantity', $required = false, $array_allowed=true );

        $do_ignore_unmapped_product_ids = (bool) $mapped_product_ids;
        $result = $do_ignore_unmapped_product_ids
                ? $mapped_product_ids
                : $unmapped_product_ids;

		if (is_array($result))
		{
			$product_id_list = $result;
		}
		elseif ($result)
		{
			$product_id      = $result;
			$product_id_list = array( $product_id );
		}
		else
		{
			$product_id_list = array();
		}

        $sanitized_product_id_list = array();
        foreach ($product_id_list as $index => $product_id) {
            $product_id = intval( $product_id );
            $quantity   = isset( $quantities[ $index]  )
                        ? $quantities[ $index ]
                        : 1;

            if (!isset($sanitized_product_id_list[$product_id])) {
                $sanitized_product_id_list[$product_id] = 0;
            }
            $sanitized_product_id_list[$product_id] += $quantity;
        }

		return $sanitized_product_id_list;
	}

	public function formMetas()
	{
		return array();
	}

	public function orderIdsAreOfSameOrder( $order_id_a, $order_id_b )
	{
		if ($order_id_a == $order_id_b)
		{
			return true;
		}

		$base_a = $this->_extractBaseOrderId( $order_id_a );
		$base_b = $this->_extractBaseOrderId( $order_id_b );

		return $base_a == $base_b;

	}

	protected function getRequestArg( $postname, $methods='default', $array_allowed=false )
	{
		$methods = $this->resolveMethods( $methods );

        $one_value = $this->_getOneRequestArg( $postname, $methods );

		if (!$array_allowed)
		{
			return $one_value;
		}

		$values = $one_value
                ? array( 1 => $one_value )
                : array();

        $i = 0;

        $have_legacy_var_array = false;

		do
		{
            $postnames_to_try = array( $postname.$i, $postname.'_'.$i );

            if (strpos($postname, '.0.') !== false) {
                $postnames_to_try[] = str_replace('.0.', '.' . $i . '.', $postname);
            }

            $had_value = false;

            foreach ($postnames_to_try as $one_postname)
            {
                $one_value = $this->_getOneRequestArg( $one_postname, $methods );
                if ($one_value) {

                    if ($i <= 1)
                    {
                        $have_legacy_var_array = true;
                        $values = array_values( $values );
                    }

                    if ($have_legacy_var_array)
                    {
                        $values[] = $one_value;
                    }
                    else
                    {
                        $values[$i] = $one_value;
                    }
                    $had_value  = true;
                }
            }


            $i++;

            $finished = $i>10 && !$had_value;

		}
		while (!$finished);

        return $values
               ? $values
               : false;
	}

	protected function getRequestArgArray( $methods='default' )
	{
		$args = array();

		$methods = $this->resolveMethods( $methods );

		if (in_array( METHOD_POST,$methods))
		{
			$args = array_merge( $args, $_POST );
		}

		if (in_array( METHOD_GET,$methods))
		{
			$args = array_merge( $args, $_GET );
		}

		return $args;
	}

	public function getParam( $variable_name, $required = false, $array_allowed=false, $do_split_mapped_and_unmapped_value=false )
	{
		$name_map = $this->parameterNameMap();

		$default=false;

		$value = false;
		$value_map = false;

		$display_variable_name = $variable_name;

		foreach ($name_map as $postname => $mapped_name )
		{
			if ($variable_name == $mapped_name)
			{
				$display_variable_name = $postname;

				$value = $this->getRequestArg( $postname, 'default', $array_allowed );

				if ($value)
				{
					$value_map = $this->parameterValueMap( $mapped_name );
					break;
				}
			}
		}

		if ($value_map === false)
		{
			$value_map = $this->parameterValueMap( $variable_name );
		}

		if ($value === false)
		{
			$value = $this->getRequestArg( $variable_name, 'default', $array_allowed );
		}

		$have_value = $value !== false;
		if (!$have_value)
		{
			if ($required)
			{
				$this->exception( _digi('Parameter "%s" is required for current type of event', $display_variable_name) );
			}
			return $default;
		}

        if ($do_split_mapped_and_unmapped_value)
        {
            if (!is_array($value))
            {
                $value = array( $value );
            }

            $mapped_values   = array();
            $unmapped_values = array();

			foreach ($value as $index => $one)
			{
				$have_mapped_value = isset( $value_map[ $one ] );

                if ($have_mapped_value)
                {
                    $mapped_values[$index] = $value_map[ $one ];
                }
                else {
                    $unmapped_values[$index] = $one;
                }
            }

            return array( $mapped_values, $unmapped_values );
		}

        if (is_array($value))
        {
            $mapped_values   = array();

            foreach ($value as $index => $one)
            {
                $have_mapped_value = isset( $value_map[ $one ] );

                $mapped_values[$index] = $have_mapped_value
                                       ? $value_map[ $one ]
                                       : $one;
            }

            return $mapped_values;
        }

		$have_mapped_value = isset( $value_map[ $value ] );

		return $have_mapped_value
			 ? $value_map[ $value ]
             : $value;
	}

	public function getAddress()
	{
		$address_params = array(
			'first_name',
			'last_name',
			'street',
			'zip_code',
			'state',
			'city',
			'country'
		);

		$address = array();
		foreach ($address_params as $one)
		{
			$address[ $one ] = $this->getParam( $one, $required = false );
		}

		return $address;
	}

	protected function parameterNameMap()
	{
		return array();
	}

	protected function methods()
	{
		return array( METHOD_POST, METHOD_GET );
	}

	final protected function resolveMethods( $what_methods )
	{
		if (is_array( $what_methods ))
		{
			return $what_methods;
		}

		switch ($what_methods)
		{
			case 'all': return array( METHOD_POST, METHOD_GET );
			case 'default': return $this->methods();
			default:
				trigger_error( "Invalid \$what_methods: '$what_methods'" );
				return $this->methods();
		}
	}

	protected function getMapped( $variable_name, $map, $default='' )
	{
		$value = $this->get( $variable_name );

		$have_mapped_value = isset( $map[ $value ] );

		return $have_mapped_value
			 ? $map[ $value ]
			 : $default;
	}

	protected function parameterValueMap( $variable_name )
	{
		switch ($variable_name)
		{
			case 'event_type':
				return $this->eventMap();

			case 'product_code':
				return $this->productCodeMap();
		}

		return array();
	}

	protected function eventMap()
	{
		return array();
	}

	protected function productCodeMap()
	{
		return $this->parseMap( 'product_code_map' );
	}

	protected function parseMap( $mapSettingsKey )
	{
		$this->api->load->helper( 'array' );

		$map_serialized = $this->meta( $mapSettingsKey );
		if (!$map_serialized)
		{
			$map_serialized = $this->data( $mapSettingsKey );
		}

		$map_reversed = ncore_simpleMapExplode( $map_serialized );

		$map = array();

		foreach ($map_reversed as $value => $keys_comma_seperated)
		{
			$keys = explode( ',', $keys_comma_seperated );
			foreach ($keys as $key)
			{
				$key = trim( $key );
				if (!$key)
				{
					continue;
				}

				$map[ $key ] = $value;
			}
		}

		return $map;
	}

	protected function exception( $error_message )
	{
		$message = $error_message;

		throw new Exception( $message );
	}

	protected function data( $key, $default = '')
	{
		$data = $this->meta( 'data', array() );
		$key = $this->type() . '_' . $key;
		return ncore_retrieve( $data, $key, $default );
	}

	protected function productOptions()
	{
		if (self::$product_options === false)
		{
			self::$product_options = $this->api->product_data->options( 'all' );
		}

		return self::$product_options;
	}

    protected function getUserNameAndPassword( $user_product_id )
    {
        if (!$user_product_id)
        {
            return array( $username='', $password='');
        }
        $up_model = $this->api->load->model( 'data/user_product' );
        $ps_model = $this->api->load->model( 'data/user' );

        $user_product = $up_model->get( $user_product_id );

        $user = ncore_getUserById( $user_product->user_id );

        $login    = $user->user_login;
        $password = $ps_model->getPassword( $user_product->user_id );

        return array( $login, $password );
    }

    protected function renderMessageForInvalidIpnPassword()
    {
        return _digi( "The payment provider used an invalid notification URL. Please check the IPN URL in the payment provider settings in the %s admin area. Adjust the URL in your payment providers back office.", $this->api->pluginDisplayName()  );
    }

	//
	// private
	//
	private $payment_provider_id = false;
	private static $product_options = false;
	private function validateCallbackPassword()
	{
		$expected_callback_pw = $this->meta( 'callback_pw' );

		$sent_callback_pw = $this->retrieveSendCallbackBw();

		$matches = $expected_callback_pw == $sent_callback_pw;

		if (!$matches)
		{
			$msg = $this->renderMessageForInvalidIpnPassword();

			throw new Exception( $msg );
		}
	}


	private function _extractBaseOrderId( $order_id )
	{
		$order_id = str_replace( array( ' ', '/' ), '-', $order_id );

		$tokens = explode( '-', $order_id );

		$have_no_pay_sequence_no = count($tokens) <= 1;
		if ($have_no_pay_sequence_no)
		{
			return $order_id;
		}

		$last = array_pop( $tokens );
		$is_pay_sequence_no = is_numeric( $last ) && $last >= 0 && $last <=99;

		if ($is_pay_sequence_no)
		{
			$base_id = preg_replace( "/.$last\$/", '', $order_id );
			return $base_id;
		}

		return $order_id;

	}

	private function retrieveSendCallbackBw()
	{
		$keys = array( 'dm_pw', 'callback_pw' );

		foreach ($keys as $key)
		{
			$pw = $this->getRequestArg( $key, $methods='all' );
			if ($pw) return $pw;

			$pw =ncore_retrieve( $_GET, $key );
			if ($pw) return $pw;

			$pw =ncore_retrieve( $_POST, $key );
			if ($pw) return $pw;

			// just in case shareit has got an url with "&amp;" instead of "&", like:
			// http://someblog.com/wp-content/plugins/digimember/ipn.php?ipn_id=1&amp;callback_pw=xxxxxxxxxxxxxxx
			$pw =ncore_retrieve( $_GET, "amp;$key" );
			if ($pw) return $pw;
		}

		return false;

	}

	private function _getOneRequestArg( $postname, $methods )
	{
		if (in_array( METHOD_POST,$methods)
			&& isset($_POST[$postname]))
			{
				return $_POST[$postname];
			}

		if (in_array( METHOD_GET,$methods)
			&& isset($_GET[$postname]))
			{
				return $_GET[$postname];
			}

		if (in_array( METHOD_INPUT,$methods))
		{
			return $this->_getInputArg( $postname );
		}

		if (in_array( METHOD_INPUT_JSON,$methods))
		{
			return $this->_getInputArg( $postname, METHOD_INPUT_JSON );
		}

		return false;
	}

	private $input_args = false;
	private function _getInputArg( $postname, $inputType = METHOD_INPUT )
	{
		if (!is_array( $this->input_args ))
		{
			global $digimember_debug_input;
			$input = empty($digimember_debug_input) || !NCORE_DEBUG
				   ? file_get_contents( 'php://input' )
				   : $digimember_debug_input;

			if ($inputType == METHOD_INPUT)
			{
                $input = preg_replace( '/^[^a-zA-Z0-9]*/', '', $input ); // Remove unicode BOM at beginning of input

                $this->api->load->helper( 'array' );
                $this->input_args = ncore_explodeKeyValuePairs( $input );
            }
			else if ($inputType == METHOD_INPUT_JSON)
			{
			    $jsonArgs = json_decode($input, true);
			    if (is_array($jsonArgs)) {
			        $this->api->load->helper('array');
                    ncore_flattenAssocArray($jsonArgs, $this->input_args);
                }
            }

			if (empty($this->input_args))
			{
				$this->input_args = array();
			}
		}

		return ncore_retrieve( $this->input_args, $postname );
	}
}
