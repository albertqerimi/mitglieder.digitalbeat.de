<?php

abstract class ncore_AdminBaseController extends ncore_Controller
{
    public function init( $settings=array() )
    {
        parent::init( $settings );
    }

    public function view()
    {
        $this->renderPageHead();

        $this->renderPageHeadline();

        $this->renderInstructions();

        $this->renderPreContent();

        $this->renderContent();

        $this->renderPageFoot();
    }

    public function setElementId( $id )
    {
        $this->element_id = $id;
    }

    //
    // protected section
    //
    protected $element_id = false;

    protected function isNetworkController()
    {
        return false;
    }

    abstract protected function pageHeadline();

    protected function pageHeadlineSuffix()
    {
        return '';
    }

    protected function getElementId()
    {
        return $this->element_id;
    }

    protected function pageHeadlineActions()
    {
        return array();
    }

    protected function renderPreContent()
    {
    }

    protected function pageSubHeadlineActions()
    {
    }

    protected function renderInstructions()
    {
        if (count($this->pageInstructions())) {
            echo '<div class="dm-tabs-content dm-form-instructions"><div class="dm-tabs-tab visible">';
                foreach ($this->pageInstructions() as $instructions)
                {
                    echo "<p class='dm-text'>$instructions</p>";
                }
            echo '</div></div>';
        }
    }

    protected function pageInstructions()
    {
        return array();
    }

    protected function renderPageHeadline()
    {
        $sep = ' <span class="dm-color-towerGray dm-headline-separator">›</span> ';

        $hl_text__or__hl_part_array = $this->pageHeadline();

        $headline = is_array( $hl_text__or__hl_part_array )
                  ? implode( $sep, $hl_text__or__hl_part_array )
                  : $hl_text__or__hl_part_array;

        list( $plugin, $name ) = $this->visiblePluginNames();

        $headline = $name . $sep . $headline;
        $links = $this->renderPageHeadlineLinks();
        $suffix = $this->pageHeadlineSuffix();

        echo '
<div class="dm-row dm-middle-xs">
    <div class="dm-col-md-6 dm-col-xs-12">
        <h1 class="dm-headline">' . $headline . '</h1>        
    </div>
    <div class="dm-col-md-6 dm-row dm-end-xs dm-middle-xs dm-col-xs-12 dm-headline-links">
        ' . $suffix . '
        ' . $links . '
    </div>
</div>
';
        echo '<div class="dm-form-messages">' . ncore_renderFlashMessages() . '</div>';
//
//        echo "<div><div id='icon-$plugin' class='icon32'></div>",
//             "<h1 class='dm-headline'>", $headline, $this->pageHeadlineSuffix(), $this->renderPageHeadlineLinks(), "</h1></div>\n",
//             ;
    }

    protected function visiblePluginNames()
    {
        $plugin = $this->api->pluginName();
        $name   = $this->api->pluginDisplayName();

        return array( $plugin, $name );
    }

    protected function renderContent()
    {
        $this->loadView();
    }

    protected function renderPageHead()
    {
        $plugin = $this->api->pluginName();
        echo "<div class='wrap ncore_wrap ${plugin}_wrap'>\n";

        echo "<div class='ncore_admin_header ${plugin}_admin_header'></div>\n";
    }

    protected function renderPageFoot()
    {
        echo "</div>\n";
    }

    protected function myAdminPage()
    {
        return ncore_retrieve( $_GET, 'page' );
    }

    protected function readAccessGranted()
    {
        if (!parent::readAccessGranted())
        {
            return false;
        }

        return ncore_canAdmin();
    }

    protected function writeAccessGranted()
    {
        if (!parent::writeAccessGranted())
        {
            return false;
        }

        return ncore_canAdmin();
    }

    protected function currentUrlArgs()
    {
        $args = array();

        $page = ncore_retrieveGET( 'page' );
        if ($page) {
            $args['page'] = $page;
        }
        return $args;
    }

    //
    // private section
    //

    private function renderPageHeadlineLinks()
    {
        $links = $this->pageHeadlineActions();

        $html = "";

        foreach ($links as $one)
        {
            $url = ncore_retrieve( $one, 'url' );
            $label = ncore_retrieve( $one, 'label' );
            $class = ncore_retrieve( $one, 'class', 'dm-btn dm-btn-primary dm-btn-outlined' );

            $html .= " <a class='$class' href='$url'>$label</a>";
        }

        return $html;
    }



}
