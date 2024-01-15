<?php

class ncore_PostMetaController extends ncore_Controller
{
    public function cbMetaBoxInit( $post, $meta )
    {
        $this->post = $post;

        $this->dispatch();
    }

    public function dispatch()
    {
        if ($this->readAccessGranted())
        {
            $this->api->load->helper( 'xss_prevention' );
            echo ncore_XssPasswordHiddenInput();

            $this->view();
        }
    }

    public function cbMetaBoxSave( $post_id )
    {
        $this->post = get_post( $post_id );

        if ($this->writeAccessGranted())
        {
            $this->handleRequest();
        }
    }

    public function isActive()
    {
        return true;
    }

    protected function getPostId()
    {
        return ncore_retrieve( $this->post, 'ID' );
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        $this->api->load->helper( 'xss_prevention' );

        if (!ncore_XssPasswordVerified())
        {
            return false;
        }

        $post_id = $this->getPostId();
        $post_type = $this->getPostType();

        switch ($post_type)
        {
            case 'page':
                return current_user_can( 'edit_page', $post_id );

            case 'post':
            default:
                return current_user_can( 'edit_post', $post_id );
        }
    }

    protected function getPostType()
    {
        return ncore_retrieve( $this->post, 'post_type' );
    }

    protected function getPost()
    {
        return $this->post;
    }

    private $post;

}