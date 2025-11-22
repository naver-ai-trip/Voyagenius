# 🗺️ TripPlanner Backend API

![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)
![Tests](https://img.shields.io/badge/Tests-206%20passing-brightgreen)
![OpenAPI](https://img.shields.io/badge/OpenAPI-3.0-85EA2D?logo=swagger)

Complete REST API backend for travel planning application with **NAVER Cloud Platform integration**, **AI Agent support**, and **real-time features**.

## 🎯 Overview

TripPlanner is a Laravel-based REST API that provides comprehensive travel planning capabilities including:

- 🛫 **Trip Management** - Create, share, and collaborate on travel itineraries
- 🗺️ **NAVER Maps Integration** - Geocoding, directions, and POI search
- 🌐 **Translation Services** - Papago text/image/OCR/speech translation
- 🤖 **AI Agent Integration** - Conversational AI for trip recommendations
- 📊 **Search Trends** - NAVER DataLab analytics for travel insights
- 🔐 **OAuth Authentication** - Google Sign-In with Laravel Sanctum
- 📡 **Real-time Updates** - WebSocket broadcasting via Laravel Reverb
- 🖼️ **Content Moderation** - Green-Eye AI for image safety

---

## 📚 Table of Contents

- [Quick Start](#-quick-start)
- [API Documentation](#-api-documentation)
- [Architecture](#-architecture)
- [Core Features](#-core-features)
- [NAVER Cloud Integration](#-naver-cloud-integration)
- [AI Agent System](#-ai-agent-system)
- [Authentication](#-authentication)
- [Real-time Features](#-real-time-features)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [API Reference](#-api-reference)

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- SQLite (default) or MySQL/PostgreSQL
- Redis (optional, for production broadcasting)

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd TripPlanner

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
touch database/database.sqlite
php artisan migrate --seed

# Generate Swagger documentation
php artisan l5-swagger:generate

# Build frontend assets
npm run build

# Start development server
composer dev
```

The `composer dev` command starts all services concurrently:
- Laravel server (http://localhost:8000)
- Queue worker
- Laravel Pail (real-time logs)
- Vite dev server

### First API Call

```bash
# Register a user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Response includes Sanctum token
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com" },
  "token": "1|abcdefghijklmnopqrstuvwxyz..."
}

# Use token for authenticated requests
curl -X GET http://localhost:8000/api/trips \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📖 API Documentation

### Interactive Swagger UI

Access the complete OpenAPI 3.0 documentation at:

```
http://localhost:8000/api/documentation
```

Features:
- ✅ **Try it out** - Execute API calls directly from the browser
- 📝 **Request/Response schemas** - Complete data models with examples
- 🔐 **Authentication** - Built-in Sanctum token authorization
- 📊 **All 80+ endpoints** - Organized by resource categories

### Documentation Files

- **[AI_AGENT_QUICK_REFERENCE.md](AI_AGENT_QUICK_REFERENCE.md)** - AI Agent integration guide
- **[NAVER_API_INTEGRATION_REPORT.md](NAVER_API_INTEGRATION_REPORT.md)** - NAVER services setup
- **[ADVANCED_FEATURES_TESTING_GUIDE.md](ADVANCED_FEATURES_TESTING_GUIDE.md)** - WebSocket & queue testing
- **[cloudflare-reverb-setup.md](cloudflare-reverb-setup.md)** - Production deployment guide

---

## 🏗️ Architecture

### Tech Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Framework** | Laravel 12.0 | REST API framework |
| **Database** | SQLite / MySQL / PostgreSQL | Data persistence |
| **Authentication** | Laravel Sanctum | Token-based API auth |
| **OAuth** | Laravel Socialite | Google Sign-In |
| **Real-time** | Laravel Reverb | WebSocket server |
| **Queue** | Database/Redis | Background job processing |
| **Cache** | Database/Redis | API response caching |
| **Broadcasting** | Pusher Protocol | WebSocket events |
| **Documentation** | L5-Swagger (OpenAPI 3.0) | API docs generation |
| **Admin Panel** | Filament 4.x | Database management |
| **Testing** | PHPUnit 11 | Unit & feature tests |

### Project Structure

```
app/
├── Http/Controllers/          # API endpoints
│   ├── TripController.php     # Trip CRUD
│   ├── PlaceController.php    # NAVER Place search
│   ├── TranslationController.php  # Papago translation
│   ├── ChatSessionController.php  # AI chat sessions
│   └── ...
├── Models/                    # Eloquent models (22 models)
│   ├── Trip.php
│   ├── ChatSession.php
│   └── ...
├── Services/                  # External API integrations
│   ├── NaverMapsService.php
│   ├── NaverPapagoService.php
│   ├── NaverOcrService.php
│   └── ...
├── Policies/                  # Authorization policies
├── Jobs/                      # Queue jobs
├── Actions/                   # Reusable business logic
└── Filament/                  # Admin panel resources

database/
├── migrations/                # Database schema (30+ tables)
└── seeders/                   # Sample data

tests/
├── Feature/                   # API integration tests
├── Unit/                      # Service unit tests
└── Integration/               # NAVER API tests

routes/
├── api.php                    # API routes (80+ endpoints)
└── web.php                    # OAuth callbacks
```

### Database Schema (ERD)

**Core Entities:**
```
users ─┬─> trips ─┬─> trip_participants
       │           ├─> itinerary_items
       │           ├─> checkpoints ──> checkpoint_images
       │           ├─> checklists
       │           ├─> trip_diaries
       │           ├─> trip_recommendations
       │           └─> shares
       │
       ├─> chat_sessions ─┬─> chat_messages
       │                   └─> agent_actions
       │
       ├─> user_preferences
       ├─> favorites
       ├─> reviews
       ├─> comments
       └─> notifications
```

**Key Relationships:**
- Trip has many Participants (user roles: owner, editor, viewer)
- ChatSession belongs to Trip (optional) and User
- AgentAction tracks AI operations (search, translation, API calls)
- TripRecommendation stores AI suggestions (accept/reject workflow)

---

## 🌟 Core Features

### 1. Trip Management

Create and manage collaborative travel itineraries with participants, checkpoints, and checklists.

**Endpoints:**
```
GET    /api/trips                 # List user's trips
POST   /api/trips                 # Create new trip
GET    /api/trips/{id}            # Get trip details
PATCH  /api/trips/{id}            # Update trip
DELETE /api/trips/{id}            # Delete trip

# Nested resources
GET    /api/trips/{id}/participants    # List participants
POST   /api/trips/{id}/participants    # Add participant
GET    /api/trips/{id}/shares          # List share links
POST   /api/trips/{id}/shares          # Create share link
```

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/trips \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Seoul Summer 2025",
    "description": "5-day adventure in South Korea",
    "start_date": "2025-07-01",
    "end_date": "2025-07-05",
    "destination": "Seoul, South Korea",
    "budget": 2000.00,
    "currency": "USD",
    "visibility": "private"
  }'
```

### 2. Place Search (NAVER Local Search)

Search for restaurants, hotels, attractions, and businesses using NAVER Local Search API.

**Endpoints:**
```
POST /api/places/search           # Keyword search
POST /api/places/search-nearby    # Proximity search
GET  /api/places/{id}             # Get place details
```

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/places/search \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "강남역 맛집",
    "latitude": 37.498095,
    "longitude": 127.027610,
    "radius": 1000,
    "sort": "rating"
  }'
```

### 3. Translation Services (NAVER Papago)

Multi-modal translation supporting text, images, OCR, and speech.

**Endpoints:**
```
POST /api/translations/text       # Text translation
POST /api/translations/image      # Image translation
POST /api/translations/ocr        # Extract & translate text from image
POST /api/translations/speech     # Speech-to-text translation
GET  /api/translations            # Translation history
```

**Supported Languages:**
`ko` (Korean), `en` (English), `ja` (Japanese), `zh-CN` (Chinese Simplified), `zh-TW` (Chinese Traditional), `es` (Spanish), `fr` (French), `de` (German), `ru` (Russian), `pt` (Portuguese), `it` (Italian), `vi` (Vietnamese), `th` (Thai), `id` (Indonesian), `ar` (Arabic)

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/translations/text \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "text": "안녕하세요, 서울입니다",
    "source": "ko",
    "target": "en"
  }'

# Response
{
  "data": {
    "id": 1,
    "source_text": "안녕하세요, 서울입니다",
    "translated_text": "Hello, this is Seoul",
    "source_language": "ko",
    "target_language": "en",
    "translation_type": "text",
    "confidence_score": 0.95
  }
}
```

### 4. Maps & Geocoding (NAVER Maps)

Address resolution, reverse geocoding, and route planning.

**Endpoints:**
```
POST /api/maps/geocode                  # Address → Coordinates
POST /api/maps/reverse-geocode          # Coordinates → Address
POST /api/maps/directions               # Point-to-point route
POST /api/maps/directions-waypoints     # Multi-stop route
```

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/maps/geocode \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "서울특별시 강남구 역삼동 테헤란로 152"
  }'
```

### 5. Search Trends (NAVER DataLab)

Analyze search popularity for travel destinations and planning.

**Endpoints:**
```
POST /api/search-trends/keywords               # Single keyword trends
POST /api/search-trends/compare                # Compare multiple keywords
POST /api/search-trends/demographics           # Age/gender breakdown
POST /api/search-trends/devices                # Device usage trends
POST /api/search-trends/destination-popularity # Destination analysis
POST /api/search-trends/seasonal-insights      # Seasonal patterns
```

### 6. Content Moderation (Green-Eye)

AI-powered image safety detection for user uploads.

**Features:**
- Adult content detection
- Violence detection
- Automatic image rejection/flagging
- Configurable thresholds

**Usage:**
Content moderation is automatically applied to:
- Checkpoint images
- Trip diary photos
- User profile pictures
- Review images

---

## 🤖 AI Agent System

Conversational AI integration for intelligent trip planning assistance.

### Chat Sessions

Create and manage AI conversations with context awareness.

**Endpoints:**
```
POST   /api/chat-sessions                      # Create session
GET    /api/chat-sessions                      # List sessions
GET    /api/chat-sessions/{id}                 # Get session
POST   /api/chat-sessions/{id}/activate        # Activate session
POST   /api/chat-sessions/{id}/deactivate      # Deactivate session
DELETE /api/chat-sessions/{id}                 # Delete session
```

**Session Types:**
- `trip_planning` - General trip planning assistance
- `place_search` - POI and attraction recommendations
- `itinerary_building` - Day-by-day schedule creation
- `budget_optimization` - Cost analysis and suggestions
- `translation_help` - Language assistance

**Example: Create Trip Planning Session**
```bash
curl -X POST http://localhost:8000/api/chat-sessions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "session_type": "trip_planning",
    "trip_id": 1,
    "context": {
      "destination": "Seoul",
      "duration": "5 days",
      "interests": ["food", "culture", "shopping"]
    }
  }'
```

### Chat Messages

Send and receive messages within a session.

**Endpoints:**
```
POST /api/chat-sessions/{id}/messages          # Send message
GET  /api/chat-sessions/{id}/messages          # Get message history
```

**Example: Send User Message**
```bash
curl -X POST http://localhost:8000/api/chat-sessions/1/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "What are the best restaurants near Gangnam Station?",
    "from_role": "user"
  }'
```

### Agent Actions

Track AI operations like API calls, searches, and data processing.

**Endpoints:**
```
POST /api/chat-sessions/{id}/actions           # Create action
POST /api/actions/{id}/complete                # Mark action complete
POST /api/actions/{id}/fail                    # Mark action failed
GET  /api/chat-sessions/{id}/actions           # List session actions
```

**Action Types:**
- `naver_place_search` - NAVER Place API search
- `naver_translation` - Papago translation
- `create_itinerary_item` - Add to itinerary
- `search_trends_analysis` - DataLab query
- `create_checkpoint` - Add map checkpoint
- `budget_calculation` - Cost estimation

**Example: Track Place Search**
```bash
# Start action
curl -X POST http://localhost:8000/api/chat-sessions/1/actions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "action_type": "naver_place_search",
    "input_data": {
      "query": "강남역 카페",
      "latitude": 37.498095,
      "longitude": 127.027610
    }
  }'

# Complete action
curl -X POST http://localhost:8000/api/actions/1/complete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "output_data": {
      "results_count": 10,
      "top_result": {
        "name": "Cafe A",
        "rating": 4.5
      }
    }
  }'
```

### Trip Recommendations

AI-generated suggestions with accept/reject workflow.

**Endpoints:**
```
POST /api/trips/{id}/recommendations           # Create recommendation
GET  /api/trips/{id}/recommendations           # List recommendations
POST /api/recommendations/{id}/accept          # Accept suggestion
POST /api/recommendations/{id}/reject          # Reject suggestion
```

**Recommendation Types:**
- `place` - POI suggestion
- `itinerary_item` - Schedule suggestion
- `budget_adjustment` - Budget optimization
- `route_optimization` - Route improvement
- `accommodation` - Hotel suggestion

**Example: AI Place Recommendation**
```bash
curl -X POST http://localhost:8000/api/trips/1/recommendations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "recommendation_type": "place",
    "data": {
      "place_id": 123,
      "name": "Gyeongbokgung Palace",
      "reason": "Must-see historical site matching your culture interest",
      "estimated_time": "2 hours",
      "cost": "3000 KRW"
    },
    "confidence_score": 0.92
  }'

# Accept recommendation
curl -X POST http://localhost:8000/api/recommendations/1/accept \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### User Preferences

Store travel preferences for AI personalization.

**Endpoints:**
```
GET    /api/user-preferences                   # List preferences
POST   /api/user-preferences                   # Create preference
PATCH  /api/user-preferences/{id}              # Update preference
DELETE /api/user-preferences/{id}              # Delete preference
```

**Preference Types:**
- `budget_range` - Price sensitivity
- `travel_pace` - Activity level (relaxed/moderate/intense)
- `food_preference` - Cuisine types, dietary restrictions
- `interest_categories` - Culture, nature, food, shopping, etc.
- `accommodation_type` - Hotel, hostel, Airbnb preferences
- `transportation_mode` - Public transit, walking, driving

**Example:**
```bash
curl -X POST http://localhost:8000/api/user-preferences \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "preference_type": "food_preference",
    "value": {
      "cuisines": ["Korean", "Japanese", "Vietnamese"],
      "dietary_restrictions": ["vegetarian"],
      "spice_level": "medium"
    },
    "priority": 8
  }'
```

### Agent Webhooks

Subscribe to real-time AI events via webhooks.

**Endpoints:**
```
POST   /api/agent-webhooks                     # Create webhook
GET    /api/agent-webhooks                     # List webhooks
POST   /api/agent-webhooks/{id}/test           # Test webhook
DELETE /api/agent-webhooks/{id}                # Delete webhook
```

**Event Types:**
- `session.created` - New chat session
- `message.received` - New user message
- `action.completed` - AI action finished
- `recommendation.generated` - New suggestion
- `error.occurred` - Action failed

---

## 🔐 Authentication

### Laravel Sanctum (API Tokens)

**Registration:**
```bash
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

# Response includes token
{
  "user": { ... },
  "token": "1|abcdef..."
}
```

**Login:**
```bash
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Using Token:**
```bash
GET /api/trips
Authorization: Bearer 1|abcdef...
```

**Logout:**
```bash
POST /api/logout
Authorization: Bearer YOUR_TOKEN
```

### Google OAuth (Laravel Socialite)

**OAuth Flow:**

1. **Redirect to Google:**
```
GET /auth/google
```

2. **Google callback (automatic):**
```
GET /auth/google/callback?code=...
```

3. **Receive user & token:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "google_id": "1234567890"
  },
  "token": "1|abcdef..."
}
```

**Configuration:**
```env
# .env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

---

## 🔌 NAVER Cloud Integration

### Service Overview

| Service | Purpose | Authentication | Status |
|---------|---------|---------------|--------|
| **NAVER Local Search** | POI/business search | Client ID/Secret | ✅ Working |
| **NAVER Maps** | Geocoding, directions | Client ID/Secret | ⚠️ Requires subscription |
| **Papago Translation** | Text/image translation | Client ID/Secret | ⚠️ Requires API access |
| **Clova OCR** | Text extraction from images | Secret Key + URL | 🟡 Ready (untested) |
| **Clova Speech** | Speech-to-text | Client ID/Secret | 🟡 Ready (untested) |
| **Green-Eye** | Content moderation | Secret Key + URL | ✅ Working |
| **DataLab** | Search trend analytics | Client ID/Secret | ✅ Working |

### Configuration

**Environment Variables:**
```env
# NAVER Developers (openapi.naver.com)
NAVER_DEVELOPERS_CLIENT_ID=your_client_id
NAVER_DEVELOPERS_CLIENT_SECRET=your_client_secret
NAVER_LOCAL_SEARCH_ENABLED=true

# NAVER Cloud Platform (ntruss.com)
NAVER_MAPS_CLIENT_ID=your_maps_client_id
NAVER_MAPS_CLIENT_SECRET=your_maps_client_secret

NAVER_PAPAGO_CLIENT_ID=your_papago_client_id
NAVER_PAPAGO_CLIENT_SECRET=your_papago_client_secret

NAVER_CLOVA_OCR_URL=https://your-endpoint.apigw.ntruss.com/...
NAVER_CLOVA_OCR_SECRET_KEY=your_secret_key

NAVER_SPEECH_CLIENT_ID=your_speech_client_id
NAVER_SPEECH_CLIENT_SECRET=your_speech_client_secret

NAVER_GREENEYE_URL=https://your-endpoint.apigw.ntruss.com/...
NAVER_GREENEYE_SECRET_KEY=your_secret_key
NAVER_GREENEYE_ENABLED=true

# Feature Flags
NAVER_TRANSLATION_ENABLED=true
NAVER_MAPS_ENABLED=true
NAVER_OCR_ENABLED=true
NAVER_SPEECH_ENABLED=true
```

### Getting API Keys

1. **NAVER Developers Console** (openapi.naver.com)
   - Local Search API
   - Papago Translation (requires approval)

2. **NAVER Cloud Platform** (console.ncloud.com)
   - Maps API (requires subscription)
   - Clova OCR (API Gateway setup)
   - Clova Speech
   - Green-Eye

**Detailed Setup Guide:**
- [NAVER_API_ACTIVATION_GUIDE.md](NAVER_API_ACTIVATION_GUIDE.md)
- [NAVER_API_INTEGRATION_REPORT.md](NAVER_API_INTEGRATION_REPORT.md)

---

## 📡 Real-time Features

### Laravel Reverb (WebSocket Server)

**Broadcasting Events:**
- `TripUpdated` - Trip modifications
- `MessageSent` - New chat messages
- `ActionCompleted` - AI action finished
- `RecommendationGenerated` - New AI suggestion
- `NotificationCreated` - User notifications

**Configuration:**
```env
# .env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

**Starting Reverb Server:**
```bash
# Development
php artisan reverb:start

# Production (with supervisor)
php artisan reverb:start --host=0.0.0.0 --port=8080
```

**Production Deployment:**
See [cloudflare-reverb-setup.md](cloudflare-reverb-setup.md) for Cloudflare Tunnel setup.

**Client-side Connection:**
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'your_app_key',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
});

// Subscribe to trip updates
window.Echo.private(`trip.${tripId}`)
    .listen('TripUpdated', (e) => {
        console.log('Trip updated:', e.trip);
    });

// Subscribe to chat messages
window.Echo.private(`chat-session.${sessionId}`)
    .listen('MessageSent', (e) => {
        console.log('New message:', e.message);
    });
```

### Queue Jobs

Background processing for long-running tasks.

**Job Types:**
- `ProcessTranslation` - OCR/Speech translation
- `SendNotification` - Email/push notifications
- `AnalyzeSearchTrends` - DataLab API calls
- `ModerateImage` - Green-Eye content check

**Running Queue Worker:**
```bash
# Development
php artisan queue:work

# Production (with supervisor)
php artisan queue:work --tries=3 --timeout=90
```

---

## 🧪 Testing

### Test Suite Overview

```
206 tests, 468 assertions

Tests/
├── Feature/               # API endpoint tests (18 files)
│   ├── TripControllerTest.php
│   ├── ChatSessionControllerTest.php
│   └── ...
├── Unit/                  # Service unit tests (8 files)
│   ├── NaverMapsServiceTest.php
│   ├── NaverPapagoServiceTest.php
│   └── ...
└── Integration/           # NAVER API integration tests
    └── NaverIntegrationTest.php
```

### Running Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test --filter=ChatSessionControllerTest

# Specific test method
php artisan test --filter=user_cannot_create_chat_session_for_other_users_trip

# With coverage
php artisan test --coverage

# Parallel execution
php artisan test --parallel
```

### Key Test Scenarios

**Trip Authorization:**
```php
/** @test */
public function user_cannot_create_chat_session_for_other_users_trip()
{
    $otherUserTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
    
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/chat-sessions', [
            'session_type' => 'trip_planning',
            'trip_id' => $otherUserTrip->id,
        ]);
    
    $response->assertStatus(403);
}
```

**AI Agent Workflow:**
```php
/** @test */
public function agent_can_complete_action_with_output()
{
    $action = AgentAction::factory()->create(['status' => 'in_progress']);
    
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/actions/{$action->id}/complete", [
            'output_data' => ['results_count' => 10],
        ]);
    
    $response->assertStatus(200);
    $this->assertEquals('completed', $action->fresh()->status);
}
```

**NAVER API Integration:**
```bash
# Real API tests (requires valid credentials)
php artisan test --filter=NaverIntegrationTest
```

### Testing Documentation

- [ADVANCED_FEATURES_TESTING_GUIDE.md](ADVANCED_FEATURES_TESTING_GUIDE.md)
- [NAVER_API_TESTING_GUIDE.md](NAVER_API_TESTING_GUIDE.md)

---

## 🚀 Deployment

### Production Checklist

- ✅ Set `APP_ENV=production` and `APP_DEBUG=false`
- ✅ Configure production database (MySQL/PostgreSQL)
- ✅ Set up Redis for cache and queues
- ✅ Configure Reverb with SSL/TLS
- ✅ Set up queue worker with Supervisor
- ✅ Enable NAVER API services with subscriptions
- ✅ Configure Cloudflare Tunnel for WebSockets
- ✅ Set up log rotation
- ✅ Configure backups

### Environment Configuration

```env
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tripplanner_prod
DB_USERNAME=tripplanner
DB_PASSWORD=secure_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# Reverb with SSL
REVERB_HOST=yourdomain.com
REVERB_SCHEME=https
REVERB_PORT=443
```

### Queue Worker (Supervisor)

```ini
# /etc/supervisor/conf.d/tripplanner-queue.conf
[program:tripplanner-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tripplanner/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/tripplanner/storage/logs/worker.log
```

### Reverb Server (Supervisor)

```ini
# /etc/supervisor/conf.d/tripplanner-reverb.conf
[program:tripplanner-reverb]
command=php /var/www/tripplanner/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/tripplanner/storage/logs/reverb.log
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/tripplanner/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # WebSocket proxy to Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
}
```

### Deployment Guide

See [cloudflare-reverb-setup.md](cloudflare-reverb-setup.md) for complete production deployment with:
- Cloudflare Zero Trust Tunnel
- SSL/TLS termination
- WebSocket support
- Nginx reverse proxy

---

## 📋 API Reference

### Resource Categories

#### 🛫 Trip Management
- **Trips** - CRUD operations for travel plans
- **Participants** - Invite users with roles (owner/editor/viewer)
- **Shares** - Generate public/private share links
- **Diaries** - Daily travel journals

#### 📍 Places & Maps
- **Places** - Search POIs with NAVER Local API
- **Maps** - Geocoding, reverse geocoding, directions
- **Checkpoints** - Save locations with images
- **Itinerary Items** - Schedule with time/location

#### 🌐 Translation
- **Text Translation** - Papago multi-language
- **Image Translation** - Translate text in photos
- **OCR Translation** - Extract & translate from images
- **Speech Translation** - Audio to translated text

#### 🤖 AI Agent
- **Chat Sessions** - Conversation management
- **Chat Messages** - Send/receive messages
- **Agent Actions** - Track AI operations
- **Recommendations** - Accept/reject AI suggestions
- **User Preferences** - Personalization settings
- **Webhooks** - Real-time event subscriptions

#### 📊 Analytics
- **Search Trends** - NAVER DataLab keyword analysis
- **Seasonal Insights** - Travel trend patterns
- **Demographics** - Age/gender breakdown

#### 👥 Social Features
- **Reviews** - Rate places/trips
- **Comments** - Discuss with other travelers
- **Favorites** - Bookmark places
- **Notifications** - Real-time updates

#### ✅ Trip Planning
- **Checklists** - Packing lists, todos
- **Tags** - Categorize trips/places

### Complete Endpoint List

See [Swagger UI](http://localhost:8000/api/documentation) for full API reference with:
- Request/response schemas
- Authentication requirements
- Query parameters
- Example payloads
- Error responses

---

## 🔧 Admin Panel

Filament-powered admin interface for database management.

**Access:**
```
http://localhost:8000/admin
```

**Features:**
- 📊 Dashboard with analytics
- 👥 User management
- 🗺️ Trip moderation
- 🖼️ Image review (Green-Eye flagged content)
- 📝 Content moderation
- 🔔 Notification management
- 📈 Usage statistics

**Creating Admin User:**
```bash
php artisan make:filament-user
```

---

## 📝 Development Guidelines

### Code Style

```bash
# Laravel Pint (automatic formatting)
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test
```

### TDD Workflow

Per [.github/instructions/mvp_instruction.instructions.md](.github/instructions/mvp_instruction.instructions.md):

1. **Write test FIRST** - Define expected behavior
2. **Implement minimum code** - Make test pass
3. **Refactor** - Improve code quality
4. **All tests green** - Verify no regressions

**Example:**
```php
// 1. Write test
/** @test */
public function trip_participant_can_create_chat_session()
{
    $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
    $trip->participants()->create(['user_id' => $this->user->id]);
    
    $response = $this->postJson('/api/chat-sessions', [
        'trip_id' => $trip->id,
        'session_type' => 'trip_planning',
    ]);
    
    $response->assertStatus(201);
}

