# AI Powered Chat - Free Version - Quick Setup

## ⚡ 5-Minute Setup

### Step 1: Install the Plugin

1. Download `ai-powered-chat-free` folder
2. Upload to `/wp-content/plugins/` via FTP or use WordPress plugin uploader
3. Go to **Dashboard → Plugins**
4. Find "AI Powered Chat - Free" and click **Activate**

You should see a success message!

### Step 2: Choose Your AI Provider

Visit **Dashboard → AI Chat → AI Providers** tab

Choose one of these:

#### Option A: OpenAI (Most Popular)
1. Get your free API key: https://platform.openai.com/api-keys
2. Sign up or log in to OpenAI
3. Go to API keys section
4. Create new secret key
5. Copy the key
6. Paste in WordPress: **AI Chat → Providers → OpenAI API Key**
7. Select model: **gpt-3.5-turbo** (free tier) or **gpt-4** (paid)
8. Click **Save Changes**

**Cost:** $0.50 per 1M input tokens, $1.50 per 1M output tokens (or subscription)

#### Option B: Claude (Recommended for Quality)
1. Get API key: https://console.anthropic.com/
2. Sign up or log in to Anthropic
3. Go to API Keys
4. Create new key
5. Copy and paste in WordPress: **AI Chat → Providers → Claude API Key**
6. Select model: **claude-3-5-sonnet** (recommended)
7. Click **Save Changes**

**Cost:** Check https://www.anthropic.com/pricing

#### Option C: Gemini (Google)
1. Get API key: https://makersuite.google.com/app/apikey
2. Sign in with Google account
3. Create new API key
4. Copy and paste in WordPress: **AI Chat → Providers → Gemini API Key**
5. Select model: **gemini-1.5-flash** (recommended)
6. Click **Save Changes**

**Cost:** Free tier available (rate limited)

#### Option D: GitHub Models (Free Option!)
1. Get token: https://github.com/settings/tokens
2. Create personal access token
3. Select scope: `read:user`
4. Copy and paste in WordPress: **AI Chat → Providers → GitHub Models Token**
5. Select model: **gpt-4o**
6. Click **Save Changes**

**Cost:** Free for beta testing!

### Step 3: Enable the Chat Widget

1. Go to **Dashboard → AI Chat → Settings**
2. Scroll to **Widget Settings**
3. Check the box: **"Enable Chat Widget"**
4. Click **Save Changes**

### Step 4: Test It Out

1. Log in as a user on your website
2. Visit any page on your site
3. Look in the **bottom-right corner** for the chat widget
4. Click it and start chatting!

---

## 🎯 What to Configure

### System Prompt (Optional but Recommended)

**Dashboard → AI Chat → Settings → System Prompt**

This tells the AI how to behave. Examples:

**For Customer Support:**
```
You are a helpful customer support representative for ABC Company. 
Provide polite, professional assistance. If you cannot help, 
suggest emailing support@example.com.
```

**For General Blog:**
```
You are a friendly assistant helping visitors learn about our blog topic.
Keep responses helpful and encouraging. Be concise (2-3 sentences).
```

**For Tech Support:**
```
You are a technical support expert. Provide clear, step-by-step solutions.
Avoid jargon when possible. Always offer alternative solutions.
```

### Welcome Message (Optional)

**Dashboard → AI Chat → Chat Customization → Welcome Message**

```
👋 Hi there! I'm here to help. What can I assist you with today?
```

---

## ✅ Common Settings

### Model Selection

| Provider | Best Model | Speed | Quality | Cost |
|----------|-----------|-------|---------|------|
| OpenAI | gpt-3.5-turbo | ⚡⚡⚡ | ⭐⭐⭐ | $ |
| OpenAI | gpt-4 | ⚡ | ⭐⭐⭐⭐⭐ | $$$$ |
| Claude | claude-3-5-sonnet | ⚡⚡ | ⭐⭐⭐⭐⭐ | $$ |
| Gemini | gemini-1.5-flash | ⚡⚡⚡ | ⭐⭐⭐ | Free |
| GitHub | gpt-4o | ⚡⚡ | ⭐⭐⭐⭐ | Free (beta) |

