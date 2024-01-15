<?php

class digimember_MailTextData extends ncore_MailTextData
{

    protected function defaultValues()
    {
        $defaults = parent::defaultValues();
        $defaults['send_policy'] = 'if_first_today';
        return $defaults;
    }






















    // LEGACY CODE - remove mayFixLegacyConfirmUrlPlaceholder in 2015
    //               also remove legacy code in ncore_InitCore::upgrade


    protected function buildObject( $object )
    {
        parent::buildObject( $object );

        $this->mayFixLegacyConfirmUrlPlaceholder( $object );
    }

    private function mayFixLegacyConfirmUrlPlaceholder( $object )
    {
        // the bug which consequences are fixed here, was fixed in 1.5.083 (on october 7th 2013).
        //
        // BUT the mail text containing the error was stored for every installation before october 7th 2013

        if (ncore_retrieve( $object, 'hook' ) !== NCORE_MAIL_HOOK_NEW_PASSWORD)
        {
            return;
        }

        $is_fixed = strpos( $object->body_html, '%%confirm_url%%' ) === false;
        if ($is_fixed)
        {
            return;
        }

        $find = array(
                    '<a href="%%confirm_url%%">%%confirm_url%%</a>',
                    "<a href='%%confirm_url%%'>%%confirm_url%%</a>",
                    '%%confirm_url%%',
                );
        $repl = "<a href=\"%%url%%\">%%url%%</a>";

        $object->body_html = str_replace( $find, $repl, $object->body_html );

        $data = array( 'body_html' => $object->body_html );
        $this->update( $object->id, $data );
    }
}