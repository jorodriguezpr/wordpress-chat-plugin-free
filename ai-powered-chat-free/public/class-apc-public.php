<?php
/**
 * Public - Handles frontend chat widget
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Public {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'output_chat_widget' ) );
	}

	/**
	 * Enqueue frontend scripts and styles
	 */
	public function enqueue_scripts() {
		if ( ! $this->should_load_chat() ) {
			return;
		}

		wp_enqueue_style( 'apc-chat-css', APC_FREE_PLUGIN_URL . 'public/css/chat.css', array(), APC_FREE_VERSION );
		wp_enqueue_script( 'apc-chat-js', APC_FREE_PLUGIN_URL . 'public/js/chat.js', array( 'jquery' ), APC_FREE_VERSION, true );

		wp_localize_script( 'apc-chat-js', 'apcChat', array(
			'restUrl' => rest_url( 'apc/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn' => is_user_logged_in(),
			'allowGuests' => (bool) get_option( 'apc_allow_guest_chat', 0 ),
			'welcomeMessage' => get_option( 'apc_welcome_message', __( 'Hello! How can I help you today?', 'apc-free' ) ),
			'labels'         => array(
				'title'        => __( 'AI Chat', 'apc-free' ),
				'placeholder'  => __( 'Type your message...', 'apc-free' ),
				'send'         => __( 'Send', 'apc-free' ),
				'newChat'      => __( 'New Chat', 'apc-free' ),
				'loginRequired' => __( 'Please log in to use the chat.', 'apc-free' ),
			),
		) );
	}

	/**
	 * Check if chat should be loaded
	 */
	private function should_load_chat() {
		$enabled = get_option( 'apc_chat_enabled', 1 );
		return (bool) $enabled;
	}

	/**
	 * Output chat widget HTML
	 */
	public function output_chat_widget() {
		if ( ! $this->should_load_chat() ) {
			return;
		}

		?>
		<!-- Chat Bubble Button (Trigger) -->
		<button id="apc-chat-bubble" class="apc-chat-bubble" aria-label="<?php esc_attr_e( 'Open AI Chat', 'apc-free' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
				<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
			</svg>
		</button>

		<!-- Chat Widget -->
		<div id="apc-chat-widget" class="apc-chat-widget">
			<div class="apc-chat-header">
				<div class="apc-chat-header-content">
				<h3><?php esc_html_e( 'AI Chat', 'apc-free' ); ?></h3>
				<div id="apc-status-indicator" class="apc-status-indicator status-ai"><?php esc_html_e( 'AI Assistant', 'apc-free' ); ?></div>
			</div>
			<button id="apc-chat-close" class="apc-chat-close" aria-label="<?php esc_attr_e( 'Close chat', 'apc-free' ); ?>">
					<span class="apc-icon">×</span>
				</button>
			</div>
			<div id="apc-chat-container" class="apc-chat-container">
				<?php 
			$allow_guests = get_option( 'apc_allow_guest_chat', 0 );
			$can_use_chat = is_user_logged_in() || $allow_guests;
			?>
			<?php if ( $can_use_chat ) : ?>
					<div class="apc-chat-messages" id="apc-chat-messages"></div>
					
					<?php
					// Display quick action buttons
					$quick_actions = get_option( 'apc_quick_actions', array() );
					if ( ! empty( $quick_actions ) ) :
						// Filter and sort enabled actions
						$enabled_actions = array_filter( $quick_actions, function( $action ) {
							return ! empty( $action['enabled'] );
						} );
						usort( $enabled_actions, function( $a, $b ) {
							return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
						} );
						
						if ( ! empty( $enabled_actions ) ) :
							?>
							<div class="apc-quick-actions">
								<?php foreach ( $enabled_actions as $action ) : ?>
									<button class="apc-quick-action-btn" 
										data-type="<?php echo esc_attr( $action['type'] ?? 'command' ); ?>"
										data-command="<?php echo esc_attr( $action['command'] ?? '' ); ?>"
										data-form-id="<?php echo esc_attr( $action['form_id'] ?? '' ); ?>">
										<span class="icon"><?php echo esc_html( $action['icon'] ?? 'Ã°Å¸â€™Â¬' ); ?></span>
										<span class="label"><?php echo esc_html( $action['label'] ); ?></span>
									</button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
					
					<div class="apc-chat-input-area">
					<button id="apc-request-human" class="apc-request-human"><?php esc_html_e( 'Talk to Human', 'apc-free' ); ?></button>						<button id="apc-close-chat" class="apc-close-chat" style="display:none;"><?php esc_html_e( 'Close Chat', 'apc-free' ); ?></button>									<textarea id="apc-chat-input" class="apc-chat-input" placeholder="<?php esc_attr_e( 'Type your message...', 'apc-free' ); ?>"></textarea>
					<button id="apc-chat-send" class="apc-chat-send"><?php esc_html_e( 'Send', 'apc-free' ); ?></button>
					</div>
				<?php else : ?>
					<div class="apc-chat-login-prompt">
					<p><?php esc_html_e( 'Please log in to use the chat feature.', 'apc-free' ); ?></p>
					<a href="<?php echo esc_url( wp_login_url() ); ?>" class="button"><?php esc_html_e( 'Log In', 'apc-free' ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