### Temperature Explained

**Dashboard → AI Chat → AI Providers → Temperature**

- **0.0-0.5** = Focused, factual responses (best for Q&A)
- **0.5-1.0** = Balanced, helpful (default, recommended)
- **1.0-2.0** = Creative, varied responses (best for brainstorming)

### Token Limit

**Dashboard → AI Chat → AI Providers → Max Tokens**

- **Lower (100-300)** = Shorter responses, cheaper
- **Default (500)** = Good balance
- **Higher (1000+)** = Longer responses, more expensive

---

## 🚀 Advanced: Using the REST API

### Create Conversation
```bash
curl -X POST https://yoursite.com/wp-json/apc/v1/conversations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{}' \
  -H "Content-Type: application/json"
```

### Send Message
```bash
curl -X POST https://yoursite.com/wp-json/apc/v1/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "conversation_id": 123,
    "message": "Hello!"
  }' \
  -H "Content-Type: application/json"
```

See [README_FREE_VERSION.md](./README_FREE_VERSION.md) for full API documentation.

---

## 🆘 Troubleshooting

### Chat widget not showing?

**Checklist:**
- [ ] Plugin is activated (check Plugins page)
- [ ] Chat enabled (Dashboard → AI Chat → Settings → Enable Chat Widget)
- [ ] You're logged in (widgets only show for logged-in users)
- [ ] Browser cache cleared (hard refresh: Ctrl+Shift+R)

### "Authentication failed" error?

- [ ] API key is correct (no spaces, exactly copied)
- [ ] API key has permission (check provider dashboard)
- [ ] Provider account is not suspended

### Chat widget appears but AI doesn't respond?

- [ ] Check API key is configured
- [ ] Test API key manually in provider dashboard
- [ ] Check WordPress error log: `/wp-content/debug.log`
- [ ] Verify provider status: 
  - OpenAI: https://status.openai.com
  - Claude: https://status.anthropic.com
  - Gemini: https://status.google.com
  - GitHub: https://www.githubstatus.com

### Database errors?

Make sure WordPress database tables were created:
- `wp_apc_conversations`
- `wp_apc_messages`
- `wp_apc_forms`

If missing, re-activate the plugin.

---

## 📚 Next Steps

1. **Customize appearance** - Edit CSS in `public/css/chat.css`
2. **Create Forms** - **Dashboard → AI Chat → Forms Builder**
3. **Add Auto-Responses** - **Dashboard → AI Chat → Auto Responses**
4. **Integrate with your site** - Add custom hooks and filters
5. **Monitor usage** - Track conversation history and AI responses

---

## ⚠️ Important Notes

### Free Version Limitations

❌ **NOT recommended for:**
- Healthcare data (use Premium HIPAA version)
- Financial information
- Legal documents
- Personal identification numbers
- Passwords or secrets

✅ **GOOD for:**
- General customer support
- FAQ automation
- Product recommendations
- Content recommendations
- User engagement

### Data Security

- Messages are stored in **plaintext** (not encrypted)
- All authenticated users can view conversations
- No audit logging of access
- For sensitive data, upgrade to **Premium HIPAA version**

---

## 🎯 Final Checklist

Before going live:

- [ ] API key configured and tested
- [ ] Chat widget visible on site
- [ ] Welcome message customized
- [ ] System prompt configured
- [ ] Model and temperature adjusted
- [ ] Tested on mobile device
- [ ] Error log checked (no errors)
- [ ] Users instructed where chat appears
- [ ] Support email added to system prompt

---

## 🚀 You're Ready!

Your AI-powered chat is now live! Start helping your customers with intelligent, instant responses.

**Questions?** Check [README_FREE_VERSION.md](./README_FREE_VERSION.md) or visit the [support forum](https://wordpress.org/support/plugin/ai-powered-chat-free/).

**Need encryption and compliance?** Upgrade to [Premium HIPAA version](https://www.microrepair.net/ai-powered-chat-premium) for end-to-end encryption and audit logging.

---

Happy chatting! 🤖💬
