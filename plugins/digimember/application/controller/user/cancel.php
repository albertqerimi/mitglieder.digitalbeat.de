<?php

$load->controllerBaseClass( 'user/form' );

class digimember_UserCancelController extends ncore_UserFormController
{
    /** @var string */
    private $form_style = 'modern';
    private $logged_in = false;
    private $userId = false;
    private $activeSubscriptions = array();
    private $has_ds24_subscriptions = false;

    private $use_generic = false;
    private $show_product_name = false;
    private $show_order_id = false;
    private $productIds = array();

    private $button_bg = '';
    private $button_fg = '';
    private $buttonRadius = '';
    private $button_text = '';
    private $buttonStyle = '';

    public function init( $settings=array() )
    {
        $products_comma_seperated = trim( ncore_retrieve( $settings, 'product' ) );
        $have_all = strpos( $products_comma_seperated, 'all' ) !== false;
        $this->productIds = $products_comma_seperated && !$have_all
            ? explode( ',' , $products_comma_seperated )
            : array();
        $this->button_bg = ncore_retrieve($settings, 'button_bg', '#2196F3');
        $this->button_fg = ncore_retrieve($settings, 'button_fg', '#FFFFFF');
        $this->buttonRadius = ncore_retrieve($settings, 'button_radius', 0);
        $this->button_text = ncore_retrieve($settings, 'button_text', _digi( 'Cancel' ));
        $this->use_generic = in_array('use_generic', $settings) || ncore_retrieve($settings, 'use_generic', 0) == 1;
        $this->show_product_name = in_array('show_product_name', $settings) || ncore_retrieve($settings, 'show_product_name', 0) == 1;
        $this->show_order_id = in_array('show_order_id', $settings) || ncore_retrieve($settings, 'show_order_id', 0) == 1;
        if (!$this->use_generic) {
            $this->logged_in = ncore_isLoggedIn();
            if ($this->logged_in) {
                $this->userId = ncore_userId();
                $this->getPurchases($this->userId);
            }
        }
        $css_class = ncore_retrieve($settings, 'container_css');
        $css_class = trim( "$css_class digimember_cancel" );
        $settings[ 'container_css' ] = $css_class;
        $this->form_style        = ncore_retrieve( $settings, 'style', 'modern' );
        parent::init( $settings );
    }

    public function getPurchases($userId) {
        $userProductModel = $this->api->load->model('data/user_product');
        $userProducts = $userProductModel->getForUser($userId);
        if (count($userProducts) > 0) {
            $this->activeSubscriptions = $this->getActiveSubscriptions($userProducts);
            if (count($this->activeSubscriptions) > 0) {
                $this->has_ds24_subscriptions = true;
            }
        }
    }

    public function getActiveSubscriptions($userProducts) {
        $activeSubscriptions = array();
        /** @var digimember_DigistoreConnectorLogic $ds24 */
        $ds24 = $this->api->load->model( 'logic/digistore_connector' );
        foreach ($userProducts as $userProduct) {
            $stopUrl = ncore_retrieve($userProduct, 'ds24_rebilling_stop_url', false);
            $order_id = ncore_retrieve($userProduct, 'order_id', false);
            if ($stopUrl && $order_id) {
                try {
                    $data = $ds24->getPurchase( $order_id );
                    $status = ncore_retrieve($data, 'billing_status', false);
                    if ($status === 'paying') {
                        if ($this->displayProduct($userProduct)) {
                            $activeSubscriptions[] = $userProduct;
                        }
                    }
                }
                catch (Exception $e) {
                    //dont do anything just catch the ds24 exception silently
                }
            }
        }
        return $activeSubscriptions;
    }

