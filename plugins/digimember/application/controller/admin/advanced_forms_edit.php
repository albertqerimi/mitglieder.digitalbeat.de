<?php
/** @var ncore_LoaderCore $load */
$load->controllerBaseClass('admin/base');

/**
 * Class digimember_AdminAdvancedSignupFormsController
 */
class digimember_AdminAdvancedFormsEditController extends ncore_AdminBaseController
{
    /**
     * @param $event
     *
     * @return bool
     */
    public function mustVerifyXssPassword($event)
    {
        return false;
    }

    /**
     * @return bool|mixed
     */
    protected function readAccessGranted()
    {
        return true;
    }

    /**
     * @return string
     */
    protected function pageHeadline()
    {
        return _ncore('Forms');
    }

    /**
     * @return string
     */
    protected function viewName()
    {
        return 'admin/advanced_forms';
    }

    /**
     * @return array
     */
    protected function viewData()
    {
        $data = parent::viewData();

        /** @var ncore_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model('logic/link');

        $data['formId'] = $_GET['id'];
        $data['locale'] = substr(get_locale(), 0, 2);
        $data['translations'] = json_encode($this->getTranslations());
        $data['ajax_url'] = $linkLogic->ajaxUrl('admin/advanced_forms_edit', 'ajaxRequest');
        $data['avatar_html_code'] = str_replace('\'', '"', ncore_userImage(ncore_userId(), 64));
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model('logic/html');
        $htmlLogic->loadPackage('advanced-forms-backend.js');

        return $data;
    }

    public function getTranslations() {
        $translations = array(
            "form" => array(
                "defaultName" => _ncore("New form"),
            ),
            "editor" => array(
                "controls" => array(
                    "general" => array(
                        "headline" => _ncore('Create new form'),
                        "name" => _ncore("Name"),
                        "namePlaceholder" => "Hier den Namen für das Formular eingeben",
                        "shortcode" => _ncore("Shortcode"),
                        "shortcodeTooltip" => _ncore("Place this shortcode somewhere on your Website to display the form."),
                        "shortcodePlaceholder" => _ncore("Please save the form to show the shortcode"),
                        "addProducts" => _ncore("Add Products"),
                        "addProductsTooltip" => _ncore("When the form is submitted an order will be placed with these products for the E-Mail adress that was used in the form. You are able to give away Freebies that way for example."),
                        "formWidth" => _ncore("Form width"),
                        "formWidthTooltip" => _ncore("You are able to set the width of your form in pixels or you use a percentage amount of the space available."),
                    ),
                    "pageEditor" => array(
                        "headline" => _ncore("Page settings"),
                        "useDefaultTitle" => _ncore("Use default label"),
                        "useDefaultBackground" => _ncore("Use transparent background"),
                        "useDefaultErrorColor" => _ncore("Use default error color"),
                        "borders" => _ncore("Borders"),
                        "bordersTooltip" => _ncore("Here you are able to set the width to full form width (default) or you define space for the left and right sides."),
                        "pageTitle" => _ncore("Title"),
                        "backgroundColor" => _ncore("Background color"),
                        "errorColor" => _ncore("Error color"),
                    ),
                    "displayElements" => array(
                        "headline" => _ncore("Display elements"),
                    ),
                    "inputElements" => array(
                        "headline" => _ncore("Input elements"),
                    ),
                    "lists" => array(
                        "emptyText" => _ncore("All elements used"),
                    ),
                    "footer" => array(
                        "saveButtonLabel" => _ncore("Save Changes"),
                        "backButtonLabel" => _ncore("Back"),
                    ),
                ),
                "elementEditor" => array(
                    "selectLevel" => _ncore("Select size"),
                    "useDefaultLabel" => _ncore("Use default label"),
                    "label" => _ncore("Label"),
                    "text" => _ncore("Text"),
                    "style" => _ncore("Style"),
                    "useDefaultSize" => _ncore("Use default size"),
                    "textColor" => _ncore("Text color"),
                    "backgroundColor" => _ncore("Background color"),
                    "borderColor" => _ncore("Border color"),
                    "textAlign" => _ncore("Align"),
                    "required" => _ncore("Input required"),
                    "width" => _ncore("Width"),
                    "height" => _ncore("Height"),
                    "useFixedRatio" => _ncore("Use fixed aspect ratio"),
                    "actionTooltip" => _ncore("Here you are able to configure what happens when the button gets clicked. The action only gets triggered when all required fields on the page are filled."),
                    "imageTooltip" => _ncore("Put in the URL of your image here. You will find the File-Url in your media library in the attachment details."),
                    "checkboxWithTextTooltip" => _ncore('Here you can put in information about your privacy policy or your terms of service. You can add links via HTML code %s.', '<a href=\"url\" target=\"_blank\">text</a>'),
                    "actions" => array(
                        "action" => _ncore("Action"),
                        "submitForm" => array(
                            "title" => _ncore("Submit form and"),
                            "actions" => array(
                                "message" => _ncore("Show message"),
                                "moveToPage" => _ncore("Open form page"),
                                "redirect" => _ncore("open url"),
                            ),
                        ),
                        "moveTo" => array(
                            "title" => _ncore("Open form page"),
                            "actions" => array(
                                "nextPage" => _ncore("the next page"),
                                "pageId" => _ncore("specific page"),
                            ),
                        ),
                        "reset" => array(
                            "title" => _ncore("Reset content"),
                            "actions" => array(
                                "form" => _ncore("complete form"),
                            ),
                        )
                    ),
                ),
                "elements" => array(
                    "input" => _ncore("Input field"),
                    "select" => _ncore("Select field"),
                    "button" => _ncore("Button"),
                    "image" => _ncore("Image"),
                    "url" => _ncore("Url"),
                    "headline" => _ncore("Headline"),
                    "text" => _ncore("Text"),
                    "divider" => _ncore("Divider"),
                    "spacer" => _ncore("Spacer"),
                    "checkboxwithtext" => _ncore("Checkbox with text"),
                    "page" => _ncore("Page"),
                    "form" => _ncore("Form"),
                    "product" => _ncore("Product"),
                    "headlinePlaceholder" => _ncore("Define headline here..."),
                    "textPlaceholder" => _ncore("Define Text here..."),
                    "editText" => _ncore("Edit"),
                    "copyText" => _ncore("Copy"),
                    "copiedText" => _ncore("Copied"),
                    "selectAction" => _ncore("Select action"),
                    "selectPage" => _ncore("Select page"),
                    "action" => _ncore("Action"),
                    "actions" => _ncore("Actions"),
                ),
                "notifications" => array(
                    "headlines" => array(
                        "emailMissing" => _ncore("E-mail field is missing!"),
                        "buttonMissing" => _ncore("Button is missing!"),
                        "productMissing" => _ncore("Product is missing!"),
                        "submitActionMissing" => _ncore("-Submit form- action is missing!"),
                        "formSaved" => _ncore("Form saved"),
                        "formLoaded" => _ncore("Form loaded"),
                    ),
                    "texts" => array(
                        "emailMissing" => _ncore("Please add an e-mail field to map the order correctly."),
                        "buttonMissing" => _ncore("Please add a button that has a -Submit- action."),
                        "productMissing" => _ncore("Please select a product that will be given away by the form."),
                        "submitActionMissing" => _ncore("At last one button in the form needs a -Submit form- action."),
                        "formSaved" => _ncore("The form was saved successfully."),
                        "formLoaded" => _ncore("The form was loaded successfully."),
                    )
                )
            ),
            "frontend" => array(
                "rules" => array(
                    "required" => _ncore("You have to fill this out."),
                    "email" => _ncore("Please enter a valid e-mail adress."),
                    "terms" => _ncore("Please agree to the terms."),
                    "number" => _ncore("Please put in a valid number."),
                    "stringmin" => _ncore("Please put in at least 3 letters."),
                )
            ),
        );
        return $translations;
    }

    public function maskDoubleQuotes ($string) {
        $output = str_replace('"', '\\\"', $string);
        $test = "";
        return $output;
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
                if (!$action = ncore_retrieve($_GET, 'action', false)) {
                    $this->responseError('Malformed request');
                }
            }
            else {
                if (!$action = ncore_retrieve($json, 'action', false)) {
                    $this->responseError('Malformed request');
                }
            }
            if ($action === 'getProducts') {
                $this->action_getProducts();
            } else if ($action === 'saveForm') {
                $this->action_saveForm(
                    ncore_retrieve($json, 'formData')
                );
            } else if ($action === 'loadForm') {
                $this->action_loadForm(
                    ncore_retrieve($_GET, 'id')
                );
            }
            else if ($action === 'getAppData') {
                $this->getAppData();
            }
            else if ($action === 'getMediaLibrary') {
                $this->getMediaLibrary();
            }
            else if ($action === 'submitForm') {
                $this->action_submitForm(
                    ncore_retrieve($json, 'formData')
                );
            }
            else {
                $this->responseError('Unknown action');
            }
        } else {
            $this->responseError('Unknown event');
        }
    }

    private function action_submitForm($formdata)
    {
        if (!is_array($formdata)) {
            $this->responseError('Malformed request');
        }

        $formId = $formdata['id'];
        $orderId = _ncore("Form")."_[".$formId."]";
        $data = $formdata['data'];
        $test = '';

        $parsedFormData = array();
        foreach ($data as $formElement) {
            $parsedFormData[$formElement['modelId']] = $formElement['dataValue'];
        }
        if (is_array($formdata['products']) && count($formdata['products']) > 0) {
            $parsedFormData['products'] = $formdata['products'];
            $parsedFormData['hasProducts'] = true;
        }
        else {
            $parsedFormData['products'] = array();
            $parsedFormData['hasProducts'] = false;
        }


        $email        = ncore_retrieve( $parsedFormData, 'email' );
        $first_name   = ncore_retrieve( $parsedFormData, 'firstname' );
        $last_name    = ncore_retrieve( $parsedFormData, 'lastname' );
        $is_confirmed = ncore_retrieve( $parsedFormData, 'terms' );
        $products = ncore_retrieve( $parsedFormData, 'products' );

        $product_ids = [];

        foreach ($products as $product) {
            $product_ids[] = $product['id'];
        }

        $customFieldsObj = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldsObj->getAllActive();
        $customFieldData = array();
        if (count($customFields) > 0) {
            foreach ($customFields as $customField) {
                if ($customField->visible === 'Y') {
                    $customFieldModelId = "customfield_".$customField->id;
                    if (array_key_exists($customFieldModelId, $parsedFormData)) {
                        $customFieldData[$customField->name] = $parsedFormData[$customFieldModelId];
                    }
                }
            }
        }

        $address = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
        );

        foreach ($customFieldData as $customFieldName => $customFieldValue) {
            $address[$customFieldName] = $customFieldValue;
        }


        $do_perform_login = $this->setting( 'login', false );

        $user_id = ncore_getUserIdByEmail( $email );
        $had_account = $user_id > 0;

        if ($had_account)
        {
            $had_admin_account = ncore_canAdmin($user_id);
        }
        else
        {
            /** @var ncore_IpLockData $model */
            $model = $this->api->load->model( 'data/ip_lock' );
            $is_locked = $model->isLocked( 'signup', 10, 86400 );
            if ($is_locked)
            {
                $this->responseError(_dgyou( 'Sorry, you have created too many accounts already.' ));
            }
        }
        $library = $this->api->load->library( 'payment_handler' );
        try
        {
            $welcome_msg_sent = $library->signUp( $email, $product_ids, $address, $do_perform_login, $orderId );
            $this->responseSuccess([
                'message' => 'FormData Submitted',
            ]);
        }
        catch (Exception $e)
        {
            $this->responseError($e->getMessage());
        }
    }

    private function action_saveForm($formdata, $silent = false)
    {
        if (!is_array($formdata)) {
            $this->responseError('Malformed request');
        }
        $id = ncore_retrieve($formdata, 'id');

        if (
            !is_numeric($id)
        ) {
            $this->responseError('Malformed request');
        }

        $errorCount = 0;
        if (isset($formdata['hasErrors'])) {
            $formHasErrors = $formdata['hasErrors'] ? "Y" : "N";
        }
        else {
            $formHasErrors = "N";
        }

        $formElements = $this->collectAndStripFormElements($formdata['pages']);
        $data = [
            'name' => $formdata['name'] !== "" ? $formdata['name'] : "Asf Form",
            'elementId' => $formdata['elementId'],
            'page_count' => count($formdata['pages']),
            'pages' => $formdata['pages'],
            'products' => $formdata['products'],
            'formDimensions' => $formdata['formDimensions'],
            'requiredElements' => $formdata['requiredElements'],
            'hasErrors' => $formHasErrors,
        ];

//        $dataJson = json_encode($data);
//        $formElementsJson = json_encode($formElements);
        //Is new
        if ($id == -1) {
            $id = $this->model()->create($data);
            $this->saveFormElements($id, $formElements);
        } else {
            $this->model()->update($id, $data);
            $this->saveFormElements($id, $formElements);
            //$this->responseError('Konnte nicht gespeichert werden.');
        }

        if (!$silent) {
            $this->responseSuccess([
                'id' => (int)$id,
                'shortcode' => '[ds_forms id="' . $id . '"]',
                'name' => $data['name'],
            ]);
        }

    }

    private function collectAndStripFormElements (&$pages) {
        $formElements = array();
        foreach ($pages as $pageKey => $page) {
            $pageElements = $page['elements'];
            foreach ($pageElements as $pageElementKey => $pageElement) {
                $formElements[$page['elementId']][] = $pageElement;
            }
            $pages[$pageKey]['elements'] = array();
        }
        return $formElements;
    }

    public function saveFormElements ($formId, $formElements) {
        $advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');

        $savedPageIds = $this->getSavedPageElementIdsByFormId($formId);

        foreach ($formElements as $pageElementId => $newPageElements) {
            if (($key = array_search($pageElementId, $savedPageIds)) !== false) {
                unset($savedPageIds[$key]);
            }
            $whereForSavedFormElementsOnPage = array(
                'formId' => $formId,
                'pageElementId' => $pageElementId
            );
            $savedFormElementsOnPage = $advancedFormsElementsModel->getAll($whereForSavedFormElementsOnPage);
            foreach ($savedFormElementsOnPage as $savedElementKey => $savedElement) {
                $removeElement = true;
                foreach ($newPageElements as $newPageElementKey => $newPageElement) {
                    if ($savedElement->elementId === $newPageElement['elementId']) {
                        //update
                        unset($newPageElement['id']);
                        $advancedFormsElementsModel->update($savedElement->id, $newPageElement);
                        unset($newPageElements[$newPageElementKey]);
                        $removeElement = false;
                    }
                }
                if ($removeElement) {
                    //remove savedElement because it was not found in submitted Elements
                    $advancedFormsElementsModel->delete($savedElement->id);
                }
            }
            //create new pageelements
            foreach ($newPageElements as $elementToCreate) {
                $elementToCreate['formId'] = $formId;
                $advancedFormsElementsModel->create($elementToCreate);
            }
        }

        //delete Elements of pages that are not in submit
        foreach ($savedPageIds as $pageIdToDelete) {
            $whereForSavedFormElementsOnPage = array(
                'formId' => $formId,
                'pageElementId' => $pageIdToDelete
            );
            $savedFormElementsOnPage = $advancedFormsElementsModel->getAll($whereForSavedFormElementsOnPage);
            foreach ($savedFormElementsOnPage as $savedPageELement) {
                $advancedFormsElementsModel->delete($savedPageELement->id);
            }
        }
    }

    private function getSavedPageElementIdsByFormId ($formId) {
        $pagesArray = array();
        $advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');
        $whereForSavedFormElementsOnForm = array(
            'formId' => $formId,
        );
        $savedFormElementsOnForm = $advancedFormsElementsModel->getAll($whereForSavedFormElementsOnForm);
        foreach ($savedFormElementsOnForm as $savedFormElement) {
            if (!in_array($savedFormElement->pageElementId, $pagesArray)) {
                $pagesArray[] = $savedFormElement->pageElementId;
            }
        }
        return $pagesArray;
    }

    private function action_loadForm($id)
    {
        $advancedFormsElementsModel = $this->api->load->model('data/advanced_forms_elements');
        if (!is_numeric($id)) {
            $this->responseError('Malformed request');
        }
        $formData = $this->model()->get($id);

        $this->api->load->helper('html_input');
        $digimemberProducts = ncore_resolveOptions('product');

        $where = array(
            'formId'    => $id,
        );
        $formElements = $advancedFormsElementsModel->getAll($where);

        foreach ($formElements as $formElement) {
            foreach ($formData->pages as $pageKey => $page) {
                if ($page['elementId'] === $formElement->pageElementId) {
                    $formData->pages[$pageKey]['elements'][] = (array) $formElement;
                }
            }
        }
        $formDataTouched = false;
        if (is_array($formData->products)) {
            foreach ($formData->products as $formProductKey => $formProduct) {
                if (!array_key_exists($formProduct["id"], $digimemberProducts)) {
                    unset($formData->products[$formProductKey]);
                    $formDataTouched = true;
                }
            }
            if ($formDataTouched) {
                if (count($formData->products) > 0) {
                    $this->action_saveForm((array) $formData, true);
                }
                else {
                    $formData->hasErrors = "Y";
                    $this->action_saveForm((array) $formData, true);
                }
            }
        }

        $this->responseSuccess([
            'id' => (int)$id,
            'formData' => $formData,
        ]);
    }

    private function getMediaLibrary() {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' =>'image',
            'post_status' => 'inherit',
            'posts_per_page' => 5,
            'orderby' => 'rand'
        );
        $query_images = new WP_Query( $args );
        $images = array();
        foreach ( $query_images->posts as $image) {
            $images[]= array(
                "id" => $image->ID,
                "url" => $image->guid,
            );
        }
        $this->responseSuccess($images);
    }

    private function getAppData() {
        $htmlElements = array(
            0 => array(
                "id" => "html_1",
                "title" => _ncore("Headline"),
                "description" => "",
                "placeholder" => _ncore("Define headline here..."),
                "type" => "title",
                "category" => "html",
                "subElements" => array("title"),
            ),
            1 => array(
                "id" => "html_2",
                "title" => _ncore("Text"),
                "description" => "",
                "placeholder" => _ncore("Define Text here..."),
                "type" => "text",
                "category" => "html",
                "subElements" => array("text"),
            ),
            2 => array(
                "id" => "email",
                "title" => _ncore("Mail"),
                "description" => _ncore("DigiMember default field"),
                "type" => "input",
                "category" => "digimember",
                "subElements" => array("label","input"),
                "requirements" => array(
                    array(
                        "rule" => array(
                            "key" => "products",
                            "operator" => ">",
                            "value" => 0,
                        ),
                        "trigger" => "ELEMENTS_EMAIL_ERROR"
                    )
                ),
                "rules" => array("required", "email"),
            ),
            3 => array(
                "id" => "firstname",
                "title" => _ncore("First name"),
                "description" => _ncore("DigiMember default field"),
                "type" => "input",
                "category" => "digimember",
                "subElements" => array("label","input"),
                "rules" => array("notRequired"),
            ),
            4 => array(
                "id" => "lastname",
                "title" => _ncore("Last name"),
                "description" => _ncore("DigiMember default field"),
                "type" => "input",
                "category" => "digimember",
                "subElements" => array("label","input"),
                "rules" => array("notRequired", "stringmin"),
            ),
            5 => array(
                "id" => "html_3",
                "title" => _ncore("Divider"),
                "description" => _ncore("A horizontal, black line"),
                "type" => "divider",
                "category" => "html",
                "subElements" => array("divider"),
            ),
            6 => array(
                "id" => "html_4",
                "title" => _ncore("Button"),
                "description" => "",
                "type" => "button",
                "category" => "html",
                "subElements" => array("button"),
            ),
            7 => array(
                "id" => "html_5",
                "title" => _ncore("Image"),
                "description" => "",
                "type" => "image",
                "category" => "html",
                "subElements" => array("image"),
            ),
            8 => array(
                "id" => "html_6",
                "title" => _ncore("Spacer"),
                "description" => _ncore("An empty space"),
                "type" => "void",
                "category" => "html",
                "subElements" => array("void"),
            ),
            9 => array(
                "id" => "terms",
                "title" => _ncore("Checkbox with text"),
                "description" => _ncore("Acknowledgements of Terms of service for example"),
                "type" => "bool",
                "category" => "digimember",
                "subElements" => array("bool"),
                "requirements" => array(
                    array(
                        "rule" => array(
                            "key" => "products",
                            "operator" => ">",
                            "value" => 0,
                        ),
                        "trigger" => "ELEMENTS_TERMSCHECK_WARNING"
                    )
                ),
                "rules" => array("termsrequired"),
            ),
        );
        $this->api->load->helper('html_input');
        $productModel = $this->api->load->model( 'data/product' );
        $products = $productModel->getAll();
        $customFieldsModel = $this->api->load->model('data/custom_fields');
        $customFields = $customFieldsModel->getAllActive();

        $appData = array(
            "customfields" => array_map(function ($obj) {
                if ($obj->type == 'select' && $obj->content_type == 'country') {
                    $customFieldsModel = $this->api->load->model('data/custom_fields');
                    $countryList = (array) $customFieldsModel->getCountrySelectOptionsAsJavascriptArray();
                    return [
                        "id" => "customfield_".$obj->id,
                        "title" => $obj->label,
                        "description" => _ncore("DigiMember custom field"),
                        "name" => $obj->name,
                        "type" => $obj->type,
                        "options" => $countryList,
                        "category" => "customfield",
                        "subElements" => array("label",$this->getSubElementTypeByType($obj->type)),
                        "rules" => array("notRequired"),
                    ];
                }
                elseif ($obj->type == 'select' && $obj->content_type == 'custom') {
                    $customFieldsModel = $this->api->load->model('data/custom_fields');
                    //$countryList = (array) $customFieldsModel->getCustomSelectOptionsAsJavascriptArray($obj->content);
                    $entryList = (array) $customFieldsModel->getCustomSelectOptionsAsJavascriptArray($obj->content);
                    return [
                        "id" => "customfield_".$obj->id,
                        "title" => $obj->label,
                        "description" => _ncore("DigiMember custom field"),
                        "name" => $obj->name,
                        "type" => $obj->type,
                        "options" => $entryList,
                        "category" => "customfield",
                        "subElements" => array("label",$this->getSubElementTypeByType($obj->type)),
                        "rules" => array("notRequired"),
                    ];
                }
                return [
                    "id" => "customfield_".$obj->id,
                    "title" => $obj->label,
                    "description" => _ncore("DigiMember custom field"),
                    "name" => $obj->name,
                    "type" => $obj->type,
                    "category" => "customfield",
                    "subElements" => array("label",$this->getSubElementTypeByType($obj->type)),
                    "rules" => array("notRequired"),
                ];
            }, $customFields),
            "products" => array_map(function ($obj) {
                return [
                    'id' => (int)$obj->id,
                    'name' => $obj->name,
                    'type' => $obj->type,
                    'elementType' => 'PRODUCT',
                    "category" => "product",
                ];
            }, $products),
            "htmlelements" => $htmlElements,
        );
        $this->responseSuccess($appData);
    }

    function getSubElementTypeByType($type) {
        $mapping = array(
            "text" => "input",
        );
        if (array_key_exists($type,$mapping)) {
            return $mapping[$type];
        }
        return $type;
    }

    private function action_getProducts()
    {
        $this->api->load->helper('html_input');
        $products = ncore_resolveOptions('product');

        $this->responseSuccess($products);
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

    /**
     * @return digimember_ShortcodeDesignData
     */
    private function model()
    {
        /** @var digimember_ShortcodeDesignData $model */
        $model = $this->api->load->model('data/advanced_forms');
        return $model;
    }
}