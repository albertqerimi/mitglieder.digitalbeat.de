<?php

class ncore_MailHookLogic extends ncore_BaseLogic
{
    public function sendMail( $recipient, $hook, $ref_id=0, $params=array(), $force_sending=false )
    {
        $meta = $this->hookMeta( $hook );
        if (!$meta)
        {
            trigger_error( "Invalid hook: '$hook'" );
            return;
        }

        $params = array_merge( $this->defaultParams(), $params );

        $api = $this->api;
        $model = $api->load->model( 'data/mail_text' );
        $renderer = $api->load->library( 'mail_renderer' );

        $mail_text = $model->getForHook( $hook, $ref_id );

        $do_send = $force_sending || $this->checkSendPolicy( $mail_text, $recipient, $ref_id, $params );
        if (!$do_send) {
            return '';
        }

        list( $subject, $body_html ) = $renderer->renderMail( $mail_text, $params );

        $have_mail = trim( $body_html ) != '';
        if (!$have_mail)
        {
            return;
        }

        $mailer = $this->api->load->library( 'mailer' );

        $mailer->to( $recipient );
        if (isset($params['cc'])) {
            $ccEmails = explode(";", $params['cc']);
            foreach ($ccEmails as $ccEmail) {
                $mailer->cc($ccEmail);
            }
        }
        $mailer->subject( $subject );
        $mailer->html( $body_html );
        if (isset($mail_text->attachment) && is_numeric(trim($mail_text->attachment))) {
            if ($attachment = get_attached_file($mail_text->attachment)) {
                $mailer->attachments(array($attachment));
            }
        }


        try
        {
            $success = $mailer->send();

            $error_msg = $mailer->lastMailError();
        }

        catch (Exception $e)
        {
            $error_msg = _ncore('Error connecting to smtp host' );
            $success = false;
        }

        $this->lastMailError = $mailer->lastMailError();


        return $success;
    }


    public function accountHookMetas()
    {
        $this->api->load->helper( 'array' );
        $all = $this->hookMeta( 'all' );
        return ncore_elementsWithKey( $all, 'context', 'account', $keep_keys=true );
    }
    public function cancelHookMetas()
    {
        $this->api->load->helper( 'array' );
        $all = $this->hookMeta( 'all' );
        return ncore_elementsWithKey( $all, 'context', 'cancel', $keep_keys=true );
    }

    public function lastMailError()
    {
        return $this->lastMailError;
    }

    public function hookMeta( $hook='all' )
    {
        $metas = $this->metas();

        if ($hook === 'all')
        {
            return $metas;
        }

        $meta =ncore_retrieve( $metas, $hook, false );

        if (!$meta)
        {
            trigger_error( "Invalid hook: '$hook'");
        }

        return $meta;
    }

    public function demoParams( $meta )
    {
        $demo_values = ncore_retrieve( $meta, 'demo_values', array() );

        $params = array_merge( $meta['placeholder'], $demo_values, $this->defaultParams() );

        return $params;
    }


    public function existingPasswordLabel()
    {
        return _ncore( 'You already have a password (check our other emails)' );
    }

    protected function checkSendPolicy( $mail_text, $recipient, $ref_id, $params ) {
        return true;
    }

    protected function metas()
    {
        $metas = array();

        $test_url = ncore_siteUrl();

        $metas[ NCORE_MAIL_HOOK_NEW_PASSWORD ] =  array(
            'context' => 'account',
            'label' => _ncore('Request password email'),
            'description' => _ncore('This email is sent after the user has requested a new password. After the user opens the URL in the mail, a new password is generated and sent by email.'),
            'placeholder' => array(
                    'url' => _ncore('URL to confirm the password'),
            ),
            'demo_values' => array(
                    'url' => $test_url,
                    'confirm_url' => "<a href=\"$test_url\">$test_url</a>",
            ),
        );

        $metas[ NCORE_MAIL_HOOK_PASSWORD_SENT ] =  array(
            'context' => 'account',
            'label' => _ncore('New password email'),
            'description' => _ncore('This email contains the new password of a user. It is sent, after a user has requested a new password. It contains the new access data of the user.'),
            'placeholder' => array(
                    'username' => _ncore('Login name'),
                    'password' => _ncore('The new password'),
                    'loginurl' => _ncore('The URL to login'),
            ),
            'demo_values' => array(
                    'username' => 'claus.myers@some-email.com',
                    'password' => 'abcd1234',
                    'loginurl' => $test_url,
            ),
        );

        $metas[ NCORE_MAIL_HOOK_CANCEL_CONFIRMATION ] =  array(
            'context' => 'cancel',
            'label' => _digi('Confirmation email on entry'),
            'placeholder' => array(
                'firstname' => _digi('The first name of the user'),
                'lastname'  => _digi('The last name of the user'),
                'customer_email'      => _digi('Customers e-mail'),
                'orderid'      => _digi('Order id'),
                'typereason' => _digi( 'Type of cancellation/Reason for cancellation' ),
                'cancellationdate' => _digi( 'Date of cancellation' ),
                'url' => _digi('Website url'),
                'admincancelemail' => _digi('Admin email for cancellation'),
            ),
            'demo_values' => array(
                'firstname' => 'Michael',
                'lastname' => 'Meier',
                'customer_mail' => 'testmail@test.de',
                'order_id' => 'order1234',
                'typereason' => _digi('timely termination'),
                'cancellationdate' => _digi( 'As fast as possible' ),
                'url' => 'Digimember',
                'admincancelemail' => 'admin@test.de',
            ),
        );

        $metas[ NCORE_MAIL_HOOK_CANCELMAIL ] =  array(
            'context' => 'cancel',
            'label' => _digi('Admin email on entry'),
            'placeholder' => array(
                'firstname' => _digi('The first name of the user'),
                'lastname'  => _digi('The last name of the user'),
                'customer_email'      => _digi('Customers e-mail'),
                'typereason' => _digi( 'Type of cancellation/Reason for cancellation' ),
                'cancellationdate' => _digi( 'Date of cancellation' ),
                'orderid'      => _digi('Order id'),
                'url' => _digi('Website url'),
                'admincancelemail' => _digi('Admin email for cancellation'),
            ),
            'demo_values' => array(
                'firstname' => 'Michael',
                'lastname' => 'Meier',
                'customer_mail' => 'testmail@test.de',
                'order_id' => 'order1234',
                'typereason' => _digi('timely termination'),
                'cancellationdate' => _digi( 'As fast as possible' ),
                'url' => 'Digimember',
                'admincancelemail' => 'admin@test.de',
            ),
        );


        $metas[ NCORE_MAIL_HOOK_TESTMAIL ] = array(
                'context' => 'test',
                'label' => _ncore('Test email'),
                'description' => _ncore('This email is sent when the admin clicks the send test email button.'),
        );

        return $metas;
    }

