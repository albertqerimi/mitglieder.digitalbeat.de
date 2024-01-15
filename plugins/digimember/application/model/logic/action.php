<?php

class digimember_ActionLogic extends ncore_ActionLogic
{
    function cronDaily()
    {
        parent::cronDaily();

        $this->_queueDigimemberActions();

    }

    private function _queueDigimemberActions()
    {
        /** @var ncore_ActionData $action_data */
        $action_data  = ncore_api()->load->model( 'data/action' );
        /** @var digimember_ActionLogic $action_logic */
        $action_logic = ncore_api()->load->model( 'logic/action' );

        $where = array();
        $where[ 'is_active' ] = 'Y';
        $where[ 'condition_type' ] = 'prd_expired';
        $actions = $action_data->getAll( $where );

        if (!$actions) {
            return;
        }

        /** @var digimember_UserProductData $user_product_data */
        $user_product_data = ncore_api()->load->model( 'data/user_product' );

        foreach ($actions as $one_action)
        {
            $now      = ncore_dbDate();
            $last_day = $one_action->condition_prd_expired_last_queued_at;

            $data = array( 'condition_prd_expired_last_queued_at' => $now );
            $action_data->update( $one_action, $data );

            if (false && $last_day) //why? discover later
            {
                $days_ago = round( (time() - ncore_unixDate( $last_day )) / 86400 );

                $max_offset = min( 14, max( 0, $days_ago ) );

                $is_completed_for_today = $max_offset == 0;
                if ($is_completed_for_today) {
                    continue;
                }
            }
            else
            {
                $max_offset = 1;
            }

            $product_ids = $one_action->condition_product_ids_comma_seperated
                         ? $one_action->condition_product_ids_comma_seperated
                         : 'all';

            $days = ncore_isTrue( $one_action->condition_prd_expired_before )
                      ? -$one_action->condition_prd_expired_days
                      : +$one_action->condition_prd_expired_days;

            for( $offset=$max_offset-1; $offset>=0; $offset--)
            {
                $all = $user_product_data->getExpiredForDay( $days-$offset, $product_ids );

                foreach ($all as $one)
                {
                    $action_logic->queueAction( $one_action, $one->user_id );
                }
            }
        }
    }
}