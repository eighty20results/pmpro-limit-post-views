<?php
/**
 * Admin functions
 *
 * Sets up admin pages and options.
 *
 * @since 0.3.0
 * @package PMPro_Limit_Post_views
 */

/**
 * Add settings page to admin menu.
 *
 * @since 0.3.0
 */
function e20rbpc_admin_menu() {
	add_options_page(
		__('Blur Content','e20rbpc'),
		__('Blur Content', 'e20rbpc'),
		apply_filters( 'pmpro_edit_member_capability', 'manage_options' ),
		'e20rbpc',
		'e20rbpc_settings_page'
	);
}

add_action( 'admin_menu', 'e20rbpc_admin_menu' );

/**
 * Include settings page.
 *
 * @since 0.1.0
 */
function e20rbpc_settings_page() {
	require_once( plugin_dir_path( __FILE__ ) . '../adminpages/e20r_blurpmprocontent.php' );
}

/**
 * Register settings sections and fields.
 *
 * @since 0.1.0
 */
function e20rbpc_admin_init() {

	// Register limits settings section.
	add_settings_section(
		'e20rbpc_settings_section',
		__('', 'e20rbpc'),
		'e20rbpc_settings_section',
		'e20rbpc'
	);

	// Register blur setting fields.
	add_settings_field(
		'e20rbpc_settings_paragraphs',
		__('Show as regular text', 'e20rbpc'),
		'e20rbpc_settings_paragraphs',
		'e20rbpc',
		'e20rbpc_settings_section'

	);

	// Register blur setting fields.
	add_settings_field(
		'e20rbpc_settings_cta',
		__('CTA content', 'e20rbpc'),
		'e20rbpc_settings_ctapage',
		'e20rbpc',
		'e20rbpc_settings_section'

	);

	// Register Blur Protected Pages setting.
	register_setting(
		'e20rbpc_settings',
		'e20rbpc_settings',
		'e20rbpc_sanitize_sizelimit'
	);

}

add_action( 'admin_init', 'e20rbpc_admin_init' );

/**
 * Sanitize word limit field
 *
 * @since 0.1.0
 * @param $args
 *
 * @return integer
 */
function e20rbpc_sanitize_sizelimit($args) {

	if(!is_numeric($args['paragraphs'])) {
		$args['paragraphs'] = 2;
	}

	return $args;
}