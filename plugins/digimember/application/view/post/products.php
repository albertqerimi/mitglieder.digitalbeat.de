<?php

	if (!$products)
	{
		$api = $this->api;
		$model = $api->load->model( 'logic/link' );
		$url = $model->createProduct();
		$label = _digi('Please setup your products first.');

		echo "<a href='$url'>$label</a>";
	}
	else
	{
        $api = $this->api;
		$api->load->helper( 'html_input' );
		$api->load->helper( 'array' );

		$link_model = $this->api->load->model( 'logic/link' );

        $features_model = $api->load->model( 'logic/features' );
        $have_wait_days = $features_model->canContentsBeUnlockedPeriodically();

        $css_have_wait_days = $have_wait_days
                            ? 'digimember_with_days'
                            : 'digimember_without_days';

        echo "<table class='digimember_post_products $css_have_wait_days'><tbody>";

		$label_product    = _digi( 'Member product' );
		$label_unlock_day = $have_wait_days
                          ? '</th><th class=\'digimember_unlockday\'>' . _digi( 'Day' )
                          : '';

		$label = '<em>' . $label_unlock_day . '</em>';

		$tooltip_product    = ncore_tooltip( _digi('A user may view this page, if he has purchased any of the selected products.|If no product is checked, everyone may view this page.|Admins always may view every page.' ) );
		$tooltip_unlock_day = $have_wait_days
                            ? ncore_tooltip( _digi('The day after purchase the page is unlocked to the buyer. This setting applies only, if the product is checked.|For step by step unlocking enter a number for %s. Enter e.g. 7 to unlock this page on the 7th day. Enter nothing or 0 to unlock when the user purchases the product.', $label_unlock_day ) )
                            : '';

        $edit_tooltip = _digi('Edit other content of this product.' );

        echo "<tr><th colspan='2' class='digimember_product'>$label_product$tooltip_product$label_unlock_day$tooltip_unlock_day</th></tr>\n";

		foreach ($products as $i => $one)
		{
			$product_id = $one->id;

			$post_product = ncore_findByKey( $post_products, 'product_id', $product_id );

			$id = ncore_retrieve( $post_product, 'id', "new_$i" );

			$product_name = esc_attr( $one->name );

			$postname = "digi_page_product[$id]";

			$checked          = ncore_retrieve( $post_product, 'is_active' ) == 'Y';
			$value_unlock_day = ncore_retrieve( $post_product, 'unlock_day' );

			$input_attr = array( 'class' => 'dm-input-int-small', 'style' => 'min-width: 40px;' );

			$checkbox = ncore_htmlCheckbox( $postname.'[is_active]', $checked );

			$unlock_day = $have_wait_days
                        ? '</td><td class=\'digimember_unlockday\'>' . ncore_htmlIntInput( $postname.'[unlock_day]', $value_unlock_day, $input_attr )
                        : ncore_htmlHiddenInput( $postname.'[unlock_day]', $value_unlock_day );

			$hidden = ncore_htmlHiddenInput( $postname . '[product_id]', $product_id )
					. ncore_htmlHiddenInput( $postname . '[post_id]',    $post_id )
					. ncore_htmlHiddenInput( $postname . '[post_type]',  $post_type );

			$url = $link_model->adminPage( 'content', array( 'element' => $product_id, 'tab' => $post_type ));
			$edit_link = "<a title=\"$edit_tooltip\" href='$url'>$product_name</a>";

			echo "<tr><td class='digimember_checkbox'>$checkbox</td><td class='digimember_product'>$edit_link$hidden$unlock_day</td></tr>\n";

		}

		echo "</tbody></table>\n";


		$link = $this->api->load->model( 'logic/link' );

		$label = _digi( 'Edit other content' );

        if (!$have_wait_days)
        {
            $model = $this->api->load->model( 'logic/link' );

            echo $model->upgradeHint( _digi( 'Unlocking content periodically is NOT included in your subscription.' ) );
        }

        if ($download_products) {

            echo '<hr />';
            echo '<table class="digimember_post_products"><tbody>';
            echo "<thead><tr><th>", _digi( 'Download products' ), '</th></tr></thead>';


            foreach ($download_products as $one)
            {
                $url = $link_model->adminPage( 'products', array( 'id' => $one->id ));

                $product_name = esc_attr( $one->name );

                $link = "<a href=\"$url\">$product_name</a>";

                echo "<tr><td class='digimember_product'>", $link, '</td></tr>';
            }
            echo '</tbody></table>';
        }

	}