    public function displayProduct ($userProduct) {
        if (count($this->productIds) > 0) {
            foreach ($this->productIds as $productId) {
                if (ncore_retrieve($userProduct, 'product_id', false) === $productId) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    protected function view()
    {
        $css = $this->renderCss();
        echo "<style type=\"text/css\">$css</style>";
        parent::view();
    }

    private function renderCss()
    {
        $form_id = $this->formId();
        $bg_color      = $this->setting( 'button_bg',    '#21759b' );
        $fg_color      = $this->setting( 'button_fg',    '#FFFFFF' );
        $button_radius = $this->setting( 'button_radius',false );
        $button_radius_css = $button_radius !== false
                           ? 'border-radius: '.$button_radius.'px; border-top-left-radius: '.$button_radius.'px; border-top-right-radius: '.$button_radius.'px; border-bottom-left-radius: '.$button_radius.'px; border-bottom-right-radius: '.$button_radius.'px;'
                           : '';
        return "
            form#$form_id div.ncore_with_focus input[type=\"text\"],
            form#$form_id div.ncore_with_focus input[type=\"password\"]
            {
                border-color: $bg_color;
                $button_radius_css
            }
            
            form#$form_id div.ncore_with_focus label,
            form#$form_id div.ncore_with_focus label
            {
                color: $bg_color;
                $button_radius_css
            }
            
            .button.button-primary.ncore_custom_button,
            form#$form_id .button.button-primary {
                 background-color: $bg_color;
                 color: $fg_color;
                 $button_radius_css
             }
        ";
    }

    protected function pageHeadline()
    {
        return _dgyou( 'Cancel' );
    }

    protected function inputMetas()
    {
        $metas = array();
        $rows = array();

        if ($this->logged_in && !$this->use_generic) {
            if ($this->has_ds24_subscriptions) {
                $products = $this->api->load->model('data/product');
                foreach ($this->activeSubscriptions as $subscription) {
                    $row = array(
                        'link' => $subscription->ds24_rebilling_stop_url,
                        'name' => $products->get($subscription->product_id)->name,
                        'order_id' => $subscription->order_id,
                    );
                    $rows[] = $row;
                }
                $metas[] = array(
                    'section' => 'cancel',
                    'type' => 'button_list',
                    'rows' => $rows,
                    'button_text' => $this->button_text,
                    'show_product_name' => $this->show_product_name,
                    'show_order_id' => $this->show_order_id,
                    'use_generic' => $this->use_generic,
                );
            }
        }
        else {
            $row = array(
                'link' => 'https://www.digistore24.com/find_my_order/cancel'
            );
            $rows[] = $row;
            $metas[] = array(
                'section' => 'cancel',
                'type' => 'button_list',
                'rows' => $rows,
                'button_text' => $this->button_text,
                'show_product_name' => false,
                'show_order_id' => false,
            );
        }
        return $metas;
    }

    protected function sectionMetas()
    {
        return array(
             'cancel' => array(
                 'headline'     => '',
                 'instructions' => ''
            )
        );
    }

    protected function buttonMetas()
    {
        $metas = array();
        return $metas;
    }

    protected function saveButtonLabel()
    {
        return $this->setting( 'button_text', _dgyou( 'Log In' ) );
    }

    protected function saveButtonUrl()
    {
        return $this->setting( array( 'img', 'image_url'), false );
    }

    protected function editedElementIds()
    {
        return array(
             0
        );
    }

    protected function containerCss()
    {
        return parent::containerCss()
                . ' ' .
                ($this->have_wordpress_login
                ? 'ncore_with_wp_login'
                : 'ncore_without_wp_login')
                . ' ' .
                ($this->fb_login_button
                ? 'ncore_with_fp_login'
                : 'ncore_without_fp_login');
    }

    protected function formSettings()
    {
        $is_legacy = $this->form_style != 'modern';
        return $is_legacy
            ? array(
                 'form_css'           => 'ncore_form_narrow',
                 'layout'             => 'narrow_legacy',
                 'hide_required_hint' => true,
              )
            :  array(
                 'layout'             => 'narrow',
                 'hide_required_hint' => true,
              );
    }

    protected function handleRequest()
    {
        parent::handleRequest();
    }


    protected function getData( $user_id )
    {
        return array();
    }

    protected function setData( $user_id, $data )
    {
        try
        {
            $username = ncore_retrieve( $data, 'username' );
            $password = ncore_retrieve( $data, 'password' );

            $url      = ncore_retrieve( $data, 'redirect_url' );
            if (!$url)
            {
                $url = $this->setting( 'redirect_url' );
            }

            if ( !$username && !$password )
            {
                throw new Exception( _dgyou( 'Please enter a username and a password.' ) );
            }

            // NOTE: using ncore_wp_login fails, since the html header is rendered at this point of time and wp_login sets a cookie
            $user = ncore_wp_authenticate( $username, $password/*, $remember */);

            if (!$url)
            {
                /** @var digimember_AccessLogic $model */
                $model = $this->api->load->model( 'logic/access' );
                $url   = $model->loginUrl( $user );
            }

            /** @var ncore_OneTimeLoginData $model */
            $model = $this->api->load->model( 'data/one_time_login' );
            $redirect_url = $model->setOneTimeLogin( $user->ID, ncore_resolveUrl( $url ) );

            ncore_redirect( $redirect_url );

            return true;
        }
        catch ( Exception $e )
        {
            $this->formError( $e->getMessage() );
            return false;
        }


    }

    protected function formSuccessMessage()
    {
        return _dgyou( 'You are logged in now.' );
    }

    protected function ajaxEventHandlers()
    {
        $handlers = parent::ajaxEventHandlers();

        $handlers['login'] = 'handleAjaxLoginEvent';
        $handlers['ok']    = 'handleAjaxRequestPasswordEvent';

        return $handlers;
    }

    protected function saveButtonClass()
    {
        return 'ncore_custom_user_button';
    }

    protected function secureAjaxEvents()
    {
        $events = parent::secureAjaxEvents();

        $events[] = 'login';
        $events[] = 'ok';

        return $events;
    }
    private $have_wordpress_login   = true;
    /** @var digimember_FacebookLoginButton | bool */
    private $fb_login_button = false;

    private $form_id;

    protected function formId()
    {
        return $this->form_id . '_form';
    }

    protected function ajaxErrorMsgDivId()
    {
        return $this->form_id . '_form_error_message';
    }

}
