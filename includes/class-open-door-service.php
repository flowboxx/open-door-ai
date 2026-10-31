<?php
/**
 * Optional inquiry service by flowboxx (separate, paid). Nothing is sent until the site owner registers for it.
 * What is sent: see readme.txt, section "External services".
 *
 * @package Open_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Open_Door_Service {

	const OPTION    = 'open_door_service';
	const TRANSIENT = 'open_door_service_state';
	/** Version of the data processing agreement the owner accepts (text at https://agent.flowboxx.de/avv). */
	const AVV_VERSION = '2026-10-31-1';

	/** @return string Base address of the service. */
	public static function api() {
		return apply_filters( 'open_door_service_url', 'https://agent.flowboxx.de/open-door' );
	}

	/** @return array{id:string,key:string} */
	public static function data() {
		$d = get_option( self::OPTION, array() );
		$d = is_array( $d ) ? $d : array();
		return array(
			'id'  => isset( $d['id'] ) ? (string) $d['id'] : '',
			'key' => isset( $d['key'] ) ? (string) $d['key'] : '',
		);
	}

	public static function is_registered() {
		$d = self::data();
		return '' !== $d['key'];
	}

	public static function forget() {
		delete_option( self::OPTION );
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Registers this site. Sends the site address and the e-mail address of the owner, once, on request.
	 *
	 * @return true|string True on success, an error text otherwise.
	 */
	public static function register() {
		$mail = sanitize_email( (string) Open_Door_Data::settings()['contact_mail'] );
		if ( '' === $mail ) {
			return __( 'Please enter an e-mail address for inquiries above and save first.', 'open-door-ai' );
		}
		if ( ! Open_Door_Data::is_https() ) {
			return __( 'Your website needs https first.', 'open-door-ai' );
		}
		$r = wp_remote_post(
			self::api() . '/registrieren',
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'site_url' => home_url( '/' ),
						'mail'     => $mail,
						'avv'      => self::AVV_VERSION,
					)
				),
			)
		);
		if ( is_wp_error( $r ) ) {
			return __( 'The service could not be reached. Please try again later.', 'open-door-ai' );
		}
		$code = wp_remote_retrieve_response_code( $r );
		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( 201 !== $code || ! is_array( $body ) || empty( $body['key'] ) ) {
			return is_array( $body ) && ! empty( $body['fehler'] ) ? sanitize_text_field( $body['fehler'] ) : __( 'The service did not accept the registration.', 'open-door-ai' );
		}
		update_option(
			self::OPTION,
			array(
				'id'  => sanitize_text_field( (string) ( $body['id'] ?? '' ) ),
				'key' => sanitize_text_field( (string) $body['key'] ),
			),
			false
		);
		delete_transient( self::TRANSIENT );
		return true;
	}

	/**
	 * State of the subscription, cached for ten minutes.
	 *
	 * @param bool $refresh Ask the service now.
	 * @return array{abo:string,mail_bestaetigt:bool}|null Null if not registered or not reachable.
	 */
	public static function state( $refresh = false ) {
		if ( ! self::is_registered() ) {
			return null;
		}
		if ( ! $refresh ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$d = self::data();
		$r = wp_remote_get(
			self::api() . '/status',
			array(
				'timeout' => 6,
				'headers' => array( 'Authorization' => 'Bearer ' . $d['key'] ),
			)
		);
		if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( ! is_array( $body ) || empty( $body['abo'] ) ) {
			return null;
		}
		$state = array(
			'abo'             => sanitize_key( $body['abo'] ),
			'mail_bestaetigt' => ! empty( $body['mail_bestaetigt'] ),
		);
		set_transient( self::TRANSIENT, $state, 10 * MINUTE_IN_SECONDS );
		return $state;
	}

	/** The assistant tool is offered only while the subscription runs. */
	public static function is_active() {
		$s = self::state();
		return is_array( $s ) && 'aktiv' === $s['abo'] && $s['mail_bestaetigt'];
	}

	/**
	 * Hands one inquiry to the service.
	 *
	 * @return string Text for the assistant.
	 */
	public static function send_inquiry( $request, $contact, $source ) {
		$count = (int) get_transient( 'open_door_inquiry_count' );
		if ( $count >= 10 ) {
			return __( 'Too many inquiries right now. Please try again later or use the contact details on the website.', 'open-door-ai' );
		}
		set_transient( 'open_door_inquiry_count', $count + 1, HOUR_IN_SECONDS );
		$d = self::data();
		$r = wp_remote_post(
			self::api() . '/anfrage',
			array(
				'timeout' => 10,
				'headers' => array(
					'Authorization' => 'Bearer ' . $d['key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'anliegen' => mb_substr( (string) $request, 0, 1500 ),
						'kontakt'  => mb_substr( (string) $contact, 0, 200 ),
						'quelle'   => mb_substr( (string) $source, 0, 80 ),
					)
				),
			)
		);
		if ( is_wp_error( $r ) ) {
			return __( 'The inquiry could not be delivered. Please use the contact details on the website.', 'open-door-ai' );
		}
		$code = wp_remote_retrieve_response_code( $r );
		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( 200 === $code ) {
			return __( 'The inquiry was handed to a person in the business. The answer comes from them, by the contact you gave.', 'open-door-ai' );
		}
		if ( 422 === $code && is_array( $body ) && ! empty( $body['fehler'] ) ) {
			return sanitize_text_field( $body['fehler'] );
		}
		return __( 'The inquiry could not be delivered. Please use the contact details on the website.', 'open-door-ai' );
	}
}
