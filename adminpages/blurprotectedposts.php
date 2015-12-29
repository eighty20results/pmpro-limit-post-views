<?php
/**
 * PMPro Limit Post Views settings page
 *
 * Displays settings page.
 *
 * @since 0.3.0
 * @package PMPro_Limit_Post_Views
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
function pmprobpp_settings_section_excerpt() {
	echo '<p>' . __( 'Users without one of the membership levels will be able to see this many words of the post/page for the protected post/page. The remaining text on the page will be obfuscated', 'pmprobpp' ) . '</p>';
}

/**
 * Determine the number of words for the visible excerpt.
 *
 * @since 0.3.0
 */
function pmprobpp_settings_field_sizelimit($args) {

	$excerpt_size = empty($args) ? get_option( 'pmprobpp_settings') : $args;?>

	<input size="10" type="text" id="pmprobpp_settings"
	       name="pmprobpp_settings[wordcount]" value="<?php echo $excerpt_size['wordcount']; ?>"> <?php _e('words readable in excerpt', 'pmprobpp' ); ?>
	<?php
}

/**
 * Display redirection section.
 *
 * @since 0.3.0
 */
function pmprobpp_settings_section_redirection() {
}


// Display settings page.
?>
	<h2><?php _e( 'PMPro - Blur Protected Posts', 'pmprobpp' ); ?></h2>
	<form action="options.php" method="POST">
		<?php settings_fields( 'pmprobpp_settings' ); ?>
		<?php do_settings_sections( 'pmpro_blurprotectedposts' ); ?>
		<?php submit_button(); ?>
	</form>
<?php

require_once(PMPRO_DIR . '/adminpages/admin_footer.php');