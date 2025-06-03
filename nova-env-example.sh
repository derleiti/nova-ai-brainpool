# Nova AI Brainpool Environment Configuration
# Copy this file to .env and configure your settings

# ==============================================
# AI Provider Settings
# ==============================================

# Active AI Provider (ailinux, openai, anthropic, local)
NOVA_AI_ACTIVE_PROVIDER=ailinux

# AI Linux API Configuration
NOVA_AI_API_URL=https://ailinux.me/api/v1
NOVA_AI_API_KEY=your_ailinux_api_key_here
NOVA_AI_MODEL=gpt-4

# OpenAI API Configuration
NOVA_AI_OPENAI_API_KEY=your_openai_api_key_here

# Anthropic API Configuration
NOVA_AI_ANTHROPIC_API_KEY=your_anthropic_api_key_here

# AI Model Parameters
NOVA_AI_MAX_TOKENS=2048
NOVA_AI_TEMPERATURE=0.7
NOVA_AI_SYSTEM_PROMPT="You are Nova AI, a helpful and knowledgeable assistant."

# ==============================================
# Web Crawler Settings
# ==============================================

# Enable/Disable Crawler
NOVA_AI_CRAWL_ENABLED=true
NOVA_AI_AUTO_CRAWL_ENABLED=true

# Sites to crawl (JSON array format)
NOVA_AI_CRAWL_SITES=["https://ailinux.me", "https://ailinux.me/blog", "https://ailinux.me/docs"]

# Crawler Configuration
NOVA_AI_CRAWL_INTERVAL=hourly
NOVA_AI_MAX_CRAWL_DEPTH=3
NOVA_AI_CRAWL_DELAY=1000

# ==============================================
# Image Generation Settings
# ==============================================

# Enable/Disable Image Generation
NOVA_AI_IMAGE_GENERATION_ENABLED=true

# Stable Diffusion API URL
NOVA_AI_IMAGE_API_URL=https://ailinux.me:7860

# Maximum Image Size
NOVA_AI_MAX_IMAGE_SIZE=1024

# ==============================================
# General Settings
# ==============================================

# Conversation Management
NOVA_AI_SAVE_CONVERSATIONS=true
NOVA_AI_CONVERSATION_RETENTION_DAYS=30

# ==============================================
# NovaNet Network Settings
# ==============================================

# Enable/Disable NovaNet
NOVA_AI_NOVANET_ENABLED=false

# NovaNet Configuration
NOVA_AI_NOVANET_URL=https://ailinux.me/novanet
NOVA_AI_NOVANET_API_KEY=your_novanet_api_key_here
NOVA_AI_NOVANET_AUTO_SHARE=false

# ==============================================
# Development & Debug Settings
# ==============================================

# WordPress Debug Mode
WP_DEBUG=false
WP_DEBUG_LOG=false
WP_DEBUG_DISPLAY=false

# Nova AI Debug Mode
NOVA_AI_DEBUG=false
NOVA_AI_LOG_LEVEL=info

# Cache Settings
NOVA_AI_CACHE_ENABLED=true
NOVA_AI_CACHE_TTL=3600

# Rate Limiting
NOVA_AI_RATE_LIMIT_ENABLED=true
NOVA_AI_RATE_LIMIT_REQUESTS=100
NOVA_AI_RATE_LIMIT_WINDOW=3600

# ==============================================
# Security Settings
# ==============================================

# API Security
NOVA_AI_API_TIMEOUT=60
NOVA_AI_MAX_REQUEST_SIZE=10485760

# User Permissions
NOVA_AI_REQUIRE_LOGIN=false
NOVA_AI_ADMIN_ONLY=false
NOVA_AI_ALLOWED_ROLES=["administrator", "editor"]

# ==============================================
# Performance Settings
# ==============================================

# Memory and Resource Limits
NOVA_AI_MEMORY_LIMIT=256M
NOVA_AI_EXECUTION_TIME=300
NOVA_AI_MAX_CONCURRENT_REQUESTS=5

# Database Optimization
NOVA_AI_DB_CLEANUP_ENABLED=true
NOVA_AI_DB_CLEANUP_INTERVAL=daily

# ==============================================
# Advanced Features
# ==============================================

# Plugin Integration
NOVA_AI_WOOCOMMERCE_INTEGRATION=false
NOVA_AI_ELEMENTOR_INTEGRATION=false
NOVA_AI_GUTENBERG_INTEGRATION=true

# Custom Extensions
NOVA_AI_CUSTOM_FUNCTIONS_ENABLED=false
NOVA_AI_WEBHOOK_ENABLED=false
NOVA_AI_WEBHOOK_URL=""

# Analytics & Monitoring
NOVA_AI_ANALYTICS_ENABLED=true
NOVA_AI_MONITORING_ENABLED=true
NOVA_AI_ERROR_REPORTING=true

# ==============================================
# Experimental Features
# ==============================================

# Beta Features (use with caution)
NOVA_AI_ENABLE_BETA_FEATURES=false
NOVA_AI_EXPERIMENTAL_MODELS=false
NOVA_AI_ADVANCED_CRAWLER=false
