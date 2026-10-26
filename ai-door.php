<?php
/**
 * Plugin Name:       AI Door
 * Description:       Lets AI assistants read what your business offers. Writes an agent card, a server card and an llms.txt from three answers. Free, no account needed.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            flowboxx
 * Author URI:        https://flowboxx.de/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-door
 * Domain Path:       /languages
 *
 * @package AI_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_DOOR_VERSION', '0.1.0' );
define( 'AI_DOOR_FILE', __FILE__ );
define( 'AI_DOOR_DIR', plugin_dir_path( __FILE__ ) );

require_once AI_DOOR_DIR . 'includes/class-ai-door-data.php';
require_once AI_DOOR_DIR . 'includes/class-ai-door-routes.php';
require_once AI_DOOR_DIR . 'includes/class-ai-door-block.php';
require_once AI_DOOR_DIR . 'includes/class-ai-door-admin.php';

add_action( 'init', array( 'AI_Door_Routes', 'maybe_serve' ), 0 );
add_action( 'rest_api_init', array( 'AI_Door_Routes', 'register_rest' ) );
add_action( 'init', array( 'AI_Door_Block', 'register' ) );
add_action( 'plugins_loaded', array( 'AI_Door_Admin', 'init' ) );
