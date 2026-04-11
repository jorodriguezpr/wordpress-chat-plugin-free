# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-04-11

### Added
- **Multi-AI Provider Support**
  - OpenAI integration (GPT-3.5 Turbo, GPT-4, GPT-4 Turbo, GPT-4o)
  - Anthropic Claude integration (Claude 3 Haiku, Sonnet, Opus)
  - Google Gemini integration (Gemini 1.5 Flash, Pro)
  - GitHub Copilot Models integration
  - Provider factory pattern for easy extensibility
- **HIPAA Compliance Features**
  - AES-256-GCM encryption for all chat data
  - Key management and rotation support
  - Complete audit logging system
  - Role-based access control with custom roles
  - Data retention policies (configurable 6+ years)
  - HIPAA compliance dashboard
  - Business Associate Agreement (BAA) documentation
- **Enhanced Security**
  - End-to-end encryption for messages
  - Secure key storage in WordPress options
  - Access control with custom HIPAA roles
  - Audit trail for all data access
- **Advanced Admin Panel**
  - Multi-provider configuration interface
  - Provider selection and switching
  - Model selection per provider
  - Temperature and max tokens adjustment
  - System prompt customization
  - HIPAA compliance settings
  - Encryption key management
- **Data Management**
  - Conversation history with full persistence
  - Message metadata (tokens used, provider info)
  - Data export capabilities
  - Retention policy enforcement
- **API Enhancements**
  - Full REST API with all CRUD operations
  - Provider-agnostic endpoints
  - Comprehensive error handling
  - Request/response logging

### Changed
- Upgraded database schema to support multiple providers and encryption
- Refactored AI handler for provider abstraction
- Enhanced REST API for better extensibility
- Improved error handling and logging

### Fixed
- Improved stability across different provider APIs
- Enhanced error messages for debugging
- Better handling of API rate limits

### Technical Details
- PHP 7.4+ compatibility
- WordPress 5.0+ compatibility
- Provider factory pattern implementation
- Encryption/decryption middleware
- Advanced audit logging system
- Object-oriented design patterns

## [1.0.0] - 2024-03-30

### Added
- Initial release of AI Powered Chat plugin
- OpenAI API integration (GPT-3.5-turbo, GPT-4 support)
- Conversation history storage in database
- Admin settings panel
  - API key configuration
  - Model selection
  - Temperature adjustment
  - Max tokens configuration
  - System prompt customization
  - Widget enable/disable toggle
- Frontend chat widget
  - Beautiful, responsive UI
  - User-only access with login prompt
  - Auto-scroll and message animations
  - Typing indicator
  - Responsive design (desktop and mobile)
- REST API endpoints
  - Create conversations
  - Send messages
  - Retrieve conversation history
  - Delete conversations
- Database tables
  - Conversations storage
  - Messages storage with token tracking
- Admin management page
  - View all conversations
  - Delete conversations
- Comprehensive documentation
  - README.md with features and usage
  - SETUP.md with installation guide
  - Inline code documentation
- WordPress localization support (i18n)
- Proper WordPress security practices
  - Nonce verification
  - Permission checks
  - User role-based access

### Technical Details
- PHP 7.4+ compatibility
- WordPress 5.0+ compatibility
- RESTful API architecture
- jQuery-based frontend
- AJAX message handling
- Responsive CSS layout
- Object-oriented PHP design

## Planned Features

### [1.1.0] - Planned
- Chat history export (JSON, PDF)
- User preferences storage
- Custom widget styling options
- Markdown support in responses
- Code syntax highlighting
- Message reactions/ratings
- Conversation search functionality

### [1.2.0] - Planned
- Multiple AI provider support
  - Anthropic Claude integration
  - Google Bard integration
  - Cohere API support
- Conversation branching
- Message archiving
- Admin moderation tools
- User usage analytics

### [2.0.0] - Planned
- Voice input/output support
- Image understanding (GPT-4 Vision)
- Custom AI model fine-tuning
- Advanced analytics dashboard
- Team conversation features
- Integration with chat platforms (Slack, Discord, Twitter)

---

For version history before 1.0.0 or pre-release versions, please check the git commit history.
