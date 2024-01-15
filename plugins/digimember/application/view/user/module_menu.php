<?php if ($data):
		ncore_resetFirst( 'product' );
		ncore_resetFirst( 'module' );
?>

	<div class='digimember_module_menu'>

	<?php foreach ($data as $product):
                $is_empty = empty( $product->modules );
                if ($is_empty) continue;
    ?>

		<div class='digimember_product_container <?php echo $product->css, (ncore_isFirst('product')?' digimember_first':''); ?>'>

			<?php if ($product->menu_headline): ?>
                <div class='digimember_product_headline'><?php echo $product->menu_headline?></div>
            <?php endif; ?>

			<div class='digimember_product_contents'>

				<?php foreach ($product->modules as $module):
                        $is_empty = empty( $module->pages );
                        if ($is_empty) continue;
                ?>
					<div class='digimember_module_container <?php echo $module->css, (ncore_isFirst('module')? 'digimember_first': '');?>'>

						<?php echo $module->link
							  ? "<div class='digimember_module_headline'>$module->link</div>"
							  : "<div class='digimember_module_without_headline'></div>";
						?>

						<ul class='digimember_module_contents'>
							<?php foreach ($module->pages as $page): ?>
								<li class='digimember_page <?php echo $page->css;?>'>
									<?php echo ncore_htmlLink( $page->url, $page->title )?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>

			</div>

		</div>
	<?php endforeach; ?>

	</div>

<?php endif; ?>