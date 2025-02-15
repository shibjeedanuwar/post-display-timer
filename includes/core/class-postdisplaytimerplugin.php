<?php
/**
 * Core functionality for Post View Timer plugin
 *
 * @package PostDisplayTimer
 * @subpackage PostDisplayTimer/includes/core
 */

namespace PostDisplayTimer\Core;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class for Post Display Timer.
 */
final class PostDisplayTimerPlugin {
	/**
	 * Singleton instance.
	 *
	 * @var self
	 */
	private static ?self $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return self Plugin instance.
	 */
	public static function post_display_timer_get_instance(): self {
		// Get plugin instance.
		return self::$instance ??= new self();
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Private constructor to prevent direct instantiation.
		$this->post_display_timer_init_hooks();
	}


	/**
	 * Initialize WordPress hooks.
	 */
	private function post_display_timer_init_hooks(): void {
		// Initialize WordPress hooks.
		register_activation_hook( __FILE__, array( __CLASS__, 'post_display_timer_activate' ) );

		// Set default options.
		add_action( 'init', array( __CLASS__, 'post_display_timer_set_default_options' ) );

		// Handle session initialization.
		add_action(
			'init',
			function () {
				if ( ! session_id() && ! headers_sent() ) {
					session_start();
				}
			}
		);
	}

	/**
	 * Plugin activation hook.
	 */
	public static function post_display_timer_activate(): void {
		// Set default options.
		self::post_display_timer_set_default_options();
	}



	/**
	 * Sets default plugin options if they don't already exist.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function post_display_timer_set_default_options(): void {
		// Define default options for the plugin.
		$default_options = array(
			'post_display_timer_post_version'          => POSTDISPLAYTIMER_VERSION,
			'post_display_timer_show_visited_post_num' => (int) 1,
			'post_display_timer_view_number_post'      => (int) 1,
			'post_display_timer_check_currentPage'     => (int) 1,
			'post_display_timer_random_post'           => (int) 1,
			'post_display_timer_multiple_tab'          => (int) 1,
		);

		// Loop through default options and add them if they don't exist.
		foreach ( $default_options as $option => $value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $value, '', 'yes' );
			}
		}
	}
}

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		PostDisplayTimerPlugin::post_display_timer_get_instance();
	}
);
