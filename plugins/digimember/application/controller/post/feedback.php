<?php

$load->controllerBaseClass( 'post/meta' );

class digimember_PostFeedbackController extends ncore_Controller
{

	protected function ajaxEventHandlers()
	{
		$handlers = parent::ajaxEventHandlers();
		$handlers['deactivate'] = 'handleDeactivate';
        $handlers['send'] = 'handleSend';
		return $handlers;
	}

	protected function handleDeactivate( $response )
	{
		$ajax_meta = $this->ajaxDialogMeta();
		$ajax = $this->api->load->library( 'ajax' );
		$dialog = $ajax->dialog( $ajax_meta );
		$dialog->setAjaxResponse( $response );
	}

	protected function handleSend()
    {
        $mailer = $this->api->load->library( 'mailer' );
        if (ncore_retrieve($_POST, 'nofeedback',false)) {
            $mailer->to( NCORE_DEACTIVATE_EMAIL );
            $mailer->subject( 'DigiMember Deaktiviert' );
            $mailer->html( '<h3>Kein Feedback abgegeben.</h3>' );
        }
        else {
            list($lang,$country) = explode('_',get_locale());
            $pluginversion = $this->api->edition();
            $feedbackChecks = explode(',', ncore_retrieve($_POST,'ncore_feedback_checklist',''));
            $doesNotFitMessage = ncore_retrieve($_POST, 'ncore_does_not_fit_to_ideas','');
            $feedbackMessage = ncore_retrieve($_POST, 'ncore_feedback_message','');
            $feedbackOptin = ncore_retrieve($_POST, 'ncore_feedback_optin','N');
            $feedbackEmail = ncore_retrieve($_POST, 'ncore_feedback_email','');
            $feedbackMessage = str_replace('"', "\\\"", $feedbackMessage);
            $feedbackData = array(
                'feedbackChecks' => $feedbackChecks,
                'feedbackDoesNotFir' => $doesNotFitMessage,
                'feedbackMessage' => $feedbackMessage,
                'feedbackOptin' => $feedbackOptin,
                'feedbackEmail' => $feedbackEmail,
                'digimember_version' => $pluginversion,
                'blog_language' => $lang
            );
            $body_html = '<h3>Feedback Mail auf Grund von Deaktivierung Digimember Free Version.</h3>';
            $body_html .= '<table><tr><td>';
            $body_html .= '<p><b>Folgende Checks wurden gesetzt:</b></p>';
            $body_html .= '</td></tr>';
            foreach ($feedbackChecks as $feedbackCheck) {
                $body_html .= '<tr><td><p>'.$feedbackCheck.'</p></td></tr>';
            }
            if ($feedbackChecks[0] === '') {
                $body_html .= '<tr><td><p>no_feedback</p></td></tr>';
            }

            if ($doesNotFitMessage != '') {
                $body_html .= '<tr><td><p><b>Konte Ideen nicht umsetzen weil:</b></p></td></tr>';
                $body_html .= '<tr><td><p>'.$doesNotFitMessage.'</p></td></tr>';
            }

            if ($feedbackMessage != '') {
                $body_html .= '<tr><td><p><b>Feedback Mitteilung:</b></p></td></tr>';
                $body_html .= '<tr><td><p>'.$feedbackMessage.'</p></td></tr>';
            }
            $body_html .= '<tr><td><p><b>Kontakterlaubnis erteilt:</b></p></td></tr>';
            $body_html .= '<tr><td>';
            $body_html .= $feedbackOptin == 'Y' ? 'Ja' : 'Nein';
            $body_html .= '</td></tr>';
            if ($feedbackOptin == 'Y') {
                $body_html .= '<tr><td><b><p>Angegebene E-mail:</p></b></td></tr>';
                $body_html .= '<tr><td><p>'.$feedbackEmail.'</p></td></tr>';
            }
            $body_html .= '<tr><td><p><b>Systeminformationen:</b></p></td></tr>';
            $body_html .= '<tr><td>';
            $body_html .= 'DigiMember Version: '.$pluginversion;
            $body_html .= '</td></tr>';
            $body_html .= '<tr><td>';
            $body_html .= 'Blog Sprache: '.$lang;
            $body_html .= '</td></tr>';
            $body_html .= '</table>';
            $body_html .= '<p><b>Übertragene Daten als JSON:</b></p><br>';
            $body_html .= json_encode($feedbackData);
            $mailer->to( NCORE_FEEDBACK_EMAIL );
            $mailer->subject( 'Feedback Mail' );
            $mailer->html( $body_html );
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
        if ($success) {
            echo '{"success": true, "message":"Feedback send."}';
            die();
        }
        echo '{"success": false, "message": "'.$error_msg.'"}';
        die();
    }

	private function ajaxDialogMeta()
	{
        $form_metas = array();
        $form_sections = array(
            'checklist' =>  array(
                'headline' => '',
                'instructions' => _ncore("Your feedback helps us to make DigiMember even better. <br> Therefore we would like to ask you to tell us why you want to deactivate DigiMember: <br> (Multiple answers possible)"),
            ),
            'doesnotfit' =>  array(
                'headline' => '',
                'instructions' => _ncore("I couldn't implement my idea with DigiMember because").":",
            ),
            'feedback' =>  array(
                'headline' => '',
                'instructions' => _ncore("How can we further improve DigiMember?"),
            ),
            'optin' =>  array(
                'headline' => '',
                'instructions' => _ncore("May DigiMember customer support contact you via email about your feedback?"),
            )
        );
	    $check_options = array(
	        'to_complicated' => _ncore("It's too complicated to set up"),
//            'does_not_fit_to_ideas' => _ncore("I couldn't implement my idea with DigiMember"),
            'missing_3rd_party_connections' => _ncore("I'm missing important connections to third-party tools"),
            'using_another_plugin' => _ncore("I am using a different member plugin"),
            'temporaly_testing' => _ncore("The deactivation is only temporary / for test purposes"),
            'to_expensive' => _ncore("DigiMember PRO is too expensive for me"),
        );
		$form_metas[] = array(
            'class' => 'dm_feedback_checklist',
            'name' => 'feedback_checklist',
            'type' => 'checkbox_list',
            'label' => 'none',
            'options' => $check_options,
            'seperator' => '<br />',
            'css' => 'dm_feedback_checklist',
            'full_width' => true,
            'section' => 'checklist',
        );

        $form_metas[] = array(
            'name' => 'does_not_fit_to_ideas',
            'type' => 'textarea',
            'section' => 'doesnotfit',
            'label' => "none",
            'rules' => 'trim|remove_whitespace',
            'rows' => 3,
        );

        $form_metas[] = array(
            'name' => 'feedback_message',
            'type' => 'textarea',
            'section' => 'feedback',
            'label' => "none",
            'rules' => 'trim|remove_whitespace',
            'rows' => 3,
            'cols' => 75,
        );

        $form_metas[] = array(
            'class' => 'dm_feedback_optin',
            'name' => 'feedback_optin',
            'type' => 'yes_no_bit_left',
            'full_width' => false,
            'section' => 'optin',
            'default' => 'N',
            'label' => 'none',
        );
        $form_metas[] = array(
            'class' => 'dm_feedback_email',
            'name' => 'feedback_email',
            'type' => 'text',
            'label' => 'E-Mail',
            'section' => 'optin',
            'depends_on' => array( 'feedback_optin' => 'Y' ),
        );
        $cb_js_code = "dmFeedback.send(form_id)";
        $skip_js_code = "dmFeedback.skip()";
		return array(   'type'          => 'feedback',
                        'close_on_ok'   => true,
						'title'         => _ncore( 'DigiMember Feedback' ),
						'form_sections' => $form_sections,
						'form_inputs'   => $form_metas,
						'label_ok'      => _ncore('Send feedback'),
                        'label_cancel'  => _ncore('Cancel'),
                        'label_skip'    => _ncore('Deactivate without Feedback'),
						'width'         => '800px',
						'height'        => '950px',
						'dialogClass'   => 'dm-dialog-feedback',
                        'cb_js_code'    => $cb_js_code,
                        'skip_js_code'  => $skip_js_code,
				 );
	}
}