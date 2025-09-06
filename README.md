# XiMoPet - Livestock Management System

## Overview

XiMoPet is a comprehensive livestock management system designed specifically for poultry farming operations. It provides tools for managing farms, livestock, feed, supplies, and financial records.

## Features

- Farm and coop management
- Livestock tracking and management
- Feed and supply inventory management
- Financial reporting and analytics
- Worker management
- Expedition tracking
- AI-powered chat assistant with rating system

## New Features

### AI Chat Rating System

We've implemented a 1-5 star rating system for AI chat responses to help improve the quality of our AI assistant. Users can now rate AI responses and provide feedback, which will be used to tune prompts and improve future responses.

Key features:
- 5-star rating system for AI responses
- Optional feedback form for detailed comments
- Admin dashboard with rating statistics
- Metadata collection for analytics
- API endpoints for integration

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database settings
4. Run `php artisan key:generate`
5. Run `php artisan migrate`
6. Run `php artisan db:seed`

## Usage

### AI Chat

The AI chat assistant can be accessed through the chat widget in the application. Users can ask questions about livestock management, feed, supplies, and financial data.

### Rating System

After receiving an AI response, users will see a rating prompt. They can:
1. Click on stars (1-5) to rate the response
2. For ratings of 1-2 or 5 stars, provide detailed feedback
3. For ratings of 3-4 stars, submit immediately

### Admin Dashboard

Administrators can view chat rating statistics in the admin dashboard, including:
- Average ratings
- Rating distribution
- Recent feedback
- Filter by time period

## API Endpoints

### Chat Rating

- `POST /api/chat/ratings` - Rate a chat message
- `GET /api/chat/ratings/stats` - Get rating statistics (admin only)

## Development

### Running Tests

```bash
php artisan test
```

### Code Structure

- `app/Models/` - Eloquent models
- `app/Services/` - Business logic services
- `app/Http/Controllers/` - API controllers
- `app/Livewire/` - Livewire components
- `resources/views/` - Blade templates
- `database/migrations/` - Database migrations
- `tests/` - Unit and feature tests

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is proprietary and confidential. All rights reserved.
