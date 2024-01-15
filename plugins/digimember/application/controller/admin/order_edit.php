<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminOrderEditController extends ncore_AdminFormController
{
    protected function pageHeadline()
    {
        $id = $this->getElementId();
        $have_id = is_numeric($id) && $id > 0;
        return $have_id
               ? _digi('Edit order')
               : _digi('Add new order');
    }

    protected function inputMetas()
    {
        $api = $this->api;
        /** @var digimember_ProductData $product_model */
        $product_model = $api->load->model( 'data/product' );
        /** @var digimember_UserProductData $userpro_model */
        $userpro_model = $api->load->model( 'data/user_product' );
        /** @var digimember_DownloadData $downlod_model */
        $downlod_model = $api->load->model( 'data/download' );

        $product_options = $product_model->options( $product_type='membership', $public_only=true);

        $id = $this->getElementId();

        $have_id = is_numeric( $id ) && $id > 0;

        $user_product = $userpro_model->get( $id );

        $userpro_model->setNewOrderId( _digi( 'by admin' ) );
        $downlod_model->setNewOrderId( _digi( 'by admin' ) );

        $meta = array();

        $meta[] = array(
            'name' => 'email',
            'section' => 'order',
            'type' => 'email',
            'label' => _digi('Email'),
            'rules' => $have_id
                       ? 'readonly'
                       : 'required|email',
            'element_id' => $id,
            'show_wp_user' => true,
        );
        $meta[] = array(
            'name' => 'first_name',
            'section' => 'order',
            'type' => 'text',
            'label' => _digi('First name'),
            'tooltip' => _digi('For new user accounts enter a first name and a last name.' ),
            'element_id' => $id,
            'rules' => $have_id
                       ? 'readonly'
                       : '',
        );
        $meta[] = array(
            'name' => 'last_name',
            'section' => 'order',
            'type' => 'text',
            'label' => _digi('Last name'),
            'tooltip' => _digi('For new user accounts enter a first name and a last name.' ),
            'element_id' => $id,
            'rules' => $have_id
                       ? 'readonly'
                       : '',
        );


        $meta[] =
            array(
                'name' => 'is_active',
                'section' => 'order',
                'type' => 'yes_no_bit',
                'label' => _digi('Active'),
                'element_id' => $id,
                'rules' => ($user_product && $user_product->is_access_too_late
                         ? 'readonly'
                         : ''),
                'hint' => ($user_product && $user_product->is_access_too_late
                           ? _digi( 'The order cannot be activated, because it has been replaced by an upgrade or package change order.' )
                           : ''),
            );

        $meta[] =
            array(
                'name' => 'order_id',
                'section' => 'order',
                'type' => 'text',
                'label' => _digi('Order id'),
                'rules' => 'required',
                'element_id' => $id,
            );

        $meta[] =
            array(
                'section' => 'order',
                'name' => 'product_id',
                'type' => 'select',
                'label' => _digi('Product' ),
                'options' => $product_options,
                'element_id' => $id,
                'hint' => _digi( 'Here you may create orders for published membership products only.' ),
                'no_options_text' => _digi( 'No products available' ),
            );

        $meta[] =
            array(
                'section' => 'order',
                'name' => 'quantity',
                'type' => 'int',
                'label' => _digi('Quantity' ),
                'hide' => !ncore_hasProductQuantities(),
                'element_id' => $id,
            );


        $meta[] =
            array(
                'section' => 'order',
                'name' => 'order_date',
                'type' => 'date',
                'label' => _ncore('Order date'),
                'element_id' => $id,
                'past_dates_only' => true,
                'with_time' => true,
            );

        $meta[] =
            array(
                'section' => 'order',
                'name' => 'last_pay_date',
                'type' => 'date',
                'label' => _digi('Last payment was on'),
                'element_id' => $id,
                'past_dates_only' => true,
                'with_time' => false,
                'hint' => _digi( 'May not be earlier than %s.', '<i>'._ncore('Order date').'</i>' ),
            );

        return $meta;
    }

    protected function buttonMetas()
    {
        $metas = parent::buttonMetas();

        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');
        $link = $linkLogic->adminPage( 'orders' );

        $metas[] = array(
                'type' => 'link',
                'label' => _ncore('Back'),
                'url' => $link,
                );

        return $metas;
    }

    protected function pageInstructions()
    {
        $id = $this->getElementId();

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            /** @var digimember_UserProductData $model */
            $model = $this->api->load->model( 'data/user_product' );
            $user_product = $model->get( $id );

            if (!$user_product)
            {
                return array();
            }

            $user_id = $user_product->user_id;

            $edit_user_url = get_edit_user_link( $user_id );

            $linkModel = $this->api->load->model('logic/link');
            $customfields_link = $linkModel->adminMenuLink('customfields');

            return array(
                _digi( 'On this page you are currently editing an order.' ),
                ncore_linkReplace( _digi( '<a>Click here</a> to edit the users profile.' ), $edit_user_url ),
                _digi('You can see the %s for the user there as well.',$customfields_link),
            );
        }

        $users_admin_url = 'users.php';

        return array(
            _digi( 'On this page you can add new orders.' ),
            _digi( 'If there is already a registered user for the entered email address, the order will be assigned to this user.' ),
            _digi( 'If there is no user for the email address, a new user account will be created.' ),
            ncore_linkReplace( _digi( '<a>Click here</a> to create user accounts without creating orders.' ), $users_admin_url ),
        );

    }

    protected function sectionMetas()
    {
        return array(
            'general' =>  array(
                            'headline' => _ncore('Settings'),
                            'instructions' => '',
            )
        );
    }

    protected function editedElementIds()
    {
        $id = $this->getElementId();

        return array( $id );
    }


    protected function getData( $id )
    {
        /** @var digimember_UserProductData $user_product_model */
        $user_product_model = $this->api->load->model( 'data/user_product' );

        $have_id = is_numeric( $id ) && $id > 0;

        if ($have_id)
        {
            $order_data = $user_product_model->get( $id );

            $user_id = ncore_retrieve( $order_data, 'user_id' );

            $user = ncore_getUserById( $user_id );

            $order_data->email      = ncore_retrieve( $user, 'user_email', _digi( 'User deleted!' ) );
            $order_data->last_name  = get_user_meta( $user_id, 'last_name',  $single=true );
            $order_data->first_name = get_user_meta( $user_id, 'first_name', $single=true );

            return $order_data;

        }
        else
        {
            $user = $user_product_model->emptyObject();

            $user->email = $this->stored_email;
            return $user;
        }
    }

    protected function setData( $id, $data )
    {
        /** @var digimember_UserProductData $user_product_model */
        $user_product_model = $this->api->load->model( 'data/user_product' );

        $have_id = is_numeric( $id ) && $id > 0;

        $email         = ncore_retrieve( $data, 'email' );
        $last_name     = ncore_retrieve( $data, 'last_name' );
        $first_name    = ncore_retrieve( $data, 'first_name' );
        $product_id    = ncore_retrieve( $data, 'product_id' );
        $order_id      = ncore_retrieve( $data, 'order_id' );
        $quantity      = ncore_retrieve( $data, 'quantity', 1 );
        $order_date    = ncore_retrieve( $data, 'order_date' );
        $last_pay_date = ncore_retrieve( $data, 'last_pay_date' );
        $is_active     = ncore_retrieve( $data, 'is_active', 'Y' );

        $msgs     = array();
        $modified = false;
        $password = '';

        $user_id = ncore_getUserIdByEmail( $email );

        if ($user_id)
        {
            if (is_multisite())
            {
                $wp_user_object = new WP_User($user_id);

                $roles = $wp_user_object->roles;

                $user_existed_before        = (bool) $roles;
                $user_existed_in_other_blog = true;
            }
            else
            {
                $user_existed_before        = true;
                $user_existed_in_other_blog = false;
            }
        }
        else
        {
            $user_existed_before        = false;
            $user_existed_in_other_blog = false;
        }

        if ($have_id)
        {

        }
        else
        {
            /** @var digimember_PaymentHandlerLib $lib */
            $lib = $this->api->load->library( 'payment_handler' );
            try {
                list( $login, $password, $type, $id ) = $lib->manuallyCreateSale( $email, $first_name, $last_name, $order_id, $product_id );
                $modified = true;
            }
            catch(Exception $e) {
                $this->formError($e->getMessage());
                return false;
            }
        }

        if ($id)
        {
            /** @var digimember_ProductData $productData */
            $productData = $this->api->load->model( 'data/product' );
            $product = $productData->get( $product_id );
            $type = $product ? $product->type : 'error';
        }
        else
        {
            $type = 'error';
        }

        switch ($type)
        {
            case 'download':
            {
                /** @var digimember_DownloadData $model */
                $model = $this->api->load->model( 'data/download' );
                $entry = $model->get( $id );

                $url = $model->downloadPageUrl( $entry );
                // $model = $this->api->load->model( 'logic/link' );
                // $url = $model->thankyouPage( $entry );


                $link = ncore_htmlLink( $url, $url, array( 'as_popup' => true ) );

                $msgs[] = _digi( 'Created new download: %s', $link );
                $modified = true;
            }
            break;
            case 'membership':
            {
                $data = array(
                    'order_date'    => $order_date,
                    'last_pay_date' => $last_pay_date,
                    'order_id'      => $order_id,
                    'is_active'     => $is_active,
                    'product_id'    => $product_id,
                    'quantity'      => $quantity,
                );
                $updated = $user_product_model->update( $id, $data);
                if ($updated)
                {
                    $modified = true;
                }
            }
            break;
        }

        if ($password && !$user_existed_before)
        {
            $msgs[] = _digi( 'Created a new account for %s with password %s .', $email, $password );
        }
        elseif ($user_existed_in_other_blog && !$user_existed_before)
        {
            $msgs[] = _digi( 'The user %s will receive his password via email.', $email );
        }
        elseif ($password && $user_existed_before)
        {
            $msgs[] = _digi( 'For email %s an account already existed with password %s .', $email, $password );
        }

        else
        {
            $msgs[] = _digi( 'An account for the user %s already exists. Email address, name and password of existing users will <u>not</u> be modified.', $email );
        }

        if ($have_id)
        {
            $msgs[] = _digi( 'Changes to order saved.' );
        }
        else
        {
            $modified = true;
            $msgs[] = _digi( 'Created new order for user %s.', $email );
        }


        $this->formSuccessMessage( implode( ' ', $msgs));

        $user_id = ncore_getUserIdByEmail( $email );
        $is_admin = ncore_canAdmin($user_id);
        if ($is_admin)
        {
            $msg = _digi( 'The email address %s belongs to an admin account. For testing purposes, please use a NON admin email address, because for admins wordpress looks and behaves different than for regular users.', "<em>$email</em>" );
            $this->formError( $msg );
        }



        $this->stored_email = $email;

        // $this->setElementId( $id );

        return $modified;
    }

    protected function formActionUrl()
    {
        $this->api->load->helper( 'url' );

        $action_url = parent::formActionUrl();

        $id =  $this->getElementId();

        if ($id)
        {

            $args = array( 'id' => $id );

            return ncore_addArgs( $action_url, $args );
        }
        else
        {
            return $action_url;
        }
    }

    private $stored_email='';
}