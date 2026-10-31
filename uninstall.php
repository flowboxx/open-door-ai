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
delete_option( 'open_door_service' );
delete_transient( 'open_door_service_state' );
delete_transient( 'open_door_inquiry_count' );
