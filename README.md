# Talos Pioneers Backend

Talos Pioneers is the backend API for a blueprint sharing platform for **Arknights Endfield**. The platform allows players to create, share, like, and comment on game blueprints (base designs/builds) using shareable codes that can be pasted directly into the game. Users can organize blueprints into collections, manage game-related facilities and items, and interact with the community through comments and likes.

## Table of Contents

- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation & Setup](#installation--setup)
- [Environment Variables](#environment-variables)
- [Database](#database)
- [API Endpoints](#api-endpoints)
- [Authentication](#authentication)
- [Features & Models](#features--models)
- [Development](#development)
- [Testing](#testing)
- [Permissions & Roles](#permissions--roles)
- [Additional Information](#additional-information)

## Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: SQLite
- **Authentication**: Laravel Sanctum (Cookied Based)
- **OAuth**: Laravel Socialite (Discord, Google)
- **Testing**: Pest PHP 4
- **Code Style**: Laravel Pint
- **Frontend**: Vite, Tailwind CSS 4
- **Content Moderation**: OpenAI API
- **Media Management**: Spatie Media Library
- **Permissions**: Spatie Laravel Permission
- **Other Packages**:
  - Spatie Tags
  - Spatie Translatable
  - Spatie Query Builder
  - Spatie Sluggable
  - BeyondCode Comments
  - Laravel Magic Link

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** 8.2 or higher
- **Composer** (PHP dependency manager)
- **Node.js** and **npm** (for frontend assets)
- **SQLite** (for development) or another supported database (MySQL, PostgreSQL)
- **OpenAI API Key** (optional, for content moderation)

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd backend
```

### 2. Install Dependencies

Install PHP dependencies:

```bash
composer install
```

Install Node.js dependencies:

```bash
npm install
```

### 3. Environment Configuration

Copy the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure Environment Variables

Edit the `.env` file with your configuration. See [Environment Variables](#environment-variables) section for details.

### 5. Database Setup

Create the database file (if using SQLite):

```bash
touch database/database.sqlite
```

Run migrations:

```bash
php artisan migrate
```

Seed the database with initial data:

```bash
php artisan db:seed
```

This will seed:
- Roles and permissions (Admin, Moderator, User)
- Initial tags

### 6. Build Frontend Assets

For development:

```bash
npm run dev
```

For production:

```bash
npm run build
```

### 7. Quick Setup (Alternative)

You can use the provided setup script that handles most of the above:

```bash
composer run setup
```

This script will:
- Install Composer dependencies
- Copy `.env.example` to `.env` if it doesn't exist
- Generate application key
- Run migrations
- Install npm dependencies
- Build frontend assets

## Environment Variables

### Required Variables

```env
APP_NAME="Talos Pioneers"
APP_ENV=local
APP_KEY=                    # Generated via `php artisan key:generate`
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite  # For SQLite
```

### Optional Variables

#### Sanctum Configuration

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

#### OAuth Providers

```env
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=http://localhost:8000/auth/discord/callback
DISCORD_AVATAR_GIF=false
DISCORD_EXTENSION_DEFAULT=webp
```

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

#### OpenAI (Content Moderation)

```env
OPENAI_API_KEY=
AUTO_MOD_ENABLED=true
```

#### Frontend URL

```env
FRONTEND_URL=http://localhost:3000
```

## Database

### Migrations

Run migrations:

```bash
php artisan migrate
```

### Seeders

The application includes the following seeders:

- **RolePermissionSeeder**: Creates roles (Admin, Moderator, User) and permissions
- **TagSeeder**: Seeds initial blueprint tags

Run all seeders:

```bash
php artisan db:seed
```

Run a specific seeder:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Database Structure

Key tables:
- `users` - User accounts
- `blueprints` - Game blueprints (base designs)
- `blueprint_collections` - Collections of blueprints
- `blueprint_likes` - User likes on blueprints
- `blueprint_copies` - Blueprint copy tracking
- `blueprint_facilities` - Blueprint-facility relationships
- `blueprint_item_inputs` - Blueprint input items
- `blueprint_item_outputs` - Blueprint output items
- `comments` - Comments on blueprints
- `facilities` - Game facilities
- `items` - Game items
- `tags` - Tags for categorization
- `model_has_tags` - Tag relationships
- `roles` - User roles
- `permissions` - Permissions
- `model_has_permissions` - Permission assignments

## API Endpoints

All API endpoints are versioned under `/api/v1/`. Authentication is handled via Laravel Sanctum tokens.

### Authentication (Web Routes)

These routes handle user authentication:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | User login (email/password) |
| POST | `/register` | User registration |
| GET | `/auth/{provider}/redirect` | OAuth provider redirect (e.g., Discord) |
| GET | `/auth/{provider}/callback` | OAuth provider callback |

### Public API Endpoints (v1)

These endpoints are publicly accessible:

#### Blueprints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/blueprints` | List published blueprints (with filtering, sorting, pagination) |
| GET | `/api/v1/blueprints/{id}` | Get blueprint details |

**Query Parameters for `/api/v1/blueprints`**:
- `filter[region]` - Filter by region (valley_iv, wuling)
- `filter[version]` - Filter by game version (cbt_3)
- `filter[is_anonymous]` - Filter anonymous blueprints (true/false)
- `filter[author_id]` - Filter by author ID
- `filter[facility]` - Filter by facility slugs (comma-separated)
- `filter[item_input]` - Filter by input item slugs (comma-separated)
- `filter[item_output]` - Filter by output item slugs (comma-separated)
- `filter[tags.id]` - Filter by tag IDs (comma-separated)
- `sort` - Sort by: `created_at`, `updated_at`, `title`, `likes_count`, `copies_count`
- `page` - Page number for pagination

#### Collections

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/collections` | List collections |
| GET | `/api/v1/collections/{id}` | Get collection details |

#### Facilities

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/facilities` | List facilities |
| GET | `/api/v1/facilities/{slug}` | Get facility details by slug |

#### Items

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/items` | List items |
| GET | `/api/v1/items/{slug}` | Get item details by slug |

#### Tags

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/tags` | List tags |

#### Comments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/blueprints/{blueprint}/comments` | List comments for a blueprint |
| GET | `/api/v1/blueprints/{blueprint}/comments/{comment}` | Get a specific comment |

### Authenticated API Endpoints (v1)

These endpoints require authentication via Sanctum token. Include the token in the `Authorization` header:

```
Authorization: Bearer {token}
```

#### Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/profile` | Get authenticated user's profile |
| PUT | `/api/v1/profile` | Update authenticated user's profile |

#### Blueprints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/blueprints` | Create a new blueprint |
| PUT | `/api/v1/blueprints/{id}` | Update a blueprint (own or with permission) |
| DELETE | `/api/v1/blueprints/{id}` | Delete a blueprint (own or with permission) |
| POST | `/api/v1/blueprints/{blueprint}/like` | Like/unlike a blueprint |
| POST | `/api/v1/blueprints/{blueprint}/copy` | Copy a blueprint (track usage) |

#### My Blueprints & Collections

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/my/blueprints` | Get authenticated user's blueprints |
| GET | `/api/v1/my/collections` | Get authenticated user's collections |

#### Collections

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/collections` | Create a new collection |
| PUT | `/api/v1/collections/{id}` | Update a collection (own or with permission) |
| DELETE | `/api/v1/collections/{id}` | Delete a collection (own or with permission) |

#### Comments

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/blueprints/{blueprint}/comments` | Create a comment on a blueprint |
| PUT | `/api/v1/blueprints/{blueprint}/comments/{comment}` | Update a comment (own or with permission) |
| DELETE | `/api/v1/blueprints/{blueprint}/comments/{comment}` | Delete a comment (own or with permission) |

#### Tags

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/tags` | Create a tag (requires permission) |
| PUT | `/api/v1/tags/{id}` | Update a tag (requires permission) |
| DELETE | `/api/v1/tags/{id}` | Delete a tag (requires permission) |

#### Users (Admin/Moderator)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/users/{user}/upgrade-to-moderator` | Upgrade a user to moderator role (requires permission) |

### Utility Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user` | Get authenticated user (Sanctum) |
| GET | `/up` | Health check endpoint |

## Authentication

### Sanctum Token Authentication

The API uses Laravel Sanctum for token-based authentication. After logging in via `/login` or OAuth, you'll receive a token that should be included in subsequent requests:

```http
Authorization: Bearer {your-token-here}
```

### OAuth Providers

The application supports OAuth authentication via Discord and Google, the process is the same :

1. Register your application with Discord and Google
2. Configure `.env` variables based on .env.example
3. Set the redirect URI to: `http://your-domain/auth/{provider}/callback`
4. Users can authenticate via: `GET /auth/{provider}/redirect`

### Magic Link Authentication

The application includes support for magic link authentication via `cesargb/laravel-magiclink`. Users can request a magic link to log in without a password.

## Features & Models

### Blueprints

Blueprints are the core entity representing game base designs/builds. Each blueprint includes:

- **Code**: Shareable code that can be pasted into Arknights Endfield
- **Title**: Blueprint title
- **Description**: Optional description
- **Status**: `draft`, `published`, or `archived`
- **Version**: Game version (currently `cbt_3`)
- **Region**: Game region (`valley_iv` or `wuling`)
- **Facilities**: Associated facilities with quantities
- **Item Inputs**: Required input items with quantities
- **Item Outputs**: Produced output items with quantities
- **Tags**: Categorization tags
- **Gallery**: Media images
- **Is Anonymous**: Option to post anonymously
- **Creator**: User who created the blueprint
- **Likes**: Users who liked the blueprint
- **Copies**: Copy tracking for usage statistics

### Blueprint Collections

Collections allow users to organize multiple blueprints into groups. Collections can be:
- Public or private
- Shared with the community
- Managed by the creator or moderators/admins

### Comments

Users can comment on blueprints. Comments support:
- Auto-moderation (via OpenAI)
- Auto-approval for trusted users
- Editing and deletion by comment author or moderators

### Tags

Tags are used to categorize blueprints. Tags can be:
- Created by users with `manage_tags` permission
- Applied to blueprints
- Filtered in blueprint listings

### Facilities and Items

Game data from Arknights Endfield:
- **Facilities**: Production facilities in the game
- **Items**: Materials, currency, and other game items
- Both support translations for multi-language support
- Used in blueprints to define inputs/outputs

### User Roles and Permissions

The application uses a role-based permission system:

#### Roles

- **Admin**: Full access to all features
- **Moderator**: Can manage all blueprints, collections, and comments
- **User**: Standard user with basic permissions

#### Permissions

- `manage_tags` - Create, update, delete tags
- `upgrade_users` - Upgrade users to moderator
- `manage_all_blueprints` - Manage any blueprint (not just own)
- `manage_all_collections` - Manage any collection (not just own)
- `manage_comments` - Manage any comment (not just own)

### Content Moderation

The application includes OpenAI-powered content moderation for:
- Blueprint titles and descriptions
- Blueprint gallery images
- Comments

Moderation can be enabled/disabled via `AUTO_MOD_ENABLED` environment variable.

## Development

### Running the Development Server

Use the provided development script that runs multiple services concurrently:

```bash
composer run dev
```

This runs:
- Laravel development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen`)
- Log viewer (`php artisan pail`)
- Vite dev server (`npm run dev`)

### Running Individual Services

**Laravel Server:**
```bash
php artisan serve
```

**Queue Worker:**
```bash
php artisan queue:work
```

**Vite Dev Server:**
```bash
npm run dev
```

### Code Formatting

Format code using Laravel Pint:

```bash
vendor/bin/pint
```

Format only changed files:

```bash
vendor/bin/pint --dirty
```

### Queue Processing

The application uses Laravel queues for background jobs. Run the queue worker:

```bash
php artisan queue:work
```

Or use the queue listener:

```bash
php artisan queue:listen
```

### Logging

View logs in real-time:

```bash
php artisan pail
```

Logs are stored in `storage/logs/laravel.log`.

### Database Commands

**Create a migration:**
```bash
php artisan make:migration create_example_table
```

**Create a model with migration:**
```bash
php artisan make:model Example -m
```

**Create a seeder:**
```bash
php artisan make:seeder ExampleSeeder
```

## Testing

The application uses **Pest PHP 4** for testing.

### Running Tests

Run all tests:

```bash
php artisan test
```

Run a specific test file:

```bash
php artisan test tests/Feature/BlueprintControllerTest.php
```

Run tests matching a filter:

```bash
php artisan test --filter=BlueprintController
```

### Test Structure

- **Feature Tests**: Located in `tests/Feature/`
  - `Auth/` - Authentication tests
  - `BlueprintControllerTest.php` - Blueprint API tests
  - `BlueprintCollectionControllerTest.php` - Collection API tests
  - `CommentControllerTest.php` - Comment API tests
  - `FacilityControllerTest.php` - Facility API tests
  - `ItemControllerTest.php` - Item API tests
  - `MyBlueprintsControllerTest.php` - User blueprints tests
  - `MyCollectionsControllerTest.php` - User collections tests
  - `ProfileControllerTest.php` - Profile API tests
  - `TagControllerTest.php` - Tag API tests
  - `UserControllerTest.php` - User management tests

- **Unit Tests**: Located in `tests/Unit/`

### Test Database

Tests use an in-memory SQLite database configured in `phpunit.xml`. The database is automatically migrated and seeded before each test run.

### Key Directories

- **Models** (`app/Models/`): Eloquent models representing database entities
- **Controllers** (`app/Http/Controllers/V1/`): API controllers organized by version
- **Requests** (`app/Http/Requests/`): Form request classes for validation
- **Resources** (`app/Http/Resources/`): API resource classes for data transformation
- **Policies** (`app/Policies/`): Authorization policies
- **Services** (`app/Services/`): Business logic services (e.g., AutoMod)
- **Enums** (`app/Enums/`): Type-safe enumerations

## Permissions & Roles

### Role Hierarchy

1. **Admin**
   - All permissions
   - Can manage tags
   - Can upgrade users to moderator
   - Can manage all blueprints and collections
   - Can manage all comments

2. **Moderator**
   - Can manage all blueprints and collections
   - Can manage all comments
   - Cannot manage tags or upgrade users

3. **User**
   - Can create and manage own blueprints
   - Can create and manage own collections
   - Can comment on blueprints
   - Can like and copy blueprints

### Permission System

Permissions are managed via Spatie Laravel Permission:

- Permissions are defined in `App\Enums\Permission`
- Roles and permissions are seeded via `RolePermissionSeeder`
- Policies enforce permissions at the controller level

## Additional Information

### Content Moderation

Content moderation is powered by OpenAI's moderation API:

- Validates blueprint titles, descriptions, and images
- Validates comments
- Can be enabled/disabled via `AUTO_MOD_ENABLED`
- Requires `OPENAI_API_KEY` when enabled

### Media Library

The application uses Spatie Media Library for managing blueprint gallery images:

- Images are stored in `storage/app/public`
- Automatic image conversions (thumbnails, optimized versions)
- WebP format for optimized images
- Gallery images are associated with blueprints

### Translation Support

Game data (facilities and items) supports translations:

- Uses Spatie Translatable
- Supports multiple locales
- Translation files in `lang/` directory

### API Versioning

The API is versioned under `/api/v1/`. Future versions can be added by creating new route files in `routes/api/`.

### Current Game-Specific Information

- **Regions**: 
  - Valley IV
  - Wuling
- **Game Version**: Currently supports CBT_3 (Closed Beta Test 3)

### Blueprint Code Format

Blueprints contain a `code` field that stores the shareable code. This code can be copied from the game and pasted into the platform, or copied from the platform and pasted into the game.

## Contributing

This is a multi-developer project. When contributing:

1. Follow Laravel conventions
2. Write tests for new features
3. Run `vendor/bin/pint` before committing
4. Ensure all tests pass: `php artisan test`
5. Follow the existing code structure and patterns

## License

TBD
