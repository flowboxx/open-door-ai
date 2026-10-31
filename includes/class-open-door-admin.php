<?php
/**
 * Setup page: three questions, the list of addresses and a self-check.
 *
 * @package Open_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Open_Door_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( OPEN_DOOR_FILE ), array( __CLASS__, 'links' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=open-door-ai' ) ) . '">' . esc_html__( 'Set up', 'open-door-ai' ) . '</a>' );
		return $links;
	}

	public static function menu() {
		add_options_page( __( 'Open Door', 'open-door-ai' ), __( 'Open Door', 'open-door-ai' ), 'manage_options', 'open-door-ai', array( __CLASS__, 'page' ) );
	}

	public static function register_setting() {
		register_setting(
			'open_door',
			Open_Door_Data::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public static function sanitize( $in ) {
		$in = is_array( $in ) ? $in : array();
		return array(
			'company'      => isset( $in['company'] ) ? mb_substr( sanitize_text_field( $in['company'] ), 0, 120 ) : '',
			'services'     => isset( $in['services'] ) ? mb_substr( sanitize_textarea_field( $in['services'] ), 0, 3000 ) : '',
			'contact_mail' => isset( $in['contact_mail'] ) ? sanitize_email( $in['contact_mail'] ) : '',
			'footer'       => empty( $in['footer'] ) ? 0 : 1,
		);
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s   = Open_Door_Data::settings();
		$opt = Open_Door_Data::OPTION;
		echo '<div class="wrap"><h1>' . esc_html__( 'Open Door for AI Assistants', 'open-door-ai' ) . '</h1>';
		echo '<p>' . esc_html__( 'Tell us about your business. We write a short description that AI assistants can read.', 'open-door-ai' ) . '</p>';
		if ( ! Open_Door_Data::is_https() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Your website needs https (the lock symbol in the browser) first. Please ask your hoster. Until then nothing is published.', 'open-door-ai' ) . '</p></div>';
		}
		if ( Open_Door_Data::has_sensitive_text() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please leave prices and e-mail addresses out of the list of services. Prices are given by a person.', 'open-door-ai' ) . '</p></div>';
		}
		echo '<form method="post" action="options.php">';
		settings_fields( 'open_door' );
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label for="open-door-ai-company">' . esc_html__( 'Company name', 'open-door-ai' ) . '</label></th><td><input id="open-door-ai-company" class="regular-text" name="' . esc_attr( $opt ) . '[company]" value="' . esc_attr( $s['company'] ) . '" required></td></tr>';
		echo '<tr><th scope="row"><label for="open-door-ai-services">' . esc_html__( 'What do you offer? One line each', 'open-door-ai' ) . '</label></th><td><textarea id="open-door-ai-services" class="large-text" rows="6" name="' . esc_attr( $opt ) . '[services]">' . esc_textarea( $s['services'] ) . '</textarea></td></tr>';
		echo '<tr><th scope="row"><label for="open-door-ai-mail">' . esc_html__( 'E-mail for inquiries (not shown on your website)', 'open-door-ai' ) . '</label></th><td><input id="open-door-ai-mail" type="email" class="regular-text" name="' . esc_attr( $opt ) . '[contact_mail]" value="' . esc_attr( $s['contact_mail'] ) . '"></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Link below the box', 'open-door-ai' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $opt ) . '[footer]" value="1"' . checked( 1, (int) $s['footer'], false ) . '> ' . esc_html__( 'Show "runs on flowboxx" under the "Ask an AI about us" box', 'open-door-ai' ) . '</label></td></tr>';
		echo '</table>';
		submit_button( __( 'Save and switch on', 'open-door-ai' ) );
		echo '</form>';

		if ( Open_Door_Data::is_configured() && Open_Door_Data::is_https() ) {
			echo '<h2>' . esc_html__( 'Done. Your short description is now readable at these addresses.', 'open-door-ai' ) . '</h2><ul>';
			foreach ( Open_Door_Data::addresses() as $url ) {
				echo '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a></li>';
			}
			echo '</ul>';
			echo '<p>' . esc_html__( 'Put the box on a page: add the block "Ask an AI about us" or the shortcode [open_door_ask].', 'open-door-ai' ) . '</p>';
			echo '<form method="post">';
			wp_nonce_field( 'open_door_check', 'open_door_check_nonce' );
			submit_button( __( 'Check my addresses', 'open-door-ai' ), 'secondary', 'open_door_check', false );
			echo '</form>';
			self::run_check();
		}
		echo '<p class="description">' . esc_html__( 'This plugin has no AI inside and sends no data anywhere. Nobody can promise that an AI assistant will mention your business.', 'open-door-ai' ) . '</p>';
		echo '</div>';
	}

	private static function run_check() {
		if ( empty( $_POST['open_door_check'] ) || ! isset( $_POST['open_door_check_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['open_door_check_nonce'] ) ), 'open_door_check' ) ) {
			return;
		}
		echo '<ul>';
		foreach ( Open_Door_Data::addresses() as $url ) {
			$r  = wp_remote_get( $url, array( 'timeout' => 8 ) );
			$ok = ! is_wp_error( $r ) && 200 === wp_remote_retrieve_response_code( $r );
			echo '<li>' . ( $ok ? esc_html__( 'OK', 'open-door-ai' ) : esc_html__( 'Not reachable. A security plugin or your hoster may block this address.', 'open-door-ai' ) ) . ': ' . esc_html( $url ) . '</li>';
		}
		echo '</ul>';
	}
}
