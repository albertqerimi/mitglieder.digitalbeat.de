<?php

class digimember_ExamCertificateData extends ncore_BaseData
{
    const QUESTION_ID_LENGTH = 15;

    public function dataType()
    {
        return NCORE_MODEL_DATA_TYPE_USER;
    }

    public function certificateDownloadPassword( $certicate_obj_or_id, $user_obj_or_id='current' )
    {
        $certificate_id = is_object( $certicate_obj_or_id )
                        ? $certicate_obj_or_id->id
                        : ncore_washInt( $certicate_obj_or_id );

        $user_id = ncore_userId( $user_obj_or_id );

        if (!$certificate_id || !$user_id)
        {
            return false;
        }

        $key = "exam_pw_$certificate_id";

        /** @var ncore_UserSettingsData $userSettingsData */
        $userSettingsData = $this->api->load->model( 'data/user_settings' );
        $pw = $userSettingsData->getForUser( $user_id, $key );
        if (!$pw) {
            $this->api->load->helper( 'string' );
            $pw = ncore_randomString( 'alnum', 32 );
            $userSettingsData->setForUser( $user_id, $key, $pw );
        }

        return $pw;
    }

    public function validateDownloadPassword( $certificate_obj_or_id, $user, $password )
    {
        $need_password = !ncore_canAccessAdminArea( $user );
        if (!$need_password) {
            return true;
        }

        if (empty($password)) {
            return false;
        }

        $stored_pw = $this->certificateDownloadPassword( $certificate_obj_or_id, $user );

        $is_match = $stored_pw == $password;

        return $is_match;
    }

    public function execDownload( $obj_or_id, $pw=false, $full_name=false )
    {
        $obj = $this->resolveToObj( $obj_or_id );
        if (!$obj) {
            return;
        }

        $user_id = ncore_userId();
        $user    = get_userdata( $user_id );
        if (!$user) {
            return;
        }

        $full_name = trim( $full_name );

        if (!ncore_canAccessAdminArea( $user ))
        {
            $full_name = false;
        }

        $is_valid = $this->validateDownloadPassword( $obj, $user, $pw );
        if (!$is_valid) {
            return;
        }

        if ($full_name && $full_name !== 'current')
        {
            $recipient_first_and_last_name = $full_name;
        }
        else
        {
            $recipient_first_and_last_name = trim( $user->first_name . ' ' . $user->last_name);
            if (!$recipient_first_and_last_name) {
                $recipient_first_and_last_name = trim( $user->display_name );
            }
        }

        list( $content_type, $content_binary ) = $this->render( $obj, $recipient_first_and_last_name );
        if (!$content_binary) {
            return;
        }

        header( "content-type: $content_type" );
        die ($content_binary );

    }

    public function getExamStatus( $certificate_obj_or_id, $user_obj_or_id='current' )
    {
        /** @var digimember_ExamData $examData */
        $examData = $this->api->load->model( 'data/exam' );
        /** @var digimember_ExamAnswerData $examAnswerData */
        $examAnswerData = $this->api->load->model( 'data/exam_answer' );
        $this->api->load->helper( 'string' );

        $certificate = $this->resolveToObj( $certificate_obj_or_id );
        $user        = ncore_getUserById( $user_obj_or_id );

        if (!$certificate) {
            return array( $passed_at=false, $started_at=false, $failed_at=false, $exams=array() );
        }

        $passed_at  = false;
        $started_at = false;
        $failed_at  = false;

        $exam_ids      = ncore_explodeAndTrim( $certificate->for_exam_ids );
        $is_no_brainer = !$exam_ids;

        if ($is_no_brainer) {
            $passed_at  = ncore_dbDate();
            $started_at = ncore_dbDate();

            $exams  = array();
        }
        else
        {
            $exams       = $examData->getAll( array( 'id IN' => $exam_ids ) );
            $status_list = $examAnswerData->getStatusMapForUser( $user, $exams );

            $are_all_passed = true;

            foreach ($exams as $exam)
            {
                $status = $status_list[ $exam->id ];

                if ($status['started_at'])
                {
                    $started_at = $started_at
                                ? min( $status['started_at'], $started_at )
                                : $status['started_at'];
                }

                if ($status['failed_at'])
                {
                    $failed_at = $failed_at
                                ? min( $status['failed_at'], $failed_at )
                                : $status['failed_at'];
                }

                if ($status['passed_at'])
                {
                    $passed_at = $passed_at
                                ? max( $status['passed_at'], $passed_at )
                                : $status['passed_at'];
                }
                else
                {
                    $are_all_passed = false;
                }

                $exam->started_at = $status[ 'started_at' ];
                $exam->failed_at  = $status[ 'failed_at' ];
                $exam->passed_at  = $status[ 'passed_at' ];
            }

            if (!$are_all_passed)
            {
                $passed_at = false;
            }
        }


        return array( $passed_at, $started_at, $failed_at, $exams );
    }

