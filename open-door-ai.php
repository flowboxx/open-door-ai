<?php
/**
 * Plugin Name:       Open Door for AI Assistants
 * Description:       When an AI assistant such as ChatGPT finds your business, it can leave an inquiry for you by e-mail (optional inquiry service). Free: agent card, llms.txt and a read-only MCP endpoint from three answers. No AI inside.
 * Version:           0.3.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            flowboxx
 * Author URI:        https://flowboxx.de/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       open-door-ai
 * Domain Path:       /languages
 *
 * @package Open_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OPEN_DOOR_VERSION', '0.3.1' );
define( 'OPEN_DOOR_FILE', __FILE__ );
define( 'OPEN_DOOR_DIR', plugin_dir_path( __FILE__ ) );

require_once OPEN_DOOR_DIR . 'includes/class-open-door-data.php';
require_once OPEN_DOOR_DIR . 'includes/class-open-door-service.php';
require_once OPEN_DOOR_DIR . 'includes/class-open-door-routes.php';
require_once OPEN_DOOR_DIR . 'includes/class-open-door-block.php';
require_once OPEN_DOOR_DIR . 'includes/class-open-door-admin.php';

add_action( 'init', array( 'Open_Door_Routes', 'maybe_serve' ), 0 );
add_action( 'rest_api_init', array( 'Open_Door_Routes', 'register_rest' ) );
add_action( 'init', array( 'Open_Door_Block', 'register' ) );
add_action( 'plugins_loaded', array( 'Open_Door_Admin', 'init' ) );
