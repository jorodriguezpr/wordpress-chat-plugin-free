<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Plugin Development Configuration
 * 
 * Uncomment and edit the values below for development/testing
 */

// Development environment - set to true for local development
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	define( 'APC_DEBUG', true );
}

/**
 * Default Configuration Values
 * Override these in wp-config.php or via WordPress settings UI
 */

// Example: Set API key via environment variable (do not commit actual keys!)
// if ( defined( 'APC_OPENAI_API_KEY' ) ) {
//     update_option( 'apc_openai_api_key', APC_OPENAI_API_KEY );
// }

// Example: Set default system prompt
// define( 'APC_DEFAULT_SYSTEM_PROMPT', 'You are a helpful assistant.' );

// Example: Set default model
// define( 'APC_DEFAULT_MODEL', 'gpt-3.5-turbo' );

/**
 * Custom Rate Limiting Configuration (Optional)
 */

// Maximum messages per user per hour
// define( 'APC_MAX_MESSAGES_PER_HOUR', 50 );

// Maximum tokens per conversation
// define( 'APC_MAX_TOKENS_PER_CONVERSATION', 10000 );

/**
 * Logging Configuration (for debugging)
 */

if ( defined( 'APC_DEBUG' ) && APC_DEBUG ) {
	// Enable detailed logging
	define( 'APC_LOG_ENABLED', true );
	define( 'APC_LOG_DIR', WP_CONTENT_DIR . '/logs/ai-chat/' );
}

/**
 * Database Configuration
 */

// Customize table prefix if needed
// define( 'APC_TABLE_PREFIX', 'apc_' );

/**
 * Security Configuration
 */

// CORS allowed origins (if using from other domains)
// define( 'APC_ALLOWED_ORIGINS', array(
//     'https://example.com',
//     'https://app.example.com',
// ) );

// JWT Secret for advanced authentication (optional)
// define( 'APC_JWT_SECRET', 'your-secret-key-here' );

/**
 * Performance Configuration
 */

// Cache conversation responses (in seconds)
// define( 'APC_CACHE_TIMEOUT', 3600 );

// Enable conversation archiving after X days
// define( 'APC_AUTO_ARCHIVE_DAYS', 90 );

/**
 * Integration Points for Customization
 */

// Example: Custom AI response handler
// add_filter( 'apc_ai_response', function( $response, $conversation_id ) {
//     // Modify or filter the AI response
//     return $response;
// }, 10, 2 );

// Example: Track API usage
// add_action( 'apc_message_sent', function( $message_id, $tokens_used ) {
//     // Log usage metrics
//     update_user_meta( get_current_user_id(), 'apc_tokens_used', 
//         (int) get_user_meta( get_current_user_id(), 'apc_tokens_used', true ) + $tokens_used 
//     );
// }, 10, 2 );
