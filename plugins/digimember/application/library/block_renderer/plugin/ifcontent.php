<?php

class digimember_BlockRenderer_PluginIfcontent extends digimember_BlockRenderer_PluginBase
{
    /**
     * register
     * registers the new block at wordpress to provide its functionality
     */
    public function register() {
        register_block_type( 'digimember/'.$this->meta('name','ifcontent'), array(
            'attributes' => $this->meta('attributes', array()),
            'render_callback' => array($this, 'render'),
        ));
    }

    /**
     * render
     * is the rendercallback defined in block registration
     * the function decides with the given attributes if it provides the given block_content at the end or not.
     * @param $attributes
     * @param $block_content
     * @return string
     */
    public function render($attributes, $block_content) {
        $has_product     = $this->productListEntries(ncore_retrieve( $attributes, 'whitelist', array()));
        $has_not_product = $this->productListEntries(ncore_retrieve( $attributes, 'blacklist', array()));
        $logged_in       = ncore_retrieve( $attributes, 'loginactive' );
        $mode            = ncore_retrieve( $attributes, 'filter' );

        $whitelistCheckPass = true;
        $blacklistCheckPass = true;
        $loginCheckPass = true;

        if ($has_product) {
            $whitelistCheckPass = $this->hasProduct( $has_product );
        }

        if ($has_not_product ) {
            $blacklistCheckPass = !$this->hasProduct( $has_not_product );
        }

        if ($logged_in) {
            $is_user_logged_in = ncore_isLoggedIn();
            $logged_in = trim( strtolower($logged_in));
            switch ($logged_in) {
                case 'loggedin':
                    $loginCheckPass = $is_user_logged_in;
                    break;
                case 'loggedout':
                    $loginCheckPass = !$is_user_logged_in;
                    break;
                default:
            }
        }

//        if ($mode) {
//            switch ($mode)
//            {
//                case 'atlastonefalse':
//                    if ($whitelistCheckPass && $blacklistCheckPass && $loginCheckPass) {
//                        return '';
//                    }
//                    break;
//                case 'allfalse':
//                    if ($whitelistCheckPass || $blacklistCheckPass || $loginCheckPass) {
//                        return '';
//                    }
//                    break;
//                default:
//            }
//        }

        if ($whitelistCheckPass && $blacklistCheckPass && $loginCheckPass) {
            return $block_content;
        }

        return '';
    }

    function hasProduct( $look_for_product_ids )
    {
        $userProductModel = $this->api->load->model('data/user_product');
        if (!$look_for_product_ids) {
            return false;
        }
        if (is_string($look_for_product_ids)) {
            $look_for_product_ids = explode( ',', $look_for_product_ids );
        }
        $products = $userProductModel->getForUser();
        foreach ($products as $one)
        {
            $id = $one->product_id;

            if (in_array( $id, $look_for_product_ids)) {
                return true;
            }
        }
        return false;
    }

    public function productListEntries($list = array()) {
        $entries = array();
        foreach ($list as $id => $status) {
            if ($status) {
                $entries[] = $id;
            }
        }
        return $entries;
    }

    /**
     * getBlockConfig
     * blockconfiguration that will be provided to the gutenberg editor to set block configuration
     * it sets all information to build the block controls on the sidebar
     * @return array[]
     */
    public function getBlockConfig() {
        return array(
            'controls' => array(
                'buttons' => array(
                    'reloadblock' => array(
                        'icon' => 'image-rotate',
                        'isSecondary' => true,
                    )
                ),
                'panel' => array(
                    'header' => 'Mögliche Bedingungen:',
                    'bodies' => array(
                        0 => array(
                            'title' => '',
                            'isOpen' => true,
                            'data' => 'createHeader',
                            'args' => array(
                                'variant' => 'body',
                                'content' => array(
                                    0 => _digi('Displays content based on conditions.'),
                                    1 => _digi('This block behaves analogously to the DigiMember ds_if shortcode.'),
                                )
                            ),
                        ),
                        1 => array(
                            'title' => _digi( 'has product' ),
                            'isOpen' => false,
                            'data' => 'createProductWhitelist',
                            'args' => array(
                                'variant' => 'caption',
                                'hint' => _digi('If multiple products selected: The text is shown, if the user has any them.' ),
                            ),
                        ),
                        2 => array(
                            'title' => _digi('has not product'),
                            'isOpen' => false,
                            'data' => 'createProductBlacklist',
                            'args' => array(
                                'variant' => 'caption',
                                'hint' => _digi('If multiple products selected: The text is shown, if the user has neither of them.' ),
                            ),
                        ),
                        3 => array(
                            'title' => _digi('is logged in'),
                            'isOpen' => false,
                            'data' => 'createIsLoggedinControl',
                            'args' => array(
                                'options' => array(
                                    array(
                                        'label' => '',
                                        'value' => 'none',
                                    ),
                                    array(
                                        'label' => _digi('yes'),
                                        'value' => 'loggedin',
                                    ),
                                    array(
                                        'label' => _digi('no'),
                                        'value' => 'loggedout',
                                    ),
                                ),
                            ),
                        ),
//                        4 => array(
//                            'title' => _digi('only if'),
//                            'isOpen' => false,
//                            'data' => 'createFilterControl',
//                            'args' => array(
//                                'options' => array(
//                                    array(
//                                        'label' => '',
//                                        'value' => 'none',
//                                    ),
//                                    array(
//                                        'label' => _digi('the previous condition did not match'),
//                                        'value' => 'atlastonefalse',
//                                    ),
//                                    array(
//                                        'label' => _digi('any condition so far did not match'),
//                                        'value' => 'allfalse',
//                                    ),
//                                ),
//                            ),
//                        ),
                    )
                ),
            ),
        );
    }
}