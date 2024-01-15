<?php

class ncore_FeaturesAdminNoticeLogic extends ncore_BaseLogic
{
    private $messages = array();

    public function getAdminNotices() {
        $this->getResponseDepricated();
        if ($this->messages) {
            return $this->messages;
        }
        return false;
    }

    public function addMessage($message, $footer = false, $type = NCORE_NOTIFY_WARNING, $closeable = true) {
        $msg = new stdClass();
        $msg->type = $type;
        $msg->text = $message;
        $msg->closeable = $closeable;
        $msg->footer = $footer;
        $this->messages[] = $msg;
    }

    /**
     * Look for old getresponse autoresponder DM-144
     * this is a temporaly method.
     * normaly it would be handled by config to determine that an autoresponder is depricated an a message should be rendered.
     * i make it static to lower the time of implementation
     */
    public function getResponseDepricated () {
        $autoresponderModel = $this->api->load->model('data/autoresponder');
        $getresponseSearchResult = $autoresponderModel->search('engine','getresponse','equal');
        if (count($getresponseSearchResult) > 0) {
            foreach ($getresponseSearchResult as $getResponseEntry){
                if ($getResponseEntry->is_active === 'Y') {
                    $message = _digi('You currently have one outdated GetResponse-DigiMember connection active (get_response, autoresponder Id: %s), which is not supported by GetResponse anymore. To connect DigiMember to GetResponse please add a new autoresponder using the “GetResponse (v3 REST / recommended for new configurations)” instead. We advise you to deactivate or delete the old connection additionally.', $getResponseEntry->id);
                    $footer = array(
                        'goto_digimember' => sprintf(
                            '<a href="%s" target="_parent">%s</a>',
                            self_admin_url( 'admin.php?page=digimember_newsletter' ),
                            _ncore( 'Go to DigiMember autoresponders' )
                        ),
                    );
                    $this->addMessage($message, $footer);
                }
            }
        }
    }
}
