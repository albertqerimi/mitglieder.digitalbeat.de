<?php

define('DM_PUBLIC_API_PARAM_ACTION', 'dm_public_api');
define('DM_PUBLIC_API_PARAM_KEY', 'dm_public_api_key');

define('DM_PUBLIC_API_PARAM_ACTION_CHECK_USER_EXISTS', 'checkUserExists');
define('DM_PUBLIC_API_PARAM_ACTION_USER_REGISTRATION', 'userRegistration');
define('DM_PUBLIC_API_PARAM_ACTION_USER_LOGIN', 'userLogin');
define('DM_PUBLIC_API_PARAM_ACTION_LIST_ACCESSIBLE_PRODUCTS', 'listAccessableProducts');
define('DM_PUBLIC_API_PARAM_ACTION_LIST_ACCESSIBLE_CONTENT', 'listAccessableContent');
define('DM_PUBLIC_API_PARAM_ACTION_GET_LECTURE_MENU', 'getLectureMenu');
define('DM_PUBLIC_API_PARAM_ACTION_LIST_ORDERS', 'listOrders');
define('DM_PUBLIC_API_PARAM_ACTION_LIST_PRODUCTS', 'listProducts');
define('DM_PUBLIC_API_PARAM_ACTION_GET_ORDER', 'getOrder');
define('DM_PUBLIC_API_PARAM_ACTION_CREATE_ORDER', 'createOrder');

define('DM_PUBLIC_API_PARAM_ACTION_VALIDATE_ZAPIER', 'validateZapier');
define('DM_PUBLIC_API_PARAM_ACTION_NEW_ORDER_WEBHOOK_SUBSCRIBE', 'newOrderWebhookSubs');
define('DM_PUBLIC_API_PARAM_ACTION_NEW_ORDER_WEBHOOK_UNSUBSCRIBE', 'newOrderWebhookUnsubs');
define('DM_PUBLIC_API_PARAM_ACTION_LATEST_ORDER', 'latestOrder');
define('DM_PUBLIC_API_PARAM_ZAPIER_VERSION', 'versionZapier');
define('DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CREATE_ORDER', 'createOrderZapier');
define('DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CREATE_USER', 'createUserZapier');
define('DM_PUBLIC_API_PARAM_ACTION_ZAPIER_LIST_PRODUCTS', 'listProductsZapier');
define('DM_PUBLIC_API_PARAM_ACTION_ZAPIER_REFUND_ORDER', 'refundOrderZapier');
define('DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CANCEL_ORDER', 'cancelOrderZapier');



define('DM_PUBLIC_API_PARAM_USER_EMAIL', 'userEmail');
define('DM_PUBLIC_API_PARAM_USER_EMAIL_OR_LOGIN_KEY', 'userEmailOrLoginKey');
define('DM_PUBLIC_API_PARAM_USER_ID_OR_LOGIN_KEY', 'userIdOrLoginKey');
define('DM_PUBLIC_API_PARAM_USER_PASSWORD', 'userPassword');
define('DM_PUBLIC_API_PARAM_USER_FIRSTNAME', 'userFirstname');
define('DM_PUBLIC_API_PARAM_USER_SURNAME', 'userSurname');
define('DM_PUBLIC_API_PARAM_PRODUCT_ID', 'productId');
define('DM_PUBLIC_API_PARAM_PRODUCT_NAME', 'productName');
define('DM_PUBLIC_API_PARAM_USER_ID', 'userId');
define('DM_PUBLIC_API_PARAM_ORDER_ID', 'orderId');
define('DM_PUBLIC_API_PARAM_ACCESS_STOPS_ON', 'accessStopsOn');
define('DM_PUBLIC_API_PARAM_WEBHOOK_ORDER_URL', 'orderURL');

/**
 * Class digimember_PublicApiController
 */
class digimember_PublicApiController extends ncore_Controller
{
    /**
     * @var null|stdClass
     */
    protected $apiKey = null;

