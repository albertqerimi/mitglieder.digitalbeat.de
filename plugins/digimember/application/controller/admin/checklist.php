<?php

$load->controllerBaseClass( 'admin/checklist' );

class digimember_AdminChecklistController extends ncore_AdminChecklistController
{
    protected function pageHeadline()
    {
        return _digi( 'Checklist' );
    }

    protected function checklistMetas()
    {
        /** @var digimember_LinkLogic $link */
        $link = $this->api->load->model('logic/link' );

        $controller = $this->api->load->controller( 'shortcode' );
        $login_shortcode = $controller->shortCode( 'login' );
        $login_shortcode_tag = $controller->renderTag( $login_shortcode );

        $create_page_url = $link->createPageUrl();
        $shortcode_url = $link->adminPage( 'shortcode' ) . '#' . $login_shortcode;
        $buy_url = $link->buyUrl();

        $msg_done = ' ' . _digi('Then click on the checkbox to mark this point as completed.' );

        $ds24 = $this->api->load->model( 'logic/digistore_connector' );
        list( $type, $message ) = $ds24->renderStatusNotice( 'button', 'link' );
        $connect_button = $type == NCORE_NOTIFY_SUCCESS
                        ? ''
                        : '&nbsp;'.$message;

        return array(

            array(
                'label' => _digi( 'Product created' ),
                'done_by' => 'doneProduct',
                'page' => 'products',
                'key' => 'product',
                'description' => _digi( 'Create at least one product you want to sell.' ),
            ),
            array(
                'label' => _digi( 'Payment provider integrated' ),
                'done_by' => 'donePaymentprovider',
                'page' => false,
                'key' => 'payment',
                'description' => _digi( 'For automated payment handling, you need to setup a payment provider like %s.',
                    '<a href="https://www.digistore24.com" target="_blank">Digistore24.com</a>' ) . $connect_button,
            ),
            array(
                'label' => _digi( 'Protected content created' ),
                'done_by' => 'manual',
                'key' => 'content_created',
                'description' => ncore_linkReplace( _digi( 'You need to create content pages, which the buyers of your product(s) will get access to. <a>Click here</a> to add your content to Wordpess.' ), $create_page_url, false ) . $msg_done,
                'page' => false,
            ),
            array(
                'label' => _digi( 'Protected content to product assigned' ),
                'done_by' => 'doneContentAssigned',
                'page' => 'content',
                'key' => 'content_assigned',
                'description' => _digi( 'Assign Wordpress pages to your product. These pages will only be visible by users with access to the product.' ),
            ),
            array(
                'label' => _digi( 'Welcome email reviewed' ),
                'done_by' => 'manual',
                'page' => 'mails',
                'key' => 'mail_created',
                'description' => _digi( 'Adjust the text of the welcome email to your customers. <a>Click here</a> to edit the welcome email. You have a welcome email each product. Use the <em>Send Test Email</em> button to review the welcome mail in your mail software.' ),
            ),
            array(
                'label' => _digi( 'Login page created' ),
                'done_by' => 'manual',
                'key' => 'login_page_created',
                'description' => ncore_linkReplace( _digi( '<a>Create a page</a> and add the <a>shortcode %s</a> for a login box. Make sure this page is linked in the menu on the home page, so that users can easily login.', $login_shortcode_tag ), $create_page_url, $shortcode_url, false )
                . $msg_done,
            ),
            array(
                'label' => _digi( 'Paylink integrated on salespage' ),
                'done_by' => 'manual',
                'key' => 'paylink_integrated',
                'pge' => false,
                'description' => _digi( 'Get your paylink from your payment provider. Put a buy button on your salespage, which follows this link.') . $msg_done
            ),
            array(
                'label' => _digi( 'Created test membership' ),
                'done_by' => 'manual,doneTestMembershipCreated',
                'page' => 'orders',
                'key' => 'order_created',
                'description' => _digi( '<a>Click here</a> and then on <em>Create</em> to add a member for testing. Don\'t use the admin email, because the admin account has too much privileges for testing.') . $msg_done
            ),
            array(
                'label' => _digi( 'Membership login tested' ),
                'done_by' => 'manual',
                'key' => 'order_tested',
                'page' => 'orders',
                'description' => array(
                    _digi( 'For your convenience use a second browser, if you have one. If so, in your first browser stay logged in as admin. In the second browser stay logged in as the buyer. As buyer, browse to the blog\'s home page and log into the test account. Review the pages assigned to your product and make sure, the buyer has access to the right pages. Make sure, he has no access, when he is logged out.' ),
                    _digi( 'If you use time based unlocking of pages, you may test this in the following way: As admin <a>edit the membership</a> and set the order date to a date in the past. Then view the pages as the buyer. This helps you to validate, that the pages are unlocked in the correct order.'),
                    $msg_done )
            ),

            array(
                'label' => _digi( 'Test sale completed' ),
                'done_by' => 'manual',
                'key' => 'test_sale',
                'page' => false,
                'description' => array(
                    _digi( 'On your payment providers salespage perform a test sale. Usually this can be done without actually paying. E.g. with Digistore24 you have a test pay button, when you are logged in and view your own orderform page.' ),
                    _digi( '<strong>Important:</strong> When performing the test sale, don\'t use your wordpress admin email address. Use an email address which is not registered with the blog. This is important, because the admin has too much priviliges for testing. And if the user already exists, DigiMember assigns the product to the existing user, but for the test it\'s important to test a completely new sale.'),
                    $msg_done )
            ),

            array(
                'label' => _digi( 'Upgrade to %s', $this->api->pluginNamePro() ),
                'description' => _digi( '<a>Click here</a> and enter the %s license key for this domain.', $this->api->pluginNamePro() )
                              . ' '
                              . ncore_linkReplace( _digi('If you don\'t have a license key, <a>click here</a> to get one.'), $buy_url, false ),
                'done_by' => 'doneLicensekey',
                'page' => 'settings',
                'key' => 'license',
            ),
    );

    }

    //
    // step validation functions
    //

    protected function doneLicensekey()
    {
        $lib = $this->api->loadLicenseLib();

        $license_valid = $lib->licenseStatus() == NCORE_LICENSE_VALID;

        return $license_valid;
    }

    protected function doneProduct()
    {
        $model = $this->api->load->model( 'data/product' );
        return $model->setupChecklistDone();
    }

    protected function donePaymentprovider()
    {
        $model = $this->api->load->model( 'logic/digistore_connector' );
        if ($model->setupChecklistDone()) {
            return true;
        }

        $model = $this->api->load->model( 'data/payment' );
        return $model->setupChecklistDone();
    }

    protected function doneContentAssigned()
    {
        $model = $this->api->load->model( 'data/page_product' );
        return $model->setupChecklistDone();
    }

    protected function doneTestMembershipCreated()
    {
        return false;
    }


}