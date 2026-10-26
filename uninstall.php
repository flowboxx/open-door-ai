<?php
/**
 * Removes the plugin's settings on deletion.
 *
 * @package AI_Door
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'ai_door_settings' );