    public function render( $obj_or_id, $recipient_first_and_last_name )
    {
        $obj = $this->resolveToObj( $obj_or_id );
        if (!$obj) {
            return false;
        }

        $args = array();

        $args[ 'type' ]      = $obj->type;
        $args[ 'recipient' ] = $recipient_first_and_last_name;
        $args[ 'date' ]      = ncore_dbDate( 'now', 'date' );

        $prefix     = 'template_';
        $prefix_len = strlen( $prefix );

        foreach ( (array) $obj as $key => $value)
        {
            if (!$value || $key == $prefix . 'serialized') {
                continue;
            }

            $is_cert_col = ncore_stringStartsWith( $key, $prefix );
            if (!$is_cert_col)
            {
                continue;
            }

            $arg_key = substr( $key, $prefix_len );

            $is_image = ncore_stringEndsWith( $arg_key, '_image' );
            if ($is_image)
            {
                $meta       = wp_get_attachment_metadata( $value );
                $upload_dir = wp_upload_dir();
                if (!$meta) {
                    continue;
                }

                $filename = $upload_dir[ 'basedir'] . DIRECTORY_SEPARATOR . $meta[ 'file' ];

                $value = file_get_contents( $filename );
            }


            $args[ $arg_key ] = $value;
        }

        /** @var ncore_RpcApiLib $rpc */
        $rpc      = $this->api->load->library( 'rpc_api' );
        $response = $rpc->certificateApi( 'render', $args );

        $content_binary = $response->content_binary;
        $content_type   = $response->content_type;

        return array( $content_type, $content_binary );
    }

    public function options( $where=array())
    {
        return $this->asArray( 'name', 'id', $where );
    }

    public function getTemplateMetas( $type=false )
    {
        $cache_key      = 'exam_certitifcate_templates';
        $cache_lifetime = NCORE_DEBUG
                        ? 10
                        : 80000;

        if ($type) {
            $this->api->load->helper( 'array' );
        }

        $templates = !empty($_GET['reload'])
                   ? false
                   : ncore_cacheRetrieve( $cache_key );

        if ($templates !== false) {
            return $type
                   ? ncore_findByKey( $templates, 'type', $type, false )
                   : $templates;
        }

        $this->api->load->model( 'data/exam_certificate' );

        /** @var ncore_RpcApiLib $rpc */
        $rpc      = $this->api->load->library( 'rpc_api' );

        $response = $rpc->certificateApi( 'list', $args=array() );
        $templates_by_index = $response->templates;

        $templates  = array();

        if ($templates_by_index) {
            foreach ($templates_by_index as $one)
            {
                $templates[ $one['type'] ] = $one;
            }
        }

        ncore_cacheStore( $cache_key, $templates, $cache_lifetime );

        return $type
               ? ncore_findByKey( $templates, 'type', $type, false )
               : $templates;
    }

    public function testCertApiCall(){
        $rpc      = $this->api->load->library( 'rpc_api' );
        return $rpc->certificateApi( 'list', $args=array() );
    }

