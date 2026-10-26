<?php
/**
 * Setup page: three questions, the list of addresses and a self-check.
 *
 * @package AI_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Door_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( AI_DOOR_FILE ), array( __CLASS__, 'links' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=ai-door' ) ) . '">' . esc_html__( 'Set up', 'ai-door' ) . '</a>' );
		return $links;
	}

	public static function menu() {
		add_options_page( __( 'AI Door', 'ai-door' ), __( 'AI Door', 'ai-door' ), 'manage_options', 'ai-door', array( __CLASS__, 'page' ) );
	}

	public static function register_setting() {
		register_setting(
			'ai_door',
			AI_Door_Data::OPTION,
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
		$s   = AI_Door_Data::settings();
		$opt = AI_Door_Data::OPTION;
		echo '<div class="wrap"><h1>' . esc_html__( 'AI Door', 'ai-door' ) . '</h1>';
		echo '<p>' . esc_html__( 'Tell us about your business. We write a short description that AI assistants can read.', 'ai-door' ) . '</p>';
		if ( ! AI_Door_Data::is_https() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Your website needs https (the lock symbol in the browser) first. Please ask your hoster. Until then nothing is published.', 'ai-door' ) . '</p></div>';
		}
		if ( AI_Door_Data::has_sensitive_text() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please leave prices and e-mail addresses out of the list of services. Prices are given by a person.', 'ai-door' ) . '</p></div>';
		}
		echo '<form method="post" action="options.php">';
		settings_fields( 'ai_door' );
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label for="ai-door-company">' . esc_html__( 'Company name', 'ai-door' ) . '</label></th><td><input id="ai-door-company" class="regular-text" name="' . esc_attr( $opt ) . '[company]" value="' . esc_attr( $s['company'] ) . '" required></td></tr>';
		echo '<tr><th scope="row"><label for="ai-door-services">' . esc_html__( 'What do you offer? One line each', 'ai-door' ) . '</label></th><td><textarea id="ai-door-services" class="large-text" rows="6" name="' . esc_attr( $opt ) . '[services]">' . esc_textarea( $s['services'] ) . '</textarea></td></tr>';
		echo '<tr><th scope="row"><label for="ai-door-mail">' . esc_html__( 'E-mail for inquiries (not shown on your website)', 'ai-door' ) . '</label></th><td><input id="ai-door-mail" type="email" class="regular-text" name="' . esc_attr( $opt ) . '[contact_mail]" value="' . esc_attr( $s['contact_mail'] ) . '"></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Link below the box', 'ai-door' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $opt ) . '[footer]" value="1"' . checked( 1, (int) $s['footer'], false ) . '> ' . esc_html__( 'Show "runs on flowboxx" under the "Ask an AI about us" box', 'ai-door' ) . '</label></td></tr>';
		echo '</table>';
		submit_button( __( 'Save and switch on', 'ai-door' ) );
		echo '</form>';

		if ( AI_Door_Data::is_configured() && AI_Door_Data::is_https() ) {
			echo '<h2>' . esc_html__( 'Done. Your short description is now readable at these addresses.', 'ai-door' ) . '</h2><ul>';
			foreach ( AI_Door_Data::addresses() as $url ) {
				echo '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a></li>';
			}
			echo '</ul>';
			echo '<p>' . esc_html__( 'Put the box on a page: add the block "Ask an AI about us" or the shortcode [ai_door_ask].', 'ai-door' ) . '</p>';
			echo '<form method="post">';
			wp_nonce_field( 'ai_door_check', 'ai_door_check_nonce' );
			submit_button( __( 'Check my addresses', 'ai-door' ), 'secondary', 'ai_door_check', false );
			echo '</form>';
			self::run_check();
		}
		echo '<p class="description">' . esc_html__( 'This plugin has no AI inside and sends no data anywhere. Nobody can promise that an AI assistant will mention your business.', 'ai-door' ) . '</p>';
		echo '</div>';
	}

	private static function run_check() {
		if ( empty( $_POST['ai_door_check'] ) || ! isset( $_POST['ai_door_check_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_door_check_nonce'] ) ), 'ai_door_check' ) ) {
			return;
		}
		echo '<ul>';
		foreach ( AI_Door_Data::addresses() as $url ) {
			$r  = wp_remote_get( $url, array( 'timeout' => 8 ) );
			$ok = ! is_wp_error( $r ) && 200 === wp_remote_retrieve_response_code( $r );
			echo '<li>' . ( $ok ? esc_html__( 'OK', 'ai-door' ) : esc_html__( 'Not reachable. A security plugin or your hoster may block this address.', 'ai-door' ) ) . ': ' . esc_html( $url ) . '</li>';
		}
		echo '</ul>';
	}
}
