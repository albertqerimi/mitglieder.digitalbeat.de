<?php

class ncore_MailerLib extends ncore_Library
{
    public function to( $email, $name = '' )
    {
        $this->to[ $email ] = $name;
    }

    public function cc( $email, $name = '' )
    {
        $this->cc[ $email ] = $name;
    }

    public function bcc( $email, $name = '' )
    {
        $this->bcc[ $email ] = $name;
    }

    public function subject( $subject )
    {
        $this->subject=$subject;
    }

    public function text( $body_text )
    {
        $this->text=$body_text;
    }

    public function html( $body_html )
    {
        $this->html=$body_html;
    }

    public function attachments ($files = array()) {
        $this->attachments = $files;
    }

    public function defaultTestEmailAddress()
    {
        return get_bloginfo('admin_email');
    }

    public function reset()
    {
        $this->header = array();
        $this->to = array();
        $this->cc = array();
        $this->bcc = array();
        $this->subject='';
        $this->html='';
        $this->text='';
        $this->attachments = array();

        $this->lastMailError = '';
    }

    public function setEmailConfigCallback( $function_name )
    {
        $previous_function = $this->email_config_callback;

        if (!function_exists($function_name))
        {
            throw new Exception( "Not a function: $function_name" );
        }

        $this->email_config_callback = $function_name;

        return $previous_function;
    }

    public function send()
    {
        $phpmailer = $this->getPhpMailer();

        $function = $this->email_config_callback;

        list( $use_smtp_mail,
              $smtp_host,
              $smtp_port,
              $smtp_security,
              $smtp_user_name,
              $smtp_user_pass,
              $sender_email,
              $sender_name,
              $reply_email  ) = $function();

        set_error_handler( array( $this, 'phpErrorHandler' ) );
        $old_level = error_reporting( E_ALL&~E_USER_NOTICE );

        $success = false;

        try
        {
            if ($reply_email)
            {
                $phpmailer->addReplyTo( $reply_email, $sender_name );
            }

            $phpmailer->From      = $sender_email;
            $phpmailer->FromName  = $sender_name;

            if ($use_smtp_mail)
            {
                $phpmailer->IsSMTP();

                $phpmailer->Host = $smtp_host;
                $phpmailer->Port = $smtp_port;

                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $smtp_user_name;
                $phpmailer->Password = $smtp_user_pass;

                $phpmailer->SMTPSecure = $smtp_security;
            }

            $recipients = array();

            foreach ($this->to as $email => $name)
            {
                $phpmailer->AddAddress( $email, $name );
                $recipients[] = $email;
            }

            foreach ($this->cc as $email => $name)
            {
                $phpmailer->AddCC( $email, $name );
                $recipients[] = $email;
            }

            foreach ($this->bcc as $email => $name)
            {
                $phpmailer->AddBCC( $email, $name );
                $recipients[] = $email;
            }
            foreach ($this->attachments as $file) {
                $phpmailer->addAttachment($file);
            }

            $subject = $this->subject;

            $phpmailer->CharSet  =  "utf-8";

            $phpmailer->Subject   = $subject;

            if ($this->html)
            {
                $phpmailer->IsHTML(true);
                $phpmailer->Body = $this->html;

                $phpmailer->AltBody = $this->text
                               ? $this->text
                               : strip_tags($this->html);
            }
            else
            {
                $phpmailer->IsHTML(false);
                $phpmailer->Body = $this->text;
                $phpmailer->AltBody = '';
            }

            $phpmailer->XMailer = $this->getXMailer();

            $this->lastMailError = '';
            $success = $phpmailer->Send();

            if ($this->lastMailError)
            {
                $error_msg = $this->lastMailError;
                $success   = false;
            }
            else {
                $error_msg = $phpmailer->ErrorInfo;
            }

            $this->reset();

            $this->lastMailError = $success
                                 ? ''
                                 : $error_msg;

        }

        catch (Exception $e)
        {
            $this->reset();
            $this->lastMailError = $e->getMessage();
            $success = false;
        }

        error_reporting( $old_level );
        restore_error_handler();


        $this->api->load->helper( 'string' );
        $short_subject = ncore_shortenText( $subject, 35, 5 );

        switch (count($recipients))
        {
            case 0:
                $recipients= 'ERROR-NO-RECIPIENT';
                break;

            case 1:
                $recipients = $recipients[0];
                break;
            case 2:
            case 3:
                $recipients = implode( ', ', $recipients );
                break;
            case 4:
            default:
                $left = count($recipients) - 1;
                $recipients = $recipients[0]
                            . ', '
                            . $recipients[1]
                            . ' '
                            . _ncore( 'and %s more', $left );
        }

        if ($success)
        {
            $this->api->log( 'mail', _ncore( 'send email "%s" to %s', $short_subject, $recipients ) );
        }
        else
        {
            $this->api->logError( 'mail', _ncore( 'could not sent email "%s" to %s. %s' ), $short_subject, $recipients, $this->lastMailError );
        }

        return $success;
    }

    public function lastMailError()
    {
        return $this->lastMailError;
    }

    public function defaultSenderEmailAddress()
    {
        $config = $this->api->load->model( 'logic/blog_config' );

        $sender_email = $config->get( 'mail_sender_email' );

        if ( !$sender_email )
        {
            $sender_email = get_bloginfo('admin_email');
        }

        return $sender_email;
    }

    public function phpErrorHandler( $errorcode, $errortext, $file, $line )
    {
        $this->lastMailError = $errortext;
        return true;
    }



    private $to = array();
    private $cc = array();
    private $bcc = array();
    private $attachments = array();
    private $subject='';
    private $html='';
    private $text='';

    private $lastMailError = '';

    private $email_config_callback = 'ncore_getEmailConfig';

    private static $mailer;

    private function getXMailer()
    {
        return sprintf( "%s v%s", $this->api->pluginName(), $this->api->pluginVersion() );
    }


    private function getPhpMailer() {

        if (empty(self::$mailer)) {
            if (file_exists(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php')) {
                if (!class_exists('PHPMailer\PHPMailer\PHPMailer'))
                {
                    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                }

                self::$mailer = new PHPMailer\PHPMailer\PHPMailer( true );
            }
            else {
                if (!class_exists('PHPMailer'))
                {
                    require_once ABSPATH . WPINC . '/class-phpmailer.php';
                    require_once ABSPATH . WPINC . '/class-smtp.php';
                }

                self::$mailer = new PHPMailer( true );
            }
        }

        if (empty(self::$mailer->SMTPOptions[ 'ssl' ])) {
            self::$mailer->SMTPOptions[ 'ssl' ] = array();
        }
        self::$mailer->SMTPOptions[ 'ssl' ]['verify_peer']       = false;
        self::$mailer->SMTPOptions[ 'ssl' ]['verify_peer_name']  = false;
        self::$mailer->SMTPOptions[ 'ssl' ]['allow_self_signed'] = true;

        self::$mailer->ClearAddresses();
        self::$mailer->ClearAllRecipients();
        self::$mailer->ClearAttachments();
        self::$mailer->ClearBCCs();
        self::$mailer->ClearCCs();
        self::$mailer->ClearCustomHeaders();
        self::$mailer->ClearReplyTos();

        return self::$mailer;
    }

}

