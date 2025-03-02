<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    PostDisplayTimer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Fix: Make sure function is not already defined.
if ( ! function_exists( 'pdt_display_timer_uninstall' ) ) {
	/**
	 * Clean up plugin data on uninstall.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function pdt_display_timer_uninstall() {
		// Security check.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Define options to delete - including all default options.
		$options_to_delete = array(
			'pdt_display_timer_enable_countdown_timer',
			'pdt_display_timer_set_count_timer',
			'pdt_display_timer_view_number_post',
			'pdt_display_timer_multiple_tab',
			'pdt_display_timer_show_visited_post_num',
			'pdt_display_timer_check_current_page',
			'pdt_display_timer_completion_code',
			'pdt_display_timer_start_button',
			'pdt_display_timer_random_post',
			'pdt_display_timer_post_urls',
			'pdt_display_timer_post_version',
		);

		// Delete options with error handling.
		foreach ( $options_to_delete as $option ) {
			if ( get_option( $option ) !== false ) {
				delete_option( $option );
			}
		}


		// Clear any cached data.
		wp_cache_flush();
	}

	
}

// Execute uninstall function.
pdt_display_timer_uninstall();