    /**
     * @var null|digimember_HttpResponseLogic
     */
    public $response = null;

    /**
     * @var stdClass
     */
    protected $user;

    public function init($settings = [])
    {
        parent::init($settings);
        /** @var digimember_HttpResponseLogic $responseLogic */
        $responseLogic = $this->api->load->model('logic/http_response');
        $this->response = $responseLogic;
        $this->response->setHttpHeader('Content-Type', 'application/json');
    }

    /**
     *
     */
    protected function handleRequest()
    {
        global $current_user;
        /** @var digimember_HttpResponseLogic $response */
        $response = $this->api->load->model('logic/http_response');
        $response->init();
        try {
            $this->user = ncore_getUserById($this->apiKey->user_id);
            $current_user = $this->user;
            wp_clear_auth_cookie();
            wp_set_current_user($this->user->ID);
            wp_set_auth_cookie($this->user->ID);
            switch ($this->request(DM_PUBLIC_API_PARAM_ACTION)) {
                case DM_PUBLIC_API_PARAM_ACTION_CHECK_USER_EXISTS:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $email = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $user = ncore_getUserIdByEmail($email);
                    if ($user) {
                        $response->setParameters((array)ncore_getUserById($user));
                        break;
                    }
                    $user = ncore_getUserIdByName($email);
                    if ($user) {
                        $response->setParameters((array)ncore_getUserById($user));
                        break;
                    }
                    $response->setError(404, _digi('User not found'));
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_USER_REGISTRATION:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $productId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_ID);
                    $email = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $pass = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_PASSWORD);
                    $orderId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ORDER_ID, 'by_public_api');
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $response->setError(422, _digi('Invalid e-mail address'));
                        break;
                    }
