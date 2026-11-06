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
		add_options_page( __( 'flowboxx Door', 'open-door-ai' ), __( 'flowboxx Door', 'open-door-ai' ), 'manage_options', 'open-door-ai', array( __CLASS__, 'page' ) );
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
		$s      = Open_Door_Data::settings();
		$opt    = Open_Door_Data::OPTION;
		$notice = self::handle_service_post();
		echo '<div class="wrap"><h1>' . esc_html__( 'flowboxx Door for AI Assistants', 'open-door-ai' ) . '</h1>';
		if ( '' !== $notice ) {
			echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>';
		}
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
			self::service_box();
		}
		echo '<p class="description">' . esc_html__( 'This plugin has no AI inside. It sends no data anywhere unless you register for the optional inquiry service. Nobody can promise that an AI assistant will mention your business.', 'open-door-ai' ) . '</p>';
		echo '</div>';
	}

	/** Handles the buttons of the inquiry service box. Returns a message for the top of the page. */
	private static function handle_service_post() {
		if ( empty( $_POST['open_door_service_action'] ) || ! isset( $_POST['open_door_service_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['open_door_service_nonce'] ) ), 'open_door_service' ) ) {
			return '';
		}
		$action = sanitize_key( wp_unslash( $_POST['open_door_service_action'] ) );
		if ( 'register' === $action ) {
			if ( empty( $_POST['open_door_avv'] ) ) {
				return __( 'Please tick the box to conclude the data processing agreement first.', 'open-door-ai' );
			}
			$result = Open_Door_Service::register();
			return true === $result ? __( 'Registered. We sent a message to your e-mail address. Please confirm the address with the link in it.', 'open-door-ai' ) : (string) $result;
		}
		if ( 'check' === $action ) {
			delete_transient( Open_Door_Service::TRANSIENT );
			return __( 'Status updated.', 'open-door-ai' );
		}
		if ( 'forget' === $action ) {
			Open_Door_Service::forget();
			return __( 'The connection to the inquiry service was removed from this website. A running subscription is not cancelled by this.', 'open-door-ai' );
		}
		return '';
	}

	private static function service_box() {
		echo '<h2>' . esc_html__( 'Inquiry service (optional, separate from this plugin)', 'open-door-ai' ) . '</h2>';
		echo '<p>' . esc_html__( 'With the inquiry service an AI assistant can leave an inquiry for you, and you receive it as an e-mail. It is a service by flowboxx and costs 19 EUR per month plus VAT, cancellable monthly. The free part works without it.', 'open-door-ai' ) . '</p>';
		echo '<form method="post">';
		wp_nonce_field( 'open_door_service', 'open_door_service_nonce' );
		if ( ! Open_Door_Service::is_registered() ) {
			echo '<p class="description">' . esc_html__( 'When you register, the address of your website and your e-mail address for inquiries are sent to flowboxx, once. Nothing is sent before.', 'open-door-ai' ) . '</p>';
			echo '<p><label><input type="checkbox" name="open_door_avv" value="1"> ' . esc_html__( 'I conclude the data processing agreement', 'open-door-ai' ) . '</label> (<a href="https://agent.flowboxx.de/avv" target="_blank" rel="noopener">' . esc_html__( 'read it', 'open-door-ai' ) . '</a>)</p>';
			echo '<button type="submit" class="button button-primary" name="open_door_service_action" value="register">' . esc_html__( 'Register for the inquiry service', 'open-door-ai' ) . '</button>';
		} else {
			$state = Open_Door_Service::state();
			$label = array(
				'aktiv'      => __( 'Active. Inquiries from AI assistants reach your e-mail address.', 'open-door-ai' ),
				'ausstehend' => __( 'Waiting. Please confirm your e-mail address with the link we sent you, then subscribe on the page that opens.', 'open-door-ai' ),
				'pausiert'   => __( 'Paused because a payment is overdue. Collected inquiries are kept and delivered after payment.', 'open-door-ai' ),
				'gekuendigt' => __( 'Cancelled.', 'open-door-ai' ),
			);
			$text  = is_array( $state ) && isset( $label[ $state['abo'] ] ) ? $label[ $state['abo'] ] : __( 'The state could not be read right now.', 'open-door-ai' );
			echo '<p><strong>' . esc_html( $text ) . '</strong></p>';
			echo '<button type="submit" class="button" name="open_door_service_action" value="check">' . esc_html__( 'Check status', 'open-door-ai' ) . '</button> ';
			echo '<button type="submit" class="button" name="open_door_service_action" value="forget">' . esc_html__( 'Remove connection from this website', 'open-door-ai' ) . '</button>';
		}
		echo '</form>';
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
