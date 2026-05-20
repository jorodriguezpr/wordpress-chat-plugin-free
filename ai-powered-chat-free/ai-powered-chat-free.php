<?php
/**
 * Plugin Name: AI Powered Chat - Free
 * Plugin URI: https://wordpress.org/plugins/ai-powered-chat-free
 * Description: An intelligent chat plugin powered by AI technology (OpenAI GPT, Claude, Gemini, GitHub Copilot) for WordPress. Free version without enterprise compliance features.
 * Version: 1.0.0
 * Author: Jose Rodriguez Arroyo
 * Author URI: https://www.microrepair.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apc-free
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package    AI_Powered_Chat_Free
 * @author     Jose Rodriguez Arroyo <jrpcone@gmail.com>
 * @link       https://www.microrepair.net
 * @copyright  2026 Jose Rodriguez Arroyo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'APC_FREE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'APC_FREE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APC_FREE_PLUGIN_FILE', __FILE__ );
define( 'APC_FREE_VERSION', '1.0.0' );

/**
 * Main plugin class - Free version
 */
class AI_Powered_Chat_Free {
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->setup_hooks();
	}

	/**
	 * Load plugin dependencies (Free version - no HIPAA/Encryption/Access Control)
	 */
	private function load_dependencies() {
		// Provider system
		require_once APC_FREE_PLUGIN_PATH . 'includes/interface-apc-ai-provider.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-provider-openai.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-provider-claude.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-provider-gemini.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-provider-github-models.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-provider-factory.php';
		
		// Core classes (Free version - no encryption, audit log, or access control)
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-ai-handler-v2.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-database.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-forms.php';
		require_once APC_FREE_PLUGIN_PATH . 'includes/class-apc-rest-api.php';
		require_once APC_FREE_PLUGIN_PATH . 'admin/class-apc-admin-v2.php';
		require_once APC_FREE_PLUGIN_PATH . 'public/class-apc-public.php';
	}

	/**
	 * Setup WordPress hooks
	 */
	private function setup_hooks() {
		// Activation and deactivation
		register_activation_hook( APC_FREE_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( APC_FREE_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Check database version and upgrade if needed
		add_action( 'plugins_loaded', array( $this, 'check_database_version' ) );

		// Load text domain for translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Initialize admin and public classes
		add_action( 'plugins_loaded', array( $this, 'init_admin' ) );
		add_action( 'wp_loaded', array( $this, 'init_public' ) );
		
		// Initialize REST API
		add_action( 'init', array( $this, 'init_rest_api' ) );

		// Cron job for auto-closing stale pending requests
		add_action( 'apc_free_auto_close_stale_requests', array( $this, 'run_auto_close_stale_requests' ) );
	}

	/**
	 * Activation hook
	 */
	public function activate() {
		APC_Database::create_tables();
		update_option( 'apc_free_db_version', APC_FREE_VERSION );
		flush_rewrite_rules();

		// Schedule cron job if not already scheduled
		if ( ! wp_next_scheduled( 'apc_free_auto_close_stale_requests' ) ) {
			wp_schedule_event( time(), 'hourly', 'apc_free_auto_close_stale_requests' );
		}
	}

	/**
	 * Check database version and upgrade if needed
	 */
	public function check_database_version() {
		$current_db_version = get_option( 'apc_free_db_version', '0' );
		
		// If database version doesn't match plugin version, upgrade
		if ( version_compare( $current_db_version, APC_FREE_VERSION, '<' ) ) {
			APC_Database::create_tables();
			update_option( 'apc_free_db_version', APC_FREE_VERSION );
		}

		// Ensure cron job is scheduled (in case it was cleared)
		if ( ! wp_next_scheduled( 'apc_free_auto_close_stale_requests' ) ) {
			wp_schedule_event( time(), 'hourly', 'apc_free_auto_close_stale_requests' );
		}
	}

	/**
	 * Deactivation hook
	 */
	public function deactivate() {
		flush_rewrite_rules();

		// Clear scheduled cron job
		$timestamp = wp_next_scheduled( 'apc_free_auto_close_stale_requests' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'apc_free_auto_close_stale_requests' );
		}
	}

	/**
	 * Initialize admin
	 */
	public function init_admin() {
		if ( is_admin() ) {
			try {
				new APC_Admin();
			} catch ( Exception $e ) {
				// Handle gracefully during deactivation
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'APC Free - Admin class error: ' . $e->getMessage() );
				}
			}
		}
	}

	/**
	 * Initialize public
	 */
	public function init_public() {
		if ( ! is_admin() ) {
			try {
				new APC_Public();
			} catch ( Exception $e ) {
				// Handle gracefully during deactivation
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'APC Free - Public class error: ' . $e->getMessage() );
				}
			}
		}
	}

	/**
	 * Initialize REST API
	 */
	public function init_rest_api() {
		try {
			new APC_REST_API();
		} catch ( Exception $e ) {
			// Handle gracefully during deactivation
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'APC Free - REST API class error: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Load plugin text domain
	/**
	 * Load plugin text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'apc-free', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Run auto-close for stale pending requests (cron callback)
	 */
	public function run_auto_close_stale_requests() {
		$timeout_minutes = get_option( 'apc_free_pending_timeout', 5 );
		
		if ( $timeout_minutes > 0 ) {
			$closed_count = APC_Database::auto_close_stale_pending_requests( $timeout_minutes );
			
			// Log for debugging (optional)
			if ( $closed_count > 0 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'APC Free: Auto-closed %d stale pending requests (timeout: %d minutes)', $closed_count, $timeout_minutes ) );
			}
		}
	}
}

// Initialize plugin
AI_Powered_Chat_Free::get_instance();