//                    if (!trim($productId)) {
//                        $response->setError(422, _digi('Product id missing'));
//                        break;
//                    }
                    $doesUserExist = (bool)ncore_getUserBy('login', $email);
                    if (!$doesUserExist) {
                        $doesUserExist = (bool)ncore_getUserBy('email', $email);
                    }
                    /** @var digimember_PaymentHandlerLib $paymentLib */
                    $paymentLib = $this->api->load->library('payment_handler');
                    try {
                        $pass = $doesUserExist ? false : $pass;
                        $paymentLib->signUp($email, $productId, null, false, $orderId, $pass);
                        $user = ncore_getUserBy('login', $email);
                        if (!$user) {
                            $user = ncore_getUserBy('email', $email);
                        }
                        if (!$user) {
                            $response->setError(417, _digi('User creation failed due to an unknown error'));
                            break;
                        }
                        /** @var digimember_LoginkeyData $loginKeyModel */
                        $loginKeyModel = $this->api->load->model('data/loginkey');
                        $user->data->user_loginkey = $loginKeyModel->getForUser($user->ID);
                        $response->setParameters((array)$user->data);
                        break;
                    } catch (Exception $e) {
                        $response->setError(422, $e->getMessage());
                        break;
                    }
                case DM_PUBLIC_API_PARAM_ACTION_CREATE_ORDER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $productId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_ID);
                    $orderId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ORDER_ID);
                    $emailOrLoginKey = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL_OR_LOGIN_KEY);

                    /** @var digimember_LoginkeyData $loginKeyModel */
                    $loginKeyModel = $this->api->load->model('data/loginkey');
                    $userId = $loginKeyModel->getUserIdByKey($emailOrLoginKey);
                    if ($userId && $user = ncore_getUserById($userId)) {
                        $emailOrLoginKey = ncore_retrieve($user, 'user_email', '');
                    }

                    if (filter_var($emailOrLoginKey, FILTER_VALIDATE_EMAIL) === false) {
                        $response->setError(422, _digi('Invalid email address'));
                        break;
                    }
                    if (!(bool)ncore_getUserIdByEmail($emailOrLoginKey) && !(bool)ncore_getUserIdByName($emailOrLoginKey)) {
                        $response->setError(404, _digi('User does not exist'));
                        break;
                    }
                    if (!trim($productId)) {
                        $response->setError(422, _digi('Product id missing'));
                        break;
                    }
                    if (!trim($orderId)) {
                        $response->setError(422, _digi('Order id missing'));
                        break;
                    }
                    try {
                        /** @var digimember_PaymentHandlerLib $paymentLib */
                        $paymentLib = dm_api()->load->library('payment_handler');
                        $paymentLib->signUp($emailOrLoginKey, $productId, [], false, $orderId);
                        $response->setParameters([
                            'success' => true,
                        ]);
                        break;
                    } catch (Exception $e) {
                        $response->setError(422, $e->getMessage());
                        break;
                    }
                case DM_PUBLIC_API_PARAM_ACTION_USER_LOGIN:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $login = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $pass = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_PASSWORD);
                    $user = ncore_getUserBy('login', $login);
                    if (!$user) {
                        $response->setError(404, _digi('User not found'));
                        break;
                    }
                    if (!wp_check_password($pass, $user->data->user_pass, $user->ID)) {
                        $response->setError(401, _digi('User password incorrect'));
                        break;
                    }
                    /** @var digimember_LoginkeyData $loginKeyModel */
                    $loginKeyModel = $this->api->load->model('data/loginkey');
                    $user->data->user_loginkey = $loginKeyModel->getForUser($user->ID);
                    $response->setParameters((array)$user->data);
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_LIST_ACCESSIBLE_PRODUCTS:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $userIdent = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_ID_OR_LOGIN_KEY, $this->user->ID);
                    /** @var digimember_LoginkeyData $loginKeyModel */
                    $loginKeyModel = $this->api->load->model('data/loginkey');
                    $userId = $loginKeyModel->getUserIdByKey($userIdent);
                    if (!$userId) {
                        $userId = $userIdent;
                    }

                    $response->setParameters(
                        digimember_listAccessableProducts(
                            $userId
                        )
                    );
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_LIST_ACCESSIBLE_CONTENT:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $userIdent = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_ID_OR_LOGIN_KEY, $this->user->ID);
                    /** @var digimember_LoginkeyData $loginKeyModel */
                    $loginKeyModel = $this->api->load->model('data/loginkey');
                    $userId = $loginKeyModel->getUserIdByKey($userIdent);
                    if (!$userId) {
                        $userId = $userIdent;
                    }

                    $productId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_ID);
                    if (!trim($productId)) {
                        $response->setError(422, _digi('Product id missing'));
                        break;
                    }
                    $response->setParameters(
                        digimember_listAccessableContent(
                            $productId,
                            $userId
                        )
                    );
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_GET_LECTURE_MENU:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $productId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_ID);
                    if (!trim($productId)) {
                        $response->setError(422, _digi('Product id missing'));
                        break;
                    }
                    /** @var array $lectureMenu */
                    $lectureMenu = digimember_getLectureMenu(
                        $productId
                    );
                    $response->setParameters(
                        $lectureMenu
                    );
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_LIST_ORDERS:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $userId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_ID, $this->user->ID);
                    $response->setParameters(
                        digimember_listOrders(
                            $userId
                        )
                    );
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_LIST_PRODUCTS:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }

                    $response->setParameters(
                        digimember_listProducts()
                    );
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_GET_ORDER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ADMIN])) {
                        break;
                    }
                    $orderId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ORDER_ID);
                    if (!$orderId) {
                        $this->notFound();
                        break;
                    }
                    $order = digimember_getOrder(
                        $orderId
                    );
                    if ($order) {
                        $response->setParameters(
                            (array)$order
                        );
                    } else {
                        $this->notFound();
                        break;
                    }
                    break;

                /*
                 *  ZAPIER SECTION BEGIN
                 */

                case DM_PUBLIC_API_PARAM_ACTION_VALIDATE_ZAPIER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }

                    $response->setParameters([
                        'success' => true,
                    ]);
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_ZAPIER_LIST_PRODUCTS:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }
                    /** @var digimember_ProductData $productModel */
                    $productModel = $this->api->load->model('data/product');
                    $products = $productModel->getAll();
                    $productsArray[] = ['id' => 0, 'product_id' => 0, 'product_name' => 'all'];
                    $i = 1;

                    foreach ($products as $product) {
                        $productsArray[] = [
                            'id' => '' . $i . '',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                        ];
                        $i++;
                    }

                    $response->setParameters($productsArray);
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_NEW_ORDER_WEBHOOK_SUBSCRIBE:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }

                    $url = ncore_retrieveGET(DM_PUBLIC_API_PARAM_WEBHOOK_ORDER_URL);
                    if (!$url) {
                        $response->setError(422, _digi("Something went wrong, no web hook URL received"));
                        break;
                    }

                    // this is needed to store the API Key with which a Zap was activated to deactivate the autoresponder if the API Key gets disabled/reactivated in the API Key Settings
                    $user_api_key = ncore_retrieveGET(DM_PUBLIC_API_PARAM_KEY);

                    $product = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_NAME);

                    /** @var digimember_ProductData $product_model */
                    $product_model = $this->api->load->model('data/product');
                    $product_options = $product_model->options($product_type = 'membership', $public_only = true);

                    if ($product == 'all') {
                        $productId = 'all';
                    }

                    foreach ($product_options as $id => $name) {
                        // to ensure that for option "All" selected, all product IDs are correctly formatted stored into the DB
                        if ($product == 'all') {
                            $productId = $productId . ',' . $id;
                        } else if ($product == $name) {
                            $productId = $id;
                        }
                    }

                    /** @var ncore_AutoresponderData $model */
                    $model = $this->api->load->model('data/autoresponder');
                    $autoresponder_data = ['zapier_notify_url' => $url, 'zapier_api_key' => $user_api_key];

                    $data = [
                        "engine" => "zapier",
                        "is_active" => "Y",
                        "product_ids_comma_seperated" => $productId,
                        "data_serialized" => serialize($autoresponder_data),
                    ];

                    $model->create($data);

                    $response->setParameters([
                        'success' => true,
                    ]);
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_NEW_ORDER_WEBHOOK_UNSUBSCRIBE:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }

                    $url = ncore_retrieveGET(DM_PUBLIC_API_PARAM_WEBHOOK_ORDER_URL);
                    if (!$url) {
                        $response->setError(422, _digi("Something went wrong, no web hook URL received"));
                        break;
                    }

                    // this is needed to store the API Key with which a Zap was activated to deactivate the autoresponder if the API Key gets disabled/reactivated in the API Key Settings
                    $user_api_key = ncore_retrieveGET(DM_PUBLIC_API_PARAM_KEY);

                    $product = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_NAME);

                    /** @var digimember_ProductData $product_model */
                    $product_model = $this->api->load->model('data/product');
                    $product_options = $product_model->options($product_type = 'membership', $public_only = true);

                    if ($product == 'all') {
                        $productId = 'all';
                    } else {
                        foreach ($product_options as $id => $name) {
                            if ($product == $name) {
                                $productId = $id;
                            }
                        }
                    }
                    /** @var ncore_AutoresponderData $model */
                    $model = $this->api->load->model('data/autoresponder');

                    $results = $model->getAll(['engine' => 'zapier', 'product_ids_comma_seperated' => $productId]);

                    if (!$results) {
                        $response->setError(422, _digi("Something went wrong, no web hook configured for this product combination"));
                    } else {
                        foreach ($results as $result) {
                            $data = unserialize($result->{"data_serialized"});
                            // checks if the found data contains settings for the currently activated api key
                            if ($data['zapier_api_key'] == $user_api_key) {
                                // the autoresponder gets deleted
                                $model->delete($result->{'id'});
                            }
                        }
                    }

                    $response->setParameters([
                        'success' => true,
                    ]);

                    break;
                case DM_PUBLIC_API_PARAM_ACTION_LATEST_ORDER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }

                    /** @var digimember_UserProductData $orders */
                    $orders = $this->api->load->model('data/user_product');
                    /** @var digimember_ProductData $products */
                    $products = $this->api->load->model('data/product');

                    $latestOrder = $orders->getWhere(['is_active' => 'Y'], 'id DESC');

                    $productId = $latestOrder->product_id;
                    $productName = $products->get($productId)->name;
                    $orderId = $latestOrder->order_id;

                    $user_obj = get_userdata($latestOrder->user_id);
                    $email = $user_obj->user_email;
                    $firstname = $user_obj->user_firstname;
                    $lastname = $user_obj->user_lastname;

                    // übernommen aus autoresponder_handler line 698 ff

                    /** @var digimember_UserData $model */
                    $model = $this->api->load->model('data/user');
                    $stored_password = $model->getPassword($latestOrder->user_id);
                    $password = '';
                    if ($stored_password) {
                        $password = $stored_password;
                    } else {
                        $password = $model->setPassword($latestOrder->user_id, $password, false);
                    }

                    $orderDate = $latestOrder->order_date;

                    $correctly_formatted_order = [
                        'product_id' => $productId,
                        'order_id' => $orderId,
                        'product_name' => $productName,
                        'email' => $email,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'order_date' => $orderDate,
                        'password' => $password,
                    ];

                    $response->setParameters([
                        $correctly_formatted_order,
                    ]);
                    break;
                case DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CREATE_ORDER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }

                    $productName = ncore_retrieveGET(DM_PUBLIC_API_PARAM_PRODUCT_NAME);
                    $email = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $orderId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ORDER_ID, 'by_zapier');
                    $firstname = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_FIRSTNAME);
                    $lastname = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_SURNAME);

                    /** @var digimember_ProductData $product_model */
                    $product_model = $this->api->load->model('data/product');
                    $product_options = $product_model->options($product_type = 'membership', $public_only = true);

                    if ($productName == 'all') {
                        $productId = 'all';
                    } else {
                        foreach ($product_options as $id => $name) {
                            if ($productName == $name) {
                                $productId = $id;
                            }
                        }
                    }

                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $response->setError(422, _digi('Invalid e-mail address'));
                        break;
                    }

                    /** @var digimember_PaymentHandlerLib $paymentLib */
                    $paymentLib = $this->api->load->library('payment_handler');
                    try {
                        $paymentLib->signUp($email, $productId, null, false, $orderId);
                        $user = ncore_getUserBy('login', $email);
                        if (!$user) {
                            $user = ncore_getUserBy('email', $email);
                        }
                        if (!$user) {
                            $response->setError(417, _digi('User creation failed due to an unknown error'));
                            break;
                        }

                        $user_id = ncore_getUserIdByEmail($email);
                        update_user_meta($user_id, 'first_name', $firstname);
                        update_user_meta($user_id, 'last_name', $lastname);

                        /** @var digimember_LoginkeyData $loginKeyModel */
                        $loginKeyModel = $this->api->load->model('data/loginkey');
                        $user->data->user_loginkey = $loginKeyModel->getForUser($user->ID);

                        $response->setParameters((array)$user->data);

                        break;
                    } catch (Exception $e) {
                        $response->setError(422, $e->getMessage());
                        break;
                    }

                case DM_PUBLIC_API_PARAM_ACTION_ZAPIER_REFUND_ORDER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }
                    $email = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $orderId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ORDER_ID, 'by_zapier');

                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $response->setError(422, _digi('Invalid e-mail address'));
                        break;
                    }
                    $user_id = ncore_getUserIdByEmail($email);
