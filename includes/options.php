<?php
// create custom plugin settings menu
add_action('admin_menu', 'bft_options_menu');

function bft_options_menu() {
	add_submenu_page(
		'balfolk-events'
		,'Options'
		,'Options'
		,'view_woocommerce_reports'
		,'balfolk-events-options'
		,'options_page'
	);

	// call register settings function
	add_action( 'admin_init', 'register_bft_plugin_settings' );
}

// register settings
function register_bft_plugin_settings() {
	register_setting( 'bft-settings-group', 'bft_products_ids_custom_checkbox' );
}

// display options page
function options_page() {
?>
<div class="wrap">
<h1>Balfolk events options</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'bft-settings-group' ); ?>
    <?php do_settings_sections( 'bft-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
			<th scope="row">
				<?php _e("Products IDs for custom checkbox (comma separated):", 'bft_events' ); ?> 
			</th>
			<td><input type="text" name="bft_products_ids_custom_checkbox" value="<?php echo esc_attr( get_option('bft_products_ids_custom_checkbox') ); ?>" /></td>
        </tr>
    </table>
    <?php submit_button(); ?>

</form>
</div>
<?php } ?>