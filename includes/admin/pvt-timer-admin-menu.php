<?php
/**
 * Admin menu functionality for Post View Timer.
 *
 * @package PostDisplayTimer
 * @since 1.0.0
 */

namespace pdt_display_timer\Admin;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Include submenu page files.
require_once PDTDISPLAYTIMER_DIR . 'includes/admin/pvt-timer-settings.php';

// Initialize admin functionality.
add_action( 'init', __NAMESPACE__ . '\pdt_display_timer_admin_init' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\pdt_display_timer_enqueue_admin_scripts' );

/**
 * Initialize admin functionality.
 */
function pdt_display_timer_admin_init() {
	add_action( 'admin_menu', __NAMESPACE__ . '\pdt_display_timer_add_admin_menu' );
}

/**
 * Add admin menu and submenus.
 */
function pdt_display_timer_add_admin_menu() {
	add_menu_page(
		__( 'Post Display Timer Settings', 'post-display-timer' ),
		__( 'Post Display Timer', 'post-display-timer' ),
		'manage_options',
		'post-display-timer-settings',
		__NAMESPACE__ . '\pdt_display_timer_settings_page',
		'dashicons-clock',
		25
	);
}

/**
 * Enqueue admin styles and scripts.
 *
 * @param string $hook Current admin page hook.
 */
function pdt_display_timer_enqueue_admin_scripts( $hook ) {
	if ( 'toplevel_page_post-display-timer-settings' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'post-display-timer-admin',
		PDTDISPLAYTIMER_URL . 'assets/admin/css/post_display_timer_admin_styles.css',
		array(),
		PDTDISPLAYTIMER_VERSION
	);
}
