<?php
/**
 * Block and shortcode "Ask an AI about us".
 *
 * @package Open_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Open_Door_Block {

	public static function register() {
		add_shortcode( 'open_door_ask', array( __CLASS__, 'render' ) );
		wp_register_style( 'open-door-ai-ask', plugins_url( 'assets/ask.css', OPEN_DOOR_FILE ), array(), OPEN_DOOR_VERSION );
		wp_register_script(
			'open-door-ai-ask-editor',
			plugins_url( 'blocks/ask/editor.js', OPEN_DOOR_FILE ),
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n' ),
			OPEN_DOOR_VERSION,
			true
		);
		register_block_type(
			OPEN_DOOR_DIR . 'blocks/ask',
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);
	}

	public static function render() {
		wp_enqueue_style( 'open-door-ai-ask' );
		$s       = Open_Door_Data::settings();
		$company = Open_Door_Data::company();
		$html    = '<div class="open-door-ai-ask">';
		$html   .= '<p class="open-door-ai-ask__title">' . esc_html__( 'Ask an AI about us', 'open-door-ai' ) . '</p>';
		$html   .= '<p>' . esc_html__( 'Open your AI assistant (for example ChatGPT) and ask:', 'open-door-ai' ) . '</p>';
		/* translators: %s: company name */
		$html   .= '<p class="open-door-ai-ask__q">' . esc_html( sprintf( __( 'What does %s offer?', 'open-door-ai' ), $company ) ) . '</p>';
		if ( ! empty( $s['footer'] ) ) {
			$html .= '<p class="open-door-ai-ask__foot"><a href="https://flowboxx.de/" rel="noopener">' . esc_html__( 'runs on flowboxx', 'open-door-ai' ) . '</a></p>';
		}
		return $html . '</div>';
	}
}
