<?php

$load->controllerBaseClass( 'admin/multi_elements');

abstract class ncore_AdminTabbedController extends ncore_AdminMultiElementsController
{
    protected function renderPreContent()
    {
        $tabs = $this->tabs();
        if ($tabs)
        {
            $selected = $this->currentTab();
            $this->renderTabs( $tabs, $selected );
        }
    }


    protected function currentUrlArgs()
    {
        $args = parent::currentUrlArgs();

        $args[ 'tab'] = $this->currentTab();

        return $args;
    }

    protected function tabs()
    {
    }

    protected function currentTab()
    {
        $tabs = $this->tabs();
        if (!$tabs)
        {
            return false;
        }
        $requested_tab = ncore_retrieve( $_GET, 'tab' );
        $is_valid = isset( $tabs[$requested_tab] );
        if ($is_valid)
        {
            return $requested_tab;
        }

        return $this->defaultTab();
    }

    protected function defaultTab()
    {
        $tabs = $this->tabs();
        if (!$tabs)
        {
            return false;
        }

        $keys = array_keys( $tabs );
        $tab = $keys[0];

        return $tab;

    }

    protected function tabUrl( $tab, $args=array(), $seperator='&' )
    {
        $current_url = ncore_currentUrl();
        $base_url = ncore_removeArgs( $current_url, 'tab', $seperator );

        return ncore_addArgs( $base_url, $args, $seperator );
    }


    private function renderTabs( $tabs, $selected )
    {
        $args = array();

        foreach ($this->elementSelectorLevels() as $level)
        {
            $selected_element = $this->selectedElement( $level );
            if ($selected_element)
            {
                $args[ $this->elementSelectorName($level) ] = $selected_element;
            }
        }

        $metas = array();

        foreach ($tabs as $tab => $label_or_meta)
        {
            if (is_array($label_or_meta)) {
                $meta = $label_or_meta;
                $label = @$meta['label'];
                $url   = @$meta['url'];
            }
            else
            {
                $label = $label_or_meta;
                $url   = false;
            }

            $is_selected = $tab==$selected;

            $args['tab'] = $tab;

            if (empty($url))
            {
                $url = $this->tabUrl( $tab, $args, '&' );
            }
            else
            {
                $url = ncore_addArgs( $url, array( 'tab' => $tab ), '&', false );
            }

            $metas[] = array(
                'label' => $label,
                'selected' => $is_selected,
                'url' => $url,
            );
        }

        echo ncore_AdminTabs( $metas );
    }

}