<?php

class ncore_FormRenderer_InputDate extends ncore_FormRenderer_InputBase
{
    protected function renderInnerWritable()
    {
        $this->api->load->helper( 'html_input_date' );

        $html_id = $this->htmlId();

        $postname = $this->postname();
        $value = $this->value();

        $is_null_allowed = $this->meta( 'allow_null', false );

        if (!$value && !$is_null_allowed)
        {
            $value =  ncore_dbDate();
        }

        $attributes = array( 'id' => $html_id );
        $settings = array();

        $with_time = $this->meta( 'with_time', false );
        $settings['past_dates_only'] = $this->meta( 'past_dates_only', false );
        $settings['future_date_only'] = $this->meta( 'future_date_only', false );

        $timeCss = $with_time ? 'dm-col-md-8' : 'dm-col-md-12';
        $input = '<div class="dm-row"><div class="' . $timeCss . '">';
        $input .= ncore_htmlDateInput( $postname, $value, $settings, $attributes );
        $input .= '</div>';

        if ($with_time)
        {
            if ($value)
            {
                list( , $time ) = explode( ' ', $value );
                list( $hour_value, $min_value, ) = ncore_retrieveList( ':', $time );
            }
            else
            {
                $default_time = $this->meta( 'default_time', '00:00' );
                list( $hour_value, $min_value, ) = ncore_retrieveList( ':', $default_time );
            }

            $hour_options = $this->num_options( 23 );
            $min_options = $this->num_options( 59 );

            $hour_value = ncore_washInt( $hour_value );
            $min_value  = ncore_washInt( $min_value );

            $hour_postname = $this->postName( 'hour' );
            $min_postname = $this->postName( 'min' );

            $attr_hour = array(
                'class' => 'ncore_date_select ncore_date_select_hour',
            );
            $attr_min = array(
                'class' => 'ncore_date_select ncore_date_select_minute',
            );

            $selectHour = ncore_htmlSelect( $hour_postname, $hour_options, $hour_value, $attr_hour );
            $selectMinute = ncore_htmlSelect( $min_postname, $min_options, $min_value, $attr_min );

            $input .= '
<div class="dm-col-md-4">
    <div class="dm-row">
        <div class="dm-col-md-6" style="padding-right: 2px;">' . $selectHour . '</div>
        <div class="dm-col-md-6" style="padding-left: 2px;">' . $selectMinute . '</div>
    </div>
</div>
';
        }
        $input .= '</div>';

        return $input;

    }

    protected function onPostedValue( $field_name, &$value )
    {
        if ($field_name)
        {
            return;
        }

        $date_formated = $value;

        $date_formated = trim( $date_formated );
        if (!$date_formated) {
            return null;
        }

        $date_db = ncore_dbDate( $date_formated );

        $with_time = $this->meta( 'with_time', false );

        list( $date, ) = explode( ' ', $date_db );



        if ($with_time)
        {
            $hour = $this->postedValue( 'hour' );
            $min = $this->postedValue( 'min' );
            $time = sprintf( "%02d:%02d:00", $hour, $min );
        }
        else
        {
            list( , $time ) = explode( ' ', ncore_dbDate() );
        }

        $value = "$date $time";
    }

    public function value()
    {
        $value = parent::value();

        return $value;
    }

    protected function defaultRules()
    {
        return 'trim';
    }

    private function num_options( $max )
    {
        $options = array();
        $i = 0;
        while ($i <= $max)
        {
            $options[ $i] = sprintf( "%02d", $i );
            $i++;
        }

        return $options;
    }
}



