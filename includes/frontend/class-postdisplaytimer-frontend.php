<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package PostDisplayTimer
 * @subpackage PostDisplayTimer/public
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Handles post timer functionality including countdowns, user tracking, and AJAX interactions
 */
class PostDisplayTimer_Frontend {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Unique user identifier for state tracking.
	 *
	 * @var string
	 */
	private $user_key;

	/**
	 * Constructor.
	 *
	 * @param array $options Plugin options.
	 */
	public function __construct( array $options = null ) {
		$this->user_key = $this->post_display_timer_get_user_key();
		$this->post_display_timer_register_hooks();
		$this->post_display_timer_register_post_actions();
		// Set options, using default options if none are provided.
		if ( $options ) {
			$this->options = $options;
		} else {
			$this->options = $this->post_display_timer_get_default_options();
		}
	}

	/**
	 * Register WordPress hooks.
	 */
	private function post_display_timer_register_hooks(): void {
		add_filter( 'the_content', array( $this, 'post_display_timer_filter_post_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'post_display_timer_register_frontend_assets' ) );

		$ajax_actions = array(
			'start_button_click',
			'start_countdown',
			'end_countdown',
			'close_current_tab',
		);

		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_{$action}", array( $this, "post_display_timer_handle_{$action}" ) );
			add_action( "wp_ajax_nopriv_{$action}", array( $this, "post_display_timer_handle_{$action}" ) );
		}
	}

	/**
	 * Default configuration values for the plugin.
	 *
	 * @return array
	 */
	private function post_display_timer_get_default_options(): array {
		return array(
			'set_count_timer'        => get_option( 'post_display_timer_set_count_timer', 60 ),
			'enable_countdown_timer' => get_option( 'post_display_timer_enable_countdown_timer', 1 ),
			'show_visited_post_num'  => get_option( 'post_display_timer_show_visited_post_num', 0 ),
			'start_button'           => get_option( 'post_display_timer_start_button', 0 ),
			'view_number_post'       => get_option( 'post_display_timer_view_number_post', 2 ),
			'completion_code'        => get_option( 'post_display_timer_completion_code', '' ),
			'random_post'            => get_option( 'post_display_timer_random_post', 0 ),
			'multiple_tab'           => get_option( 'post_display_timer_multiple_tab', 0 ),
			'post_urls'              => get_option( 'post_display_timer_post_urls', '' ),
			'check_currentPage'      => get_option( 'post_display_timer_check_currentPage', 0 ),
		);
	}

	/**
	 * Generate unique user identifier for state tracking.
	 *
	 * @return string
	 */
	private function post_display_timer_get_user_key(): string {
		$cookie_name   = 'post_display_timer__guest_id';
		$cookie_expiry = time() + ( HOUR_IN_SECONDS );

		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			$guest_id = bin2hex( random_bytes( 16 ) );
			setcookie(
				$cookie_name,
				wp_slash( $guest_id ), // Sanitize output when setting.
				array(
					'expires'  => $cookie_expiry,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax', // or 'None' if needed for cross-site usage.
				)
			);
		} else {
			$guest_id = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		}

		return 'post_display_timer_' . $guest_id;
	}

	/**
	 * Register post action handlers.
	 */
	private function post_display_timer_register_post_actions(): void {
		add_action( 'wp', array( $this, 'post_display_timer_handle_post_requests' ) );
	}

	/**
	 * Handle form submissions for next post navigation.
	 */
	public function post_display_timer_handle_post_requests(): void {
		if ( ! isset( $_GET['action'] ) || 'next' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			return;
		}

		// Verify nonce and user permissions.
		if ( ! isset( $_GET['post_timer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['post_timer_nonce'] ) ), 'post_display_timer_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'post-display-timer' ) );
		}

		$this->post_display_timer_handle_next_post();
	}

	/**
	 * Process next post navigation.
	 */
	private function post_display_timer_handle_next_post(): void {
		// Update hit counter.
		$hit_count = (int) get_transient( "{$this->user_key}_pvt_hits" );
		set_transient( "{$this->user_key}_pvt_hits", $hit_count + 1, 30 * MINUTE_IN_SECONDS );

		if ( ! empty( $this->options['post_urls'] ) ) {
			$this->post_display_timer_redirect_to_url_from_list();
		} else {
			$this->post_display_timer_redirect_to_random_post();
		}
	}

	/**
	 * Redirect to a post.
	 *
	 * @param string $url URL to redirect to.
	 */
	private function post_display_timer_redirect_to_post( $url = null ): void {
		if ( $url ) {
			// Add validation for URL.
			if ( ! wp_http_validate_url( $url ) ) {
				return;
			}

			$redirect_result = wp_safe_redirect( esc_url_raw( $url ) );
			if ( ! $redirect_result ) {
				return;
			}
			exit();
		}
	}

	/**
	 * Redirect to a random post from configured URLs.
	 */
	private function post_display_timer_redirect_to_random_post(): void {
		$current_post_id = get_the_ID();

		// First, get a small batch of random posts.
		$args = array(
			'numberposts' => 5, // Fetch small batch for better randomization.
			'orderby'     => 'rand',
			'post_status' => 'publish',
			'post_type'   => 'post',
			'fields'      => 'ids', // Only get IDs for better performance.
		);

		$random_posts = get_posts( $args );

		if ( ! empty( $random_posts ) ) {
			// Remove current post from results if present.
			$random_posts = array_values(
				array_filter(
					$random_posts,
					function ( $post_id ) use ( $current_post_id ) {
						return $post_id !== $current_post_id;
					}
				)
			);

			if ( ! empty( $random_posts ) ) {
				// Get first post or random post from filtered array.
				$redirect_post_id = $random_posts[0];
				$next_url         = get_permalink( $redirect_post_id );
				$this->post_display_timer_redirect_to_post( $next_url );
				return;
			}
		}

		// Fallback to home if no posts found.
		wp_safe_redirect( home_url() );
		exit();
	}

