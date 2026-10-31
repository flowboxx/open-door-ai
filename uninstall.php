<?php
/**
 * Removes the plugin's settings on deletion.
 *
 * @package Open_Door
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'open_door_settings' );
