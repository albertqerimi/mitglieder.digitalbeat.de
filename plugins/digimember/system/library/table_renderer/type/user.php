<?php

class ncore_TableRenderer_TypeUser extends ncore_TableRenderer_TypeBase
{
    protected function renderInner( $row )
    {
        $user_id = $this->value( $row );

        $user = ncore_getUserById( $user_id );

        $key = $this->meta( 'display_column', 'user_email' );
        $link = $this->meta( 'link', false );

        $value = ncore_retrieve( $user, $key );

        if (!$value)
        {
            $value = _ncore( 'User #%s', $user_id );
        }
        if ($link) {
            $value = '<a href="' . get_edit_user_link($user_id) . '" target="_blank">' . $value . '</a>';
        }

        return $value;
    }

    public function renderWhere( $search_for )
    {
        $compare = $this->meta( 'compare', 'equal' );

        $users = ncore_searchUsers( $search_for, $compare );


        $user_ids = array();
        foreach ($users as $one)
        {
            $is_id = is_numeric( $one);
            $user_ids[] = $is_id
                        ? $one
                        : ncore_retrieve( $one, 'ID' );
        }

        $where = array();

        $column = $this->column;
        $where["$column IN"] = $user_ids;

        return $where;
    }
}