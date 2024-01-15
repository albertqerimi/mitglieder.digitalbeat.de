<?php

$load->controllerBaseClass('admin/base');

abstract class ncore_AdminMultiElementsController extends ncore_AdminBaseController
{
    protected function elementSelectorLevelCount()
    {
        return 0;
    }

    protected function elementOptions( $level=1 )
    {
        return array();
    }

    protected function elementSelectionMandatory()
    {
        return false;
    }

    final protected function elementSelectorLevels( $max_level='all')
    {
        $levels = array();
        for ($level=1; $level<=$this->elementSelectorLevelCount(); $level++)
        {
            if ($max_level==='all' || $level<=$max_level)
            {
                $levels[] = $level;
            }
            else
            {
                break;
            }
        }
        return $levels;
    }

    protected function pageHeadlineSuffix()
    {
        if ($this->haveElementSelection())
        {
            $selector = $this->renderElementSelector( ' ' );
            return ' ' . $selector;
        }
        else
        {
            return '';
        }
    }

    protected function setSelectedElement( $level, $id )
    {
        $selected =& $this->selectedElement[ $level ];
        $selected = $id;
    }

    protected function selectedElement( $level=1 )
    {
        $selected =& $this->selectedElement[ $level ];
        if (!isset($selected))
        {
            $selected = false;

            $name = $this->elementSelectorName($level);
            $element_id = ncore_retrieve( $_POST, $name );
            if (!$element_id)
            {
                $element_id =ncore_retrieve( $_GET, $name );
            }

            $options = $this->elementOptions( $level );

            if ($options)
            {
                $have_valid_tab = isset( $options[ $element_id ] );

                if (!$have_valid_tab)
                {
                    $element_ids = array_keys( $options );
                    $element_id = $element_ids[0];
                }

                $selected = $element_id;
            }
        }

        return $selected;
    }

    protected function renderInstructions()
    {
        if ($this->haveElementSelection()
            || !$this->elementSelectionMandatory())
        {
            parent::renderInstructions();
        }
    }

    protected function currentUrlArgs()
    {
        $args = parent::currentUrlArgs();

        foreach ($this->elementSelectorLevels() as $level) {

            $name = $this->elementSelectorName($level);
            $id   = $this->selectedElement( $level );

            $args[ $name ] = $id;
        }

        return $args;
    }


    protected function loadView()
    {
        if ($this->mustLoadNoElementsView())
        {
            $this->loadNoElementsView();
        }
        else
        {
            parent::loadView();
        }
    }

    protected function loadNoElementsView()
    {
        $view = $this->noElementsViewName();

        $data = $this->noElementsViewData();

        extract( $data );

        $rootdir = $this->api->rootDir();

        $path = "$rootdir/application/view/$view.php";

        if (file_exists( $path ))
        {
            require $path;
            return;
        }

        $path = "$rootdir/system/view/$view.php";

        require $path;
    }

    protected function noElementsViewName()
    {
        return 'admin/message';
    }

    protected function noElementsMessage()
    {
        return _ncore( 'Currently you have no elements to edit.');
    }

    protected function noElementsViewData()
    {
        $data = array();

        $data['message'] = $this->noElementsMessage();

        return $data;
    }

    protected function elementSelectorName( $level=1 )
    {
        $suffix = $level >= 2
                ? "_$level"
                : '';
        return "element$suffix";
    }

    private $selectedElement = array();

    private function renderElementSelector( $seperator= ' ' )
    {
        $this->api->load->helper( 'html_input' );

        $page = $this->myAdminPage();

        $url = $this->api->link_logic->adminPage( $page  );

        $tab = $this->currentTab();
        if ($tab)
        {
            $url = ncore_addArgs( $url, array( 'tab' => $tab ), '&' );
        }

        $model = $this->api->load->model( 'logic/html' );

        $selects = array();
        foreach ($this->elementSelectorLevels() as $level)
        {
            $one_url = $url;

            foreach ($this->elementSelectorLevels($level-1) as $i)
            {
                $n = $this->elementSelectorName( $i );
                $v = $this->selectedElement( $i );
                $one_url .= "&$n=$v";
            }

            $options = $this->elementOptions( $level );
            $name = $this->elementSelectorName( $level );
            $selected = $this->selectedElement( $level);

            $js_on_select = "location.href=\"$one_url&$name=\" + ncoreJQ(this).val()";

            $model->jsChange( "select[name=$name]", $js_on_select );

            $selects[] = ncore_htmlSelect( $name, $options, $selected );
        }

        return implode( $seperator, $selects );
    }

    private function haveElementSelection()
    {
        $elements_enabled = $this->elementSelectorLevelCount() >= 1
                        && count($this->elementOptions( $level=1 ) ) >= 1;

        return $elements_enabled;
    }

    private function mustLoadNoElementsView()
    {
        if ($this->haveElementSelection())
        {
            return false;
        }

        if ($this->elementSelectionMandatory())
        {
            return true;
        }

        return false;
    }
}