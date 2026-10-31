<?php
/**
 * Settings and document builders (agent card, server card, llms.txt, MCP texts).
 *
 * @package Open_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Open_Door_Data {

	const OPTION = 'open_door_settings';

	/** @return array{company:string,services:string,contact_mail:string,footer:int} */
	public static function settings() {
		$defaults = array(
			'company'      => '',
			'services'     => '',
			'contact_mail' => '',
			'footer'       => 0,
		);
		$saved = get_option( self::OPTION, array() );
		return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
	}

	public static function is_configured() {
		$s = self::settings();
		return '' !== trim( $s['company'] );
	}

	/** Cards need an https address, otherwise assistants cannot use them. */
	public static function is_https() {
		return 0 === strpos( home_url( '/' ), 'https://' );
	}

	public static function company() {
		$s = self::settings();
		return '' !== trim( $s['company'] ) ? trim( $s['company'] ) : wp_strip_all_tags( get_bloginfo( 'name' ) );
	}

	/** @return string[] */
	public static function services() {
		$s     = self::settings();
		$lines = preg_split( '/\r\n|\r|\n/', (string) $s['services'] );
		$out   = array();
		foreach ( (array) $lines as $line ) {
			$line = trim( wp_strip_all_tags( $line ) );
			if ( '' !== $line ) {
				$out[] = mb_substr( $line, 0, 120 );
			}
		}
		return array_slice( $out, 0, 20 );
	}

	public static function description() {
		$tag = trim( wp_strip_all_tags( get_bloginfo( 'description' ) ) );
		if ( '' === $tag ) {
			/* translators: %s: company name */
			return sprintf( __( 'Information about %s for AI assistants.', 'open-door-ai' ), self::company() );
		}
		return $tag;
	}

	public static function endpoint_url() {
		$url = rest_url( 'open-door-ai/v1/mcp' );
		return apply_filters( 'open_door_interface_url', $url );
	}

	public static function addresses() {
		return array(
			'agent-card.json' => home_url( '/.well-known/agent-card.json' ),
			'agent.json'      => home_url( '/.well-known/agent.json' ),
			'server-card'     => home_url( '/.well-known/mcp/server-card.json' ),
			'llms.txt'        => home_url( '/llms.txt' ),
		);
	}

	/** True if a service line contains a price or an e-mail address (these do not belong into a card). */
	public static function has_sensitive_text() {
		foreach ( self::services() as $line ) {
			if ( preg_match( '/[€$]|EUR|@/u', $line ) ) {
				return true;
			}
		}
		return false;
	}

	public static function agent_card() {
		$skills = array();
		foreach ( self::services() as $i => $line ) {
			$slug     = sanitize_title( $line );
			$skills[] = array(
				'id'          => ( '' !== $slug ? $slug : 'service' ) . '-' . ( $i + 1 ),
				'name'        => $line,
				/* translators: 1: service, 2: company */
				'description' => sprintf( __( '%1$s, offered by %2$s.', 'open-door-ai' ), $line, self::company() ),
				'tags'        => self::tags( $line ),
			);
		}
		if ( ! $skills ) {
			$skills[] = array(
				'id'          => 'about',
				'name'        => __( 'About the business', 'open-door-ai' ),
				'description' => self::description(),
				'tags'        => array( 'about', 'business' ),
			);
		}
		return array(
			'name'                => self::company(),
			'description'         => self::description(),
			'version'             => '1.0.0',
			'supportedInterfaces' => array(
				array(
					'url'             => self::endpoint_url(),
					'protocolBinding' => 'MCP',
					'protocolVersion' => '2025-06-18',
				),
			),
			'provider'            => array(
				'organization' => self::company(),
				'url'          => home_url( '/' ),
			),
			'documentationUrl'    => home_url( '/' ),
			'capabilities'        => (object) array(
				'streaming'         => false,
				'pushNotifications' => false,
			),
			'defaultInputModes'   => array( 'application/json' ),
			'defaultOutputModes'  => array( 'application/json' ),
			'skills'              => $skills,
		);
	}

	public static function server_card() {
		return array(
			'$schema'     => 'https://static.modelcontextprotocol.io/schemas/v1/server-card.schema.json',
			'name'        => 'wordpress.open-door-ai/' . sanitize_title( wp_parse_url( home_url(), PHP_URL_HOST ) ),
			'title'       => self::company(),
			'version'     => '1.0.0',
			'description' => self::description(),
			'websiteUrl'  => home_url( '/' ),
			'remotes'     => array(
				array(
					'type'                      => 'streamable-http',
					'url'                       => self::endpoint_url(),
					'supportedProtocolVersions' => array( '2025-06-18' ),
				),
			),
		);
	}

	public static function llms_txt() {
		$out  = '# ' . self::company() . "\n\n";
		$out .= '> ' . self::description() . "\n\n";
		$svc  = self::services();
		if ( $svc ) {
			$out .= '## ' . __( 'What we offer', 'open-door-ai' ) . "\n\n";
			foreach ( $svc as $line ) {
				$out .= '- ' . $line . "\n";
			}
			$out .= "\n";
		}
		$pages = get_pages(
			array(
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
				'number'      => 50,
			)
		);
		if ( $pages ) {
			$out .= '## ' . __( 'Pages', 'open-door-ai' ) . "\n\n";
			foreach ( $pages as $page ) {
				$title = str_replace( array( '[', ']' ), '', wp_strip_all_tags( get_the_title( $page ) ) );
				if ( '' === trim( $title ) ) {
					continue;
				}
				$out .= '- [' . $title . '](' . get_permalink( $page ) . ")\n";
			}
		}
		return $out;
	}

	/** Text for the MCP tool about_business. */
	public static function about_text() {
		return self::company() . "\n" . self::description() . "\n" . home_url( '/' );
	}

	/** Text for the MCP tool list_services. */
	public static function services_text() {
		$svc = self::services();
		if ( ! $svc ) {
			return __( 'No services listed yet. Please see the website.', 'open-door-ai' ) . ' ' . home_url( '/' );
		}
		return '- ' . implode( "\n- ", $svc );
	}

	/** @return string[] */
	private static function tags( $line ) {
		$words = preg_split( '/[^\p{L}\p{N}]+/u', mb_strtolower( $line ), -1, PREG_SPLIT_NO_EMPTY );
		$words = array_values(
			array_unique(
				array_filter(
					(array) $words,
					static function ( $w ) {
						return mb_strlen( $w ) > 2;
					}
				)
			)
		);
		return $words ? array_slice( $words, 0, 5 ) : array( 'service' );
	}
}
