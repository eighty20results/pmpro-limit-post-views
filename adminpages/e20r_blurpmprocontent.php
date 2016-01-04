<?php
/**
 * E20R Blur Protected Content settings page
 *
 * Displays settings page.
 *
 * @since 0.1.0
 */

// Check permissions first
if ( ! current_user_can( apply_filters( 'pmpro_edit_member_capability', 'manage_options' ) ) ) {
	wp_die( 'You do not have sufficient permissions to access this page.' );
}


require_once( PMPRO_DIR . '/adminpages/admin_header.php' );


/**
 * Display membership limits section.
 *
 * @since 0.3.0
 */
function e20rbpc_settings_section() {
	echo '<p>' . __( 'Users without one of the membership levels will be able to read the content of this many paragraphs of the protected content. The remaining text on the page will be blurred with a call-to-action (CTA) overlay', 'e20rbpc' ) . '</p>';
}

/**
 * Determine the number of words for the visible excerpt.
 *
 * @since 0.3.0
 */
function e20rbpc_settings_paragraphs($args) {

	$excerpt_size = empty($args) ? get_option( 'e20rbpc_settings') : $args;?>

	<input size="10" type="text" id="e20rbpc_settings_paragraphs"
	       name="e20rbpc_settings[paragraphs]" value="<?php echo esc_attr($excerpt_size['paragraphs']); ?>"> <?php _e('paragraphs readable', 'e20rbpc' ); ?>
	<?php
}

/**
 * Display redirection section.
 *
 * @since 0.3.0
 */
function e20rbpc_settings_ctapage($args) {

	$options = empty($args) ? get_option( 'e20rbpc_settings') : $args;

	$pagelist = wp_dropdown_pages(
		array(
			'selected' => $options['ctapage'],
			'name' => "e20rbpc_settings[ctapage]",
			'show_option_none' => __("PMPro Levels page", "e20rbpc"),
			'option_none_value' => 0,
		)
	);

	e20rbpc_write_log("Current CTA page: {$options['ctapage']}");
}


// Display settings page.
?>
	<h2><?php _e( 'Blur PMPro Content', 'e20rbpc' ); ?></h2>
	<form action="options.php" method="POST">
		<?php settings_fields( 'e20rbpc_settings' ); ?>
		<?php do_settings_sections( 'e20rbpc' ); ?>
		<?php submit_button(); ?>
	</form>
<?php

require_once(PMPRO_DIR . '/adminpages/admin_footer.php');