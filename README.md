# App Store Reviews Parser

A standalone PHP library for fetching and parsing App Store customer reviews from the iTunes RSS feed.

[![Latest Stable Version](https://img.shields.io/packagist/v/appstore-reviews-parser/parser)](https://packagist.org/packages/appstore-reviews-parser/parser)
![GitHub License](https://img.shields.io/github/license/fitz0z/appstore_reviews_parser)

## Features

- Fetch reviews from any App Store (country-specific)
- Support for pagination (up to 10 pages)
- Sort by most recent or most helpful reviews
- Minimal dependencies - only requires PHP 8.0+
- Comprehensive error handling
- Rate limiting detection
- Returns normalized JSON data structure
- Create parser from JSON configuration

## Requirements

- PHP 8.0 or higher
- SimpleXML extension (usually included with PHP)

## Installation

Install via Composer:

```bash
composer require appstore-reviews-parser/parser
```

Or add to your composer.json:

```json
{
    "require": {
        "appstore-reviews-parser/parser": "^1.0"
    }
}
```

## Usage

### Using JSON Configuration

You can create a parser instance by passing a JSON string with configuration:

```php
use AppStoreReviewsParser\AppStoreReviewsParser;

$jsonConfig = '{
    "app_id": "123456789",
    "country": "us",
    "page": 1,
    "sort_by": "mostrecent",
    "timeout": 30
}';

try {
    $parser = AppStoreReviewsParser::createFromJson($jsonConfig);
    $result = $parser->fetch();
    
    if ($result['success']) {
        print_r($result['data']);
    } else {
        echo "Error: " . $result['error'];
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
```
See `examples/examples.php` for more usage examples.

### Available Configuration Options

- `app_id`: Apple App Store ID (required unless using app_url with parse_from_url)
- `app_url`: App Store URL to parse App ID from
- `parse_from_url`: Whether to parse App ID from app_url (default: false). When true, app_id can be omitted
- `country`: Two-letter country code (default: "us")
- `page`: Page number 1-10 (default: 1)
- `sort_by`: "mostrecent" or "mosthelpful" (default: "mostrecent")
- `format`: "json" or "xml" (default: "json")
- `timeout`: Request timeout in seconds (default: 30)
- `user_agent`: Custom user agent string

### Response Format

The parser returns a JSON object with the following structure:

```json
{
  "success": true,
  "data": [
    {
      "id": "1234567890",
      "title": "Great app!",
      "content": "I really love using this app. It helps me a lot.",
      "rating": 5,
      "author": "John Doe",
      "version": "1.2.3",
      "vote_count": 42,
      "vote_sum": 168,
      "country": "us",
      "date": "2024-01-15T10:30:00+00:00"
    }
  ],
  "meta": {
    "app_id": "123456789",
    "country": "us",
    "page": 1,
    "total_pages": 10,
    "total_reviews": 50,
    "sort_by": "mostrecent"
  },
  "error": null
}
```

#### Response Fields

- `success` (boolean): Indicates whether the request was successful
- `data` (array): Array of review objects, each containing:
  - `id` (string): Unique review identifier
  - `title` (string): Review title
  - `content` (string): Review text content
  - `rating` (integer): Star rating (1-5)
  - `author` (string): Name of the reviewer
  - `version` (string): App version the review was written for
  - `vote_count` (integer): Number of votes the review received
  - `vote_sum` (integer): Sum of all vote scores
  - `country` (string): Country code of the App Store
  - `date` (string): ISO 8601 formatted date of the review
- `meta` (object): Metadata about the request (only present on success):
  - `app_id` (string): App Store ID
  - `country` (string): Country code used
  - `page` (integer): Current page number
  - `total_pages` (integer): Total available pages (max 10)
  - `total_reviews` (integer): Number of reviews returned
  - `sort_by` (string): Sort order used
- `error` (string|null): Error message if the request failed, null otherwise