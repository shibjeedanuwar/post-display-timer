<?php
/**
 * Admin settings page for Post Timer
 *
 * @package PostDisplayTimer
 * @since 1.0.0
 */

namespace pdt_display_timer\Admin;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Display the Timer Settings page
 */
function pdt_display_timer_settings_page() {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}

	$message      = '';
	$message_type = '';

	// Handle form submission.
	if ( isset( $_POST['action'] ) && 'pdt_display_timer_save_settings' === $_POST['action'] ) {
		\check_admin_referer( 'pdt_display_timer_settings_nonce', 'pdt_display_timer_nonce' );

		// Get sanitized form data.
		$form_data = array(
			'enable_countdown_timer' => isset( $_POST['enable_countdown_timer'] ) ? 1 : 0,
			'set_count_timer'        => isset( $_POST['set_count_timer'] ) ? \absint( \wp_unslash( $_POST['set_count_timer'] ) ) : 0,
			'show_visited_post_num'  => isset( $_POST['show_visited_post_num'] ) ? 1 : 0,
			'multiple_tab'           => isset( $_POST['multiple_tab'] ) ? 1 : 0,
			'check_currentPage'      => isset( $_POST['check_currentPage'] ) ? 1 : 0,
			'completion_code'        => isset( $_POST['completion_code'] ) ? \sanitize_textarea_field( \wp_unslash( $_POST['completion_code'] ) ) : '',
			'start_button'           => isset( $_POST['start_button'] ) ? 1 : 0,
			'view_number_post'       => isset( $_POST['view_number_post'] ) ? \absint( \wp_unslash( $_POST['view_number_post'] ) ) : 2,
			'random_post'            => isset( $_POST['random_post'] ) ? 1 : 0,
			'post_urls'              => isset( $_POST['post_urls'] ) ? \sanitize_textarea_field( \wp_unslash( $_POST['post_urls'] ) ) : '',
			'delayed_timer'          => isset( $_POST['delayed_timer'] ) ? 1 : 0,
		);

		$errors = array();

		// Validation checks.
		if ( $form_data['set_count_timer'] <= 0 ) {
			$errors[] = 'The timer must be a positive number.';
		}
		if ( $form_data['view_number_post'] < 2 || $form_data['view_number_post'] > 10 ) {
			$errors[] = 'Please select a number between 2 and 10 for the view number post.';
		}
		if ( ! empty( $form_data['post_urls'] ) ) {
			if ( $form_data['random_post'] ) {
				$errors[] = 'Please uncheck the "Show Random post" option when using custom URLs.';
			}
			$post_urls  = rtrim( $form_data['post_urls'], ',' ) . ',';
			$urls_array = array_map( 'trim', explode( ',', $post_urls ) );
			if ( count( $urls_array ) - 1 !== $form_data['view_number_post'] ) {
				$errors[] = 'The number of URLs must match the view number post.';
			}
		} elseif ( ! $form_data['random_post'] ) {
				$errors[] = 'Please check the "Show Random Post" option if you are not entering post URLs.';
		}
		if ( $form_data['enable_countdown_timer'] && empty( $form_data['completion_code'] ) ) {
			$errors[] = 'Please enter the code once the visitor has completed viewing all pages for verification.';
		}

		if ( empty( $errors ) ) {
			// Save validated options.
			foreach ( $form_data as $key => $value ) {
				update_option( "pdt_display_timer_{$key}", $value );
			}
			$message      = 'Settings saved successfully!';
			$message_type = 'success';
		} else {
			$message      = implode( '<br>', $errors );
			$message_type = 'error';
		}
	}

	// Get settings with defaults.
	$options = array(
		'enable_countdown_timer' => get_option( 'pdt_display_timer_enable_countdown_timer', 0 ),
		'set_count_timer'        => get_option( 'pdt_display_timer_set_count_timer', 60 ),
		'show_visited_post_num'  => get_option( 'pdt_display_timer_show_visited_post_num', 0 ),
		'check_currentPage'      => get_option( 'pdt_display_timer_check_currentPage', 0 ),
		'completion_code'        => get_option( 'pdt_display_timer_completion_code', '' ),
		'start_button'           => get_option( 'pdt_display_timer_start_button', 0 ),
		'view_number_post'       => get_option( 'pdt_display_timer_view_number_post', 2 ),
		'random_post'            => get_option( 'pdt_display_timer_random_post', 0 ),
		'post_urls'              => get_option( 'pdt_display_timer_post_urls', '' ),
		'multiple_tab'           => get_option( 'pdt_display_timer_multiple_tab', 0 ),
		'delayed_timer'          => get_option( 'pdt_display_timer_delayed_timer', 0 ),
	);

	?>
	
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( ! empty( $message ) ) : ?>
			<div class="notice notice-<?php echo 'success' === $message_type ? 'success' : 'error'; ?> is-dismissible">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
		<?php endif; ?>

		<div class="row">
			<div class="col-12 col-lg-8 border">
			<form method="post" action="">
					<?php wp_nonce_field( 'pdt_display_timer_settings_nonce', 'pdt_display_timer_nonce' ); ?>
					<input type="hidden" name="action" value="pdt_display_timer_save_settings">
					<table class="form-table indent-children" role="presentation" id="post-timer">
						<tbody class="">
							<tr>
								<th scope="row">Default Timer Settings</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span>Default Post Settings</span></legend>
										<label for="enable_countdown_timer" class="pdt_display_timer_switch">
											<input type="checkbox" 
											id="enable_countdown_timer" 
											name="enable_countdown_timer" 
											value="1" 
											<?php checked( $options['enable_countdown_timer'], 1 ); ?>>
											<span class="pdt_display_timer_slider"></span>
										</label>
										Enable Countdown Timer
										<br><br>
										<ul>
											<li>
												<label for="timer-set">
													<input name="set_count_timer" type="number" step="1" min="0" id="timer-set" value="<?php echo esc_attr( $options['set_count_timer'] ); ?>" class="small-text">
													Set the <strong class="text-warning">Timer</strong> in <strong class="text-danger">Seconds</strong> for how long each visitor stays on the post.
												</label>
											</li>
										</ul>
									</fieldset>
								</td>
							</tr>

							<tr>
								<th scope="row">Track Visitor</th>
								<td>
									<fieldset>
										<?php
										// Array of checkboxes for tracking visitors.
										$checkboxes = array(
											'show_visited_post_num' => 'Show Keep track of user visited pages <strong class="text-danger">1/10</strong>',
											'multiple_tab' => 'Avoid Displaying Multiple Countdown Timers in Different Tabs',
											'check_currentPage' => 'If a user leaves the current page, <strong>Counter timer pause</strong>',
										);
										foreach ( $checkboxes as $name => $label ) :
											?>
											<label for="<?php echo esc_attr( $name ); ?>">
												<input name="<?php echo esc_attr( $name ); ?>" type="checkbox" id="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $options[ $name ], 1 ); ?>>
												<?php echo wp_kses_post( $label ); ?>
											</label>
											<br>
										<?php endforeach; ?>
									</fieldset>
								</td>
							</tr>

							<tr>
								<th scope="row"> ENTER THE Code</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span>Get Code When Visitor Completes All Pages</span></legend>
										<p>
											<label for="completion_code">Enter the code that will be shown when a visitor has completed visiting all pages:</label>
										</p>
										<p>
											<textarea name="completion_code" rows="4" cols="50" id="completion_code" class="large-text code" placeholder="Enter the code to verify visitor views all pages:"><?php echo esc_textarea( $options['completion_code'] ); ?></textarea>
										</p>
									</fieldset>
								</td>
							</tr>

							<tr>
								<th scope="row">Optional Settings</th>
								<td>
									<fieldset>
										<?php
											// Optional settings checkboxes.
											$optional_checkboxes = array(
												'start_button' => 'Show a <span class="btn btn-success" style="font-size: 10px; padding: 10px;">START</span> button all timer when the visitor clicks, then the countdown timer starts.',
											);
											foreach ( $optional_checkboxes as $name => $label ) :
												?>
											<label for="<?php echo esc_attr( $name ); ?>">
												<input name="<?php echo esc_attr( $name ); ?>" 
													type="checkbox" 
													id="<?php echo esc_attr( $name ); ?>" 
													value="1"  
													<?php checked( $options[ $name ], 1 ); ?>>
												<?php echo wp_kses_post( $label ); ?>
											</label>
											<br>
												<?php
										endforeach;
											?>
									</fieldset>
								</td>
							</tr>

							<tr>
								<th scope="row">Post URLs Customize</th>
								<td>
									<fieldset>
										<select name="view_number_post" id="thread_comments_depth">
											<?php for ( $i = 2; $i <= 10; $i++ ) : ?>
												<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $options['view_number_post'], $i ); ?>><?php echo esc_html( $i ); ?></option>
											<?php endfor; ?>
										</select>
										<label for="thread_comments_depth">Select the number of posts you want visitors to view .</label>
										<p class="discription"><b class="text-danger">NOTE:</b> How many you want to show visitors to view</p>
										<br><br>
										<label for="random">
											<input name="random_post" type="checkbox" id="random" value="1" <?php checked( $options['random_post'], 1 ); ?> />
											Show Random post
										</label>
										<br><br>
										<p>
											<label for="post_urls">When you want to visit special posts only, please enter the post URLs, for example:<br>
												https://xyz.com/how-to-make-money-online/,<br/>
												https://xyz.com/post-timer/,<br>
												https://xyz.com/wordpress-free-plugin/,<br>
												https://xyz.com/thank-you-for-creating/,<br>
												Don't forget to add a comma.
											</label>
										</p>
										<p>Please enter each URL on a new line:</p>
										<textarea name="post_urls" rows="10" cols="50" id="post_urls" class="large-text code" placeholder="Note: Enter post URLs Here"><?php echo esc_textarea( $options['post_urls'] ); ?></textarea><br>
										<p class="discription"><b class="text-danger">NOTE:</b> Please remember that when you enter your post URLs, visitors will be randomly redirected to different landing URLs. Ensure that each visitor receives one specific landing URL to start the countdown from.</p>
								   
									</fieldset>
								</td>
							</tr>
						</tbody>
					</table>

					<div class="reflected-changes d-flex">
						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'post-display-timer' ); ?>">
						</p>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php
}
