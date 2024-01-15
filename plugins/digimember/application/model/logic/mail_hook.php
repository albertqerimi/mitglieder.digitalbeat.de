<?php

class digimember_MailHookLogic extends ncore_MailHookLogic
{
    public function sendPolicyOptions($hook)
    {
        switch ($hook)
        {
            case DIGIMEMBER_MAIL_HOOK_WELCOME:
                return array(
                    'allways'         => _digi( '... always - for every order' ),
                    'if_first_ever'   => _digi( '... only if this is the first order EVER for the given email address' ),
                    'if_first_today'  => _digi( '... only if this is the first order within 24 hours for the given email address' ),
                    'never'           => _digi( '... never - do not send this email at all' ),
                );
            case DIGIMEMBER_MAIL_HOOK_DOWNLOAD:
                return array(
                    'allways'         => _digi( '... always - for every order' ),
                    'never'           => _digi( '... never - do not send this email at all' ),
                );
            default:
                return array();
        }
    }

    public function demoParams( $meta )
    {
        $params = parent::demoParams( $meta );

        $context    = ncore_retrieve( $meta, 'context' );
        $product_id = ncore_retrieve( $meta, 'product_id' );
        $email      = ncore_retrieve( $meta, 'test_email'  );

        switch ($context)
        {
            case 'membership_product':
                $model = $this->api->load->model( 'data/product' );
                $product = $model->get( $product_id );
                if ($product) {
                    $params[ 'product_id' ]   = $product->id;
                    $params[ 'product_name' ] = $product->name;
                }
            break;
            case 'download_product':
                $model = $this->api->load->model( 'data/download' );
                $id = $model->createTestUrl( $email, $product_id );
                $entry = $model->get( $id );

                $params[ 'url' ] = $model->downloadPageUrl( $entry );

                $model = $this->api->load->model( 'data/product' );
                $product = $model->get( $product_id );
                if ($product) {
                    $params[ 'product_id' ]   = $product->id;
                    $params[ 'product_name' ] = $product->name;
                }
            break;
            case 'cancel_mail':

            break;
            case 'cancel_confirmation_mail':

            break;
        }

        return $params;
    }


    public function membershipProductHookMetas()
    {
        $this->api->load->helper( 'array' );
        $all = $this->hookMeta( 'all' );
        return ncore_elementsWithKey( $all, 'context', 'membership_product', $keep_keys=true );
    }

    public function downloadProductHookMetas()
    {
        $this->api->load->helper( 'array' );
        $all = $this->hookMeta( 'all' );
        return ncore_elementsWithKey( $all, 'context', 'download_product', $keep_keys=true );
    }

    public function existingPasswordLabel()
    {
        return _dgyou( 'You already have a password (check our other emails)' );
    }

    protected function checkSendPolicy( $mail_text, $recipient, $ref_id, $params ) {

        $is_download_mail = $mail_text->hook == DIGIMEMBER_MAIL_HOOK_DOWNLOAD;
        if ($is_download_mail)
        {
            return $mail_text->send_policy != 'never';
        }

        $is_welcome_mail = $mail_text->hook == DIGIMEMBER_MAIL_HOOK_WELCOME;
        if (!$is_welcome_mail) {
            return true;
        }

        switch ($mail_text->send_policy)
        {
            case 'if_first_ever':
                $check_time_span = false;
                break;
            case 'if_first_today':
                $check_time_span = 86400;
                break;

            case 'never':
                return false;

            case 'allways':
            default:
                return true;
        }

        $user_id = email_exists( $recipient );
        if (!$user_id) {
            return true;
        }

        $model = $this->api->load->model( 'data/user_product' );
        $where = array();
        $where[ 'user_id' ]     = $user_id;
        $where[ 'is_active' ]   = 'Y';

        $all = $model->getAll( $where );
        if (is_array($all) && count($all) > 1) {
            if (!$check_time_span) {
                return false;
            }
            else {
                $hasOrderToday = false;
                $limit_unix = time() - $check_time_span;
                foreach ($all as $one) {
                    $created_unix = strtotime( $one->created );
                    if ($created_unix > $limit_unix && $created_unix < time() - 60) {
                        $hasOrderToday = true;
                    }
                }
                if ($hasOrderToday) {
                    return false;
                }
            }
        }
        return true;
    }


