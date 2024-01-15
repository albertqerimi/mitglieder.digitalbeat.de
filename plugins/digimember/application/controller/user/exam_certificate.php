<?php

$load->controllerBaseClass( 'user/base' );

class digimember_UserExamCertificateController extends ncore_UserBaseController
{
    public function init( $settings=array() )
    {
        $this->certificate_id = ncore_retrieve( $settings, 'id' );

        $this->user_id = ncore_userId();
    }

    protected function handleRequest()
    {
        parent::handleRequest();
    }



    protected function viewData()
    {
        $data = array();

        /** @var digimember_ExamCertificateData $examCertificateData */
        $examCertificateData = $this->api->load->model( 'data/exam_certificate' );
        $data[ 'text' ] = $examCertificateData->renderDownloadText( $this->certificate_id, $this->user_id );

        return $data;
    }


    private $certificate_id = false;
    private $user_id        = false;
}