//                    if (!$user_id) {
//                        $response->setError(422, _digi('User not found'));
//                        break;
//                    }

                    /** @var digimember_PaymentHandlerLib $paymentLib */
                    $paymentLib = $this->api->load->library('payment_handler');
                    $data = array(
                        'orderId' => $orderId,
                        'userEmail' => $email,
                        'action' => DM_PUBLIC_API_PARAM_ACTION_ZAPIER_REFUND_ORDER,
                    );
                    try {
                        $paymentLib->onRefundZapier(0, $orderId, $user_id);
                        $data['status'] = 'success';
                        $response->setParameters((array)$data);
                        break;
                    } catch (Exception $e) {
                        $data['status'] = $e->getMessage();
                        $response->setParameters((array)$data);
                        $this->api->logError(
                            'zapier',
                            "[".DM_PUBLIC_API_PARAM_ACTION_ZAPIER_REFUND_ORDER."] "._digi('Zapier action failed. Order ID: %s email: %s error message: %s'),
                            $orderId,
                            $email,
                            $e->getMessage()
                        );
                        //$response->setError(422, $e->getMessage());
                        break;
                    }

                case DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CANCEL_ORDER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }
                    $email = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $orderId = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ORDER_ID, 'by_zapier');
                    $accessStopsOn = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ACCESS_STOPS_ON, false);

                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $response->setError(422, _digi('Invalid e-mail address'));
                        break;
                    }
                    $user_id = ncore_getUserIdByEmail($email);