    public function renderDownloadText( $certificate_obj_or_id, $user_obj_or_id )
    {
        if (!$certificate_obj_or_id || !$user_obj_or_id)
        {
            return '';
        }

        list( $find, $repl, $text_template ) = $this->_getPlaceholders( $certificate_obj_or_id,  $user_obj_or_id );

        return str_replace( $find, $repl, $text_template );
    }

    public function getPlaceholders()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        list( $find, $repl, $text_template ) = $this->_getPlaceholders();

        return $find;
    }


    private function _getPlaceholders( $certificate_obj_or_id=false, $user_obj_or_id=false )
    {
        $this->api->load->model( 'data/exam' );

        $certificate = $this->resolveToObj( $certificate_obj_or_id );
        $user_id     = ncore_userId( $user_obj_or_id );

        $download_button = '';
        $exam_list       = '';
        $preview_image   = '';
        $text_template   = '';

        if ($certificate) {

            $meta          = $this->getTemplateMetas( $certificate->type );
            $url           = ncore_retrieve( $meta, 'preview_image_url' );
            $preview_image = $url
                           ? "<img src=\"$url\" style='max-width: 150px; max-height: 100px;' alt=\"$certificate->type\" />"
                           : '';

            /** @noinspection PhpUnusedLocalVariableInspection */
            list( $passed_at, $started_at, $failed_at, $exams ) = $this->getExamStatus( $certificate, $user_id );

            $text_template = $passed_at
                             ? $certificate->text_passed
                             : ( $failed_at
                                 ? $certificate->text_failed
                                 : $certificate->text_pending );

            $can_download = (bool)  $passed_at;

            if ($can_download)
            {
                /** @var digimember_LinkLogic $linkLogic */
                $linkLogic = $this->api->load->model( 'logic/link' );
                $url = $linkLogic->certificateDownload( $certificate );

                $msg = _digi( 'Your certificate will now be generated.|This will take a couple of seconds.|Please be patient.' );

                $msg = str_replace( array( "'", '|' ), array( "\\'", "\\r\\n\\r\\n" ), $msg );

                $js = "alert('$msg'); window.open('$url');";

                $extra_css = 'button-primary';

            }
            else
            {
                $msg = $failed_at
                     ? _digi( 'Sorry - you failed at least one exam required for this certificate.' )
                     : _digi( 'You have not yet passed all exams required for this certificate.' );

                $msg = str_replace( "'", "\\'", $msg );

                $js = "alert( '$msg' ); return false;";

                $extra_css = 'button-primary button-disabled';
            }

            $download_button = $this->_renderDownloadButton( $certificate, $js, $extra_css );

            if ($exams)
            {
                $this->api->load->helper( 'date' );

                $exam_list .= '<ol class="dm_exam_list">';

                foreach ($exams as $one)
                {
                    if ($one->passed_at)
                    {
                        $css   = 'dm_exam_passed';
                        $title = _digi( 'You passed this exam on %s.', ncore_formatDate( $one->passed_at ) );
                    }
                    elseif ($one->failed_at)
                    {
                        $css   = 'dm_exam_failed';
                        $title = _digi( 'You failed this exam on %s.', ncore_formatDate( $one->failed_at ) );
                    }
                    elseif ($one->started_at)
                    {
                        $css = 'dm_exam_tried';
                        $title = _digi( 'You have tried this first exam on %s, but did not yet succeed.', ncore_formatDate( $one->started_at ) );
                    }
                    else
                    {
                        $css = 'dm_exam_new';
                        $title = _digi( 'You have not yet passed this exam' );
                    }

                    $label = $one->name;

                    $exam_list.= "<li title=\"$title\" class='dm_exam $css'>$label</li>";

                }

                $exam_list .= '</ol>';
            }
        }


        $find = array(
            '[DOWNLOAD_BUTTON]',
            '[EXAM_LIST]',
            '[PREVIEW_IMAGE]',
        );

        $repl = array(
            $download_button,
            $exam_list,
            $preview_image,
        );

        return array( $find, $repl, $text_template );
    }

    //
    // protected section
    //
    protected function sqlBaseTableName()
    {
        return 'exam_certificate';
    }

    protected function serializedDataMeta() {
        return array(
            'template_serialized'  => 'template_',
        );
    }

    protected function sqlTableMeta()
    {
       $columns = array(
            'name'                   => 'string[127]',
            'type'                   => 'string[127]',
            'for_exam_ids'           => 'text',
            'is_active'              => 'yes_no_bit',

            'signature_text'         => 'string[127]',
            'signature_image_id'     => 'int',

            'title'                  => 'string[127]',
            'notes_hl'               => 'string[127]',
            'notes'                  => 'text',

            'banner_image_id'        => 'int',
            'seal_image_id'          => 'int',

            'text_passed'            => 'text',
            'text_pending'           => 'text',
            'text_failed'            => 'text',

            'download_button_type'      => 'string[15]',
            'download_button_label'     => 'text',
            'download_button_image_id'  => 'id',
            'download_button_bg_color'  => 'string[15]',
            'download_button_fg_color'  => 'string[15]',
            'download_button_radius'    => 'int',

       );

       $indexes = array( /*'order_id', 'product_id', 'email'*/ );

       $meta = array(
        'columns' => $columns,
        'indexes' => $indexes,
       );

       return $meta;
    }

    protected function buildObject( $obj )
    {
        parent::buildObject( $obj );
    }


    protected function hasTrash()
    {
        return true;
    }

    protected function defaultValues()
    {
        $values = parent::defaultValues();

        $values[ 'name' ] = _digi( 'New certificate' );
        $values[ 'is_active' ] = 'Y';
        $values[ 'text_passed' ] = '[DOWNLOAD_BUTTON]';

        $values[ 'download_button_type' ]        = 'button';
        $values[ 'download_button_label' ]       = _digi( 'Download exam certificate' );
        $values[ 'download_button_fg_color' ]    = '#FFFFFF';
        $values[ 'download_button_bg_color' ]    = '#2196F3';
        $values[ 'download_button_radius' ]      = 100;

        return $values;
    }

    protected function hasModified()
    {
        return true;
    }


    private function _renderDownloadButton( $certificate, $js, $css )
    {


        $method = ncore_retrieve( $certificate, 'download_button_type', 'button' );

        $is_image  = $method == 'image';
        $is_button = $method == 'button';

        if (!$is_image && !$is_button) {
            return '';
        }

        $inner_html = '';
        $style      = '';
        $is_text    = true;

        if ($is_image )
        {
            $image_id = ncore_retrieve( $certificate, 'download_button_image_id' );
            if ($image_id)
            {
                $url  = wp_get_attachment_url( $image_id );

                if ($url) {
                    $meta = wp_get_attachment_metadata( $image_id );
                    $have_size = !empty( $meta['width'] ) && !empty( $meta['height'] );
                    $size_attr = $have_size
                               ? "style=\"width: ${meta['width']}px; height: ${meta['height']};\""
                               : '';
                    $inner_html = "<img $size_attr src=\"$url\" alt='' />";
                    $is_text    = false;
                }
            }
        }
        else
        {
            $label      = ncore_retrieve( $certificate, 'download_button_label', _digi( 'Download exam certificate' ) );
            $inner_html = $label;
        }

        if (!$inner_html)
        {
            $inner_html = _digi( 'Download exam certificate' );
        }

        if ($is_text)
        {
            $bg_color      = ncore_retrieve( $certificate, 'download_button_bg_color', '#555' );
            $fg_color      = ncore_retrieve( $certificate, 'download_button_fg_color', '#FFF' );
            $border_radius = ncore_retrieve( $certificate, 'download_button_radius',   0 );

            $style .= "border-radius: ${border_radius}px; background-color: $bg_color; color: $fg_color;";
        }

        $css .= $is_text
              ? ' dm_certificate_download_text'
              : ' dm_certificate_download_image';


        return "<button onclick=\"$js\" style='$style'  class=\"dm_certificate_download button $css\">$inner_html</button>";
    }

}
