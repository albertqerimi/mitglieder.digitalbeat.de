<?php

$load->controllerBaseClass( 'admin/form' );

class digimember_AdminCertificateSelectController  extends ncore_AdminTabbedController
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseExams();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        /** @var digimember_FeaturesLogic $model */
        $model = ncore_api()->load->model( 'logic/features' );
        return $model->canUseExams();
    }

    protected function pageHeadline()
    {
        return _digi( 'New exam certificate' );
    }


    protected function viewData()
    {
        /** @var digimember_ExamCertificateData $examCertificateData */
        $examCertificateData = $this->api->load->model( 'data/exam_certificate' );
        /** @var digimember_LinkLogic $linkLogic */
        $linkLogic = $this->api->load->model( 'logic/link' );

        $metas    = $examCertificateData->getTemplateMetas();
        $base_url = $linkLogic->adminPage( 'certificates', array( 'id' => 'new', 'type' => '__TYPE__' ) );


        $data = array(
            'metas'    => $metas,
            'base_url' => $base_url,
        );

        return $data;
    }




}