//                    if (!$user_id) {
//                        $response->setError(422, _digi('User not found'));
//                        break;
//                    }

                    /** @var digimember_PaymentHandlerLib $paymentLib */
                    $paymentLib = $this->api->load->library('payment_handler');
                    $data = array(
                        'orderId' => $orderId,
                        'userEmail' => $email,
                        'accessStopsOn' => $accessStopsOn ? true : false,
                        'accessStopsOnDate' => $accessStopsOn ? $accessStopsOn : null,
                        'action' => DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CANCEL_ORDER,
                    );
                    try {
                        $paymentLib->onCancelZapier(0, $orderId, $user_id, $accessStopsOn);
                        $data['status'] = 'success';
                        $response->setParameters((array)$data);
                        break;
                    } catch (Exception $e) {
                        $data['status'] = $e->getMessage();
                        $response->setParameters((array)$data);
                        $this->api->logError(
                            'zapier',
                            "[".DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CANCEL_ORDER."] "._digi('Zapier action failed. Order ID: %s email: %s error message: %s'),
                            $orderId,
                            $email,
                            $e->getMessage()
                        );
                        //$response->setError(422, $e->getMessage());
                        break;
                    }

                // currently this UC is not used anymore in the Zapier version, I left the code if we decide to reintegrate it in the future
                case DM_PUBLIC_API_PARAM_ACTION_ZAPIER_CREATE_USER:
                    if (!$this->verifyScope([DM_API_KEY_SCOPE_ZAPIER])) {
                        $response->setError(403, _digi("Permission needs to be Zapier"));
                        break;
                    }

                    $zapierVersion = ncore_retrieveGET(DM_PUBLIC_API_PARAM_ZAPIER_VERSION);
                    if (!$this->verifyZapierVersion($zapierVersion)) {
                        $response->setError(403, _digi("Wrong Zapier Version"));
                        break;
                    }
                    $email = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_EMAIL);
                    $firstname = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_FIRSTNAME);
                    $lastname = ncore_retrieveGET(DM_PUBLIC_API_PARAM_USER_SURNAME);

                    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        $response->setError(422, _digi('Invalid e-mail address'));
                        break;
                    }

                    $doesUserExist = (bool)ncore_getUserBy('login', $email);
                    if (!$doesUserExist) {
                        $doesUserExist = (bool)ncore_getUserBy('email', $email);
                    }
                    if (!$doesUserExist) {
                        $this->api->load->helper('string');
                        $password = ncore_randomString('password', NCORE_PASSWORD_LENGTH);
                    }
                    try {
                        wp_create_user($email, $password, $email);
                        $user = ncore_getUserBy('login', $email);
                        $user_id = ncore_getUserIdByEmail($email);
                        update_user_meta($user_id, 'first_name', $firstname);
                        update_user_meta($user_id, 'last_name', $lastname);

                        if (!$user)
                            $response->setError(417, _digi('User creation failed due to an unknown error'));

                        /** @var digimember_LoginkeyData $loginKeyModel */
                        $loginKeyModel = $this->api->load->model('data/loginkey');
                        $user->data->user_loginkey = $loginKeyModel->getForUser($user->ID);
                        $response->setParameters((array)$user->data);
                        break;
                    } catch (Exception $e) {
                        $response->setError(422, $e->getMessage());
                        break;
                    }

                /*
                *  ZAPIER SECTION END
                */

                default:
                    $this->notFound();
            }
        } catch (Exception $e) {
            $response->setError(401, 'access_denied', $e->getMessage());
        }
        $response->send();
    }

    /**
     * @return bool
     */
    protected function writeAccessGranted()
    {
        $key = $this->request(DM_PUBLIC_API_PARAM_KEY);
        if (!$key || strlen($key) != 32) {
            $this->response->setError(400, _digi('Invalid API key'), _digi('API key missing or has invalid format'));
            return false;
        }

        /** @var digimember_ApiKeyData $apiKeyData */
        $apiKeyData = $this->api->load->model('data/api_key');
        $keyModel = $apiKeyData->getWhere([
            'key' => $key,
            'deleted' => null,
        ]);
        if (!$keyModel) {
            $this->response->setError(401, _digi('Invalid API key'), 'API key not registered or was deleted.');
            return false;
        }
        if ($keyModel->is_active == 'N') {
            $this->response->setError(412, _digi('Invalid API key'), 'API key currently deactivated.');
            return false;
        }
        if ($keyModel->status == 'deleted') {
            $this->response->setError(412, _digi('Invalid API key'), 'API key was deleted, you need to restore it first.');
            return false;
        }

        $this->apiKey = $keyModel;

        return true;
    }

    /**
     * @param string|string[] $scope
     * @param bool            $or
     *
     * @return bool
     */
    private function verifyScope($scope, $or = true)
    {
        if (!$this->apiKey) {
            $this->response->setError(400, _digi('Invalid API key'), _digi('API key missing or has invalid format'));
        }
        if (!is_array($scope)) {
            $scope = [$scope];
        }
        foreach ($scope as $val) {
            if ($or && strpos($this->apiKey->scope, $val) !== false) {
                return true;
            }
            if (!$or && strpos($this->apiKey->scope, $val) === false) {
                $this->response->setError(401, _digi('Permission denied'), _digi('You cannot access this resource'));
                return false;
            }
        }
        if ($or) {
            $this->response->setError(401, _digi('Permission denied'), _digi('You cannot access this resource'));
            return false;
        }
        return true;
    }

    /**
     * @param $key
     *
     * @return null|string
     */
    private function request($key)
    {
        static $cache = [];
        $result =& $cache[$key];
        if ($result) {
            return $result;
        }
        $result = ncore_retrievePOST($key, ncore_retrieveGET($key));

        return $result;
    }

    private function notFound()
    {
        $this->response->setError(404, _digi('Resource not found'), _digi('Requested API resource was not found'));
    }

    /**
     * private helper function, checks if the Zapier call is made from the latest DigiMember Version in Zapier
     * @param $zapierVersion
     * @return bool true if it is the latest version, false otherwise
     */
    private function verifyZapierVersion($zapierVersion)
    {
        if ($zapierVersion != '1.0.1') {
            return false;
        }
        return true;
    }
}