    public function defaultMailText( $hook )
    {
        switch ($hook)
        {
            case NCORE_MAIL_HOOK_CANCELMAIL:
            {
                $subject = _ncore('Entry cancellation form on %%url%%');
                $text = _ncore('
Hello,

There was an entry for the cancellation form on %%url%%.

The following data was entered:

First name: %%firstname%%

Last name: %%lastname%%

Email: %%customer_email%%

Order ID: %%order_id%%

Type of cancellation / Reason for cancellation: %%typereason%%

Date of cancellation: %%cancellationdate%%

The sender has received a confirmation email about the entry.


Tip: You can customize the content of this email in DigiMember > Mailtexts on your website.');
                $message = '<p>' . str_replace( "\n\n", '</p><p>', trim($text)) . '</p>';
                break;
            }
            case NCORE_MAIL_HOOK_CANCEL_CONFIRMATION:
            {
                $subject = _ncore('Confirmation entry cancellation form on %%url%%');
                $text = _ncore('
Hello %%firstname%% %%lastname%%,

Your entry in the cancellation form on %%url%% for order ID %%order_id%%, with type/reason of cancellation %%typereason%% and cancellation date %%cancellationdate%% was successful and we have received your data.

We will process this data and send you a confirmation e-mail as soon as the cancellation has been carried out.

If you have any questions regarding your cancellation, please contact %%admin_cancel_email%%.

With kind regards,


%%url%%');
                $message = '<p>' . str_replace( "\n\n", '</p><p>', trim($text)) . '</p>';
                break;
            }
            case NCORE_MAIL_HOOK_TESTMAIL:
            {
                $subject = _ncore('%s Test Mail', $this->api->pluginDisplayName());

                $text = _ncore('

    Hey there!

    This is just a test that your email software works fine.

    Enjoy %s!

    ', $this->api->pluginDisplayName());

                $message = '<p>' . str_replace( "\n\n", '</p><p>', trim($text)) . '</p>';
                break;
            }

            case NCORE_MAIL_HOOK_NEW_PASSWORD:
            {
                $subject = _ncore('Confirm your new password');

                $text = _ncore('
    Hey there!

    Someone - hopefully you - has set a new password for your account.

    To activate your new password, click on this URL:

    %s

    If you did not set a new password for your account, just ignore this email.

    Enjoy!
    ', '<a href="%%url%%">%%url%%</a>', $this->api->pluginDisplayName());

                $message = '<p>' . str_replace( "\n\n", '</p><p>', trim($text)) . '</p>';
                break;
            }

            case NCORE_MAIL_HOOK_PASSWORD_SENT:
            {
                $subject = _ncore('Your new password is in this email');

                $text = _ncore('
    Hey there!

    Your new password has just been created.

    Your new access data are:

    login URL: %%loginurl%%
    user name: %%username%%
    password:  %%password%%

    Enjoy!
    ' );

                $message = '<p>' . str_replace( "\n\n", '</p><p>', trim($text)) . '</p>';
                break;
            }


            default:
            {
                $subject = '';
                $message = '';
            }


        }

        return array( $subject, $message );
    }

    protected function defaultParams()
    {
        $params = array();

        $params['url'] = ncore_siteUrl();

        return $params;
    }

    private $lastMailError = '';


}

