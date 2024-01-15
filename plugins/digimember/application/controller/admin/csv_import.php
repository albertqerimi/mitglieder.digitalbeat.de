<?php
$load->controllerBaseClass('admin/base');

/**
 * Class digimember_AdminCsvImportController
 */
class digimember_AdminCsvImportController extends ncore_AdminBaseController
{
    /**
     * @return string
     */
    protected function pageHeadline()
    {
        return _digi('CSV Import');
    }

    /**
     * @return string
     */
    protected function viewName()
    {
        return 'admin/csv_import';
    }

    /**
     * @return array
     */
    protected function viewData()
    {
        $data = parent::viewData();

        /** @var ncore_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');

        $data['locale'] = substr(get_locale(), 0, 2);
        $data['ajax_url'] = $linkLogic->ajaxUrl('admin/csv_import', 'ajaxRequest');
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model('logic/html');
        $htmlLogic->loadPackage('csv-import.js');

        return $data;
    }

    /**
     * @param $event
     * @param $response
     */
    protected function handleAjaxEvent($event, $response)
    {
        if ($event === 'ajaxRequest') {
            $json = json_decode(file_get_contents('php://input'), true);
            if (!$json) {
                $this->responseError('Malformed request');
            }

            $action = ncore_retrieve($json, 'action');
            if ($action === 'checkExisting') {
                $this->actionCheckExisting($json);
            } else if ($action === 'getProducts') {
                $this->actionGetProducts($json);
            } else if ($action === 'import') {
                $this->actionImport($json);
            } else {
                $this->responseError('Unknown action');
            }
        } else {
            $this->responseError('Unknown event');
        }
    }

    /**
     * @param array $json
     */
    private function actionImport($json)
    {
        /** @var array $data */
        $data = ncore_retrieve($json, 'data');
        if (!$data || !isset($data['products']) || !isset($data['rows']) || !isset($data['productIdMatches'])) {
            $this->responseError('Malformed data');
        }

        $productIds = join(',', ncore_retrieve($data, 'products'));
        $rows = ncore_retrieve($data, 'rows');
        if (!is_array($rows)) {
            $this->responseError('Malformed data');
        }
        /** @var array $productIdMatches */
        $productIdMatches = ncore_retrieve($data, 'productIdMatches');

        /** @var digimember_PaymentHandlerLib $library */
        $library = $this->api->load->library('payment_handler');
        $results = [];
        foreach ($rows as $row) {
            $id = ncore_retrieve($row, 'id');
            $email = ncore_retrieve($row, 'email');
            $firstName = ncore_retrieve($row, 'firstName');
            $lastName = ncore_retrieve($row, 'lastName');
            $orderId = ncore_retrieve($row, 'orderId');
            $paymentProvider = ncore_retrieve($row, 'paymentProvider', false);
            $password = ncore_retrieve($row, 'password');
            $productId = ncore_retrieve($row, 'productId');

            if (!$id || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $results[] = [
                    'id' => $id,
                    'state' => 'error',
                    'error' => _digi('%s is not a valid email address', $email),
                ];
            } else {
                $address = ['first_name' => $firstName, 'last_name' => $lastName];
                $order_id = $orderId;
                $password = $password ? $password : false;
                $userExists = ncore_getUserIdByEmail($email) !== false;
                try {
                    $userProductIds = $productIds;
                    if ($productId) {
                        foreach ($productIdMatches as $key => $val) {
                            if (strpos($productId, $val) !== false) {
                                $userProductIds .= ','.$key;
                            }
                        }
                    }
                    $library->signUp($email, $userProductIds, $address, $perform_login = false, $order_id, $password, $paymentProvider);
                    $results[] = [
                        'id' => $id,
                        'state' => 'success',
                        'success' => !$userExists,
                    ];
                } catch (Exception $e) {
                    $results[] = [
                        'id' => $id,
                        'state' => 'error',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        $this->responseSuccess($results);
    }

    /**
     * @param array $json
     */
    private function actionGetProducts($json)
    {
        /** @var digimember_ProductData $model */
        $model = $this->api->load->model('data/product');
        $products = $model->getAll(['published !=' => null, 'type !=' => 'download']);
        $this->responseSuccess($products);
    }

    /**
     * @param array $json
     */
    private function actionCheckExisting($json)
    {
        $existing = [];
        $array = ncore_retrieve($json, 'emailArray');
        if (!is_array($array)) {
            $this->responseError('Malformed data');
        }

        /** @var digimember_UserProductData $userProductModel */
        $userProductModel = $this->api->load->model('data/user_product');

        foreach ($array as $value) {
            if (is_array($value) && isset($value[0]) && strlen($value[0]) == 32 && isset($value[1]) && filter_var($value[1], FILTER_VALIDATE_EMAIL) !== true) {
                $userId = ncore_getUserIdByEmail($value[1]);
                if ($userId !== false) {
                    $userProducts = $userProductModel->getForUser($userId);
                    $userProductIds = [];
                    foreach ($userProducts as $userProduct) {
                        $userProductIds[] = $userProduct->product_id;
                    }
                    $existing[$value[0]] = $userProductIds;
                }
            }
        }

        $this->responseSuccess($existing);
    }

    private function responseHeader()
    {
        header('Content-Type: application/json');
    }

    /**
     * @param string $error
     */
    private function responseError($error)
    {
        $this->responseHeader();
        echo json_encode([
            'state' => 'error',
            'error' => $error,
        ]);
        die();
    }

    /**
     * @param array $data
     */
    private function responseSuccess($data)
    {
        $this->responseHeader();
        echo json_encode([
            'state' => 'success',
            'data' => $data,
        ]);
        die();
    }
}