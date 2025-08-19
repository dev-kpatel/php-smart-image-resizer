# PHP Smart Image Resizer

A lightweight Slim 4 based microservice for dynamic image resizing, caching, and delivery.  
This README was generated with AI assistance.

## Features
- Resize images on-the-fly using GD or Imagick
- Automatic caching of resized images
- Cache clearing endpoint with token protection
- Health check endpoint (`/healthz`)
- Extendable and structured project setup

## Requirements
- PHP 8.1+
- Composer
- GD or Imagick extension
- Slim 4 framework

## Installation
```bash
git clone <your-repo-url> php-smart-image-resizer
cd php-smart-image-resizer
composer install
cp config/app.example.env config/.env
php -S localhost:8080 -t public
```

## Usage

### Resize an Image
```http
GET /resize/{path}?w=300&h=200&fit=cover
```

### Clear Cache
```http
POST /cache/clear
Authorization: Bearer <ADMIN_TOKEN>
```

### Health Check
```http
GET /healthz
```

## Development

Run locally with:
```bash
php -S localhost:8080 -t public
```

## Tests

### Run PHPUnit Tests
```bash
composer test
```

## License
MIT
