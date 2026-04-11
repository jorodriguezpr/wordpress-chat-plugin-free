<?php
/**
 * Admin - Updated for multi-provider support
 *
 * @package    AI_Powered_Chat
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APC_Admin {
	private $ai_handler;

	public function __construct() {
		try {
			$this->ai_handler = new APC_AI_Handler();
		} catch ( Exception $e ) {
			// Handle gracefully during deactivation
			$this->ai_handler = null;
		}
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		add_action( 'wp_ajax_apc_reset_database', array( $this, 'handle_reset_database' ) );
	}

	/**
	 * Show admin notices
	 */
	public function show_admin_notices() {
		// Show success message after settings update
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( in_array( $page, array( 'apc-settings', 'apc-providers' ) ) ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully!', 'apc-free' ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Sanitize checkbox value
	 */
	public static function sanitize_checkbox( $value ) {
		return empty( $value ) ? 0 : 1;
	}

	/**
	 * Sanitize float value
	 */
	public static function sanitize_float( $value ) {
		return floatval( $value );
	}

	/**
	 * Sanitize integer value
	 */
	public static function sanitize_integer( $value ) {
		return intval( $value );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'AI Chat Settings', 'apc-free' ),
			__( 'AI Chat', 'apc-free' ),
			'manage_options',
			'apc-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-format-chat',
			80
		);

		add_submenu_page(
			'apc-settings',
			__( 'Settings', 'apc-free' ),
			__( 'Settings', 'apc-free' ),
			'manage_options',
			'apc-settings',
			array( $this, 'render_settings_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Chat Customization', 'apc-free' ),
			__( 'Chat Customization', 'apc-free' ),
			'manage_options',
			'apc-customization',
			array( $this, 'render_customization_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'AI Providers', 'apc-free' ),
			__( 'AI Providers', 'apc-free' ),
			'manage_options',
			'apc-providers',
			array( $this, 'render_providers_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Conversations', 'apc-free' ),
			__( 'Conversations', 'apc-free' ),
			'manage_options',
			'apc-conversations',
			array( $this, 'render_conversations_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Conversational Forms', 'apc-free' ),
			__( 'Forms Builder', 'apc-free' ),
			'manage_options',
			'apc-forms',
			array( $this, 'render_forms_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Quick Actions', 'apc-free' ),
			__( 'Quick Actions', 'apc-free' ),
			'manage_options',
			'apc-quick-actions',
			array( $this, 'render_quick_actions_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Predefined Responses', 'apc-free' ),
			__( 'Auto Responses', 'apc-free' ),
			'manage_options',
			'apc-predefined-responses',
			array( $this, 'render_predefined_responses_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Live Support', 'apc-free' ),
			__( 'Live Support', 'apc-free' ),
			'manage_options',
			'apc-live-support',
			array( $this, 'render_support_page' ) );

		add_submenu_page(
			'apc-settings',
			__( 'Maintenance', 'apc-free' ),
			__( 'Maintenance', 'apc-free' ),
			'manage_options',
			'apc-maintenance',
			array( $this, 'render_maintenance_page' ) );
	}

	/**
	 * Register settings with sanitization callbacks
	 */
	public function register_settings() {
		// General settings - using separate group
		register_setting( 'apc_general_settings', 'apc_active_provider', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
			'default'           => 'openai',
		) );
		register_setting( 'apc_general_settings', 'apc_system_prompt', array(
			'sanitize_callback' => 'wp_kses_post',
			'type'              => 'string',
			'default'           => __( 'You are a helpful assistant.', 'apc-free' ),
		) );
		register_setting( 'apc_general_settings', 'apc_chat_enabled', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_checkbox' ),
			'type'              => 'boolean',
			'default'           => true,
		) );
		register_setting( 'apc_general_settings', 'apc_welcome_message', array(
			'sanitize_callback' => 'sanitize_textarea_field',
			'type'              => 'string',
			'default'           => __( 'Hello! How can I help you today?', 'apc-free' ),
		) );
		register_setting( 'apc_general_settings', 'apc_ai_enabled', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_checkbox' ),
			'type'              => 'boolean',
			'default'           => true,
		) );
		register_setting( 'apc_general_settings', 'apc_pending_timeout', array(
			'sanitize_callback' => 'absint',
			'type'              => 'integer',
			'default'           => 5,
		) );
		register_setting( 'apc_general_settings', 'apc_allow_guest_chat', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_checkbox' ),
			'type'              => 'boolean',
			'default'           => false,
		) );

		// Provider settings - using separate group
		// Also register active provider here so it can be saved from providers page
		register_setting( 'apc_provider_settings', 'apc_active_provider', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
			'default'           => 'openai',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_openai_api_key', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_openai_model', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
			'default'           => 'gpt-3.5-turbo',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_openai_temperature', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_float' ),
			'type'              => 'number',
			'default'           => 0.7,
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_openai_max_tokens', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_integer' ),
			'type'              => 'number',
			'default'           => 500,
		) );

		// Claude provider settings
		register_setting( 'apc_provider_settings', 'apc_provider_claude_api_key', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_claude_model', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
			'default'           => 'claude-3-5-sonnet-20241022',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_claude_temperature', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_float' ),
			'type'              => 'number',
			'default'           => 1.0,
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_claude_max_tokens', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_integer' ),
			'type'              => 'number',
			'default'           => 500,
		) );

		// Gemini provider settings
		register_setting( 'apc_provider_settings', 'apc_provider_gemini_api_key', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_gemini_model', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
			'default'           => 'gemini-1.5-flash',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_gemini_temperature', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_float' ),
			'type'              => 'number',
			'default'           => 0.9,
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_gemini_max_tokens', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_integer' ),
			'type'              => 'number',
			'default'           => 500,
		) );

		// GitHub Models provider settings
		register_setting( 'apc_provider_settings', 'apc_provider_github_models_token', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_github_models_model', array(
			'sanitize_callback' => 'sanitize_text_field',
			'type'              => 'string',
			'default'           => 'gpt-4o',
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_github_models_temperature', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_float' ),
			'type'              => 'number',
			'default'           => 1.0,
		) );
		register_setting( 'apc_provider_settings', 'apc_provider_github_models_max_tokens', array(
			'sanitize_callback' => array( 'APC_Admin', 'sanitize_integer' ),
			'type'              => 'number',
			'default'           => 500,
		) );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'apc-' ) === false ) {
			return;
		}

		wp_enqueue_style( 'apc-admin-css', APC_FREE_PLUGIN_URL . 'admin/css/admin.css', array(), APC_FREE_VERSION );
		wp_enqueue_script( 'apc-admin-js', APC_FREE_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), APC_FREE_VERSION, true );

		wp_localize_script( 'apc-admin-js', 'apcAdmin', array(
			'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce'   => esc_js( wp_create_nonce( 'apc_admin_nonce' ) ),
			'restUrl' => esc_url( rest_url( 'apc/v1' ) ),
			'restNonce' => esc_js( wp_create_nonce( 'wp_rest' ) ),
		) );
	}

	/**
	 * Render main settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		$active_provider = get_option( 'apc_active_provider', 'openai' );
		$system_prompt = get_option( 'apc_system_prompt', __( 'You are a helpful assistant.', 'apc-free' ) );
		$chat_enabled = get_option( 'apc_chat_enabled', 1 );
		$ai_enabled = get_option( 'apc_ai_enabled', 1 );
		$pending_timeout = get_option( 'apc_pending_timeout', 5 );
		$providers = $this->ai_handler->get_available_providers();

		?>
		<div class="wrap apc-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="nav-tab-wrapper">
				<a href="?page=apc-settings" class="nav-tab nav-tab-active"><?php esc_html_e( 'General Settings', 'apc-free' ); ?></a>
				<a href="?page=apc-providers" class="nav-tab"><?php esc_html_e( 'Configure Providers', 'apc-free' ); ?></a>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'apc_general_settings' ); ?>

				<div class="card">
					<h2><?php esc_html_e( 'AI Provider Selection', 'apc-free' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="apc_active_provider"><?php esc_html_e( 'Active AI Provider', 'apc-free' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<select name="apc_active_provider" id="apc_active_provider">
									<?php foreach ( $providers as $id => $provider ) : ?>
										<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $active_provider, $id ); ?>>
											<?php echo esc_html( $provider['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'Choose which AI provider to use for responses. Configure each provider on the AI Providers tab.', 'apc-free' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'AI Behavior', 'apc-free' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="apc_system_prompt"><?php esc_html_e( 'System Prompt', 'apc-free' ); ?></label>
							</th>
							<td>
								<textarea name="apc_system_prompt" id="apc_system_prompt" rows="5" class="large-text"><?php echo esc_textarea( $system_prompt ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Instructions for the AI behavior. This defines how the assistant should respond.', 'apc-free' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Chat Mode', 'apc-free' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Enable AI Responses', 'apc-free' ); ?>
							</th>
							<td>
								<input type="checkbox" name="apc_ai_enabled" id="apc_ai_enabled" value="1" <?php checked( $ai_enabled, 1 ); ?> />
								<label for="apc_ai_enabled"><?php esc_html_e( 'Allow AI to respond to chat messages', 'apc-free' ); ?></label>
								<p class="description"><?php esc_html_e( 'When disabled, all conversations will go directly to human agents without AI assistance.', 'apc-free' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Support Request Settings', 'apc-free' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="apc_pending_timeout"><?php esc_html_e( 'Auto-Close Timeout', 'apc-free' ); ?></label>
							</th>
							<td>
								<input type="number" name="apc_pending_timeout" id="apc_pending_timeout" value="<?php echo esc_attr( $pending_timeout ); ?>" min="1" max="1440" class="small-text" />
								<span><?php esc_html_e( 'minutes', 'apc-free' ); ?></span>
								<p class="description">
									<?php esc_html_e( 'Automatically close pending support requests that have not been accepted by an agent after this time period. Set to 0 to disable auto-close.', 'apc-free' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Widget Settings', 'apc-free' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Enable Chat Widget', 'apc-free' ); ?>
							</th>
							<td>
								<input type="checkbox" name="apc_chat_enabled" id="apc_chat_enabled" value="1" <?php checked( $chat_enabled, 1 ); ?> />
								<label for="apc_chat_enabled"><?php esc_html_e( 'Display chat widget on frontend', 'apc-free' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Allow Guest Users', 'apc-free' ); ?>
							</th>
							<td>
								<?php $allow_guest = get_option( 'apc_allow_guest_chat', 0 ); ?>
								<input type="checkbox" name="apc_allow_guest_chat" id="apc_allow_guest_chat" value="1" <?php checked( $allow_guest, 1 ); ?> />
								<label for="apc_allow_guest_chat"><?php esc_html_e( 'Allow guest users (not logged in) to chat with agents without login', 'apc-free' ); ?></label>
								<p class="description">
									<?php esc_html_e( 'When enabled, visitors will be able to start a conversation with support agents without needing to create an account or login.', 'apc-free' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render chat customization page
	 */
	public function render_customization_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		$welcome_message = get_option( 'apc_welcome_message', __( 'Hello! How can I help you today?', 'apc-free' ) );

		// Handle form submission
		if ( isset( $_POST['apc_customization_nonce'] ) && wp_verify_nonce( $_POST['apc_customization_nonce'], 'apc_customization_save' ) ) {
			if ( isset( $_POST['apc_welcome_message'] ) ) {
				update_option( 'apc_welcome_message', sanitize_textarea_field( $_POST['apc_welcome_message'] ) );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Chat customization settings saved successfully.', 'apc-free' ) . '</p></div>';
				$welcome_message = get_option( 'apc_welcome_message' );
			}
		}

		?>
		<div class="wrap apc-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Customize the chat widget appearance and messages.', 'apc-free' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( 'apc_customization_save', 'apc_customization_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="apc_welcome_message"><?php esc_html_e( 'Welcome Message', 'apc-free' ); ?></label>
						</th>
						<td>
							<textarea name="apc_welcome_message" id="apc_welcome_message" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Enter the greeting message users see when starting a chat...', 'apc-free' ); ?>"><?php echo esc_textarea( $welcome_message ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'This message is displayed when users first open the chat widget. Make it friendly and welcoming!', 'apc-free' ); ?>
							</p>
							<p class="description">
								<strong><?php esc_html_e( 'Current preview:', 'apc-free' ); ?></strong> 
								<em><?php echo esc_html( $welcome_message ); ?></em>
							</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'apc-free' ); ?>">
				</p>
			</form>

			<hr style="margin: 40px 0;">

			<h2><?php esc_html_e( 'Live Preview', 'apc-free' ); ?></h2>
			<p class="description"><?php esc_html_e( 'This is how your welcome message will appear in the chat widget:', 'apc-free' ); ?></p>
			<div style="max-width: 400px; border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #f9f9f9; margin-top: 15px;">
				<div style="background: #0073aa; color: white; padding: 10px; border-radius: 6px 6px 0 0; margin: -20px -20px 15px -20px;">
					<strong><?php esc_html_e( 'AI Chat', 'apc-free' ); ?></strong>
				</div>
				<div style="background: #e7f5ff; padding: 12px; border-radius: 6px; margin-bottom: 10px;">
					<strong style="color: #0073aa;"><?php esc_html_e( 'System:', 'apc-free' ); ?></strong><br>
					<span style="color: #333; margin-top: 5px; display: block;"><?php echo esc_html( $welcome_message ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render providers configuration page
	 */
	public function render_providers_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		$providers = $this->ai_handler->get_available_providers();
		$active_provider = get_option( 'apc_active_provider', 'openai' );
		$provider_status = $this->ai_handler->get_all_providers_status();

		?>
		<div class="wrap apc-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="nav-tab-wrapper">
				<a href="?page=apc-settings" class="nav-tab"><?php esc_html_e( 'General Settings', 'apc-free' ); ?></a>
				<a href="?page=apc-providers" class="nav-tab nav-tab-active"><?php esc_html_e( 'Configure Providers', 'apc-free' ); ?></a>
			</div>

			<div class="apc-providers-status">
				<h2><?php esc_html_e( 'Provider Status', 'apc-free' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Provider', 'apc-free' ); ?></th>
							<th><?php esc_html_e( 'Status', 'apc-free' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $provider_status as $id => $status ) : ?>
							<tr class="<?php echo $status['connected'] ? 'apc-status-connected' : 'apc-status-disconnected'; ?>">
								<td>
									<strong><?php echo esc_html( $status['name'] ); ?></strong>
									<?php if ( $id === $active_provider ) : ?>
										<span class="badge-active"><?php esc_html_e( 'Active', 'apc-free' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $status['connected'] ) : ?>
										<span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Connected', 'apc-free' ); ?>
									<?php else : ?>
										<span class="dashicons dashicons-no" style="color: #dc3545;"></span> <?php esc_html_e( 'Not Configured', 'apc-free' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'apc_provider_settings' ); ?>

				<div class="card">
					<h2><?php esc_html_e( 'Active Provider Selection', 'apc-free' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="apc_active_provider"><?php esc_html_e( 'Active AI Provider', 'apc-free' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<select name="apc_active_provider" id="apc_active_provider">
									<?php foreach ( $providers as $id => $provider ) : ?>
										<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $active_provider, $id ); ?>>
											<?php echo esc_html( $provider['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'Choose which AI provider to use for chat responses.', 'apc-free' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<?php foreach ( $providers as $provider_id => $provider_data ) : ?>
					<?php $config_fields = $provider_data['class']->get_config_fields(); ?>
					<div class="card apc-provider-config">
						<h2><?php echo esc_html( $provider_data['name'] ); ?></h2>
						
						<table class="form-table">
							<?php foreach ( $config_fields as $field ) : ?>
								<?php 
								$default = isset( $field['default'] ) ? $field['default'] : '';
								$value = get_option( $field['id'], $default );
								
								// Debug output for troubleshooting
								if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $provider_id === 'github-models' ) {
									echo '<!-- Field: ' . esc_html( $field['id'] ) . ' | Value from DB: ' . esc_html( var_export( $value, true ) ) . ' | Default: ' . esc_html( var_export( $default, true ) ) . ' -->';
								}
								?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $field['id'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
										</label>
									</th>
									<td>
										<?php if ( 'password' === $field['type'] ) : ?>
											<input type="password" name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
										<?php elseif ( 'select' === $field['type'] ) : ?>
											<select name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>">
												<?php foreach ( $field['options'] as $opt_value => $opt_label ) : ?>
													<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $value, $opt_value ); ?>>
														<?php echo esc_html( $opt_label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										<?php elseif ( 'number' === $field['type'] ) : ?>
											<input type="number" name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( $value ); ?>" 
												<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
												<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
												<?php if ( isset( $field['step'] ) ) : ?>step="<?php echo esc_attr( $field['step'] ); ?>"<?php endif; ?>
												class="regular-text" />
										<?php endif; ?>
										<?php if ( isset( $field['help'] ) ) : ?>
											<p class="description"><?php echo wp_kses_post( $field['help'] ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				<?php endforeach; ?>

				<?php submit_button(); ?>
			</form>

			<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<?php $this->debug_registered_settings(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Debug: Display registered settings
	 */
	public function debug_registered_settings() {
		global $wp_registered_settings;
		echo '<div class="card" style="margin-top: 20px;">';
		echo '<h3>Debug: Registered Settings</h3>';
		echo '<pre style="overflow: auto; max-height: 400px;">';
		$apc_settings = array_filter( $wp_registered_settings, function( $key ) {
			return strpos( $key, 'apc_' ) === 0;
		}, ARRAY_FILTER_USE_KEY );
		echo 'Total APC Settings Registered: ' . count( $apc_settings ) . "\n\n";
		foreach ( $apc_settings as $key => $settings ) {
			$value = get_option( $key );
			$display_value = $value !== false ? var_export( $value, true ) : 'EMPTY';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Debug output for settings display
			echo "$key => $display_value\n";
		}
		echo '</pre>';
		echo '</div>';
	}

	/**
	 * Render conversations page
	 */
	public function render_conversations_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to view conversations', 'apc-free' ) ) );
		}

		?>
		<div class="wrap apc-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'View and manage all chat conversations.', 'apc-free' ); ?></p>

			<div class="apc-conversations-container">
				<div class="apc-conversations-list-section">
					<h2><?php esc_html_e( 'All Conversations', 'apc-free' ); ?></h2>
					<div id="apc-conversations-list" class="apc-conversations-list">
						<p><?php esc_html_e( 'Loading conversations...', 'apc-free' ); ?></p>
					</div>
				</div>

				<div class="apc-conversation-details-section">
					<h2><?php esc_html_e( 'Conversation Details', 'apc-free' ); ?></h2>
					<div id="apc-conversation-details" class="apc-conversation-details">
						<p class="apc-no-selection"><?php esc_html_e( 'Select a conversation to view details', 'apc-free' ); ?></p>
					</div>
				</div>
			</div>

			<style>
				.apc-conversations-container {
					display: flex;
					gap: 20px;
					margin-top: 20px;
				}
				.apc-conversations-list-section {
					flex: 0 0 480px;
					max-width: 480px;
				}
				.apc-conversations-list-section h2 {
					background: white;
					padding: 15px 20px;
					margin: 0 0 2px 0;
					border-radius: 4px 4px 0 0;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					font-size: 16px;
				}
				.apc-conversation-details-section {
					flex: 1;
					min-width: 0;
				}
				.apc-conversation-details-section h2 {
					background: white;
					padding: 15px 20px;
					margin: 0 0 2px 0;
					border-radius: 4px 4px 0 0;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					font-size: 16px;
				}
				.apc-conversations-list {
					background: white;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					border-radius: 0 0 4px 4px;
					max-height: 700px;
					overflow-y: auto;
				}
				.apc-conversations-list table {
					width: 100%;
					border-collapse: collapse;
					font-size: 13px;
				}
				.apc-conversations-list th,
				.apc-conversations-list td {
					padding: 10px 8px;
					text-align: left;
					border-bottom: 1px solid #e0e0e0;
				}
				.apc-conversations-list th {
					background: #f8f9fa;
					font-weight: 600;
					font-size: 12px;
					color: #555;
					text-transform: uppercase;
					letter-spacing: 0.5px;
					position: sticky;
					top: 0;
					z-index: 10;
				}
				.apc-conversations-list th:nth-child(1) { width: 40px; }
				.apc-conversations-list th:nth-child(2) { width: 140px; }
				.apc-conversations-list th:nth-child(3) { width: 90px; }
				.apc-conversations-list th:nth-child(4) { width: 80px; }
				.apc-conversations-list th:nth-child(5) { width: 110px; }
				.apc-conversations-list td {
					color: #333;
				}
				.apc-conversations-list td small {
					color: #888;
					font-size: 11px;
					display: block;
					margin-top: 2px;
				}
				.apc-conversations-list tbody tr {
					transition: all 0.2s ease;
				}
				.apc-conversations-list tbody tr:hover {
					background: #f5f8fa;
					cursor: pointer;
				}
				.apc-conversations-list tr.selected {
					background: #e3f2fd !important;
					border-left: 3px solid #2196f3;
				}
				.apc-conversation-details {
					background: white;
					padding: 20px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					border-radius: 0 0 4px 4px;
					min-height: 400px;
					max-height: 700px;
					overflow-y: auto;
				}
				.apc-conversation-info {
					margin-bottom: 20px;
					padding: 20px;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					border-radius: 8px;
					color: white;
					box-shadow: 0 4px 6px rgba(0,0,0,0.1);
				}
				.apc-conversation-info h3 {
					margin: 0 0 15px 0;
					font-size: 20px;
					color: white;
				}
				.apc-conversation-info p {
					margin: 8px 0;
					font-size: 14px;
					opacity: 0.95;
				}
				.apc-conversation-info strong {
					font-weight: 600;
					opacity: 1;
				}
				.apc-messages-list {
					margin-top: 20px;
				}
				.apc-messages-list h4 {
					font-size: 16px;
					color: #333;
					margin-bottom: 15px;
					padding-bottom: 10px;
					border-bottom: 2px solid #e0e0e0;
				}
				.apc-message-item {
					padding: 15px;
					margin-bottom: 12px;
					border-radius: 8px;
					border-left: 4px solid #ddd;
					box-shadow: 0 2px 4px rgba(0,0,0,0.05);
					transition: all 0.2s ease;
				}
				.apc-message-item:hover {
					box-shadow: 0 4px 8px rgba(0,0,0,0.1);
					transform: translateX(2px);
				}
				.apc-message-item.user {
					background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
					border-left-color: #2196f3;
				}
				.apc-message-item.assistant {
					background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
					border-left-color: #9c27b0;
				}
				.apc-message-item.agent {
					background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
					border-left-color: #4caf50;
				}
				.apc-message-item.system {
					background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
					border-left-color: #ff9800;
				}
				.apc-message-header {
					font-size: 11px;
					color: #666;
					margin-bottom: 8px;
					font-weight: 600;
					text-transform: uppercase;
					letter-spacing: 0.5px;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}
				.apc-message-content {
					color: #333;
					line-height: 1.6;
					font-size: 14px;
					white-space: pre-wrap;
					word-wrap: break-word;
				}
				.apc-no-selection {
					text-align: center;
					color: #999;
					padding: 60px 20px;
					font-size: 15px;
				}
				.apc-status-badge {
					display: inline-block;
					padding: 4px 10px;
					border-radius: 12px;
					font-size: 10px;
					font-weight: 700;
					text-transform: uppercase;
					letter-spacing: 0.5px;
					white-space: nowrap;
				}
				.apc-status-badge.ai { 
					background: #2196f3; 
					color: white; 
				}
				.apc-status-badge.human_requested { 
					background: #ff9800; 
					color: white; 
				}
				.apc-status-badge.human_active { 
					background: #4caf50; 
					color: white; 
				}
				.apc-status-badge.closed { 
					background: #9e9e9e; 
					color: white; 
				}
				/* Scrollbar styling */
				.apc-conversations-list::-webkit-scrollbar,
				.apc-conversation-details::-webkit-scrollbar {
					width: 8px;
				}
				.apc-conversations-list::-webkit-scrollbar-track,
				.apc-conversation-details::-webkit-scrollbar-track {
					background: #f1f1f1;
				}
				.apc-conversations-list::-webkit-scrollbar-thumb,
				.apc-conversation-details::-webkit-scrollbar-thumb {
					background: #888;
					border-radius: 4px;
				}
				.apc-conversations-list::-webkit-scrollbar-thumb:hover,
				.apc-conversation-details::-webkit-scrollbar-thumb:hover {
					background: #555;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Render conversational forms builder page
	 */
	public function render_forms_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		// Handle form actions
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;

		if ( $action === 'edit' && $form_id ) {
			$this->render_form_editor( $form_id );
		} elseif ( $action === 'new' ) {
			$this->render_form_editor( 0 );
		} elseif ( $action === 'submissions' && $form_id ) {
			$this->render_form_submissions( $form_id );
		} else {
			$this->render_forms_list();
		}
	}

	/**
	 * Render forms list page
	 */
	private function render_forms_list() {
		$forms = APC_Forms::get_all_forms();
		
		// Handle CSV export
		if ( isset( $_GET['export_csv'] ) && isset( $_GET['form_id'] ) ) {
			$form_id = intval( $_GET['form_id'] );
			$csv = APC_Forms::export_submissions_csv( $form_id );
			$form = APC_Forms::get_form( $form_id );
			
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $form['name'] ) . '-submissions-' . date( 'Y-m-d' ) . '.csv"' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV output
			echo $csv;
			exit;
		}
		?>
		<div class="wrap apc-admin apc-forms-page">
			<h1>
				<?php esc_html_e( 'Conversational Forms', 'apc-free' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms&action=new' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New Form', 'apc-free' ); ?>
				</a>
			</h1>
			<p><?php esc_html_e( 'Create conditional conversational forms for your chat widget.', 'apc-free' ); ?></p>

			<div class="apc-forms-list-container">
				<?php if ( empty( $forms ) ) : ?>
					<div class="apc-empty-state">
						<h2><?php esc_html_e( 'No Forms Yet', 'apc-free' ); ?></h2>
						<p><?php esc_html_e( 'Create your first conversational form to collect information from your chat visitors.', 'apc-free' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms&action=new' ) ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Create Your First Form', 'apc-free' ); ?>
						</a>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Form Name', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Trigger Type', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Trigger Value', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Submissions', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Status', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'apc-free' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $forms as $form ) : 
								$analytics = APC_Forms::get_form_analytics( $form['id'] );
								?>
								<tr>
									<td><strong><?php echo esc_html( $form['name'] ); ?></strong></td>
									<td>
										<span class="apc-trigger-badge trigger-<?php echo esc_attr( $form['trigger_type'] ); ?>">
											<?php echo esc_html( ucfirst( $form['trigger_type'] ) ); ?>
										</span>
									</td>
									<td>
										<?php if ( $form['trigger_type'] === 'command' ) : ?>
											<code><?php echo esc_html( $form['trigger_command'] ); ?></code>
										<?php elseif ( $form['trigger_type'] === 'link' ) : ?>
											<?php echo esc_html( $form['trigger_button_text'] ); ?>
										<?php else : ?>
											<?php echo esc_html( wp_trim_words( $form['workflow_keywords'], 5  ) ); ?>
										<?php endif; ?>
									</td>
									<td>
										<strong><?php echo esc_html( $analytics['total'] ); ?></strong>
										<span class="description">(<?php echo esc_html( $analytics['today'] ); ?> today)</span>
									</td>
									<td>
										<?php if ( $form['is_active'] ) : ?>
											<span class="apc-status-badge active"><?php esc_html_e( 'Active', 'apc-free' ); ?></span>
										<?php else : ?>
											<span class="apc-status-badge inactive"><?php esc_html_e( 'Inactive', 'apc-free' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms&action=submissions&form_id=' . $form['id'] ) ); ?>" class="button button-small">
											<?php esc_html_e( 'View Submissions', 'apc-free' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms&action=edit&form_id=' . $form['id'] ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'apc-free' ); ?>
										</a>
										<button class="button button-small apc-delete-form" data-form-id="<?php echo esc_attr( $form['id'] ); ?>">
											<?php esc_html_e( 'Delete', 'apc-free' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<style>
				.apc-forms-list-container {
					margin-top: 20px;
					background: white;
					padding: 20px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}
				.apc-empty-state {
					text-align: center;
					padding: 60px 20px;
				}
				.apc-empty-state h2 {
					font-size: 24px;
					margin-bottom: 15px;
				}
				.apc-empty-state p {
					font-size: 16px;
					color: #666;
					margin-bottom: 30px;
				}
				.apc-status-badge.active {
					background: #4caf50;
					color: white;
					padding: 4px 10px;
					border-radius: 12px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
				.apc-status-badge.inactive {
					background: #9e9e9e;
					color: white;
					padding: 4px 10px;
					border-radius: 12px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
			.apc-trigger-badge {
				padding: 4px 10px;
				border-radius: 12px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				color: white;
			}
			.apc-trigger-badge.trigger-command {
				background: #2196f3;
			}
			.apc-trigger-badge.trigger-link {
				background: #9c27b0;
			}
			.apc-trigger-badge.trigger-workflow {
				background: #ff9800;
			}
					var formId = $(this).data('form-id');
					var button = $(this);

					$.ajax({
						url: apcAdmin.restUrl + '/forms/' + formId,
						method: 'DELETE',
						headers: {
							'X-WP-Nonce': apcAdmin.restNonce
						},
						success: function(response) {
							if (response.success) {
								button.closest('tr').fadeOut(function() {
									$(this).remove();
								});
							} else {
								alert('Failed to delete form.');
							}
						},
						error: function() {
							alert('Error deleting form.');
						}
					});
				});
			});
			</script>
		</div>
		<?php
	}

	/**
	 * Render form submissions page
	 */
	private function render_form_submissions( $form_id ) {
		$form = APC_Forms::get_form( $form_id );
		if ( ! $form ) {
			wp_die( esc_html( __( 'Form not found', 'apc-free' ) ) );
		}

		$analytics = APC_Forms::get_form_analytics( $form_id );
		$submissions = APC_Forms::get_submissions( $form_id );
		$fields = APC_Forms::get_form_fields( $form_id );
		?>
		<div class="wrap apc-admin apc-submissions-page">
			<h1>
				<?php 
				/* translators: %s: form name */
				printf( esc_html__( 'Submissions: %s', 'apc-free' ), esc_html( $form['name'] ) ); 
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Back to Forms', 'apc-free' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms&export_csv=1&form_id=' . $form_id ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Export CSV', 'apc-free' ); ?>
				</a>
			</h1>

			<!-- Analytics Cards -->
			<div class="apc-analytics-cards">
				<div class="apc-card">
					<div class="apc-card-icon">Ã°Å¸â€œÅ </div>
					<div class="apc-card-content">
						<div class="apc-card-value"><?php echo esc_html( $analytics['total'] ); ?></div>
						<div class="apc-card-label"><?php esc_html_e( 'Total Submissions', 'apc-free' ); ?></div>
					</div>
				</div>
				<div class="apc-card">
					<div class="apc-card-icon">📅</div>
					<div class="apc-card-content">
						<div class="apc-card-value"><?php echo esc_html( $analytics['this_month'] ); ?></div>
						<div class="apc-card-label"><?php esc_html_e( 'This Month', 'apc-free' ); ?></div>
					</div>
				</div>
				<div class="apc-card">
					<div class="apc-card-icon">Ã°Å¸Å’Å¸</div>
					<div class="apc-card-content">
						<div class="apc-card-value"><?php echo esc_html( $analytics['today'] ); ?></div>
						<div class="apc-card-label"><?php esc_html_e( 'Today', 'apc-free' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Submissions Table -->
			<div class="apc-submissions-container">
				<?php if ( empty( $submissions ) ) : ?>
					<div class="apc-empty-state">
						<h2><?php esc_html_e( 'No Submissions Yet', 'apc-free' ); ?></h2>
						<p><?php esc_html_e( 'When users complete this form, their submissions will appear here.', 'apc-free' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'User', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Submitted At', 'apc-free' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'apc-free' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $submissions as $submission ) : ?>
								<tr>
									<td><?php echo esc_html( $submission['id'] ); ?></td>
									<td><?php echo esc_html( $submission['user_name'] ?: 'Guest' ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission['submitted_at'] ) ) ); ?></td>
									<td>
										<button class="button button-small view-submission" data-submission-id="<?php echo esc_attr( $submission['id'] ); ?>">
											<?php esc_html_e( 'View Details', 'apc-free' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Submission Details Modal -->
			<div id="submission-modal" class="apc-modal" style="display:none;">
				<div class="apc-modal-content">
					<div class="apc-modal-header">
						<h3><?php esc_html_e( 'Submission Details', 'apc-free' ); ?></h3>
						<span class="apc-modal-close">&times;</span>
					</div>
					<div class="apc-modal-body" id="submission-details">
						<!-- Details loaded via AJAX -->
					</div>
				</div>
			</div>

			<style>
				.apc-analytics-cards {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(250px, 1fr) );
					gap: 20px;
					margin: 20px 0;
				}
				.apc-card {
					background: white;
					padding: 20px;
					border-radius: 8px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					display: flex;
					align-items: center;
					gap: 15px;
				}
				.apc-card-icon {
					font-size: 36px;
				}
				.apc-card-value {
					font-size: 32px;
					font-weight: 700;
					color: #667eea;
				}
				.apc-card-label {
					font-size: 14px;
					color: #666;
					text-transform: uppercase;
				}
				.apc-submissions-container {
					margin-top: 20px;
					background: white;
					padding: 20px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}
			</style>

			<script>
			jQuery(document).ready(function($) {
				var submissionFields = <?php echo wp_json_encode( $fields ); ?>;

				$('.view-submission').on('click', function() {
					var submissionId = $(this).data('submission-id');

					$.ajax({
						url: apcAdmin.restUrl + '/forms/submissions/' + submissionId + '/responses',
						method: 'GET',
						headers: {
							'X-WP-Nonce': apcAdmin.restNonce
						},
						success: function(response) {
							console.log('Submission response:', response);
							if (response.success) {
								displaySubmissionDetails(response.responses);
							}
						},
						error: function(xhr, status, error) {
							console.error('Error loading submission:', error);
							alert('Failed to load submission details');
						}
					});
				});

				function displaySubmissionDetails(responses) {
					console.log('Displaying responses:', responses);
					var html = '<table class="widefat">';
					html += '<thead><tr><th>Field</th><th>Response</th></tr></thead>';
					html += '<tbody>';

					if (!responses || responses.length === 0) {
						html += '<tr><td colspan="2" style="text-align:center;">No responses found</td></tr>';
					} else {
						responses.forEach(function(response) {
							html += '<tr>';
							html += '<td><strong>' + (response.field_label || 'Unknown Field') + '</strong></td>';
							html += '<td>' + (response.field_value || '-') + '</td>';
							html += '</tr>';
						});
					}

					html += '</tbody></table>';
					$('#submission-details').html(html);
					$('#submission-modal').fadeIn();
				}

				$(document).on('click', '.apc-modal-close', function() {
					$('#submission-modal').fadeOut();
				});
			});
			</script>
		</div>
		<?php
	}

	/**
	 * Render form editor page
	 */
	private function render_form_editor( $form_id ) {
		$form = $form_id ? APC_Forms::get_form( $form_id ) : null;
		$fields = $form_id ? APC_Forms::get_form_fields( $form_id ) : array();
		$is_new = ! $form_id;

		?>
		<div class="wrap apc-admin apc-form-editor">
			<h1><?php echo $is_new ? esc_html__( 'Create New Form', 'apc-free' ) : esc_html__( 'Edit Form', 'apc-free' ); ?></h1>

			<div class="apc-form-editor-container">
				<div class="apc-form-settings-panel">
					<h2><?php esc_html_e( 'Form Settings', 'apc-free' ); ?></h2>
					
					<div class="apc-form-group">
						<label><?php esc_html_e( 'Form Name', 'apc-free' ); ?> *</label>
						<input type="text" id="form-name" class="regular-text" value="<?php echo esc_attr( $form['name'] ?? '' ); ?>" required>
					</div>

					<div class="apc-form-group">
						<label><?php esc_html_e( 'Description', 'apc-free' ); ?></label>
						<textarea id="form-description" class="large-text" rows="3"><?php echo esc_textarea( $form['description'] ?? '' ); ?></textarea>
					</div>

					<div class="apc-form-group">
					<label><?php esc_html_e( 'Trigger Type', 'apc-free' ); ?></label>
					<select id="form-trigger-type" class="regular-text">
						<option value="command" <?php selected( $form['trigger_type'] ?? 'command', 'command' ); ?>><?php esc_html_e( 'Command', 'apc-free' ); ?></option>
						<option value="link" <?php selected( $form['trigger_type'] ?? 'command', 'link' ); ?>><?php esc_html_e( 'Link Button', 'apc-free' ); ?></option>
						<option value="workflow" <?php selected( $form['trigger_type'] ?? 'command', 'workflow' ); ?>><?php esc_html_e( 'Workflow (Auto)', 'apc-free' ); ?></option>
					</select>
				</div>

				<div class="apc-form-group trigger-command-group">
					<label><?php esc_html_e( 'Trigger Command', 'apc-free' ); ?></label>
					<input type="text" id="form-trigger-command" class="regular-text" value="<?php echo esc_attr( $form['trigger_command'] ?? '' ); ?>" placeholder="e.g., /contact">
					<p class="description"><?php esc_html_e( 'Users can type this command to start the form (e.g., /contact)', 'apc-free' ); ?></p>
				</div>

				<div class="apc-form-group trigger-link-group" style="display:none;">
					<label><?php esc_html_e( 'Button Text', 'apc-free' ); ?></label>
					<input type="text" id="form-trigger-button" class="regular-text" value="<?php echo esc_attr( $form['trigger_button_text'] ?? '' ); ?>" placeholder="e.g., Contact Us">
					<p class="description"><?php esc_html_e( 'Text displayed on the clickable button', 'apc-free' ); ?></p>
				</div>

				<div class="apc-form-group trigger-workflow-group" style="display:none;">
					<label><?php esc_html_e( 'Workflow Keywords', 'apc-free' ); ?></label>
					<input type="text" id="form-workflow-keywords" class="regular-text" value="<?php echo esc_attr( $form['workflow_keywords'] ?? '' ); ?>" placeholder="e.g., contact, help, support">
					<p class="description"><?php esc_html_e( 'Comma-separated keywords that trigger this form automatically', 'apc-free' ); ?></p>
				</div>

				<div class="apc-form-group">
					<label>
						<input type="checkbox" id="form-email-notification" <?php checked( $form['email_notification'] ?? 0, 1 ); ?>>
						<?php esc_html_e( 'Send Email Notification', 'apc-free' ); ?>
					</label>
				</div>

				<div class="apc-form-group email-recipients-group" style="<?php echo ( $form['email_notification'] ?? 0 ) ? '' : 'display:none;'; ?>">
					<label><?php esc_html_e( 'Email Recipients', 'apc-free' ); ?></label>
					<input type="text" id="form-email-recipients" class="regular-text" value="<?php echo esc_attr( $form['email_recipients'] ?? get_option( 'admin_email' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Comma-separated email addresses to notify when form is submitted', 'apc-free' ); ?></p>
				</div>

				<div class="apc-form-group">
					<label>
						<input type="checkbox" id="form-active" <?php checked( $form['is_active'] ?? 1, 1 ); ?>>
						<?php esc_html_e( 'Form is Active', 'apc-free' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Inactive forms will not be triggered or displayed', 'apc-free' ); ?></p>
				</div>

					<button id="save-form" class="button button-primary button-large"><?php esc_html_e( 'Save Form', 'apc-free' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-forms' ) ); ?>" class="button button-large"><?php esc_html_e( 'Cancel', 'apc-free' ); ?></a>
				</div>

				<div class="apc-form-builder-panel">
					<h2><?php esc_html_e( 'Form Fields', 'apc-free' ); ?></h2>
					
					<div class="apc-field-types-toolbar">
						<button class="button add-field" data-field-type="text"><?php esc_html_e( '+ Text', 'apc-free' ); ?></button>
						<button class="button add-field" data-field-type="email"><?php esc_html_e( '+ Email', 'apc-free' ); ?></button>
						<button class="button add-field" data-field-type="number"><?php esc_html_e( '+ Number', 'apc-free' ); ?></button>
						<button class="button add-field" data-field-type="textarea"><?php esc_html_e( '+ Textarea', 'apc-free' ); ?></button>
						<button class="button add-field" data-field-type="select"><?php esc_html_e( '+ Dropdown', 'apc-free' ); ?></button>
						<button class="button add-field" data-field-type="checkbox"><?php esc_html_e( '+ Checkbox', 'apc-free' ); ?></button>
						<button class="button add-field" data-field-type="radio"><?php esc_html_e( '+ Radio', 'apc-free' ); ?></button>
					</div>

					<div id="form-fields-list" class="apc-fields-list">
						<?php if ( empty( $fields ) ) : ?>
							<div class="apc-no-fields">
								<p><?php esc_html_e( 'No fields yet. Add your first field using the buttons above.', 'apc-free' ); ?></p>
							</div>
						<?php else : ?>
							<?php foreach ( $fields as $field ) : ?>
								<div class="apc-field-item" data-field-id="<?php echo esc_attr( $field['id'] ); ?>">
									<div class="field-header">
										<span class="field-type-badge"><?php echo esc_html( $field['field_type'] ); ?></span>
										<span class="field-label"><?php echo esc_html( $field['field_label'] ); ?></span>
										<div class="field-actions">
											<button class="button-link edit-field"><?php esc_html_e( 'Edit', 'apc-free' ); ?></button>
											<button class="button-link delete-field"><?php esc_html_e( 'Delete', 'apc-free' ); ?></button>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Field Editor Modal will be added via JavaScript -->

			<style>
				.apc-form-editor-container {
					display: flex;
					gap: 20px;
					margin-top: 20px;
				}
				.apc-form-settings-panel {
					flex: 0 0 350px;
					background: white;
					padding: 20px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}
				.apc-form-builder-panel {
					flex: 1;
					background: white;
					padding: 20px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				}
				.apc-form-group {
					margin-bottom: 20px;
				}
				.apc-form-group label {
					display: block;
					font-weight: 600;
					margin-bottom: 8px;
				}
				.apc-field-types-toolbar {
					margin-bottom: 20px;
					padding-bottom: 15px;
					border-bottom: 2px solid #e0e0e0;
				}
				.apc-field-types-toolbar .button {
					margin-right: 5px;
					margin-bottom: 5px;
				}
				.apc-fields-list {
					min-height: 300px;
				}
				.apc-no-fields {
					text-align: center;
					padding: 60px 20px;
					color: #999;
				}
				.apc-field-item {
					background: #f9f9f9;
					border: 1px solid #ddd;
					border-radius: 4px;
					padding: 15px;
					margin-bottom: 10px;
					cursor: move;
				}
				.apc-field-item .field-header {
					display: flex;
					align-items: center;
					gap: 10px;
				}
				.field-type-badge {
					background: #2196f3;
					color: white;
					padding: 4px 10px;
					border-radius: 12px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
				.field-label {
					flex: 1;
					font-weight: 600;
				}
				.field-actions {
					display: flex;
					gap: 10px;
				}
				.button-link {
					background: none;
					border: none;
					color: #0073aa;
					cursor: pointer;
					text-decoration: underline;
				}
				.button-link:hover {
					color: #005177;
				}
			</style>

			<script>
			jQuery(document).ready(function($) {
				var formId = <?php echo esc_js( $form_id ?: 0 ); ?>;
				var fields = <?php echo wp_json_encode( $fields ); ?>;

				// Toggle trigger options based on type
				$('#form-trigger-type').on('change', function() {
					var triggerType = $(this).val();
					$('.trigger-command-group, .trigger-link-group, .trigger-workflow-group').hide();
					
					if (triggerType === 'command') {
						$('.trigger-command-group').show();
					} else if (triggerType === 'link') {
						$('.trigger-link-group').show();
					} else if (triggerType === 'workflow') {
						$('.trigger-workflow-group').show();
					}
				}).trigger('change');

				// Toggle email recipients based on notification checkbox
				$('#form-email-notification').on('change', function() {
					if ($(this).is(':checked')) {
						$('.email-recipients-group').slideDown();
					} else {
						$('.email-recipients-group').slideUp();
					}
				});

				// Save form
				$('#save-form').on('click', function() {
					var triggerType = $('#form-trigger-type').val();
					var formData = {
						name: $('#form-name').val(),
						description: $('#form-description').val(),
						trigger_type: triggerType,
						trigger_command: $('#form-trigger-command').val(),
						trigger_button_text: $('#form-trigger-button').val(),
						workflow_keywords: $('#form-workflow-keywords').val(),
						email_notification: $('#form-email-notification').is(':checked') ? 1 : 0,
						email_recipients: $('#form-email-recipients').val(),
						is_active: $('#form-active').is(':checked') ? 1 : 0
					};

					if (!formData.name) {
						alert('<?php esc_html_e( 'Please enter a form name', 'apc-free' ); ?>');
						return;
					}

					var url = apcAdmin.restUrl + '/forms' + (formId ? '/' + formId : '');
					var method = formId ? 'PUT' : 'POST';

					$(this).prop('disabled', true).text('<?php esc_html_e( 'Saving...', 'apc-free' ); ?>');

					$.ajax({
						url: url,
						method: method,
						headers: {
							'X-WP-Nonce': apcAdmin.restNonce
						},
						data: JSON.stringify(formData),
						contentType: 'application/json',
						success: function(response) {
							if (response.success) {
								if (!formId) {
									// Redirect to edit page with new form ID
									window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=apc-forms&action=edit&form_id=' ) ); ?>' + response.form_id;
								} else {
									alert('<?php esc_html_e( 'Form saved successfully!', 'apc-free' ); ?>');
									location.reload();
								}
							}
						},
						error: function() {
							alert('<?php esc_html_e( 'Error saving form', 'apc-free' ); ?>');
							$('#save-form').prop('disabled', false).text('<?php esc_html_e( 'Save Form', 'apc-free' ); ?>');
						}
					});
				});

				// Add field buttons
				$('.add-field').on('click', function() {
					if (!formId) {
						alert('<?php esc_html_e( 'Please save the form first before adding fields', 'apc-free' ); ?>');
						return;
					}
					var fieldType = $(this).data('field-type');
					openFieldModal(fieldType, null);
				});

				// Edit field
				$(document).on('click', '.edit-field', function() {
					var fieldId = $(this).closest('.apc-field-item').data('field-id');
					var field = fields.find(f => f.id == fieldId);
					openFieldModal(field.field_type, field);
				});

				// Delete field
				$(document).on('click', '.delete-field', function() {
					if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this field?', 'apc-free' ); ?>')) {
						return;
					}
					var fieldId = $(this).closest('.apc-field-item').data('field-id');
					deleteField(fieldId);
				});

				// Field modal functions
				function openFieldModal(fieldType, fieldData) {
					var isEdit = fieldData !== null;
					var modalHtml = '<div id="apc-field-modal" class="apc-modal">' +
						'<div class="apc-modal-content">' +
						'<div class="apc-modal-header">' +
						'<h3>' + (isEdit ? '<?php esc_html_e( 'Edit Field', 'apc-free' ); ?>' : '<?php esc_html_e( 'Add Field', 'apc-free' ); ?>') + '</h3>' +
						'<span class="apc-modal-close">&times;</span>' +
						'</div>' +
						'<div class="apc-modal-body">' +
						'<div class="apc-form-group">' +
						'<label><?php esc_html_e( 'Field Type', 'apc-free' ); ?></label>' +
						'<input type="text" id="modal-field-type" class="regular-text" value="' + fieldType + '" readonly>' +
						'</div>' +
						'<div class="apc-form-group">' +
						'<label><?php esc_html_e( 'Field Name', 'apc-free' ); ?> *</label>' +
						'<input type="text" id="modal-field-name" class="regular-text" value="' + (fieldData?.field_name || '') + '" placeholder="e.g., user_email">' +
						'<p class="description"><?php esc_html_e( 'Internal name for this field (no spaces)', 'apc-free' ); ?></p>' +
						'</div>' +
						'<div class="apc-form-group">' +
						'<label><?php esc_html_e( 'Field Label', 'apc-free' ); ?> *</label>' +
						'<input type="text" id="modal-field-label" class="regular-text" value="' + (fieldData?.field_label || '') + '" placeholder="e.g., What is your email?">' +
						'</div>' +
						'<div class="apc-form-group">' +
						'<label><?php esc_html_e( 'Placeholder Text', 'apc-free' ); ?></label>' +
						'<input type="text" id="modal-field-placeholder" class="regular-text" value="' + (fieldData?.field_placeholder || '') + '">' +
						'</div>';

					// Options for select, checkbox, radio
					if (['select', 'checkbox', 'radio'].includes(fieldType)) {
						var options = fieldData?.field_options ? JSON.parse(fieldData.field_options) : [''];
						modalHtml += '<div class="apc-form-group">' +
							'<label><?php esc_html_e( 'Options', 'apc-free' ); ?></label>' +
							'<div id="field-options-list">';
						
						options.forEach(function(option, index) {
							modalHtml += '<div class="option-item">' +
								'<input type="text" class="option-value" value="' + option + '" placeholder="Option ' + (index + 1) + '">' +
								'<button type="button" class="button remove-option">-</button>' +
								'</div>';
						});
						
						modalHtml += '</div>' +
							'<button type="button" id="add-option" class="button"><?php esc_html_e( 'Add Option', 'apc-free' ); ?></button>' +
							'</div>';
					}

					modalHtml += '<div class="apc-form-group">' +
						'<label>' +
						'<input type="checkbox" id="modal-field-required" ' + (fieldData?.is_required ? 'checked' : '') + '>' +
						' <?php esc_html_e( 'Required Field', 'apc-free' ); ?>' +
						'</label>' +
						'</div>' +
						'</div>' +
						'<div class="apc-modal-footer">' +
						'<button id="save-field" class="button button-primary"><?php esc_html_e( 'Save Field', 'apc-free' ); ?></button>' +
						'<button class="button apc-modal-close"><?php esc_html_e( 'Cancel', 'apc-free' ); ?></button>' +
						'</div>' +
						'</div>' +
						'</div>';

					$('body').append(modalHtml);
					$('#apc-field-modal').fadeIn();

					// Add option button
					$(document).on('click', '#add-option', function() {
						var optionCount = $('.option-item').length + 1;
						var optionHtml = '<div class="option-item">' +
							'<input type="text" class="option-value" placeholder="Option ' + optionCount + '">' +
							'<button type="button" class="button remove-option">-</button>' +
							'</div>';
						$('#field-options-list').append(optionHtml);
					});

					// Remove option button
					$(document).on('click', '.remove-option', function() {
						$(this).closest('.option-item').remove();
					});

					// Close modal
					$(document).on('click', '.apc-modal-close', function() {
						$('#apc-field-modal').fadeOut(function() {
							$(this).remove();
						});
					});

					// Save field
					$(document).on('click', '#save-field', function() {
						var fieldName = $('#modal-field-name').val();
						var fieldLabel = $('#modal-field-label').val();

						if (!fieldName || !fieldLabel) {
							alert('<?php esc_html_e( 'Please fill in all required fields', 'apc-free' ); ?>');
							return;
						}

						var fieldPayload = {
							field_type: fieldType,
							field_name: fieldName,
							field_label: fieldLabel,
							field_placeholder: $('#modal-field-placeholder').val(),
							is_required: $('#modal-field-required').is(':checked') ? 1 : 0
						};

						// Collect options for select, checkbox, radio
						if (['select', 'checkbox', 'radio'].includes(fieldType)) {
							var options = [];
							$('.option-value').each(function() {
								if ($(this).val()) {
									options.push($(this).val() );
								}
							});
							fieldPayload.field_options = JSON.stringify(options);
						}

						if (isEdit) {
							updateField(fieldData.id, fieldPayload);
						} else {
							addField(fieldPayload);
						}
					});
				}

				function addField(fieldData) {
					$.ajax({
						url: apcAdmin.restUrl + '/forms/' + formId + '/fields',
						method: 'POST',
						headers: {
							'X-WP-Nonce': apcAdmin.restNonce
						},
						data: JSON.stringify(fieldData),
						contentType: 'application/json',
						success: function(response) {
							if (response.success) {
								$('#apc-field-modal').fadeOut(function() {
									$(this).remove();
								});
								location.reload();
							}
						},
						error: function() {
							alert('<?php esc_html_e( 'Error adding field', 'apc-free' ); ?>');
						}
					});
				}

				function updateField(fieldId, fieldData) {
					$.ajax({
						url: apcAdmin.restUrl + '/forms/fields/' + fieldId,
						method: 'PUT',
						headers: {
							'X-WP-Nonce': apcAdmin.restNonce
						},
						data: JSON.stringify(fieldData),
						contentType: 'application/json',
						success: function(response) {
							if (response.success) {
								$('#apc-field-modal').fadeOut(function() {
									$(this).remove();
								});
								location.reload();
							}
						},
						error: function() {
							alert('<?php esc_html_e( 'Error updating field', 'apc-free' ); ?>');
						}
					});
				}

				function deleteField(fieldId) {
					$.ajax({
						url: apcAdmin.restUrl + '/forms/fields/' + fieldId,
						method: 'DELETE',
						headers: {
							'X-WP-Nonce': apcAdmin.restNonce
						},
						success: function(response) {
							if (response.success) {
								location.reload();
							}
						},
						error: function() {
							alert('<?php esc_html_e( 'Error deleting field', 'apc-free' ); ?>');
						}
					});
				}
			});
			</script>

			<style>
				.apc-modal {
					display: none;
					position: fixed;
					z-index: 100000;
					left: 0;
					top: 0;
					width: 100%;
					height: 100%;
					background-color: rgba(0,0,0,0.5);
				}
				.apc-modal-content {
					background-color: #fff;
					margin: 50px auto;
					width: 600px;
					max-width: 90%;
					border-radius: 4px;
					box-shadow: 0 5px 15px rgba(0,0,0,0.3);
				}
				.apc-modal-header {
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 20px;
					border-bottom: 1px solid #ddd;
				}
				.apc-modal-header h3 {
					margin: 0;
				}
				.apc-modal-close {
					font-size: 28px;
					font-weight: bold;
					color: #aaa;
					cursor: pointer;
					line-height: 20px;
				}
				.apc-modal-close:hover {
					color: #000;
				}
				.apc-modal-body {
					padding: 20px;
					max-height: 60vh;
					overflow-y: auto;
				}
				.apc-modal-footer {
					padding: 20px;
					border-top: 1px solid #ddd;
					text-align: right;
				}
				.apc-modal-footer .button {
					margin-left: 10px;
				}
				.option-item {
					display: flex;
					gap: 10px;
					margin-bottom: 10px;
					align-items: center;
				}
				.option-item input {
					flex: 1;
				}
				#field-options-list {
					margin-bottom: 10px;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Render quick actions management page
	 */
	public function render_quick_actions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		// Handle save/delete actions
		if ( isset( $_POST['apc_quick_actions_nonce'] ) && wp_verify_nonce( $_POST['apc_quick_actions_nonce'], 'apc_save_quick_actions' ) ) {
			update_option( 'apc_quick_actions', $_POST['quick_actions'] ?? array() );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Quick Actions saved successfully!', 'apc-free' ) . '</p></div>';
		}

		$quick_actions = get_option( 'apc_quick_actions', array() );
		$forms = APC_Forms::get_all_forms();
		?>
		<div class="wrap apc-admin apc-quick-actions-page">
			<h1><?php esc_html_e( 'Quick Actions', 'apc-free' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Configure persistent buttons that appear in the chat widget. Users can click these to trigger commands or forms without typing.', 'apc-free' ); ?>
			</p>

			<form method="post" action="" id="quick-actions-form">
				<?php wp_nonce_field( 'apc_save_quick_actions', 'apc_quick_actions_nonce' ); ?>

				<table class="wp-list-table widefat fixed striped" id="quick-actions-table">
					<thead>
						<tr>
							<th style="width: 30px;"><?php esc_html_e( 'Order', 'apc-free' ); ?></th>
							<th><?php esc_html_e( 'Label', 'apc-free' ); ?></th>
							<th><?php esc_html_e( 'Action Type', 'apc-free' ); ?></th>
							<th><?php esc_html_e( 'Command/Form', 'apc-free' ); ?></th>
							<th><?php esc_html_e( 'Icon', 'apc-free' ); ?></th>
							<th style="width: 80px;"><?php esc_html_e( 'Enabled', 'apc-free' ); ?></th>
							<th style="width: 80px;"><?php esc_html_e( 'Actions', 'apc-free' ); ?></th>
						</tr>
					</thead>
					<tbody id="quick-actions-list">
						<?php if ( ! empty( $quick_actions ) ) : ?>
							<?php foreach ( $quick_actions as $index => $action ) : ?>
								<tr data-index="<?php echo esc_attr( $index ); ?>">
									<td>
										<input type="number" name="quick_actions[<?php echo esc_attr( $index ); ?>][order]" 
											value="<?php echo esc_attr( $action['order'] ?? $index ); ?>" 
											class="small-text" min="0">
									</td>
									<td>
										<input type="text" name="quick_actions[<?php echo esc_attr( $index ); ?>][label]" 
											value="<?php echo esc_attr( $action['label'] ?? '' ); ?>" 
											class="regular-text" required>
									</td>
									<td>
										<select name="quick_actions[<?php echo esc_attr( $index ); ?>][type]" class="action-type-select" required>
											<option value="command" <?php selected( $action['type'] ?? 'command', 'command' ); ?>><?php esc_html_e( 'Command', 'apc-free' ); ?></option>
											<option value="form" <?php selected( $action['type'] ?? 'command', 'form' ); ?>><?php esc_html_e( 'Form', 'apc-free' ); ?></option>
										</select>
									</td>
									<td>
										<input type="text" name="quick_actions[<?php echo esc_attr( $index ); ?>][command]" 
											value="<?php echo esc_attr( $action['command'] ?? '' ); ?>" 
											class="regular-text command-input" 
											placeholder="/help"
											style="<?php echo ( $action['type'] ?? 'command' ) === 'form' ? 'display:none;' : ''; ?>">
										<select name="quick_actions[<?php echo esc_attr( $index ); ?>][form_id]" 
											class="form-select"
											style="<?php echo ( $action['type'] ?? 'command' ) === 'command' ? 'display:none;' : ''; ?>">
											<option value=""><?php esc_html_e( 'Select Form', 'apc-free' ); ?></option>
											<?php foreach ( $forms as $form ) : ?>
												<option value="<?php echo esc_attr( $form['id'] ); ?>" 
													<?php selected( $action['form_id'] ?? '', $form['id'] ); ?>>
													<?php echo esc_html( $form['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</td>
									<td>
										<input type="text" name="quick_actions[<?php echo esc_attr( $index ); ?>][icon]" 
											value="<?php echo esc_attr( $action['icon'] ?? '💬' ); ?>" 
											class="small-text" 
											placeholder="💬">
									</td>
									<td>
										<input type="checkbox" name="quick_actions[<?php echo esc_attr( $index ); ?>][enabled]" 
											value="1" 
											<?php checked( $action['enabled'] ?? 1, 1 ); ?>>
									</td>
									<td>
										<button type="button" class="button button-small delete-action"><?php esc_html_e( 'Delete', 'apc-free' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<p>
					<button type="button" class="button" id="add-action"><?php esc_html_e( 'Add Quick Action', 'apc-free' ); ?></button>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save All Changes', 'apc-free' ); ?></button>
				</p>
			</form>

			<style>
				.apc-quick-actions-page .wp-list-table input[type="text"],
				.apc-quick-actions-page .wp-list-table select {
					width: 100%;
				}
				.apc-quick-actions-page .wp-list-table input[type="number"] {
					width: 60px;
				}
			</style>

			<script>
			jQuery(document).ready(function($) {
				var actionIndex = <?php echo count( $quick_actions ); ?>;
				var forms = <?php echo wp_json_encode( $forms ); ?>;

				// Add new action row
				$('#add-action').on('click', function() {
					var formOptions = '<option value=""><?php esc_html_e( 'Select Form', 'apc-free' ); ?></option>';
					forms.forEach(function(form) {
						formOptions += '<option value="' + form.id + '">' + form.name + '</option>';
					});

					var row = '<tr data-index="' + actionIndex + '">' +
						'<td><input type="number" name="quick_actions[' + actionIndex + '][order]" value="' + actionIndex + '" class="small-text" min="0"></td>' +
						'<td><input type="text" name="quick_actions[' + actionIndex + '][label]" class="regular-text" required></td>' +
						'<td><select name="quick_actions[' + actionIndex + '][type]" class="action-type-select" required>' +
						'<option value="command"><?php esc_html_e( 'Command', 'apc-free' ); ?></option>' +
						'<option value="form"><?php esc_html_e( 'Form', 'apc-free' ); ?></option>' +
						'</select></td>' +
						'<td>' +
						'<input type="text" name="quick_actions[' + actionIndex + '][command]" class="regular-text command-input" placeholder="/help">' +
						'<select name="quick_actions[' + actionIndex + '][form_id]" class="form-select" style="display:none;">' + formOptions + '</select>' +
						'</td>' +
						'<td><input type="text" name="quick_actions[' + actionIndex + '][icon]" class="small-text" placeholder="💬" value="💬"></td>' +
						'<td><input type="checkbox" name="quick_actions[' + actionIndex + '][enabled]" value="1" checked></td>' +
						'<td><button type="button" class="button button-small delete-action"><?php esc_html_e( 'Delete', 'apc-free' ); ?></button></td>' +
						'</tr>';

					$('#quick-actions-list').append(row);
					actionIndex++;
				});

				// Delete action row
				$(document).on('click', '.delete-action', function() {
					$(this).closest('tr').remove();
				});

				// Toggle command/form input based on type
				$(document).on('change', '.action-type-select', function() {
					var $row = $(this).closest('tr');
					var type = $(this).val();

					if (type === 'command') {
						$row.find('.command-input').show();
						$row.find('.form-select').hide();
					} else {
						$row.find('.command-input').hide();
						$row.find('.form-select').show();
					}
				});
			});
			</script>
		</div>
		<?php
	}

	/**
	 * Render predefined responses page
	 */
	public function render_predefined_responses_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		// Handle form submission
		if ( isset( $_POST['apc_save_response'] ) && check_admin_referer( 'apc_save_response' ) ) {
			$response_id = isset( $_POST['response_id'] ) ? (int) $_POST['response_id'] : 0;
			$response_data = array(
				'question'   => $_POST['question'] ?? '',
				'answer'     => $_POST['answer'] ?? '',
				'keywords'   => $_POST['keywords'] ?? '',
				'page_link'  => $_POST['page_link'] ?? '',
				'priority'   => $_POST['priority'] ?? 0,
				'is_active'  => isset( $_POST['is_active'] ) ? 1 : 0,
			);

			if ( $response_id > 0 ) {
				APC_Database::update_predefined_response( $response_id, $response_data );
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Response updated successfully!', 'apc-free' ) . '</p></div>';
			} else {
				APC_Database::create_predefined_response( $response_data );
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Response created successfully!', 'apc-free' ) . '</p></div>';
			}
		}

		// Handle delete
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete_response_' . $_GET['id'] ) ) {
			APC_Database::delete_predefined_response( (int) $_GET['id'] );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Response deleted successfully!', 'apc-free' ) . '</p></div>';
		}

		// Get editing response if ID is provided
		$editing_response = null;
		if ( isset( $_GET['edit'] ) && $_GET['edit'] > 0 ) {
			$editing_response = APC_Database::get_predefined_response( (int) $_GET['edit'] );
		}

		// Get all responses
		$responses = APC_Database::get_all_predefined_responses();

		// Get WordPress pages for dropdown
		$pages = get_pages();

		?>
		<div class="wrap apc-predefined-responses-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Create automatic responses for common questions. When a user message matches keywords, the chatbot will respond with your predefined answer.', 'apc-free' ); ?></p>

			<div class="apc-admin-two-column">
				<!-- Response Form -->
				<div class="apc-admin-column-main">
					<div class="card">
						<h2><?php echo $editing_response ? esc_html__( 'Edit Response', 'apc-free' ) : esc_html__( 'Add New Response', 'apc-free' ); ?></h2>
						<form method="post" action="">
							<?php wp_nonce_field( 'apc_save_response' ); ?>
							<input type="hidden" name="response_id" value="<?php echo $editing_response ? esc_attr( $editing_response['id'] ) : '0'; ?>">

							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="question"><?php esc_html_e( 'Question/Trigger', 'apc-free' ); ?> <span class="required">*</span></label>
									</th>
									<td>
										<input type="text" name="question" id="question" 
											value="<?php echo esc_attr( $editing_response['question'] ?? '' ); ?>" 
											class="large-text" required>
										<p class="description"><?php esc_html_e( 'The question or phrase that triggers this response (e.g., "What are your hours?", "Current specials").', 'apc-free' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="answer"><?php esc_html_e( 'Answer', 'apc-free' ); ?> <span class="required">*</span></label>
									</th>
									<td>
										<?php
										wp_editor( 
											$editing_response['answer'] ?? '', 
											'answer',
											array(
												'textarea_name' => 'answer',
												'textarea_rows' => 8,
												'media_buttons' => false,
												'teeny'         => true,
												'quicktags'     => true
											) );
										?>
										<p class="description"><?php esc_html_e( 'The response that will be sent to the user. You can use HTML formatting.', 'apc-free' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="keywords"><?php esc_html_e( 'Keywords', 'apc-free' ); ?></label>
									</th>
									<td>
										<textarea name="keywords" id="keywords" rows="3" class="large-text"><?php echo esc_textarea( $editing_response['keywords'] ?? '' ); ?></textarea>
										<p class="description"><?php esc_html_e( 'Comma-separated keywords to trigger this response (e.g., hours, schedule, open, closing time). The more keywords, the better the matching.', 'apc-free' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="page_link"><?php esc_html_e( 'Link to Page', 'apc-free' ); ?></label>
									</th>
									<td>
										<select name="page_link" id="page_link" class="regular-text">
											<option value=""><?php esc_html_e( '-- None --', 'apc-free' ); ?></option>
											<?php foreach ( $pages as $page ) : ?>
												<option value="<?php echo esc_url( get_permalink( $page->ID ) ); ?>" 
													<?php selected( $editing_response['page_link'] ?? '', get_permalink( $page->ID ) ); ?>>
													<?php echo esc_html( $page->post_title ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Optional: Link to a WordPress page for more information. The link will be appended to the answer.', 'apc-free' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="priority"><?php esc_html_e( 'Priority', 'apc-free' ); ?></label>
									</th>
									<td>
										<input type="number" name="priority" id="priority" 
											value="<?php echo esc_attr( $editing_response['priority'] ?? 0 ); ?>" 
											class="small-text" min="0" max="100">
										<p class="description"><?php esc_html_e( 'Higher priority responses are checked first (0-100). Use this to prioritize specific answers.', 'apc-free' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<?php esc_html_e( 'Status', 'apc-free' ); ?>
									</th>
									<td>
										<label>
											<input type="checkbox" name="is_active" value="1" 
												<?php checked( $editing_response['is_active'] ?? 1, 1 ); ?>>
											<?php esc_html_e( 'Active', 'apc-free' ); ?>
										</label>
										<p class="description"><?php esc_html_e( 'Only active responses will be used for matching.', 'apc-free' ); ?></p>
									</td>
								</tr>
							</table>

							<p class="submit">
								<button type="submit" name="apc_save_response" class="button button-primary">
									<?php echo $editing_response ? esc_html__( 'Update Response', 'apc-free' ) : esc_html__( 'Add Response', 'apc-free' ); ?>
								</button>
								<?php if ( $editing_response ) : ?>
									<a href="?page=apc-predefined-responses" class="button"><?php esc_html_e( 'Cancel', 'apc-free' ); ?></a>
								<?php endif; ?>
							</p>
						</form>
					</div>
				</div>

				<!-- Responses List -->
				<div class="apc-admin-column-sidebar">
					<div class="card">
						<h2><?php esc_html_e( 'Existing Responses', 'apc-free' ); ?></h2>
						<?php if ( empty( $responses ) ) : ?>
							<p><?php esc_html_e( 'No responses yet. Create your first one!', 'apc-free' ); ?></p>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Question', 'apc-free' ); ?></th>
										<th><?php esc_html_e( 'Status', 'apc-free' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'apc-free' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $responses as $response ) : ?>
										<tr>
											<td>
												<strong><?php echo esc_html( $response['question'] ); ?></strong>
												<?php if ( $response['priority'] > 0 ) : ?>
													<br><small style="color: #666;">Priority: <?php echo esc_html( $response['priority'] ); ?></small>
												<?php endif; ?>
											</td>
											<td>
												<?php if ( $response['is_active'] ) : ?>
													<span class="apc-status-badge active"><?php esc_html_e( 'Active', 'apc-free' ); ?></span>
												<?php else : ?>
													<span class="apc-status-badge inactive"><?php esc_html_e( 'Inactive', 'apc-free' ); ?></span>
												<?php endif; ?>
											</td>
											<td>
												<a href="?page=apc-predefined-responses&edit=<?php echo esc_attr( $response['id'] ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'apc-free' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( '?page=apc-predefined-responses&action=delete&id=' . $response['id'], 'delete_response_' . $response['id'] ) ); ?>" 
													class="button button-small" 
													onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete this response?', 'apc-free' ); ?>');">
													<?php esc_html_e( 'Delete', 'apc-free' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<style>
				.apc-admin-two-column {
					display: grid;
					grid-template-columns: 1fr 400px;
					gap: 20px;
					margin-top: 20px;
				}
				.apc-admin-column-main .card,
				.apc-admin-column-sidebar .card {
					padding: 20px;
				}
				@media (max-width: 1280px) {
					.apc-admin-two-column {
						grid-template-columns: 1fr;
					}
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Render live support dashboard
	 */
	public function render_support_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		// Handle manual cleanup
		$cleanup_message = '';
		if ( isset( $_POST['apc_manual_cleanup'] ) && check_admin_referer( 'apc_manual_cleanup' ) ) {
			$timeout_minutes = get_option( 'apc_pending_timeout', 5 );
			$closed_count = APC_Database::auto_close_stale_pending_requests( $timeout_minutes );
			
			if ( $closed_count > 0 ) {
				$cleanup_message = sprintf( 
					/* translators: %d: number of closed requests */
					__( 'Successfully closed %d stale pending request(s).', 'apc-free' ), 
					$closed_count 
				);
			} else {
				$cleanup_message = __( 'No stale pending requests found to close.', 'apc-free' );
			}
		}

		?>
		<div class="wrap apc-admin apc-support-dashboard">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Manage live support conversations and respond to user requests.', 'apc-free' ); ?></p>

			<?php if ( $cleanup_message ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $cleanup_message ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Agent Status Toggle -->
			<div class="card" style="max-width: 100%; margin-bottom: 20px;">
				<h2><?php esc_html_e( 'Agent Status', 'apc-free' ); ?></h2>
				<div class="apc-agent-status-control">
					<label class="apc-status-toggle-label">
						<input type="checkbox" id="apc-agent-status-toggle" class="apc-status-toggle" />
						<span class="apc-status-slider"></span>
						<span id="apc-status-text" class="apc-status-text"><?php esc_html_e( 'Offline', 'apc-free' ); ?></span>
					</label>
					<p class="description">
						<?php esc_html_e( 'Toggle your availability to accept chat requests. When online, visitors can see agents are available.', 'apc-free' ); ?>
					</p>
				</div>
			</div>

			<div class="card" style="max-width: 100%; margin-bottom: 20px;">
				<h2><?php esc_html_e( 'Auto-Close Settings', 'apc-free' ); ?></h2>
				<p>
					<?php
					$timeout = get_option( 'apc_pending_timeout', 5 );
					if ( $timeout > 0 ) {
						printf(
							/* translators: %d: timeout in minutes */
							esc_html__( 'Pending requests auto-close after %d minutes of inactivity. Automatic cleanup runs hourly.', 'apc-free' ),
							esc_html( $timeout )
						);
					} else {
						esc_html_e( 'Auto-close is currently disabled.', 'apc-free' );
					}
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apc-settings' ) ); ?>"><?php esc_html_e( 'Change settings', 'apc-free' ); ?></a>
				</p>
				<form method="post" style="margin-top: 10px;">
					<?php wp_nonce_field( 'apc_manual_cleanup' ); ?>
					<button type="submit" name="apc_manual_cleanup" class="button button-secondary">
						<?php esc_html_e( '🗑️ Clean Up Stale Requests Now', 'apc-free' ); ?>
					</button>
					<span class="description" style="margin-left: 10px;">
						<?php esc_html_e( 'Manually close all pending requests that have exceeded the timeout period.', 'apc-free' ); ?>
					</span>
				</form>
			</div>

			<div class="apc-support-container">
				<!-- Pending Requests Panel -->
				<div class="apc-support-panel apc-pending-panel">
					<h2><?php esc_html_e( 'Pending Support Requests', 'apc-free' ); ?></h2>
					<div id="apc-pending-requests" class="apc-support-list">
						<p class="apc-loading"><?php esc_html_e( 'Loading...', 'apc-free' ); ?></p>
					</div>
				</div>

				<!-- Active Chats Panel -->
				<div class="apc-support-panel apc-active-panel">
					<h2><?php esc_html_e( 'My Active Chats', 'apc-free' ); ?></h2>
					<div id="apc-active-chats" class="apc-support-list">
						<p class="apc-loading"><?php esc_html_e( 'Loading...', 'apc-free' ); ?></p>
					</div>
				</div>

				<!-- Chat Window Panel -->
				<div class="apc-support-panel apc-chat-panel">
					<div class="apc-chat-window-header">
						<h2 id="apc-chat-title"><?php esc_html_e( 'Select a conversation', 'apc-free' ); ?></h2>
						<button id="apc-close-chat" class="button" style="display: none;"><?php esc_html_e( 'Close Conversation', 'apc-free' ); ?></button>
					</div>
					<div id="apc-chat-window" class="apc-chat-window">
						<p class="apc-no-selection"><?php esc_html_e( 'Select a pending request or active chat to view messages', 'apc-free' ); ?></p>
					</div>
					<div id="apc-chat-input-area" class="apc-chat-input-area" style="display: none;">
						<!-- Quick Actions Panel -->
						<div id="apc-quick-actions-panel" class="apc-quick-actions-panel">
							<div class="apc-quick-actions-header">
								<strong><?php esc_html_e( '⚡ Quick Actions', 'apc-free' ); ?></strong>
								<span class="apc-quick-actions-hint"><?php esc_html_e( 'Click to send a form', 'apc-free' ); ?></span>
							</div>
							<div id="apc-quick-actions-list" class="apc-quick-actions-list">
								<p class="apc-loading"><?php esc_html_e( 'Loading forms...', 'apc-free' ); ?></p>
							</div>
						</div>
						<div>
							<textarea id="apc-agent-message" placeholder="<?php esc_html_e( 'Type your message...', 'apc-free' ); ?>"></textarea>
							<button id="apc-send-message" class="button button-primary"><?php esc_html_e( 'Send', 'apc-free' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<style>
			.apc-support-dashboard {
				max-width: 100%;
			}
			.apc-support-container {
				display: grid;
				grid-template-columns: 250px 250px 1fr;
				gap: 20px;
				margin-top: 20px;
			}
			.apc-support-panel {
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			.apc-support-panel h2 {
				margin: 0;
				padding: 15px;
				font-size: 14px;
				border-bottom: 1px solid #ccd0d4;
				background: #f6f7f7;
			}
			.apc-support-list {
				max-height: 600px;
				overflow-y: auto;
			}
			.apc-support-item {
				padding: 12px 15px;
				border-bottom: 1px solid #eee;
				cursor: pointer;
				transition: background 0.2s;
			}
			.apc-support-item:hover {
				background: #f6f7f7;
			}
			.apc-support-item.active {
				background: #e5f5ff;
				border-left: 3px solid #0073aa;
			}
			.apc-support-item-user {
				font-weight: 600;
				margin-bottom: 5px;
			}
			.apc-support-item-time {
				font-size: 12px;
				color: #666;
			}
			.apc-support-item-preview {
				font-size: 13px;
				color: #666;
				margin-top: 5px;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			.apc-chat-panel {
				display: flex;
				flex-direction: column;
			}
			.apc-chat-window-header {
				padding: 15px;
				background: #f6f7f7;
				border-bottom: 1px solid #ccd0d4;
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.apc-chat-window {
				flex: 1;
				padding: 15px;
				overflow-y: auto;
				min-height: 400px;
				max-height: 500px;
			}
			.apc-chat-message {
				margin-bottom: 15px;
				max-width: 70%;
			}
			.apc-chat-message-user {
				margin-left: auto;
			}
			.apc-chat-message-header {
				font-size: 12px;
				color: #666;
				margin-bottom: 5px;
			}
			.apc-chat-message-content {
				padding: 10px 15px;
				border-radius: 12px;
				background: #f0f0f0;
			}
			.apc-chat-message-user .apc-chat-message-content {
				background: #0073aa;
				color: white;
			}
			.apc-chat-message-agent .apc-chat-message-content {
				background: #10b981;
				color: white;
			}
			.apc-chat-message-system .apc-chat-message-content {
				background: #fef3c7;
				color: #92400e;
				font-style: italic;
				text-align: center;
			}
			.apc-chat-input-area {
				padding: 15px;
				border-top: 1px solid #ccd0d4;
				display: flex;
				flex-direction: column;
				gap: 10px;
			}
			.apc-chat-input-area > div:last-child {
				display: flex;
				gap: 10px;
			}
			.apc-chat-input-area textarea {
				flex: 1;
				resize: vertical;
				min-height: 60px;
				max-height: 120px;
			}
			/* Quick Actions Panel Styles */
			.apc-quick-actions-panel {
				background: #f9fafb;
				border: 1px solid #e5e7eb;
				border-radius: 6px;
				padding: 10px;
				margin-bottom: 10px;
			}
			.apc-quick-actions-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 8px;
				padding-bottom: 8px;
				border-bottom: 1px solid #e5e7eb;
			}
			.apc-quick-actions-header strong {
				font-size: 13px;
				color: #374151;
			}
			.apc-quick-actions-hint {
				font-size: 11px;
				color: #6b7280;
			}
			.apc-quick-actions-list {
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
			}
			.apc-quick-action-btn {
				display: inline-flex;
				align-items: center;
				gap: 5px;
				padding: 6px 12px;
				background: #ffffff;
				border: 1px solid #d1d5db;
				border-radius: 4px;
				font-size: 12px;
				color: #374151;
				cursor: pointer;
				transition: all 0.2s ease;
				white-space: nowrap;
			}
			.apc-quick-action-btn:hover {
				background: #0073aa;
				color: white;
				border-color: #0073aa;
				transform: translateY(-1px);
				box-shadow: 0 2px 4px rgba(0, 115, 170, 0.2);
			}
			.apc-quick-action-btn:active {
				transform: translateY(0);
			}
			.apc-quick-action-btn .form-icon {
				font-size: 14px;
			}
			.apc-loading {
				text-align: center;
				padding: 20px;
				color: #666;
			}
			.apc-no-selection {
				text-align: center;
				padding: 40px 20px;
				color: #666;
			}
			.apc-accept-button {
				margin-top: 8px;
			}
			@media (max-width: 1280px) {
				.apc-support-container {
					grid-template-columns: 1fr;
				}
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var currentConversationId = null;
			var lastMessageId = 0;
			var pollInterval = null;

			// Load pending requests
			function loadPendingRequests() {
				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/support/pending' ) ); ?>',
					method: 'GET',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					success: function(response) {
						if (response.success && response.requests) {
							renderPendingRequests(response.requests);
						}
					}
				});
			}

			// Load active chats
			function loadActiveChats() {
				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/support/my-conversations' ) ); ?>',
					method: 'GET',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					success: function(response) {
						if (response.success && response.conversations) {
							renderActiveChats(response.conversations);
						}
					}
				});
			}

			// Render pending requests
			function renderPendingRequests(requests) {
				var $container = $('#apc-pending-requests');
				if (requests.length === 0) {
					$container.html('<p class="apc-no-selection"><?php esc_html_e( 'No pending requests', 'apc-free' ); ?></p>');
					return;
				}

				var html = '';
				requests.forEach(function(req) {
					html += '<div class="apc-support-item" data-conversation-id="' + req.conversation_id + '">';
					html += '<div class="apc-support-item-user">' + escapeHtml(req.user_name) + '</div>';
					html += '<div class="apc-support-item-time">' + req.requested_time + '</div>';
					html += '<div class="apc-support-item-preview">' + escapeHtml(req.last_message || '') + '</div>';
					html += '<button class="button button-small button-primary apc-accept-button" data-conversation-id="' + req.conversation_id + '"><?php esc_html_e( 'Accept', 'apc-free' ); ?></button>';
					html += '</div>';
				});
				$container.html(html);
			}

			// Render active chats
			function renderActiveChats(conversations) {
				var $container = $('#apc-active-chats');
				if (conversations.length === 0) {
					$container.html('<p class="apc-no-selection"><?php esc_html_e( 'No active chats', 'apc-free' ); ?></p>');
					return;
				}

				var html = '';
				conversations.forEach(function(conv) {
					var activeClass = (currentConversationId === conv.conversation_id) ? ' active' : '';
					html += '<div class="apc-support-item' + activeClass + '" data-conversation-id="' + conv.conversation_id + '">';
					html += '<div class="apc-support-item-user">' + escapeHtml(conv.user_name) + '</div>';
					html += '<div class="apc-support-item-time">' + conv.joined_time + '</div>';
					html += '<div class="apc-support-item-preview">' + escapeHtml(conv.last_message || '') + '</div>';
					html += '</div>';
				});
				$container.html(html);
			}

			// Load Quick Action Forms
			function loadQuickActionForms() {
				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/forms' ) ); ?>',
					method: 'GET',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					success: function(response) {
						if (response.success && response.forms && response.forms.length > 0) {
							renderQuickActionForms(response.forms);
						} else {
							$('#apc-quick-actions-list').html('<p style="margin:0;font-size:12px;color:#666;"><?php esc_html_e( 'No forms available', 'apc-free' ); ?></p>');
						}
					},
					error: function() {
						$('#apc-quick-actions-list').html('<p style="margin:0;font-size:12px;color:#dc2626;"><?php esc_html_e( 'Error loading forms', 'apc-free' ); ?></p>');
					}
				});
			}

			// Render Quick Action Forms
			function renderQuickActionForms(forms) {
				var $container = $('#apc-quick-actions-list');
				var html = '';
				
				forms.forEach(function(form) {
					// Only show active forms
					if (form.is_active == 1) {
						html += '<button class="apc-quick-action-btn" data-form-id="' + form.id + '" data-form-name="' + escapeHtml(form.name) + '" title="' + escapeHtml(form.description || form.name) + '">';
						html += '<span class="form-icon">📋</span>';
						html += '<span>' + escapeHtml(form.name) + '</span>';
						html += '</button>';
					}
				});
				
				if (html === '') {
					html = '<p style="margin:0;font-size:12px;color:#666;"><?php esc_html_e( 'No active forms', 'apc-free' ); ?></p>';
				}
				
				$container.html(html);
			}

			// Handle Quick Action button clicks
			$(document).on('click', '.apc-quick-action-btn', function() {
				var formId = $(this).data('form-id');
				var formName = $(this).data('form-name');
				var autoFormCode = '[auto_form:' + formId + ']';
				
				// Insert the autoform code into the textarea
				var $textarea = $('#apc-agent-message');
				var currentText = $textarea.val();
				
				// Add the autoform code on a new line if there's existing text
				if (currentText.trim()) {
					$textarea.val(currentText + '\n' + autoFormCode);
				} else {
					$textarea.val(autoFormCode);
				}
				
				// Focus the textarea
				$textarea.focus();
				
				// Visual feedback
				$(this).css('background', '#10b981');
				$(this).css('border-color', '#10b981');
				$(this).css('color', 'white');
				setTimeout(function() {
					$('.apc-quick-action-btn').css('background', '');
					$('.apc-quick-action-btn').css('border-color', '');
					$('.apc-quick-action-btn').css('color', '');
				}, 300);
			});

			// Accept request
			$(document).on('click', '.apc-accept-button', function(e) {
				e.stopPropagation();
				var conversationId = $(this).data('conversation-id');
				
				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/support/' ) ); ?>' + conversationId + '/assign',
					method: 'POST',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							loadPendingRequests();
							loadActiveChats();
							openConversation(conversationId);
						}
					}
				});
			});

			// Open conversation
			$(document).on('click', '.apc-support-item', function() {
				var conversationId = $(this).data('conversation-id');
				openConversation(conversationId);
			});

			// Open conversation
			function openConversation(conversationId) {
				currentConversationId = conversationId;
				lastMessageId = 0;
				$('#apc-chat-title').text('<?php esc_html_e( 'Conversation #', 'apc-free' ); ?>' + conversationId);
				$('#apc-close-chat').show();
				$('#apc-chat-input-area').show();
				$('.apc-support-item').removeClass('active');
				$('.apc-support-item[data-conversation-id="' + conversationId + '"]').addClass('active');
				
				loadMessages(conversationId);
				loadQuickActionForms(); // Load forms for quick actions
				startPolling();
			}

			// Load messages
			function loadMessages(conversationId) {
				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/conversations/' ) ); ?>' + conversationId + '/poll',
					method: 'GET',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					data: {
						since_id: 0
					},
					success: function(response) {
						if (response.success && response.messages) {
							renderMessages(response.messages);
							if (response.messages.length > 0) {
								lastMessageId = response.messages[response.messages.length - 1].id;
							}
						}
					}
				});
			}

			// Render messages
			function renderMessages(messages) {
				var $window = $('#apc-chat-window');
				$window.empty();

				messages.forEach(function(msg) {
					var roleClass = 'apc-chat-message-' + msg.role;
					var html = '<div class="apc-chat-message ' + roleClass + '">';
					html += '<div class="apc-chat-message-header">' + msg.role + ' - ' + msg.created_at + '</div>';
					html += '<div class="apc-chat-message-content">' + escapeHtml(msg.content) + '</div>';
					html += '</div>';
					$window.append(html);
				});

				$window.scrollTop($window[0].scrollHeight);
			}

			// Poll for new messages
			function startPolling() {
				if (pollInterval) {
					clearInterval(pollInterval);
				}
				pollInterval = setInterval(function() {
					if (currentConversationId) {
						pollMessages();
					}
				}, 3000);
			}

			function pollMessages() {
				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/conversations/' ) ); ?>' + currentConversationId + '/poll',
					method: 'GET',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					data: {
						since_id: lastMessageId
					},
					success: function(response) {
						if (response.success && response.messages && response.messages.length > 0) {
							response.messages.forEach(function(msg) {
								appendMessage(msg);
								lastMessageId = Math.max(lastMessageId, msg.id);
							});
						}
					}
				});
			}

			function appendMessage(msg) {
				var $window = $('#apc-chat-window');
				var roleClass = 'apc-chat-message-' + msg.role;
				var html = '<div class="apc-chat-message ' + roleClass + '">';
				html += '<div class="apc-chat-message-header">' + msg.role + ' - ' + msg.created_at + '</div>';
				html += '<div class="apc-chat-message-content">' + escapeHtml(msg.content) + '</div>';
				html += '</div>';
				$window.append(html);
				$window.scrollTop($window[0].scrollHeight);
			}

			// Send message
			$('#apc-send-message').on('click', function() {
				var message = $('#apc-agent-message').val().trim();
				if (!message || !currentConversationId) {
					return;
				}

				// Add message to UI immediately
				var tempMsg = {
					id: Date.now(),
					role: 'agent',
					content: message,
					created_at: new Date().toLocaleString()
				};
				appendMessage(tempMsg);
				$('#apc-agent-message').val('');

				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/support/' ) ); ?>' + currentConversationId + '/message',
					method: 'POST',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					data: JSON.stringify({
						message: message
					}),
					contentType: 'application/json',
					success: function(response) {
						if (response.success && response.message_id) {
							// Update lastMessageId to prevent polling from fetching this message again
							lastMessageId = Math.max(lastMessageId, response.message_id);
						}
					}
				});
			});

			// Close conversation
			$('#apc-close-chat').on('click', function() {
				if (!currentConversationId) {
					return;
				}

				if (!confirm('<?php esc_html_e( 'Are you sure you want to close this conversation?', 'apc-free' ); ?>')) {
					return;
				}

				$.ajax({
					url: '<?php echo esc_url( rest_url( 'apc/v1/support/' ) ); ?>' + currentConversationId + '/close',
					method: 'POST',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							currentConversationId = null;
							$('#apc-chat-window').html('<p class="apc-no-selection"><?php esc_html_e( 'Conversation closed', 'apc-free' ); ?></p>');
							$('#apc-chat-input-area').hide();
							$('#apc-close-chat').hide();
							loadActiveChats();
							loadPendingRequests();
						}
					}
				});
			});

			// Utility function
			function escapeHtml(text) {
				var map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#039;'
				};
				return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
			}

			// Initial load
			loadPendingRequests();
			loadActiveChats();

			// Refresh every 10 seconds
			setInterval(function() {
				loadPendingRequests();
				loadActiveChats();
			}, 10000);
		});
		</script>
		<?php
	}

	/**
	 * Render maintenance page
	 */
	public function render_maintenance_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have permission to access this page', 'apc-free' ) ) );
		}

		?>
		<div class="wrap apc-admin apc-maintenance-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="description"><?php esc_html_e( 'Manage database and plugin maintenance tasks. Exercise caution when using these tools.', 'apc-free' ); ?></p>

			<div class="card" style="max-width: 600px; margin-top: 20px;">
				<h2><?php esc_html_e( '🗑️ Reset Database', 'apc-free' ); ?></h2>
				<p><?php esc_html_e( 'Delete all chat conversations, messages, forms, and responses from the database. The database tables will be recreated and reset to their initial state.', 'apc-free' ); ?></p>
				
				<div style="background: #fff8e6; border-left: 4px solid #ff9800; padding: 15px; margin: 15px 0; border-radius: 4px;">
					<p style="margin: 0; color: #e65100; font-weight: bold;">
						<strong>⚠️ <?php esc_html_e( 'WARNING:', 'apc-free' ); ?></strong>
					</p>
					<p style="margin: 10px 0 0 0; color: #e65100;">
						<?php esc_html_e( 'This action cannot be undone. All existing chat history will be permanently deleted.', 'apc-free' ); ?>
					</p>
				</div>

				<button id="apc-reset-database-btn" class="button button-primary" style="background-color: #dc2626; border-color: #991b1b;">
					<?php esc_html_e( 'Reset Database Now', 'apc-free' ); ?>
				</button>

				<div id="apc-reset-status" style="margin-top: 15px; display: none;"></div>
			</div>

			<style>
				.apc-maintenance-page .card {
					box-shadow: 0 1px 1px rgba(0,0,0,0.04);
					border: 1px solid #e0e0e0;
				}
				#apc-reset-status {
					padding: 15px;
					border-radius: 4px;
					margin-top: 15px;
				}
				#apc-reset-status.success {
					background: #d4edda;
					border: 1px solid #c3e6cb;
					color: #155724;
				}
				#apc-reset-status.error {
					background: #f8d7da;
					border: 1px solid #f5c6cb;
					color: #721c24;
				}
				#apc-reset-status.loading {
					background: #d1ecf1;
					border: 1px solid #bee5eb;
					color: #0c5460;
				}
			</style>

			<script>
			jQuery(document).ready(function($) {
				$('#apc-reset-database-btn').on('click', function() {
					var confirmMessage = '<?php echo esc_js( __( 'Are you absolutely sure you want to reset the database? This will delete ALL conversations, messages, forms, and responses. This action CANNOT be undone!', 'apc-free' ) ); ?>';
					
					if (!confirm(confirmMessage)) {
						return;
					}

					var confirmAgain = '<?php echo esc_js( __( 'This is your final warning. All data will be permanently deleted. Type YES to confirm:', 'apc-free' ) ); ?>';
					var userInput = prompt(confirmAgain);
					
					if (userInput !== 'YES') {
						alert('<?php esc_html_e( 'Reset cancelled.', 'apc-free' ); ?>');
						return;
					}

					var $btn = $(this);
					var $status = $('#apc-reset-status');

					$btn.prop('disabled', true);
					$status.removeClass('success error').addClass('loading').html('<strong><?php esc_html_e( 'Processing...', 'apc-free' ); ?></strong> <?php esc_html_e( 'Resetting database, please wait...', 'apc-free' ); ?>').show();

					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: {
							action: 'apc_reset_database',
							nonce: '<?php echo esc_js( wp_create_nonce( 'apc_reset_database' ) ); ?>'
						},
						success: function(response) {
							if (response.success) {
								$status.removeClass('loading error').addClass('success')
									.html('<strong>✓ <?php esc_html_e( 'Success!', 'apc-free' ); ?></strong> ' + response.data.message);
								setTimeout(function() {
									location.reload();
								}, 2000);
							} else {
								$status.removeClass('loading').addClass('error')
									.html('<strong>✕ <?php esc_html_e( 'Error!', 'apc-free' ); ?></strong> ' + response.data.message);
								$btn.prop('disabled', false);
							}
						},
						error: function() {
							$status.removeClass('loading').addClass('error')
								.html('<strong>✕ <?php esc_html_e( 'Error!', 'apc-free' ); ?></strong> <?php esc_html_e( 'Failed to reset database. Please try again.', 'apc-free' ); ?>');
							$btn.prop('disabled', false);
						}
					});
				});
			});
			</script>
		</div>
		<?php
	}

	/**
	 * Handle AJAX reset database request
	 */
	public function handle_reset_database() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'apc_reset_database' ) ) {
			wp_send_json_error( 
				array( 'message' => __( 'Security verification failed.', 'apc-free' ) )
			);
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'apc-free' ) )
			);
		}

		try {
			$result = APC_Database::reset_database();

			if ( isset( $result['success'] ) && $result['success'] ) {
				wp_send_json_success(
					array( 'message' => $result['message'] . ' Reloading page...' )
				);
			} else {
				wp_send_json_error(
					array( 'message' => isset( $result['message'] ) ? $result['message'] : __( 'Failed to reset database.', 'apc-free' ) )
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array( 'message' => __( 'An error occurred: ', 'apc-free' ) . $e->getMessage() )
			);
		}
	}
}
