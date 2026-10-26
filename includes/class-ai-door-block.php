<?php
/**
 * Block and shortcode "Ask an AI about us".
 *
 * @package AI_Door
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Door_Block {

	public static function register() {
		add_shortcode( 'ai_door_ask', array( __CLASS__, 'render' ) );
		wp_register_style( 'ai-door-ask', plugins_url( 'assets/ask.css', AI_DOOR_FILE ), array(), AI_DOOR_VERSION );
		wp_register_script(
			'ai-door-ask-editor',
			plugins_url( 'blocks/ask/editor.js', AI_DOOR_FILE ),
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n' ),
			AI_DOOR_VERSION,
			true
		);
		register_block_type(
			AI_DOOR_DIR . 'blocks/ask',
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);
	}

	public static function render() {
		wp_enqueue_style( 'ai-door-ask' );
		$s       = AI_Door_Data::settings();
		$company = AI_Door_Data::company();
		$html    = '<div class="ai-door-ask">';
		$html   .= '<p class="ai-door-ask__title">' . esc_html__( 'Ask an AI about us', 'ai-door' ) . '</p>';
		$html   .= '<p>' . esc_html__( 'Open your AI assistant (for example ChatGPT) and ask:', 'ai-door' ) . '</p>';
		/* translators: %s: company name */
		$html   .= '<p class="ai-door-ask__q">' . esc_html( sprintf( __( 'What does %s offer?', 'ai-door' ), $company ) ) . '</p>';
		if ( ! empty( $s['footer'] ) ) {
			$html .= '<p class="ai-door-ask__foot"><a href="https://flowboxx.de/" rel="noopener">' . esc_html__( 'runs on flowboxx', 'ai-door' ) . '</a></p>';
		}
		return $html . '</div>';
	}
}
