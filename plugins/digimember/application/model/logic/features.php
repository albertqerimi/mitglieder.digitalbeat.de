<?php

class digimember_FeaturesLogic extends ncore_FeaturesLogic
{
    public function graceDays()
    {
        return 14;
    }

    public function freeMaxMembers()
    {
        return 50;
    }

    public function canAffiliateFooterLinkBeDisabled() {
        return $this->canUseFeatures();
    }

    public function canContentsBeUnlockedPeriodically() {
        return $this->canUseFeatures();
    }

    public function canUseAutoJoin() {
        return $this->canUseFeatures();
    }

    public function canUseFacebook() {

        return ncore_hasFacebookApp() && $this->canUseFeatures();
    }

    public function canUseOtherPaymentProviders() {

        return $this->canUseFeatures();
    }

    public function canUseActions() {
        return $this->canUseFeatures();
    }

    public function canUseExams() {
        if ($can_use = $this->canUseFeatures()) {
            return $this->canUseExamCertificates();
        }
        return $can_use;
    }

    public function canUseForms() {
        $can_use = $this->canUseFeatures();
        return $can_use;
    }

    public function canUseExamCertificates() {
        try {
            $this->api->load->model( 'data/exam_certificate' );
            $this->api->exam_certificate_data->testCertApiCall();
            return true;
        }
        catch (Exception $e){
            return false;
        }
    }

    public function canUsePushNotifications() {
        return $this->canUseFeatures();
    }

    public function canUseWebhooks() {
        return $this->canUseFeatures();
    }

    public function hasFreeVersion() {
        return !$this->canUseFeatures();
    }


    private function canUseFeatures() {

        $lib = $this->api->loadLicenseLib();

        $can_do_it = ncore_isTrue( $lib->getFeature( 'can_use_features' ) );

        return $can_do_it;
    }

    public function signUpObstacles() {
        if ($this->canUseFeatures()) {
            return false;
        }

        $cur_member_count = $this->curMemberCount();
        if ((int)$cur_member_count < $this->freeMaxMembers()) {
            return false;
        }

        $grace_ends = $this->_getGraceEndsAt();
        $is_in_grace = $grace_ends > time();
        if ($is_in_grace) {
            return '';
        }

        $lib = $this->api->loadLicenseLib();
        $lib->getLicense( $force_reload = true );

        return _digi( 'Member registration is not possible, because the member limit of %s has been reached. Please upgrade %s.', $this->freeMaxMembers(), $this->api->pluginDisplayName() );
    }

    public function curMemberCount()
    {
        /** @var digimember_UserProductData $model */
        $model = $this->api->load->model( 'data/user_product' );

        return $model->countMembers();
    }

    public function maxProductCount()
    {
        return $this->canUseFeatures()
               ? false
               : 1;
    }

    public function curProductCount()
    {
        $count =& $this->cache[ 'cur_product_count' ];

        if (!isset($count)) {
            $this->api->load->model( 'data/product' );
            $count = count( $this->api->product_data->getAll() );
        }

        return $count;
    }

    protected function getAdminNotices()
    {
        $notices = parent::getAdminNotices();

        if ($notices) {
            return $notices;
        }

        if ($this->canUseFeatures()) {
            return false;
        }

        if ($notices === self::ADMIN_NOTICES_HIDDEN) {
            return self::ADMIN_NOTICES_HIDDEN;
        }

        $cur_member_count = $this->curMemberCount();
        $max_member_count = $this->canUseFeatures() ? 1000000 : $this->freeMaxMembers();

        $is_error   = $cur_member_count > $max_member_count;
        $is_warning = 1.0*(int)$cur_member_count > 0.9*(int)$max_member_count;

        if (!$is_error && !$is_warning) {
            $this->_clearGraceEndsAt();
            return;
        }

        if ($is_error)
        {
            $grace_ends_at = $this->_getGraceEndsAt();

            $is_grace_over = $grace_ends_at < time();

            $this->api->load->helper( 'date' );
            $grace_ends_at = ncore_formatDate( $grace_ends_at );
        }

        $dm_free = $this->api->pluginNameFree();
        $dm_pro  = $this->api->pluginNamePro();

        $type = $is_error
              ? NCORE_NOTIFY_ERROR
              : NCORE_NOTIFY_WARNING;

        $text = $is_error
              ? ($is_grace_over
                 ? _digi( 'Registering new members is disabled. You currently have %s of %s members. New member registration has stopped on %s.', $cur_member_count, $max_member_count, $grace_ends_at )
                 : _digi( 'Registering new members will be disabled soon. You currently have %s of %s members. New member registration will stop on %s.', $cur_member_count, $max_member_count, $grace_ends_at ))
              : _digi( 'Registering new members will be disabled, when you reach %s members. You currently have %s members.', $max_member_count, $cur_member_count );

        $label = $this->hasFreeVersion()
               ? _digi( 'Upgrade to [PRO] to unlock more members.' )
               : _digi( 'Click here to upgrade you member package.');

        $model = $this->api->load->model( 'logic/link' );
        $text = $model->upgradeHint( $text, $label, $tag='span' );

        $msg = new stdClass();
        $msg->type = $type;
        $msg->text = $text;



        return array( $msg );
    }


    private $cache = array();

    private function _clearGraceEndsAt()
    {
        $config = $this->api->load->model( 'logic/blog_config' );
        $grace_ends_at = $config->get( 'dm_member_limit_grace_ends_at', false );
        if ($grace_ends_at) {
            $config->delete( 'dm_member_limit_grace_ends_at' );

            $model = $this->api->load->model( 'logic/notifier' );
            $package = $this->api->pluginBaseName();
            $model->clear( 'member_limit', $package );
        }
    }

    private function _getGraceEndsAt()
    {
        $config = $this->api->load->model( 'logic/blog_config' );
        $grace_ends_at = $config->get( 'dm_member_limit_grace_ends_at', false );
        if (!$grace_ends_at) {

            $this->api->load->helper( 'date' );

            $grace_ends_at = time() + 86400 * $this->graceDays();
            $config->set( 'dm_member_limit_grace_ends_at', $grace_ends_at );

            if ($this->hasFreeVersion())
            {
                $model = $this->api->load->model( 'logic/notifier' );
                $params = array(
                    'cur_member_count' => $this->curMemberCount(),
                    'max_member_count' => $this->canUseFeatures() ? 1000000 : $this->freeMaxMembers(),
                    'block_date'       => ncore_formatDate( $grace_ends_at ),
                    'grace_days'       => $this->graceDays(),
                );

                $package = $this->api->pluginBaseName();

                $model->send( DM_NOTIFY_LICENSE_FREE_MEMBERS, $package, $params );
            }
        }

        return $grace_ends_at;
    }



}