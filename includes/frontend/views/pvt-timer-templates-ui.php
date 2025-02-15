<?php
/**
 * Post Timer Template
 *
 * @package PostDisplayTimer
 * @version 1.1.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;


?>
<div id="timer-count-post-<?php echo esc_attr( $post_id ); ?>"
	class="pvt-timer-container"
	role="region"
	aria-label="<?php esc_attr_e( 'Post View Timer', 'post-view-timer' ); ?>">

	<div class="pvt-timer-countdown-wrapper">
		<div class="pvt-timer-parent">
			<div id="countdown-<?php echo esc_attr( $post_id ); ?>"
				class="pvt-timer-countdown"
				role="timer"
				aria-label="<?php esc_attr_e( 'Countdown Timer', 'post-view-timer' ); ?>">
				<?php echo esc_html( $this->options['set_count_timer'] ); ?>
			</div>
			<?php if ( $this->options['show_visited_post_num'] ) : ?>
			<div id="hitCount-<?php echo esc_attr( $post_id ); ?>"
				class="pvt-timer-hit-count"
				aria-live="polite">
				<?php
				printf(
					/* translators: 1: Current hit count, 2: Maximum allowed hits */
					esc_html__( 'View: %1$d/%2$d', 'post-view-timer' ),
					esc_html( $hit_count ),
					esc_html( $this->options['view_number_post'] )
				);
				?>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $this->options['start_button'] && $hit_count <= $this->options['view_number_post'] ) : ?>
		<div class="pvt-timer-start-btn-wrapper">
			<button
			type="submit"
				class="pvt-timer-next-btn"
				id="startButton-<?php echo esc_attr( $post_id ); ?>"
				aria-label="<?php esc_attr_e( 'start timer', 'post-view-timer' ); ?>">
				<?php esc_html_e( 'Start Timer', 'post-view-timer' ); ?>
			</button>
		</div>
	<?php endif; ?>
	<?php if ( $hit_count < $this->options['view_number_post'] ) : ?>
	<div 
		id="nextPostContainer-<?php echo esc_attr( $post_id ); ?>" 
		class="pvt-timer-next-container" 
		aria-live="polite" 
		aria-atomic="true">
		<form 
			method="GET" 
			action="" 
			id="nextForm-<?php echo esc_attr( $post_id ); ?>" 
			class="pvt-timer-next-form">
			<input type="hidden" name="action" value="next">
			<input 
				type="hidden" 
				name="current_post_id" 
				value="<?php echo esc_attr( $post_id ); ?>">
			<?php wp_nonce_field( 'post_display_timer_nonce', 'post_timer_nonce' ); ?>
			<button 
				type="submit" 
				class="pvt-timer-next-btn" 
				id="nextPost-<?php echo esc_attr( $post_id ); ?>" 
				disabled 
				aria-label="<?php esc_attr_e( 'Next Post', 'post-display-timer' ); ?>" style="display:none">
				<?php echo esc_html( $this->options['next_post_button_text'] ?? __( 'Next Post', 'post-view-timer' ) ); ?>
			</button>
		</form>
	</div> <!-- End of nextPostContainer -->
	<?php endif; ?>
</div> <!-- End of timer-count-post -->