// 2. Implement authorization
public function authorize(): bool
{
    if ($this->has('trip_id')) {
        $trip = Trip::find($this->input('trip_id'));
        return $trip->user_id === $this->user()->id
            || $trip->participants()->where('user_id', $this->user()->id)->exists();
    }
    return true;
}

// 3. Test passes ✅
```

### Adding New Endpoints

1. Create route in `routes/api.php`
2. Generate controller: `php artisan make:controller NewController`
3. Add Swagger annotations (`@OA\Get`, `@OA\Post`, etc.)
4. Create FormRequest: `php artisan make:request StoreNewRequest`
5. Add policy: `php artisan make:policy NewPolicy`
6. Write tests: `php artisan make:test NewControllerTest`
7. Regenerate docs: `php artisan l5-swagger:generate`

---

## 🐛 Troubleshooting

### Common Issues

**1. Swagger shows HTML instead of JSON**
- ✅ Add `Content-Type: application/json` header
- ✅ Include `@OA\RequestBody` in controller annotations
- ✅ Regenerate docs: `php artisan l5-swagger:generate`

**2. NAVER API errors**
- ❌ "Permission Denied" → Check API subscription in console
- ❌ "API does not exist" → Verify Client ID/Secret for correct service
- ❌ Rate limit → Implement exponential backoff

**3. WebSocket connection fails**
- ✅ Verify Reverb is running: `php artisan reverb:start`
- ✅ Check `REVERB_HOST` and `REVERB_PORT` in `.env`
- ✅ Ensure port 8080 is accessible

**4. Queue jobs not processing**
- ✅ Start worker: `php artisan queue:work`
- ✅ Check `QUEUE_CONNECTION` in `.env`
- ✅ Verify database connection

**5. Tests failing**
- ✅ Clear config: `php artisan config:clear`
- ✅ Migrate fresh: `php artisan migrate:fresh`
- ✅ Check `.env.testing` file

---

## 📚 Additional Resources

### Official Documentation
- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)
- [Laravel Reverb](https://reverb.laravel.com)
- [Filament](https://filamentphp.com/docs)
- [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger)

### NAVER API Docs
- [NAVER Developers](https://developers.naver.com/docs)
- [NAVER Cloud Platform](https://guide.ncloud-docs.com)
- [Papago Translation](https://developers.naver.com/docs/papago)
- [Maps API](https://guide.ncloud-docs.com/docs/maps)
- [Clova OCR](https://guide.ncloud-docs.com/docs/clovaocr)

### Project Documentation
- [AI_AGENT_QUICK_REFERENCE.md](AI_AGENT_QUICK_REFERENCE.md) - AI integration guide
- [NAVER_INTEGRATION_COMPLETE.md](NAVER_INTEGRATION_COMPLETE.md) - NAVER setup summary
- [ADVANCED_FEATURES_SUMMARY.md](ADVANCED_FEATURES_SUMMARY.md) - WebSocket & queue features
- [cloudflare-reverb-setup.md](cloudflare-reverb-setup.md) - Production deployment

---

## 🤝 Contributing

### Test-Driven Development (TDD)

**All contributions MUST follow TDD workflow:**

1. Write failing test first
2. Implement minimum code to pass
3. Refactor while keeping tests green
4. Document changes

**Pull Request Checklist:**
- ✅ All tests passing (`php artisan test`)
- ✅ Code formatted (`./vendor/bin/pint`)
- ✅ Swagger annotations updated
- ✅ Documentation updated (if public API changes)
- ✅ No security vulnerabilities

---

## 📄 License

MIT License

---

## 🙏 Credits

**Built with:**
- [Laravel](https://laravel.com) - PHP Framework
- [NAVER Cloud Platform](https://www.ncloud.com) - AI & Maps APIs
- [Filament](https://filamentphp.com) - Admin Panel
- [Laravel Reverb](https://reverb.laravel.com) - WebSocket Server

---

## 📞 Support

**Documentation Issues:**
- Open GitHub issue with `documentation` label

**API Questions:**
- Check [Swagger UI](http://localhost:8000/api/documentation) first
- Review [AI_AGENT_QUICK_REFERENCE.md](AI_AGENT_QUICK_REFERENCE.md)

**NAVER API Setup:**
- See [NAVER_API_ACTIVATION_GUIDE.md](NAVER_API_ACTIVATION_GUIDE.md)
- Check [NAVER_API_INTEGRATION_REPORT.md](NAVER_API_INTEGRATION_REPORT.md)

---

**Last Updated:** November 17, 2025  
**Version:** 1.0.0  
**API Version:** 1.0.0
