<?php

$load->controllerBaseClass( 'admin/base' );

abstract class ncore_AdminChecklistController extends ncore_AdminBaseController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );

        $this->api->load->helper( 'url' );
    }

    protected function viewName()
    {
        return 'admin/checklist';
    }

    protected function viewData()
    {
        $this->initJs();

        $data = parent::viewData();

        $points = $this->createAllPoints();

        $data[ 'checklist' ] = $points;

        list( $total, $completed ) = $this->completedCount();
        $progress = round( 100*$completed / $total );
        $data[ 'progress' ] = $progress;

        return $data;
    }

    protected function renderOnePoint( $point )
    {
        $html = "<div title=\"$point->tooltip\" class='ncore_big_checkbox $point->css'>
                    <div class='ncore_label'>$point->label</div>
                </div>";

        foreach ($point->description as $one)
        {
            $html .= "<div class='ncore_description'>$one</div>\n";
        }

        return $html;
    }

    private $points = false;
    private function createAllPoints()
    {
        if ($this->points === false)
        {
            $this->points = array();

            $metas = $this->checklistMetas();

            foreach ($metas as $meta)
            {
                $point = $this->createPoint( $meta );

                $this->points[] = $point;
            }
        }

        return $this->points;
    }

    private function completedCount()
    {
        $points = $this->createAllPoints();

        $completed = 0;
        foreach ($points as $one)
        {
            if ($one->completed)
            {
                $completed++;
            }
        }

        $total = count( $points );

        return array( $total, $completed );
    }

    private function createPoint( $meta )
    {
        $html_id = $this->pointHtmlId( $meta );
        $label = $meta['label'];

        list( $checked, $writable ) = $this->_evalDoneBy( $meta );

        $point = new stdClass();
        $point->writable = $writable;
        $point->label = $label;

        $point->css = $checked ? 'checked' : '';
        if ($writable)
        {
            $point->css .= ' writable';

            $tooltip = $checked
                     ? _ncore( 'Completed - click to mark as uncompleted' )
                     : _ncore( 'Click to mark as completed' );
        }
        else
        {
            $tooltip = $checked
                     ? _ncore( 'Completed' )
                     : _ncore( 'Not yet completed' );
        }

        $point->id = $html_id;
        $point->completed = (bool) $checked;
        $point->tooltip = $tooltip;
        $point->description = $this->renderDescription( $meta );

        return $point;
    }


    abstract protected function checklistMetas();

    protected function ajaxEventHandlers()
    {
        $handlers = parent::ajaxEventHandlers();

        $handlers['checkboxClicked'] = 'handleAjaxClickEvent';

        return $handlers;
    }

    protected function handleAjaxClickEvent( $response )
    {
        $div_id = $this->ajaxArg( 'div_id' );

        $key = $this->pointKey( $div_id );
        $meta = $this->getMetaByKey( $key );

        $this->toggleCheckbox( $meta );

        $point = $this->createPoint( $meta );

        if (!$point)
        {
            $response->error( 'Internal error' );
            return;
        }

        $html = $this->renderOnePoint( $point );

        $response->html( $div_id, $html );

        $js = $this->jsOnload();

        $response->js( $js );
    }

    private function getMetaByKey( $meta_key )
    {
        $metas = $this->checklistMetas();
        foreach ($metas as $one)
        {
            if ($one['key'] == $meta_key)
            {
                return $one;
            }
        }

        return false;
    }

    private function _actionUrl( $meta )
    {
        $page = ncore_retrieve( $meta, 'page' );

        if ($page)
        {
            $link = $this->api->load->model('logic/link' );
            return $link->adminPage( $page );
        }

        return '';
    }

    private function _evalDoneBy( $meta )
    {

        $done_by = explode( ',', $meta[ 'done_by'] );
        $has_manual = false;
        $done = true;
        foreach ($done_by as $one)
        {
            if ($one === 'manual')
            {
                $has_manual = true;
                $done = false;

                $y_n_done_manually = $this->_doneManual( $meta );
                if ($y_n_done_manually !=  '')
                {
                    $done = $y_n_done_manually == 'Y';
                    break;
                }
                continue;
            }

            $function = $one;
            if (!$this->$function())
            {
                $done = false;
            }
        }

        return array( $done, $has_manual );
    }

    private function _doneManual( $meta, $newValue=null )
    {
        /** @var digimember_BlogConfigLogic $model */
        $model = $this->api->load->model( 'logic/blog_config' );
        $key = 'done_' . $meta['key'];

        if (isset($newValue))
        {
            $newValue = $newValue === 'N' || $newValue === false
                      ? 'N'
                      : 'Y';

            $model->set( $key, $newValue );
        }

        $y_n_done = $model->get( $key );

        return $y_n_done;
    }

    private function jsOnload()
    {
        list( $total, $completed ) = $this->completedCount();

        return "ncoreJQ( 'div.ncore_big_checkbox.writable' ).off('click').click( ncore_toggleBigCheckbox ); ncore_setChecklistProgressBar( $total, $completed );";
    }

    private function initJs()
    {
        $html = $this->api->load->model( 'logic/html' );
        $js_onload = $this->jsOnload();
        $html->jsOnLoad( $js_onload );


        $event = 'checkboxClicked';
        $js_ajax_cb = $this->renderAjaxJs('checkboxClicked', $params=array(),'data');

        $js_functions = array();

        $js_functions[] = "

function ncore_toggleBigCheckbox() {
var div_id = ncoreJQ( this ).parent().attr('id');
var data = { 'div_id': div_id };
$js_ajax_cb
}";

        $js_functions[] = "
function ncore_setChecklistProgressBar( total, completed )
{
    var percent = Math.round( 100 * completed / total );
    ncoreJQ( '#ncore_checklist_progressbar' ).width( percent + '%' );
    ncoreJQ( 'div.checklist_progress_bar_percentage' ).html( percent + '%' );
    if (completed == total)
        ncoreJQ( 'div.checklist_progress_bar_completed' ).fadeIn();
    else
        ncoreJQ( 'div.checklist_progress_bar_completed' ).fadeOut();
}

";

        $html->jsFunction( $js_functions );
    }

    private function pointHtmlId( $meta )
    {
        $div_id = 'ncore_checklist_' . ncore_retrieve( $meta, 'key' );
        return $div_id;
    }

    private function pointKey( $div_id )
    {
        $key = str_replace( 'ncore_checklist_', '', $div_id );
        return$key;
    }

    private function toggleCheckbox( $meta )
    {
        $is_writable = in_array( 'manual', explode( ',', ncore_retrieve( $meta, 'done_by' ) ) );
        if (!$is_writable)
        {
            return '';
        }

        $old_checked = $this->_doneManual( $meta ) === 'Y';
        $new_checked = !$old_checked;

        $this->_doneManual( $meta, $new_checked );
    }

    private function renderDescription( $meta )
    {
        $description = ncore_retrieve( $meta, 'description' );
        $url = $this->_actionUrl( $meta );

        if (!$description)
        {
            return array();
        }

        $have_array = is_array( $description );
        if (!$have_array)
        {
            $description = array( $description );
        }

        $have_link = false;
        foreach ($description as $one)
        {
            if (strpos( $one, '<a>' ) !== false)
            {
                $have_link = true;
                break;
            }
        }

        if (!$url)
        {
            // empty
        }
        elseif ($have_link)
        {
            foreach ($description as $index => $one)
            {
                $description[ $index ] = ncore_linkReplace( $one, $url, false );
            }
        }
        else
        {
            $append_to_line = count( $description ) == 1;
            if ($append_to_line)
            {
                $description[0] .= " <a href='$url'>" . _ncore( 'Click here.' ) . '</a>';
            }
            else
            {
                $description[] = "<a href='$url'>" . _ncore( 'Click here.' ) . '</a>';
            }
        }

        return $description;
    }

}



















