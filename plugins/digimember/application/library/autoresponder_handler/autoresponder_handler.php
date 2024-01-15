<?php

class digimember_AutoresponderHandlerLib extends ncore_Library
{
    public function getTagOptions( $autoresponderConfig )
    {
        try {

            $plugin = $this->loadPlugin( $autoresponderConfig );

            if (!$plugin)
            {
                return false;
            }

            if (!$plugin->isActionSupportAvailable()) {
                return false;
            }

            return $plugin->getTagOptions();
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string     $autoresponderConfig
     * @param string|int $wp_user_id_or_email
     * @param string     $add_tag_ids_comma_seperated
     * @param string     $remove_tag_ids_comma_seperated
     * @return mixed
     * @throws Exception
     */
    public function setTags( $autoresponderConfig, $wp_user_id_or_email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated='' )
    {
        $is_email = strpos( $wp_user_id_or_email, '@' ) !== false;

        if ($is_email) {
            $email = $wp_user_id_or_email;
        }
        else
        {
            $wp_user_id = $wp_user_id_or_email;

            $user =  ncore_getUserById( $wp_user_id );
            if (!$user) {
                throw new Exception( "Wordpres user #$wp_user_id not found" );
            }

            $email = $user->user_email;
        }

        $plugin = $this->loadPlugin( $autoresponderConfig );

        if (!$plugin) {
            throw new Exception( "Invalid autoresponder configuration." );
        }

        if (!$plugin->isActionSupportAvailable()) {
            throw new Exception( "The selected plugin cannot handle tags." );
        }

        return $plugin->setTags( $email, $add_tag_ids_comma_seperated, $remove_tag_ids_comma_seperated );
    }

    public function createTag( $autoresponderConfig, $new_tag_name  )
    {
        try {
            $plugin = $this->loadPlugin($autoresponderConfig);
        } catch (Exception $e) {
            return false;
        }

        if (!$plugin)
        {
            return false;
        }

        if (!$plugin->isActionSupportAvailable()) {
            return false;
        }

        $tags = $plugin->getTagOptions();

        $tag_id = $tags
                ? array_search( $new_tag_name, $tags )
                : false;
        if ($tag_id!==false)
        {
            return $tag_id;
        }

        return $plugin->createTag( $new_tag_name );
    }

    /**
     * @param stdClass $autoresponderConfig
     * @return mixed
     * @throws Exception
     */
    public function isActive($autoresponderConfig )
    {
        $plugin = $this->loadPlugin( $autoresponderConfig );

        if (!$plugin)
        {
            throw new Exception( 'Invalid $autoresponderConfig' );
        }

        return $plugin->isEnabled();
    }

    /**
     * @param stdClass $autoresponderConfig
     * @return string
     * @throws Exception
     */
    public function label($autoresponderConfig )
    {
        $plugin = $this->loadPlugin( $autoresponderConfig );

        if (!$plugin)
        {
            throw new Exception( 'Invalid $autoresponderConfig' );
        }

        $prefix = $autoresponderConfig->id
                ? $autoresponderConfig->id . ' - '
                : '';

        $label = $autoresponderConfig->name
               ? $autoresponderConfig->name
               : $plugin->label();

        return $prefix . $label;
    }

    /**
     * @param string $autoresponder_id
     * @return bool|mixed
     * @throws Exception
     */
    public function plugin($autoresponder_id )
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model( 'data/autoresponder' );

        $autoresponderConfig = $model->get( $autoresponder_id );

        if (!$autoresponderConfig) {
            throw new Exception( _ncore( 'Invalid autoresponder id: %s', $autoresponder_id ) );
        }

        $plugin = $this->loadPlugin( $autoresponderConfig );

        return $plugin;
    }

    /**
     * @param        $autoresponder_id
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param int    $product_id
     * @param string $order_id
     * @return string
     */
    public function subscribe( $autoresponder_id, $email, $first_name='', $last_name='', $product_id=0, $order_id='' )
    {
        try
        {
            /** @var ncore_AutoresponderData $model */
            $model = $this->api->load->model( 'data/autoresponder' );

            $autoresponderConfig = $model->get( $autoresponder_id );

            $plugin = $this->loadPlugin( $autoresponderConfig );

            if (!$plugin)
            {
                throw new Exception( 'Invalid $autoresponder_id' );
            }

            /** @var ncore_RuleValidatorLib $lib */
            $lib = $this->api->load->library( 'rule_validator' );
            $is_valid_email = $lib->validate( 'dummylabel', $email, 'email' ) == '';
            if (!$is_valid_email)
            {
                throw new Exception( _ncore( 'The email address %s is invalid.', $email ) );
            }

            $is_active = $plugin->isEnabled();
            if (!$is_active)
            {
                throw new Exception( _ncore( 'The autoresponder connection #%s is not active.', $autoresponder_id ) );
            }

            $plugin->subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin=true );

            return '';

        }

        catch (Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function subscribeForOrder( $orderData )
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model( 'data/autoresponder' );

        $product_id = ncore_retrieve( $orderData, 'product_id' );

        $autoresponders = $model->getForProduct( $product_id );

        $success = true;

        foreach ($autoresponders as $one)
        {
            try {
                $is_active = $this->isActive($one);
            } catch (Exception $e) {
                continue;
            }
            if ($is_active)
            {
                $error_message = '';

                $old_display = ini_set('display_errors','On');
                ob_start();
                try
                {
                   $this->_subscribe( $one, $orderData );
                }
                catch (Exception $e)
                {
                    $error_message = $e->getMessage();
                }

                $output = ob_get_clean();
                if ($old_display !== false)
                {
                    ini_set('display_errors', $old_display );
                }

                if ($output)
                {
                    $error_message .= $output;
                }

                if ($error_message)
                {
                    try {
                        $plugin = $this->loadPlugin($one);
                    } catch (Exception $e) {
                        $plugin = null;
                    }

                    $email = ncore_retrieve( $orderData, 'email' );
                    $msg = _ncore( 'Subscription of %s to %s failed: %s',
                                    $email,
                                    $plugin ? $plugin->label() : '',
                                    $error_message );

                    $this->api->logError( 'ipn', $msg );

                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * @param stdClass $autoresponder
     * @param stdClass $orderData
     * @throws Exception
     */
    public function testSubscribe( $autoresponder, $orderData )
    {
        $this->loadPlugin( $autoresponder, $force_reload=true );
        $success = $this->_subscribe( $autoresponder, $orderData );
        return $success;
    }

    public function getProviders()
    {
        $all_plugins = [
            'klicktipp_api' => _digi3('KlickTipp (full integration / requires KlickTipp Premium)'),
            'klicktipp_via_mail' => _digi3('KlickTipp (email integration / for all KlickTipp versions)'),
            'quentn' => 'Quentn',
            'active_campaign' => 'ActiveCampaign',
            'mailchimp' => 'MailChimp',
            'aweber' => 'AWeber',
            //'flatratenewsletter' => 'FlateRateNewsletter',
            //'getresponse' => _digi3('GetResponse (v1.5.0 JSON-RPC / depricated. Will be disabled on 10-01-2021)'), //removed with DM-168
            'getresponse_rest' => _digi3('GetResponse (v3 REST / recommended for new configurations)'),
            'mailjet' => 'MailJet',
            'zapier' => 'Zapier',
            'generic' => _ncore('generic'),
        ];
        if (dm_api()->edition() == 'US') {
            $all_plugins = array_merge([
                'maropost' => 'Maropost',
            ], $all_plugins);
        } else {
            $all_plugins = array_merge($all_plugins, [
                'cleverreach' => _digi3('CleverReach (SOAP Api / deprecated. For existing configurations only)'),
                'cleverreach_rest' => _digi3('CleverReach (REST Api / recommended for new configurations)'),
                'leadmotor' => 'LeadMotor',
            ]);
        }

        return $all_plugins;
    }

    public function engineInputMetas( $engine, $id=false )
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model( 'data/autoresponder' );

        $meta = $id > 0
              ? $model->getCached( $id )
              : false;

        if (!$meta)
        {
            $meta = new StdClass();
            $meta->id = 0;
        }

        $meta->engine = $engine;

        try {
            $plugin = $this->loadPlugin($meta);
        } catch (Exception $e) {
            return [];
        }

        if (!$plugin->isActive())
        {
            return array( $this->inactiveMeta( $plugin ) );
        }

        $metas = array();

        $instructions = $plugin->instructions();
        if ($instructions)
        {
            $have_numbers = $plugin->haveInstructionNumbers();
            $html = $have_numbers
                    ? '<ol class="ncore_instructions"><li>' . implode( '</li><li>', $instructions ) . '</li></ol>'
                    : '<p class="ncore_instructions">' . implode( '</p><p>', $instructions ) . '</p>';
            $metas[] = array(
                'name' => 'instructions',
                'type' => 'html',
                'label' => _ncore('Instructions'),
                'html' => $html,
            );
        }

        $metas = array_merge( $metas, $plugin->getFormMetas() );

        $local_names = array();

        foreach ($metas as $index => $one)
        {
            $name = ncore_retrieve( $one, 'name', 'dummy' );
            $local_names[] = $name;
            $metas[$index]['name'] = $engine . '_' . $name;
        }

        foreach ($metas as $index => $one)
        {
            $depends_on = ncore_retrieve( $one, 'depends_on', array() );
            foreach ($depends_on as $name => $value)
            {
                $is_local_name = in_array( $name, $local_names );
                if (!$is_local_name)
                {
                    continue;
                }

                unset( $metas[$index]['depends_on'][$name] );

                $metas[$index]['depends_on'][$engine . '_' . $name] = $value;
            }
        }

        return $metas;
    }

    public function renderAutojoinTypeList( $concat='and', $tag='strong' )
    {
        $types = $this->autojoinAutoresponderTypes();

        return ncore_renderHtmlList( $types,  $concat, $prefix="<$tag>", $suffix="</$tag>" );
    }

    public function renderActionSupportTypeList( $concat='and', $tag='strong' )
    {
        $types = $this->actionsupportAutoresponderTypes();

        return ncore_renderHtmlList( $types,  $concat, $prefix="<$tag>", $suffix="</$tag>" );
    }

    public function autojoinAutoresponderTypes()
    {
        $options = array();

        $all = $this->getProviders();
        foreach ($all as $engine => $label)
        {
             $meta = new StdClass();
             $meta->id = 0;
             $meta->engine = $engine;

            try {
                $plugin = $this->loadPlugin($meta);
                $can_auto_join = $plugin->isAutoJoinAvailable();
            } catch (Exception $e) {
                $can_auto_join = false;
            }

             if ($can_auto_join) {
                 $options[ $engine ] = $label;
             }
        }

        return $options;
    }

    public function maybeUnsubscribeDeletedUser( $email )
    {
        $this->api->load->model( 'data/autoresponder' );

        $where = array(
            'is_active' => 'Y',
            'is_user_opted_out_if_deleted' => 'Y',
        );

        $all = $this->api->autoresponder_data->getAll( $where );
        foreach ($all as $engine => $one)
        {
            try
            {
                $plugin = $this->loadPlugin( $one );

                if ($plugin->hasUnsubscribe())
                {
                    $plugin->unsubscribe( $email );
                }
            }
            catch (Exception $e)
            {
            }
        }
    }

    public function getPersonalData( $email )
    {
        $this->api->load->model( 'data/autoresponder' );

        $where = array(
            'is_active' => 'Y',
            'is_personal_ar_data_exported' => 'Y',
        );

        $all = $this->api->autoresponder_data->getAll( $where );

        $data = array();

        foreach ($all as $engine => $one)
        {
            try
            {
                $plugin = $this->loadPlugin( $one );

                if ($plugin->hasUnsubscribe())
                {
                    $one_data = $plugin->getPersonalData( $email );
                    if ($one_data)
                    {
                        $hl = $plugin->label();
                        $no = 1;

                        while (true) {
                            $key = $hl . ($no==1?'':" $no");
                            if (!isset( $data[$key]))
                            {
                                break;
                            }
                            $no++;
                        }

                        if (!empty($key)) {
                            $data[ $key ] = $one_data;
                        }
                    }
                }
            }
            catch (Exception $e)
            {
            }
        }

        return $data;
    }

    public function unsubscribeAutoresponderTypes()
    {
        $types = array();

        $all = $this->getProviders();
        foreach ($all as $engine => $label)
        {
             $meta = new StdClass();
             $meta->id = 0;
             $meta->engine = $engine;

            try {
                $plugin = $this->loadPlugin($meta);
                $can_unsubscribe = $plugin->hasUnsubscribe();
            } catch (Exception $e) {
                $can_unsubscribe = false;
            }

             if ($can_unsubscribe) {
                 $types[] = $engine;
             }
        }

        return $types;
    }



    public function actionsupportAutoresponderTypes()
    {
        $options = array();

        $all = $this->getProviders();
        foreach ($all as $engine => $label)
        {
             $meta = new StdClass();
             $meta->id = 0;
             $meta->engine = $engine;

            try {
                $plugin = $this->loadPlugin($meta);
                $can_auto_join = $plugin->isActionSupportAvailable();
            } catch (Exception $e) {
                $can_auto_join = false;
            }

             if ($can_auto_join) {
                 $options[ $engine ] = $label;
             }
        }

        return $options;
    }

    /**
     * @param string $autoresponder_id
     * @param string $product_ids_comma_seperated
     * @return array|mixed
     * @throws Exception
     */
    public function handleAutojoin( $autoresponder_id, $product_ids_comma_seperated)
    {
        if ($autoresponder_id === 'auto')
        {
            $plugin = false;
            $options = $this->autoresponderOptions();
            foreach ($options as $a_id => $label)
            {
                $p = $this->plugin( $a_id );
                if ($p  && $p->isAutoJoinAvailable()) {
                    $autoresponder_id = $a_id;
                    $plugin           = $p;
                    break;
                }
            }
        }
        else
        {
            $plugin = $this->plugin( $autoresponder_id );
        }

        if (!$plugin) {
            throw new Exception( _ncore( 'No active autoresponder connection found for autojoin.' ) );
        }

        if (!$plugin->isEnabled())
        {
            throw new Exception( _ncore( "%s is not active.", $plugin->renderOptionLabel()) );
        }

        $product_ids = $this->_explodeProductIds( $product_ids_comma_seperated );

        static $cache;

        $contact_data   = & $cache[ $autoresponder_id ][ 'concat_data' ];
        $given_products = & $cache[ $autoresponder_id ][ 'product_ids' ];

        if (empty($given_products)) {
            $given_products = array();
        }

        if (isset($contact_data)) {

            $product_ids_to_signup = array();

            if (is_array($product_ids))
            {
                foreach ($product_ids as $one)
                {
                    if (!in_array($one, $given_products)) {
                        $product_ids_to_signup[] = $one;
                    }
                }
            }

            if ($product_ids_to_signup)
            {
                list( $email, ) = $contact_data;

                /** @var digimember_PaymentHandlerLib $library */
                $library = $this->api->load->library( 'payment_handler' );
                $library->signUp( $email, $product_ids_to_signup, $address=array() );
            }

            return $contact_data;
        }


        if ($product_ids && $product_ids !== 'none')
        {
            $given_products = array_unique( array_merge( $given_products, $product_ids ) );
        }
       if (!$given_products)
        {
            throw new Exception( _ncore( 'No products selected.' ) );
        }



        $user_id    = 0;
        $username   = '';
        $password   = '';
        $loginkey   = '';
        $first_name = '';
        $last_name  = '';

        $contact_data = array( $user_id, $username, $password, $loginkey, $first_name, $last_name );

        try {

            list( $extern_contact_id, $email, $first_name, $last_name, $password, $loginkey ) = $plugin->retrieveAutojoinContactData();
            if (!$email) {
                throw new Exception( _ncore( 'Could not retrieve contact data from thank you page URL.' ) );
            }

            $user_id = ncore_getUserIdByEmail( $email );
            if (!$user_id) {
                $user_id = ncore_getUserIdByName( $email );
            }
            $did_user_exist_before = $user_id > 0;



            $address = array();
            $address['first_name'] = $first_name;
            $address['last_name']  = $last_name;

            /** @var digimember_PaymentHandlerLib $library */
            $library = $this->api->load->library( 'payment_handler' );
            $library->signUp( $email, $product_ids, $address );

            $user_id = ncore_getUserIdByEmail( $email );

            if ($did_user_exist_before) {
                $password = '';
                $contact_data = array( $user_id, $email, $password, $loginkey='', $first_name, $last_name );
                return $contact_data;
            }

            /** @var digimember_UserData $model */
            $model = $this->api->load->model( 'data/user' );
            $stored_password = $model->getPassword($user_id);
            if ($stored_password)
            {
                $password = $stored_password;
            }
            elseif ($password)
            {
                $password = $model->setPassword($user_id, $password, false);
            }


            /** @var digimember_LoginkeyData $model */
            $model = $this->api->load->model( 'data/loginkey' );
            $loginkey = $model->getForUser( $user_id, $loginkey );


            /** @var digimember_BlogConfigLogic $config */
            $config = $this->api->load->model( 'logic/blog_config' );
            $login_url = $config->loginUrl();

            // $wp_user = get_userdata($user_id);
            // $username = $wp_user->user_login;
            $username = $email;

            $plugin->setAutojoinLoginData( $extern_contact_id, $username, $password, $login_url, $loginkey );

        }
        catch (Exception $e)
        {
            throw new Exception( _ncore( "%s - %s", $plugin->renderOptionLabel(), $e->getMessage() ) );
        }

        $contact_data = array( $user_id, $username, $password, $loginkey, $first_name, $last_name );
        return $contact_data;

    }

    /**
     * @param string $autoresponder_id
     * @return array
     * @throws Exception
     */
    public function retrieveThankyouPageContactData( $autoresponder_id )
    {
        $plugin = $this->plugin( $autoresponder_id );

        if (!$plugin->isEnabled())
        {
            return array( $email='', $first_name='', $last_name='' );
        }

        try {

            /** @noinspection PhpUnusedLocalVariableInspection */
            list( $extern_contact_id, $email, $first_name, $last_name, $password, $loginkey ) = $plugin->retrieveAutojoinContactData();
            if (!$email) {
                return array( $email='', $first_name='', $last_name='' );
            }

            return array( $email, $first_name, $last_name );
        }

        catch (Exception $e)
        {
        }

        return array( $email='', $first_name='', $last_name='' );
    }

    public function autoresponderOptions( $null_entry_label=false, $engine='any' )
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model( 'data/autoresponder' );

        $where = array( 'is_active' => 'Y' );

        $must_search_for_engine = $engine && $engine != 'any';
        if ($must_search_for_engine) {
            $where[ 'engine' ] = $engine;
        }

        $all = $model->getAll( $where );

        $labels = array();
        $ids    = array();
        $sort   = array();

        foreach ($all as $one)
        {
            try {
                $plugin = $this->loadPlugin($one);
            } catch (Exception $e) {
                continue;
            }

            $label = $plugin->renderOptionLabel();

            $labels[] = $label;
            $ids[]    = $one->id;
            $sort[]   = strtolower( $label );
        }

        array_multisort( $sort, $ids, $labels );

        $options = array_combine( $ids, $labels );

        if ($null_entry_label)
        {
            $this->api->load->helper( 'html_input' );

            if (!is_string($null_entry_label))
            {
                $null_entry_label = _ncore('Select autoresponder' );
            }
            $null_entry = array( 0 => ncore_htmlSelectNullEntryLabel( $null_entry_label ) );

            $options = array_merge($null_entry, $options);
        }

        if ($options) {
            return $options;
        }

        return false;
    }


    public function autojoinAutoresponderOptions()
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model( 'data/autoresponder' );

        $where = array( 'is_active' => 'Y' );

        $all = $model->getAll( $where );

        $options = array();

        foreach ($all as $one)
        {
            try {
                $plugin = $this->loadPlugin($one);
            } catch (Exception $e) {
                continue;
            }

            if (!$plugin->isAutoJoinAvailable()) {
                continue;
            }

            $label = $plugin->renderOptionLabel();

            $options[ $one->id ] = $label;
        }

        if ($options) {
            return $options;
        }



        $autojoin_types = $this->renderActionSupportTypeList( 'or' );
        if (!$autojoin_types) {
            return array();
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $link = $model->adminMenuLink( 'newsletter' );

        return _digi3( 'Go to %s and set up an autosreponder of type %s.', $link, $autojoin_types );

    }

    public function actionSupportAutoresponderOptions( $allow_null=false )
    {
        /** @var ncore_AutoresponderData $model */
        $model = $this->api->load->model( 'data/autoresponder' );

        $where = array( 'is_active' => 'Y' );

        $all = $model->getAll( $where );

        $options = array();

        if ($allow_null) {
            $options[ 0 ] = _ncore( '(Please select ...)' );
        }

        foreach ($all as $one)
        {
            try {
                $plugin = $this->loadPlugin($one);
            } catch (Exception $e) {
                continue;
            }

            if (!$plugin->isActionSupportAvailable()) {
                continue;
            }

            $label = $plugin->renderOptionLabel();

            $options[ $one->id ] = $label;
        }

        if ($options) {
            return $options;
        }



        $autojoin_types = $this->renderAutojoinTypeList( 'or' );
        if (!$autojoin_types) {
            return array();
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $link = $model->adminMenuLink( 'newsletter' );

        return _digi3( 'Go to %s and set up an autosreponder of type %s.', $link, $autojoin_types );

    }


    private $loaded_plugins = array();

    /**
     * @param string $meta_obj_or_id
     * @param bool   $force_reload
     * @return bool|mixed
     * @throws Exception
     */
    private function loadPlugin( $meta_obj_or_id, $force_reload=false  )
    {
        if (empty($meta_obj_or_id))
        {
            return false;
        }
        elseif (is_numeric($meta_obj_or_id)) {
            $id = $meta_obj_or_id;
            /** @var ncore_AutoresponderData $model */
            $model = $this->api->load->model( 'data/autoresponder' );
            $meta = $model->get( $id );
        }
        else
        {
            $meta = $meta_obj_or_id;
        }

        $type = $meta->engine;
        $id   = $meta->id;

        $key = "$type-$id";
        $plugin =& $this->loaded_plugins[ $key ];

        if (!isset($plugin) || $force_reload)
        {
            $all_types = $this->getProviders();
            $is_valid = isset( $all_types[ $type ] );
            if (!$is_valid) {
                throw new Exception( "Autoresponder not available with type'$type' and id #$id" );
            }

            $class_name = $this->loadPluginClass( $type );

            if (empty($class_name) || !class_exists( $class_name ))
            {
                trigger_error( "Could not load class file for type '$type' / id #$id" );
                return false;
            }

            $plugin = new $class_name( $this, $meta );
        }

        return $plugin;
    }

    /**
     * @param digimember_AutoresponderHandler_PluginBase $plugin
     * @return array
     */
    private function inactiveMeta( $plugin )
    {
        $msg = $plugin->inactiveMsg();

        return array(
            'name' => 'error',
            'type' => 'html',
            'label' => '<strong>'._digi3( 'Important note!' ).'</strong>',
            'html' => "<strong>$msg</strong>",
        );
    }

    /**
     * @param stdClass $autoresponderConfig
     * @param stdClass $orderData
     * @throws Exception
     */
    private function _subscribe( $autoresponderConfig, $orderData )
    {
        $first_name = $orderData->first_name;
        $last_name  = $orderData->last_name;
        $email      = $orderData->email;
        $product_id = ncore_retrieve( $orderData, 'product_id', 0 );
        $order_id   = ncore_retrieve( $orderData, 'order_id', '' );
        $force_double_optin = ncore_retrieve( $orderData, 'force_double_optin' ) == 'Y';

        $plugin = $this->loadPlugin( $autoresponderConfig );

        if (!$plugin)
        {
            throw new Exception( 'Invalid $autoresponderConfig' );
        }

        if ($plugin->isEnabled())
        {
            $custom_data = $plugin->fillCustomFields( $orderData );
            $plugin->subscribe( $email, $first_name, $last_name, $product_id, $order_id, $force_double_optin, $custom_data );

            $message_templ = _ncore( 'Subscribed %s to %s.' );
            $this->api->log( 'ipn', $message_templ, $email, $plugin->label() );

            $user_id = ncore_getUserIdByEmail($email);
            apply_filters('digimember_ipn_push_arcf_links', $user_id);
        }
    }

    private function _explodeProductIds( $product_ids_comma_seperated )
    {
        $product_ids_raw = explode( ',', $product_ids_comma_seperated );
        $product_ids = array();
        foreach ($product_ids_raw as $product_id)
        {
            $product_id = ncore_washInt( $product_id );
            if ($product_id>0 && !in_array( $product_id, $product_ids )) {
                $product_ids[] = $product_id;
            }
        }
        if (!$product_ids) {
            $product_ids = 'none';
        }

        return $product_ids;
    }
}