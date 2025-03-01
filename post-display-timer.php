<?php
/**
 * Plugin Name:       Post Display Timer
 * Plugin URI:        https://github.com/shibjeedanuwar/post-display-timer
 * Description:       Displays posts with a customizable timer, perfect for research studies and timed reading exercises.
 * Version:          1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:           Shibjee Danuwar
 * License:          GPL v2 or later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:      post-display-timer
 * 
 * @package PostisplayTimer
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $PDT_options;

// Constants.
define( 'PDTDISPLAYTIMER_DIR', plugin_dir_path( __FILE__ ) );
define( 'PDTDISPLAYTIMER_URL', plugin_dir_url( __FILE__ ) );
define( 'PDTDISPLAYTIMER_VERSION', '1.0.0' );


// Load core functionality.
require_once PDTDISPLAYTIMER_DIR . 'includes/core/class-postdisplaytimerplugin.php';
require_once PDTDISPLAYTIMER_DIR . 'includes/admin/pvt-timer-admin-menu.php';
require_once PDTDISPLAYTIMER_DIR . 'includes/frontend/class-pdt-display-timer-frontend.php';
