<?php

$load->controllerBaseClass( 'post/meta' );

class digimember_PostShortcodesController extends ncore_Controller
{

	protected function ajaxEventHandlers()
	{
		$handlers = parent::ajaxEventHandlers();
		$handlers['dialog'] = 'showDialog';
        $handlers['list'] = 'listShortcodes';
        $handlers['add'] = 'addShortcode';
		return $handlers;
	}

	protected function showDialog( $response )
	{
		$ajax_meta = $this->ajaxDialogMeta();
		$ajax = $this->api->load->library( 'ajax' );
		$dialog = $ajax->dialog( $ajax_meta );
		$dialog->setAjaxResponse( $response );
	}

	protected function listShortcodes($response) {
        $shortcodeModel = $this->api->load->model('data/shortcodes');
        $shortcodeList = $shortcodeModel->getAll( [], $limit="0,10", $order_by='id DESC' );
        $html = '';
        foreach ($shortcodeList as $shortcode) {
            $html .= $this->generateShortcodeHtml($shortcode, $shortcode->id);
        }
        if ($html == '') {
            $html = '<div class="dm-formbox-item dm-row dm-middle-xs"><div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-11">'._digi( 'No shortcodes added until now.' ).'</div></div>';
        }
        $response->html('shortcodes-recent-list', $html);
        $response->success('Shortcode list loaded.');
        $response->output();
    }

    protected function addShortcode($response) {
	    if (isset($_POST['shortcode']) && $_POST['shortcode'] != '') {
	        $shortcodeModel = $this->api->load->model('data/shortcodes');
	        $id = $shortcodeModel->create(array(
	            "shortcode" => $_POST['shortcode'],
            ));
	        $lastAdded = $shortcodeModel->get($id);
	        $response->html('shortcodes-recent-list', $this->generateShortcodeHtml($lastAdded, $id));
            $response->success('Shortcode has been added.');
        }
	    else {
	        $response->error('No Shortcode submitted');
        }
        $response->output();
    }


    protected function generateShortcodeHtml($shortcode, $id) {
        $this->api->load->helper( 'date' );
        $date_unix = strtotime( $shortcode->created );
        $long_date =  ncore_formatDate( $date_unix ).' - '.ncore_formatTime( $date_unix );
	    $html = '';
	    $html .= '<div class="dm-formbox-item dm-row dm-middle-xs">';
            $html .= '<div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-11">';
                $html .= $long_date;
            $html .= '</div>';
            $html .= '<div class="dm-col-md-3 dm-col-sm-4 dm-col-xs-11">';
                $html .= "<a onClick=\"ncore_copyTooltipInputToClipboard(event,this); ncore_switchElementAttribute(this,'data-dm-tooltip','"._ncore('Copied...')."','"._ncore('Copy to clipboard')."', 1000);\" id=\"shortcode_tooltip_'.$id.'\" data-dm-tooltip=\""._ncore('Copy to clipboard')."\" href=\"\" class=\"dm-tooltip-simple\" style='color: rgb(58,65,73); text-decoration: none;'>";
                    $html .= "<input size=\"26\" readonly=\"readonly\" class=\"ncore_code ncore_select_all dm-input dm-fullwidth dm-tooltip-simple\" value=\"".htmlentities($shortcode->shortcode)."\" type=\"text\" name=\"dummy\">";
                $html .= "</a>";
            $html .= '</div>';
        $html .= '</div>';
        return $html;

    }

    private function ajaxDialogMeta()
    {
        $this->api->load->helper( 'array' );

        /** @var digimember_ShortCodeController $controller */
        $controller = $this->api->load->controller( 'shortcode' );

        $shortcode_metas = $controller->getShortcodeMetas();

        foreach ($shortcode_metas as $index => $one)
        {
            if (!empty($one['hide'])) {
                unset($shortcode_metas[$index]);
            }
        }

        $shortcode_options = ncore_listToArray( $shortcode_metas, 'tag', 'rendered', 'section' );

        $cb_js_code = "dmShortcodes.modalCallback(form_id)";

        $form_metas = array();

        $form_metas[] = array(
            'name' => 'shortcode',
            'type' => 'select',
            'label' => _ncore('Shortcode' ),
            'options' => $shortcode_options,
        );

        foreach ($shortcode_metas as $one)
        {
            $tag = $one['tag'];
            $description = $one['description'];

            $form_metas[] = array(
                'label' => _ncore('Description'),
                'type' => 'html',
                'html' => $description,
                'depends_on' => array( 'shortcode' => $tag ),
            );

            $arg_metas = ncore_retrieve( $one, 'args' );
            foreach ($arg_metas as $arg)
            {
                $is_hidden = ncore_retrieve( $arg, 'hide', false );
                if ($is_hidden) {
                    continue;
                }

                $is_hidden = !empty( $arg[ 'is_only_for' ] )
                    && str_replace( '_', '', $arg[ 'is_only_for' ] ) !== 'shortcode';
                if ($is_hidden) {
                    continue;
                }

                $depends_on = ncore_retrieve( $arg, 'depends_on', array() );
                $depends_on[ 'shortcode' ] = $tag;
                $arg['depends_on'] = $depends_on;

                $arg['css'] = 'ncore_shortcode_'.$tag;

                if ($arg['type']  == 'url')
                {
                    $arg['size'] = 40;
                }

                $form_metas[] = $arg;
            }
        }

        /** @var digimember_LinkLogic $model */
        $model = $this->api->load->model( 'logic/link' );
        $url = $model->adminPage( 'shortcode' );
        $form_metas[] = array(
            'type' => 'html',
            'html' => ncore_linkReplace( _digi( 'For more infos <a>click here</a>.' ), $url, $asPopup=true ),
        );



        return array(   'type'          => 'form',
            'cb_js_code'    => $cb_js_code,
            'close_on_ok'   => true,
            'title'         => _digi( 'DigiMember Shortcode' ),
            'form_sections' => array(),
            'form_inputs'   => $form_metas,
            'width'         => '800px',
            'height'        => '600px',
            'dialogClass'   => 'dm-shortcode-dialog',
        );
    }
}