	/**
	 * Redirect to a URL from the predefined list.
	 */
	private function post_display_timer_redirect_to_url_from_list(): void {
		$urls = array_filter( array_map( 'trim', explode( ',', $this->options['post_urls'] ) ) );

		if ( ! empty( $urls ) ) {
			$random_url = $urls[ array_rand( $urls ) ];
			$this->post_display_timer_redirect_to_post( $random_url );
		}
	}

	/**
	 * Add timer elements to post content.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public function post_display_timer_filter_post_content( $content ): string {
		if ( ! is_single() || ! $this->options['enable_countdown_timer'] ) {
			return $content;
		}

		// Check for active timers in multiple tab mode.
		if ( $this->options['multiple_tab'] ) {
			$transient_key = "{$this->user_key}_pvt_timer_active";
			$active_status = get_transient( $transient_key );

			if ( $active_status ) {
				return $content;
			}
		}

		// Render timer template.
		$hit_count = (int) get_transient( "{$this->user_key}_pvt_hits" );

		ob_start();
		$this->post_display_timer_render_timer_template( get_the_ID(), $hit_count );
		return $content . ob_get_clean();
	}

	/**
	 * Handle countdown start request.
	 */
	public function post_display_timer_handle_start_countdown(): void {
		// Fix: Improve error handling and validation.
		try {
			if ( ! check_ajax_referer( 'post_display_timer_nonce', 'post_timer_nonce', false ) ) {
				wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
				return;
			}

			if ( ! isset( $_POST['post_id'] ) ) {
				wp_send_json_error( array( 'message' => 'Missing post ID' ), 400 );
				return;
			}

			$transient_key = "{$this->user_key}_pvt_timer_active";
			$active_status = get_transient( $transient_key );

			if ( $active_status ) {
				wp_send_json_error(
					array(
						'message' => 'Timer already active in another tab',
						'status'  => false,
					)
				);
				return;
			}

			set_transient( $transient_key, true, 5 * MINUTE_IN_SECONDS );

			wp_send_json_success(
				array(
					'status'        => 'success',
					'message'       => 'Countdown started',
					'active_status' => true,
					'transient_key' => $transient_key,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}

	/**
	 * Handle click start button request.
	 */
	public function post_display_timer_handle_start_button_click(): void {
		if ( ! check_ajax_referer( 'post_display_timer_nonce', 'post_timer_nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			return;
		}

		$transient_key = "{$this->user_key}_pvt_timer_active";
		$active_status = get_transient( $transient_key );

		if ( $active_status ) {
			$data = array(
				'status' => true,
			);
		} else {
			$data = array(
				'status' => false,
			);
		}

		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Handle countdown completion.
	 */
	public function post_display_timer_handle_end_countdown(): void {
		if ( ! check_ajax_referer( 'post_display_timer_nonce', 'post_timer_nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			return;
		}

		delete_transient( "{$this->user_key}_pvt_timer_active" );

		$hit_count        = (int) get_transient( $this->user_key . '_pvt_hits' );
		$view_number_post = (int) $this->options['view_number_post'];
		$completion_code  = '';

		// Reset hit count before sending response if condition is met.
		if ( $hit_count >= $view_number_post ) {
			set_transient( $this->user_key . '_pvt_hits', 0 );
			$completion_code = $this->options['completion_code'];
		} else {
			$completion_code = '';
		}

		$response = array(
			'pvt_complete_code' => $completion_code,
		);

		wp_send_json_success( $response );
		wp_die();
	}

	/**
	 * Handle tab close event cleanup.
	 */
	public function post_display_timer_handle_close_current_tab(): void {
		if ( ! check_ajax_referer( 'post_display_timer_nonce', 'post_timer_nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			return;
		}

		delete_transient( "{$this->user_key}_pvt_timer_active" );
		wp_send_json_success( array( 'message' => 'Timer state cleared' ) );
	}

	/**
	 * Register frontend scripts and localization data.
	 */
	public function post_display_timer_register_frontend_assets(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script(
			'postdisplaytimer-frontend',
			POSTDISPLAYTIMER_URL . 'assets/js/post_display_timer_frontend.js',
			array( 'jquery' ),
			POSTDISPLAYTIMER_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		// Timer data localization.
		$timer_data = array(
			'admin_url' => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'post_display_timer_nonce' ),
			'post_id'   => get_the_ID(),
			'options'   => array(
				'set_count_timer'        => $this->options['set_count_timer'],
				'enable_countdown_timer' => $this->options['enable_countdown_timer'],
				'start_button'           => $this->options['start_button'],
				'hit_count'              => (int) get_transient( $this->user_key . '_pvt_hits' ),
				'view_number_post'       => $this->options['view_number_post'],
				'check_currentPage'      => $this->options['check_currentPage'],
				'multiple_tab'           => $this->options['multiple_tab'],
			),
		);

		wp_localize_script( 'postdisplaytimer-frontend', 'PostDisplayTimerData', $timer_data );
	}

	/**
	 * Renders the timer template for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @param int $hit_count Hit count.
	 */
	private function post_display_timer_render_timer_template( $post_id, $hit_count ): void {
		include POSTDISPLAYTIMER_DIR . '/includes/frontend/views/pvt-timer-templates-ui.php';
	}
}

new PostDisplayTimer_Frontend();
