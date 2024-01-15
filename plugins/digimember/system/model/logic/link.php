<?php

class ncore_LinkLogic extends ncore_BaseLogic
{
	public function logoff( $redirect_url = 'auto' )
	{
		if ($redirect_url === 'auto')
		{
			$redirect_url = ncore_siteUrl();
		}

        $this->api->load->helper( 'url' );

		return ncore_logoutUrl( $redirect_url );
	}

    public function productInfoUrl( $product, $type )
    {
        switch ($product)
        {
            case 'klicktipp':

                $id = defined( 'DM_KLICKTIPP_AFFILIATE_ID' ) ? DM_KLICKTIPP_AFFILIATE_ID : 3158;

                switch ($type) {
                    case 'info': return "https://www.Klick-Tipp.com/$id";
                    default:     return "https://www.Klick-Tipp.com/bestellen/$id";
                }
        }

        return false;
    }

    private $menu=false;
    public function adminMenuLink( $what_page='', $anchor='' )
    {
        if ( $this->menu === false )
        {
            $config = $this->api->load->config( 'menu' );

            $this->menu = $config->get( 'admin_pages' );
        }

        $main_entry = ncore_retrieve( $this->menu, $this->api->pluginName() );
        $main_label = str_replace( '[PLUGIN]', $this->api->pluginDisplayName(), ncore_retrieve( $main_entry, 'menu_title' ) );

        if (!$what_page)
        {
            $sub_label = ncore_retrieve( $main_entry, 'menu_entry' );
        }
        else
        {
            $menus = ncore_retrieve( $main_entry, 'submenu' );
            $sub_entry = ncore_retrieve( $menus, $what_page );
            $sub_label  = ncore_retrieve( $sub_entry, 'menu_entry' );
        }

        $label = $main_label;
        if ($sub_label)
        {
            $label .= ' - ' . $sub_label;
        }

        $url = $this->adminPage( $what_page );

        if ($anchor) {
            $url .= "#$anchor";
        }

        return ncore_htmlLink( $url, $label );
    }

	public function adminPage( $what_page='', $args = array(), $seperator = '&amp;' )
	{
		return $this->_adminUrl( 'admin.php', $what_page, $args, $seperator );
	}

	public function networkPage( $what_page='', $args = array(), $seperator = '&amp;' )
	{
		return $this->_adminUrl( 'network/admin.php', $what_page, $args, $seperator );
	}

	public function readPost( $post_type, $post_id )
	{
		$post_id = urlencode( $post_id );

		switch ($post_type)
		{
			case 'post':
				return ncore_siteUrl( 'index.php?p=' . $post_id );


			case 'page':
            default:
				return ncore_siteUrl( 'index.php?page_id=' . $post_id );
		}
	}

	public function editPageUrl( $page_id )
	{
		$page_id = urlencode( $page_id );
		return get_admin_url() . "post.php?post=$page_id&action=edit";
	}

	public function createPageUrl()
	{
		return get_admin_url() . 'post-new.php?post_type=page';
	}

	public function ajaxUrl( $controller, $event='__EVENT__', $args = array() )
	{
		if (is_object($controller))
		{
			$controller = $controller->baseName();
		}

        static $base_url;

        if (!isset($base_url))
        {
            $this->api->load->helper( 'xss_prevention' );

		    // $base_url = ncore_localUrl();
            // $base_url = $this->api->pluginUrl( 'ajax.php', $stripHost=true );

            if (ncore_isAdminArea())
            {
                $base_url = admin_url( 'admin-ajax.php', 'relative' );
            }
            else
            {
                $base_url = parse_url( ncore_currentUrl(), PHP_URL_PATH );
            }
        }


        $args[ ncore_XssVariableName() ] = ncore_XssPassword();
		$args[ 'event' ] = $event;
		$args[ 'controller' ] = $controller;
        $args[ 'current_url' ] = ncore_currentUrl();
        $args[ 'dm_is_ajax_request' ] = $this->api->pluginName();
        $args[ 'ncore_is_admin' ] = (ncore_isAdminArea() ? 'Y' : 'N');

		$this->api->load->helper( 'url' );
		$url = ncore_addArgs( $base_url, $args, '&'  );

		return $url;

	}

	public function downloadExample( $file )
	{
	    return $this->api->licenseServerBaseUrl() . NCORE_API_ROOT . "download.php?example=$file";
	}

	private function _adminUrl( $file, $what_page='', $args = array(), $seperator = '&amp;' )
	{
		$plugin = $this->api->pluginName();

		if ($what_page)
		{
			$page = ncore_stringStartsWith( $what_page, $plugin )
				  ? $what_page
				  : $plugin . '_' . $what_page;
		}
		else
		{
			$page = $plugin;
		}

		$args['page'] = $page;
		$url = get_admin_url() . $file;

		return ncore_addArgs( $url, $args, $seperator );
	}
}