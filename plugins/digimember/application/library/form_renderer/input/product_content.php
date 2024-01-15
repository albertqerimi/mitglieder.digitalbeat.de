<?php

/**
 * Class digimember_FormRenderer_InputProductContent
 */
class digimember_FormRenderer_InputProductContent extends ncore_FormRenderer_InputBase
{
    /**
     * digimember_FormRenderer_InputProductContent constructor.
     *
     * @param $parent
     * @param $meta
     */
    public function __construct($parent, $meta)
    {
        parent::__construct($parent, $meta);

        $this->api->load->helper('html_input');
        /** @var ncore_HtmlLogic $htmlLogic */
        $htmlLogic = $this->api->load->model('logic/html');
        $htmlLogic->loadPackage('content-list.js');
    }

    private $selectedSubelements = [];

    /**
     * @param $values
     */
    public function setValue($values)
    {
        $sub_ids = [];

        $this->selectedSubelements = [];

        $is_active_key = $this->meta('is_active_key');
        $is_active_val = $this->meta('is_active_val');

        if ($values) {
            foreach ($values as $rec) {
                $is_inactive = $is_active_key
                    && ncore_retrieve($rec, $is_active_key) != $is_active_val;

                if ($is_inactive) {
                    continue;
                }

                $sub_id = ncore_retrieve($rec, $this->meta('id_name', 'post_id'));
                $sub_ids[] = $sub_id;

                $this->selectedSubelements[] = $rec;
            }
        }

        $sub_ids_comma_seperated = implode(',', $sub_ids);

        parent::setValue($sub_ids_comma_seperated);
    }

    /**
     * @return string
     */
    protected function renderInnerWritable()
    {
        /** @var digimember_FeaturesLogic $model */
        $model = $this->api->load->model('logic/features');
        $can_unlock = $model->canContentsBeUnlockedPeriodically();
        return '<input
            type="hidden"
            class="dm-content-list"
            name="' . $this->postname() . '"
            data-value="' . htmlspecialchars(json_encode($this->getValue(), JSON_NUMERIC_CHECK)) . '"
            data-items="' . htmlspecialchars(json_encode($this->getItems(), JSON_NUMERIC_CHECK)) . '"
            data-inputs="' . htmlspecialchars(json_encode($this->meta('inputs', []), JSON_NUMERIC_CHECK)) . '"
            data-headline-available="' . $this->meta('headline_available', '') . '"
            data-headline-selected="' . $this->meta('headline_selected', '') . '"
            data-message-empty="' . $this->meta('message_empty', _digi('There are no items to display')) . '"
            data-unlock="' . ($can_unlock ? 'y' : 'n') . '"
        />';
    }

    /**
     * @return array
     */
    private function getValue()
    {
        return array_map(function ($element) {
            $inputValues = [];
            foreach ($this->meta('inputs', []) as $input) {
                $key = ncore_retrieve($input, 'name', 'dummy');
                $inputValues[$key] = ncore_retrieve($element, $key, '');
            }
            return array_merge($inputValues, [
                'id' => ncore_retrieve($element, $this->meta('id_name', 'post_id')),
                'level' => ncore_retrieve($element, $this->meta('level_name', 'level')),
            ]);
        }, $this->selectedSubelements);
    }

    /**
     * @return array
     */
    private function getItems()
    {
        $options = $this->meta('options', []);
        $url = $this->meta('details_url');
        $returnArray = [];
        foreach ($options as $id => $title) {
            $returnArray[] = [
                'id' => $id,
                'title' => (string)$title,
                'url' => str_replace('__ID__', $id, $url),
            ];
        }
        return $returnArray;
    }

    /**
     * @return bool
     */
    public function fullWidth()
    {
        return true;
    }

    /**
     * @return string
     */
    protected function defaultRules()
    {
        return 'trim';
    }
}
