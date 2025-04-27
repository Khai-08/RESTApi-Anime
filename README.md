# RESTApi-Anime | DB Anime Backend

![CodeIgniter Version](https://img.shields.io/badge/CodeIgniter-4.x-red)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange)

## Table of Contents
- [Project Description](#project-description)
- [API Endpoints](#api-endpoints)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

## Project Description
Backend system for anime database application built with CodeIgniter 4. Provides user authentication and anime list management through a RESTful API.

## API Endpoints
### Authentication
| Endpoint                    | Method | Description                          |
|-----------------------------|--------|--------------------------------------|
| `/api/auth/forgot-password` | POST   | Request password reset               |
| `/api/auth/reset-password`  | POST   | Complete password reset              |
| `/api/auth/resend-email`    | POST   | Resend verification email            |
| `/api/auth/register`        | POST   | Register new user                    |
| `/api/auth/verify`          | GET    | Verify email address                 |
| `/api/auth/login`           | POST   | User login with JWT                  |
| `/api/auth/logout`          | POST   | Invalidate JWT token                 |

### User Anime Lists
| Endpoint          | Method | Description                          |
|-------------------|--------|--------------------------------------|
| `/api/addAnime`          | POST   | Add new anime to list                |


## Installation

```bash
# Clone repository
git clone https://github.com/Khai-08/RESTApi-Anime.git

# Install dependencies
composer install

# Set up environment (Linux/Mac)
cp env .env

# Run migrations
php spark migrate