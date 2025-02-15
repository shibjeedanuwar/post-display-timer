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
if ( ! function_exists( 'post_display_timer_uninstall' ) ) {
	/**
	 * Clean up plugin data on uninstall.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function post_display_timer_uninstall() {
		// Security check.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Define options to delete - including all default options.
		$options_to_delete = array(
			'post_display_timer_enable_countdown_timer',
			'post_display_timer_set_count_timer',
			'post_display_timer_view_number_post',
			'post_display_timer_multiple_tab',
			'post_display_timer_show_visited_post_num',
			'post_display_timer_check_current_page',
			'post_display_timer_completion_code',
			'post_display_timer_start_button',
			'post_display_timer_random_post',
			'post_display_timer_post_urls',
			'post_display_timer_post_version',
		);

		// Delete options with error handling.
		foreach ( $options_to_delete as $option ) {
			if ( get_option( $option ) !== false ) {
				delete_option( $option );
			}
		}

		// Clean up any transients and other options with prefix.
		post_display_timer_delete_plugin_options_and_transients();

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Delete plugin options and transients using WordPress functions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function post_display_timer_delete_plugin_options_and_transients() {
		// Use WordPress caching functions for options.
		$cache_key   = 'post_display_timer_options_list';
		$all_options = wp_cache_get( $cache_key );

		if ( false === $all_options ) {
			$all_options = wp_load_alloptions();
			wp_cache_set( $cache_key, $all_options );
		}

		// Delete plugin options with proper prefix check.
		foreach ( $all_options as $option => $value ) {
			if ( 0 === strpos( $option, 'post_display_timer_' ) ) {
				delete_option( $option );
				wp_cache_delete( $option, 'options' );
			}
		}

		// Clean up transients with proper error handling.
		$transient_keys = array(
			'post_display_timer_hits',
			'post_display_timer_active',
			// Add any other specific transient keys here.
		);

		foreach ( $transient_keys as $transient ) {
			delete_transient( $transient );
			wp_cache_delete( $transient, 'transient' );
		}

		// Clean up the options cache.
		wp_cache_delete( $cache_key );
		wp_cache_flush();
	}
}

// Execute uninstall function.
post_display_timer_uninstall();
