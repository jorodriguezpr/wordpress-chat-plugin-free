# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