    protected function metas()
    {
        $metas = parent::metas();

        $password_hint = $this->existingPasswordLabel();

        $placeholders = array(
                    'username'  => _digi('Login name of the user. Usually his email address.'),
                    'firstname' => _digi('The first name of the user.'),
                    'lastname'  => _digi('The last name of the user.'),
                    'password' => _digi('Either the user\'s password (if a new account is created) or the text \'%s\' (if the user already has an account)', $password_hint),
                    'product_name' => _digi('Name of the product'),
                    'product_id' => _digi('Id of the product'),
                    'product_url' => _digi('URL of the product'),
                    'url'      => _digi('URL to the web site'),
                    'loginurl' => _digi('URL to login into the web site (as given in the %s settings)', $this->api->pluginDisplayName()),
        );

        $placeholders = apply_filters( 'digimember_welcome_mail_placeholder_description', $placeholders );


        $metas[ DIGIMEMBER_MAIL_HOOK_WELCOME ] =  array(
                'context' => 'membership_product',
                'label' => _digi('Welcome Email'),
                'description' => _digi('The welcome email is sent every time a user buys this membership product.'),
                'placeholder' => $placeholders,
                'demo_values' => array(
                    'username' => 'demo-user',
                    'password' => 'wvKrnMWu',
                    'firstname' => 'Michael',
                    'lastname' => 'Meier',
                ),
            );

        $placeholders = array(
                    'firstname' => _digi('The first name of the user.'),
                    'lastname'  => _digi('The last name of the user.'),
                    'product_name' => _digi('Name of the product'),
                    'product_id' => _digi('Id of the product'),
                    'url'      => _digi('URL to the download page'),
        );

        $metas[ DIGIMEMBER_MAIL_HOOK_DOWNLOAD ] = array(
                'context' => 'download_product',
                'label' => _digi('Download Email'),
                'description' => _digi('The download email is sent every time a user buys this download product.'),
                'placeholder' => $placeholders,
                'demo_values' => array(
                    'firstname' => 'Michael',
                    'lastname' => 'Meier',
                ),
            );

        $placeholders = array(
            'firstname' => _digi('The first name of the user'),
            'lastname'  => _digi('The last name of the user'),
            'customer_email'      => _digi('Customers e-mail'),
            'order_id'      => _digi('Order id'),
            'typereason' => _digi( 'Type of cancellation/Reason for cancellation' ),
            'cancellationdate' => _digi( 'Date of cancellation' ),
            'url' => _digi('Website url'),
            'admin_cancel_email' => _digi('Admin email for cancellation'),
        );

        $metas[ DIGIMEMBER_MAIL_HOOK_CANCEL_CONFIRMATION ] = array(
            'context' => 'cancel',
            'label' => _digi('Confirmation email on entry'),
            'description' => _digi('This e-mail will be sent to the user after they entered their data.'),
            'placeholder' => $placeholders,
            'demo_values' => array(
                'firstname' => 'Michael',
                'lastname' => 'Meier',
                'customer_email' => 'testmail@test.de',
                'typereason' => _digi('timely termination'),
                'cancellationdate' => _digi( 'As fast as possible' ),
                'order_id' => 'order1234',
                'url' => 'DigiMember',
                'admin_cancel_email' => 'admin@test.de',
            ),
        );

        $placeholders = array(
            'firstname' => _digi('The first name of the user'),
            'lastname'  => _digi('The last name of the user'),
            'customer_email'      => _digi('Customers e-mail'),
            'order_id'      => _digi('Order id'),
            'typereason' => _digi( 'Type of cancellation/Reason for cancellation' ),
            'cancellationdate' => _digi( 'Date of cancellation' ),
            'url' => _digi('Website url'),
            'admin_cancel_email' => _digi('Admin email for cancellation'),
        );

        $metas[ DIGIMEMBER_MAIL_HOOK_CANCELMAIL ] = array(
            'context' => 'cancel',
            'label' => _digi('Admin email on entry'),
            'description' => _digi('This e-mail will be sent to the admin email you configure in the shortcode, if a user enters their data.'),
            'placeholder' => $placeholders,
            'demo_values' => array(
                'firstname' => 'Michael',
                'lastname' => 'Meier',
                'customer_email' => 'testmail@test.de',
                'typereason' => _digi('timely termination'),
                'cancellationdate' => _digi( 'As fast as possible' ),
                'order_id' => 'order1234',
                'url' => 'DigiMember',
                'admin_cancel_email' => 'admin@test.de',
            ),
        );

        return $metas;
    }

    protected function defaultParams()
    {
        $params = parent::defaultParams();

        $config = $this->api->load->model( 'logic/blog_config' );
        $params['loginurl'] = $config->loginUrl();

        return $params;
    }


    public function defaultMailText( $hook )
    {
        switch ($hook)
        {
            case DIGIMEMBER_MAIL_HOOK_DOWNLOAD:
{
                $subject = _digi('Your download link for %s', '%%product_name%%');

                $hey_there = _digi('Hey there!');
                $thanks_for_purchasing_the_product = _digi( 'Thank you for purchasing %s!' , '%%product_name%%' );
                $to_acces_the_product_visit_the_url = _digi( 'To access your product, please visit this URL:' );

                $to_acces_the_product_visit_the_url .= '<p><a href="%%url%%">%%url%%</a></p>';

                $greetings = _digi( 'Enjoy %s and best regards!', '%%product_name%%' );

                $message = "

    <p>
        $hey_there
    </p>
    <p>
        $thanks_for_purchasing_the_product
    </p>
    <p>
        $to_acces_the_product_visit_the_url
    </p>
    <p>
        $greetings
    </p>

    ";
                return array( $subject, $message );
            }

            case DIGIMEMBER_MAIL_HOOK_WELCOME:
            {
                $subject = _digi('Welcome to %s', '%%product_name%%');

                $hey_there = _digi('Hey there!');
                $thanks_for_purchasing_the_product = _digi( 'Thank you for purchasing %s!' , '%%product_name%%' );
                $to_acces_the_product_visit_the_url = _digi( 'To access your product, please visit %s and log in.' );

                $to_acces_the_product_visit_the_url = sprintf( $to_acces_the_product_visit_the_url, '<a href="%%loginurl%%">%%loginurl%%</a>' );

                $your_username = _digi( 'Your user name:' );
                $your_password = _digi( 'Your password:' );

                $greetings = _digi( 'Enjoy %s and best regards!', '%%product_name%%' );

                $message = "

    <p>
        $hey_there
    </p>
    <p>
        $thanks_for_purchasing_the_product
    </p>
    <p>
        $to_acces_the_product_visit_the_url
    </p>
    <table><tbody>
    <tr>
         <td>$your_username</td>
         <td>%%username%%</td>
    </tr>
    <tr>
         <td>$your_password</td>
         <td>%%password%%</td>
    </tr>
    </tbody></table>
    <p>
        $greetings
    </p>

    ";
                return array( $subject, $message );
            }

        }

        return parent::defaultMailText( $hook );

    }


}