/*
 *
 * Example controller
 *


<?php

$load->controllerBaseClass( 'admin/checklist' );

class ncore_AdminChecklistController extends ncore_AdminChecklistController
{
    protected function pageHeadline()
    {
        return _ncore( 'Checklist' );
    }

    protected function checklistMetas()
    {
        $link = $this->api->load->model('logic/link' );

        $controller = $this->api->load->controller( 'shortcode' );
        $login_shortcode = $controller->shortCode( 'login' );
        $login_shortcode_tag = $controller->renderTag( $login_shortcode );

        $create_page_url = $link->createPageUrl();
        $shortcode_url = $link->adminPage( 'shortcode' ) . '#' . $login_shortcode;
        $buy_url = $link->buyUrl();

        $msg_done = ' ' . _ncore('Then click on the checkbox to mark this point as completed.' );

        $ds24 = $this->api->load->model( 'logic/digistore_connector' );
        list( $type, $message ) = $ds24->renderStatusNotice( 'button', 'link' );
        $connect_button = $type == NCORE_NOTIFY_SUCCESS
                        ? ''
                        : '&nbsp;'.$message;

        return array(

            array(
                'label' => _ncore( 'Product created' ),
                'done_by' => 'doneProduct',
                'page' => 'products',
                'key' => 'product',
                'description' => _ncore( 'Create at least one product you want to sell.' ),
            ),
            array(
                'label' => _ncore( 'Payment provider integrated' ),
                'done_by' => 'donePaymentprovider',
                'page' => false,
                'key' => 'payment',
                'description' => _ncore( 'For automated payment handling, you need to setup a payment provider like %s.',
                    '<a href="https://www.digistore24.com" target="_blank">Digistore24.com</a>' ) . $connect_button,
            ),
            array(
                'label' => _ncore( 'Protected content created' ),
                'done_by' => 'manual',
                'key' => 'content_created',
                'description' => ncore_linkReplace( _ncore( 'You need to create content pages, which the buyers of your product(s) will get access to. <a>Click here</a> to add your content to Wordpess.' ), $create_page_url, false ) . $msg_done,
                'page' => false,
            ),
            array(
                'label' => _ncore( 'Protected content to product assigned' ),
                'done_by' => 'doneContentAssigned',
                'page' => 'content',
                'key' => 'content_assigned',
                'description' => _ncore( 'Assign Wordpress pages to you product. These pages will only be visible by users with access to the product.' ),
            ),
            array(
                'label' => _ncore( 'Welcome email reviewed' ),
                'done_by' => 'manual',
                'page' => 'mails',
                'key' => 'mail_created',
                'description' => _ncore( 'Adjust the text of the welcome email to your customers. <a>Click here</a> to edit the welcome email. You have a welcome email each product. Use the <em>Send Test Email</em> button to review the welcome mail in your mail software.' ),
            ),
            array(
                'label' => _ncore( 'Login page created' ),
                'done_by' => 'manual',
                'key' => 'login_page_created',
                'description' => ncore_linkReplace( _ncore( '<a>Create a page</a> and add the <a>shortcode %s</a> for a login box. Make sure this page is linked in the menu on the home page, so that users can easily login.', $login_shortcode_tag ), $create_page_url, $shortcode_url, false )
                . $msg_done,
            ),
            array(
                'label' => _ncore( 'Paylink integrated on salespage' ),
                'done_by' => 'manual',
                'key' => 'paylink_integrated',
                'pge' => false,
                'description' => _ncore( 'Get your paylink from your payment provider. Put a buy button on your salespage, which follows this link.') . $msg_done
            ),
            array(
                'label' => _ncore( 'Created test membership' ),
                'done_by' => 'manual,doneTestMembershipCreated',
                'page' => 'orders',
                'key' => 'order_created',
                'description' => _ncore( '<a>Click here</a> and then on <em>Create</em> to add a member for testing. Don\'t use the admin email, because the admin account has too much privileges for testing.') . $msg_done
            ),
            array(
                'label' => _ncore( 'Membership login tested' ),
                'done_by' => 'manual',
                'key' => 'order_tested',
                'page' => 'orders',
                'description' => array(
                    _ncore( 'For your convenience use a second browser, if you have one. If so, in your first browser stay logged in as admin. In the second browser stay logged in as the buyer. As buyer, browse to the blog\'s home page and log into the test account. Review the pages assigned to your product and make sure, the buyer has access to the right pages. Make sure, he has no access, when he is logged out.' ),
                    _ncore( 'If you use time based unlocking of pages, you may test this in the following way: As admin <a>edit the membership</a> and set the order date to a date in the past. Then view the pages as the buyer. This helps you to validate, that the pages are unlocked in the correct order.'),
                    $msg_done )
            ),

            array(
                'label' => _ncore( 'Test sale completed' ),
                'done_by' => 'manual',
                'key' => 'test_sale',
                'page' => false,
                'description' => array(
                    _ncore( 'On your payment providers salespage perform a test sale. Usually this can be done without actually paying. E.g. with Digistore24 you have a test pay button, when you are logged in and view your own orderform page.' ),
                    _ncore( '<strong>Important:</strong> When performing the test sale, don\'t use your wordpress admin email address. Use an email address which is not registered with the blog. This is important, because the admin has too much priviliges for testing. And if the user already exists, DigiMember assigns the product to the existing user, but for the test it\'s important to test a completely new sale.'),
                    $msg_done )
            ),

            array(
                'label' => _ncore( 'Upgrade to %s', $this->api->pluginNamePro() ),
                'description' => _ncore( '<a>Click here</a> and enter the %s license key for this domain.', $this->api->pluginNamePro() )
                              . ' '
                              . ncore_linkReplace( _ncore('If you don\'t have a license key, <a>click here</a> to get one.'), $buy_url, false ),
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
*/