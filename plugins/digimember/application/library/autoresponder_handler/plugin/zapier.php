<?php

//require_once dirname(__FILE__) . '../autoresponder_handler.php';

/**
 * Class digimember_AutoresponderHandler_PluginZapier
 */
class digimember_AutoresponderHandler_PluginZapier extends digimember_AutoresponderHandler_PluginBase
{
    /**
     * @param $url
     */
    public function setSubscribeWebhook($url)
    {
        if (!$this->data('notify_url')) {
            $this->data('notify_url')->$url;
        }
    }

    /**
     * @return bool
     */
    public function hasUnsubscribe()
    {
        return true;
    }

    /**
     * @param $email
     */
    public function unsubscribe($email)
    {
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    public function getPersonalData($email)
    {
        return [];
    }

    // in wie weit ist das notwendig bzw. in wie weit ist dann die richtige Oberklasse? Metas und Oberfläche werden ja eigentlich nicht gebraucht, da es sich um keinen Auswähbaren Eintrag handelt

    /**
     * @return mixed
     */
    protected function formMetas()
    {
        $metas = [
            [
                'name' => 'notify_url',
                'type' => 'url',
                'label' => _ncore('Notification URL'),
            ],
        ];

        return $metas;

    }

    /**
     * @param       $email
     * @param       $first_name
     * @param       $last_name
     * @param       $product_id
     * @param       $order_id
     * @param bool  $force_double_optin
     * @param array $custom_fields
     *
     * @return mixed
     */
    public function subscribe($email, $first_name, $last_name, $product_id, $order_id, $force_double_optin = true, $custom_fields = [])
    {
        $notify_url = $this->data('notify_url');

        /** @var ncore_HttpRequestLib $lib */
        $lib = $this->api->load->library('http_request');
        /** @var digimember_ProductData $product_model */
        $product_model = $this->api->load->model('data/product');
        /** @var digimember_UserProductData $order_model */
        $order_model = $this->api->load->model('data/user_product');
        /** @var digimember_UserData $user_model */
        $user_model = $this->api->load->model('data/user');

        $product = $product_model->get($product_id);
        $product_name = $product->name;

        $user = ncore_getUserBy('email', $email);
        $order = $order_model->getWhere(['order_id' => $order_id, 'user_id' => $user->ID, 'product_id' => $product_id]);
        $order_date = $order->order_date;

        // übernommen aus autoresponder_handler line 698 ff

        /** @var digimember_UserData $model */
        $model = $this->api->load->model('data/user');
        $stored_password = $user_model->getPassword($user->ID);
        $password = '';
        if ($stored_password) {
            $password = $stored_password;
        } else if ($password) {
            $password = $model->setPassword($user->ID, $password, false);
        }

        $params = [
            'email' => $email,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'order_id' => $order_id,
            'order_date' => $order_date,
            'password' => $password,
        ];

        $response = $lib->postRequest($notify_url, $params);

        $have_error = $response->isError();
        if ($have_error) {
            throw new Exception($response->errorMsg());
        }
        return $response;
    }

    /**
     * ggf. völlig unnötig da wir ja keine wirkliche Oberklasse brauchen und das auch nicht unbedingt auswähl bar sein soll, sondern zapier lediglich intern als autoresponder gehandelt weredn soll
     * @return array
     */
    public function instructions()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function testLabel()
    {
        return 'Zapier';
    }
}