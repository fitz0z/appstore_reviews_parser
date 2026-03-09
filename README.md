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


See `examples/json_config_example.php` for more usage examples.
