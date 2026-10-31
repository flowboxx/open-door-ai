<?php
/**
 * Serves the well-known documents, llms.txt and the read-only MCP endpoint.
 * Nothing is written to disk. Deactivating the plugin removes every address.
 *
 * @package Open_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Open_Door_Routes {

	const PROTOCOL = '2025-06-18';

	public static function maybe_serve() {
		if ( ! Open_Door_Data::is_configured() || ! Open_Door_Data::is_https() ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( '' !== $base && 0 === strpos( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		$path = '/' . ltrim( $path, '/' );

		$json_routes = array(
			'/.well-known/agent-card.json'      => array( 'Open_Door_Data', 'agent_card' ),
			'/.well-known/agent.json'           => array( 'Open_Door_Data', 'agent_card' ),
			'/.well-known/mcp/server-card.json' => array( 'Open_Door_Data', 'server_card' ),
		);
		if ( isset( $json_routes[ $path ] ) ) {
			self::send( 'application/json; charset=utf-8', wp_json_encode( call_user_func( $json_routes[ $path ] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
		}
		if ( '/llms.txt' === $path ) {
			self::send( 'text/plain; charset=utf-8', Open_Door_Data::llms_txt() );
		}
	}

	private static function send( $type, $body ) {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			status_header( 405 );
			header( 'Allow: GET, HEAD' );
			exit;
		}
		status_header( 200 );
		header( 'Content-Type: ' . $type );
		header( 'Cache-Control: no-cache' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'X-Content-Type-Options: nosniff' );
		if ( 'HEAD' !== $method ) {
			echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON or plain text, built from sanitized values.
		}
		exit;
	}

	public static function register_rest() {
		register_rest_route(
			'open-door-ai/v1',
			'/mcp',
			array(
				'methods'             => array( 'POST', 'GET' ),
				'callback'            => array( __CLASS__, 'mcp' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function mcp( WP_REST_Request $request ) {
		if ( ! Open_Door_Data::is_configured() ) {
			return new WP_REST_Response( array( 'error' => 'not configured' ), 404 );
		}
		if ( 'POST' !== $request->get_method() ) {
			return new WP_REST_Response( array( 'error' => 'POST only' ), 405 );
		}
		$raw = $request->get_body();
		if ( strlen( $raw ) > 10000 ) {
			return new WP_REST_Response( array( 'error' => 'too large' ), 413 );
		}
		$msg = json_decode( $raw, true );
		if ( ! is_array( $msg ) || ! isset( $msg['method'] ) || ! is_string( $msg['method'] ) ) {
			return self::rpc_error( null, -32600, 'Invalid request' );
		}
		if ( ! array_key_exists( 'id', $msg ) ) { // notification, no answer expected.
			return new WP_REST_Response( null, 202 );
		}
		$id = $msg['id'];
		switch ( $msg['method'] ) {
			case 'initialize':
				return self::rpc_result(
					$id,
					array(
						'protocolVersion' => self::PROTOCOL,
						'capabilities'    => array( 'tools' => (object) array() ),
						'serverInfo'      => array(
							'name'    => Open_Door_Data::company(),
							'version' => OPEN_DOOR_VERSION,
						),
					)
				);
			case 'ping':
				return self::rpc_result( $id, (object) array() );
			case 'tools/list':
				$schema = array(
					'type'       => 'object',
					'properties' => (object) array(),
				);
				return self::rpc_result(
					$id,
					array(
						'tools' => array(
							array(
								'name'        => 'about_business',
								'description' => 'Short description of the business and its website.',
								'inputSchema' => $schema,
							),
							array(
								'name'        => 'list_services',
								'description' => 'What the business offers.',
								'inputSchema' => $schema,
							),
						),
					)
				);
			case 'tools/call':
				$name = isset( $msg['params']['name'] ) ? (string) $msg['params']['name'] : '';
				if ( 'about_business' === $name ) {
					$text = Open_Door_Data::about_text();
				} elseif ( 'list_services' === $name ) {
					$text = Open_Door_Data::services_text();
				} else {
					return self::rpc_error( $id, -32602, 'Unknown tool' );
				}
				return self::rpc_result( $id, array( 'content' => array( array( 'type' => 'text', 'text' => $text ) ) ) );
		}
		return self::rpc_error( $id, -32601, 'Method not found' );
	}

	private static function rpc_result( $id, $result ) {
		return new WP_REST_Response( array( 'jsonrpc' => '2.0', 'id' => $id, 'result' => $result ), 200 );
	}

	private static function rpc_error( $id, $code, $message ) {
		return new WP_REST_Response( array( 'jsonrpc' => '2.0', 'id' => $id, 'error' => array( 'code' => $code, 'message' => $message ) ), 200 );
	}
}
