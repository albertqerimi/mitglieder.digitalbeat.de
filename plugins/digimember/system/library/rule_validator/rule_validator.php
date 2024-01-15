<?php

class ncore_RuleValidatorLib extends ncore_Library
{
    public function hints( $rules_string )
    {
        $rules = explode('|', $rules_string);

        $hints = array();
        $sort  = array();

        $has_required_rule = false;

        foreach ($rules as $rule)
        {
            $is_required_rule = $rule == 'required';
            if ($is_required_rule)
            {
                $has_required_rule = true;
                continue;
            }

            $is_readonly_rule = $rule == 'readonly';
            if ($is_readonly_rule)
            {
                continue;
            }

            if (!$rule)
            {
                continue;
            }

            list( $type, $arg1, $arg2, $arg3) = $this->parseRule($rule);
            if (!$type) {
                continue;
            }

            $obj = $this->loadRule( $type );
            if (!$obj)
            {
                continue;
            }

            $hint = $obj->hintText( $arg1, $arg2, $arg3 );
            $prio = $obj->hintPriority();


            if (!$hint)
            {
                continue;
            }

            $hints[] = $hint . _ncore('.');
            $sort[]  = $prio;
        }

        array_multisort($sort, SORT_NUMERIC, $hints);

        return array( $has_required_rule, $hints );
    }


    public function validate($label, &$value, $rules_string)
    {
        $rules = explode('|', $rules_string);

        $has_required_rule = false;

        $label = trim($label);

        $have_label = (bool) $label
                    && $label != 'none'
                    && $label != 'void'
                    && $label != '&nbsp;';

        if ($have_label)
        {
            self::$last_label = $label;
        }
        else
        {
            $label = self::$last_label;
        }

        foreach ($rules as $rule)
        {
            $is_required_rule = $rule == 'required';
            if ($is_required_rule)
            {
                $has_required_rule = true;
                continue;
            }

            if (!$rule)
            {
                continue;
            }

            $have_value = isset( $value )
                   && $value !== ''
                   && $value !== 0;

            if (!$have_value)
            {
                continue;
            }

            list( $type, $arg1, $arg2, $arg3) = $this->parseRule($rule);

            $obj = $this->loadRule( $type );
            if (!$obj)
            {
                continue;
            }

            $result = $obj->validate( $value, $arg1, $arg2, $arg3 );

            $is_invalid = $result === false;
            $is_value   = $result !== false && $result !== true;

            if ($is_invalid)
            {
                $msgTemplate = $obj->errorMessageTemplate();
                return $this->errorMessage( $msgTemplate, $label, $arg1, $arg2, $arg3);
            }

            if ($is_value)
            {
                $value = $result;
            }
        }

        $have_value = isset( $value )
                   && $value !== ''
                   && $value !== 0;


        if ($has_required_rule && !$have_value)
        {
            $msg = _ncore('[NAME] is required. Please enter a value.');
            return $this->errorMessage( $msg, $label );
        }


        return false;
    }

    //
    // private section
    //
    private $loaded_plugins = array();
    private static $last_label = '';

    private function loadRule( $type  )
    {
        $non_loadable_rules = array( 'readonly', 'required' );

        $plugin =& $this->loaded_plugins[ $type ];

        if (!isset($plugin))
        {
            $is_loadable = !in_array( $type, $non_loadable_rules );

            if ($is_loadable)
            {
                $class_name = $this->loadPluginClass( $type );

                if (empty($class_name))
                {
                    trigger_error( "Invalid rule: '$type'" );
                }

                $plugin = new $class_name( $this, $type );
            }
            else
            {
                $plugin = false;
            }
        }

        return $plugin;
    }


    private function parseRule($rule)
    {
        $have_args = preg_match('/^(.*)\[(.*)\]$/', $rule, $matches);
        if ($have_args)
        {
            $rule_name            = $matches[ 1 ];
            $args_comma_separated = $matches[ 2 ];
            $args                 = explode(',', $args_comma_separated);

            $arg1 = ncore_retrieve( $args, 0 );
            $arg2 = ncore_retrieve( $args, 1 );
            $arg3 = ncore_retrieve( $args, 2 );
        }
        else
        {
            $rule_name = trim($rule);
            $arg1 = '';
            $arg2 = '';
            $arg3 = '';
        }

        return array(
            trim($rule_name),
            trim($arg1),
            trim($arg2),
            trim($arg3)
        );
    }

    private function errorMessage( $msg_template, $label, $arg1='', $arg2='', $arg3='')
    {
        $find = array( '[NAME]', '[ARG]', '[ARG1]', '[ARG2]', '[ARG3]' );
        $repl = array( $label, $arg1, $arg1, $arg2, $arg3 );

        $msg = str_replace($find, $repl, $msg_template);

        return $msg;
    }

    protected function pluginDir()
    {
        return 'rule';
    }
}