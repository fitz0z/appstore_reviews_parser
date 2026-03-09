<?php

/**
 * Example: Using AppStoreReviewsParser with JSON configuration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AppStoreReviewsParser\AppStoreReviewsParser;

// Example 1: Simple JSON configuration via app id
$jsonConfig1 = '{
    "app_id": "1476033780",
    "country": "us",
    "page": 1,
    "sort_by": "mostrecent"
}';

try {
    $parser1 = AppStoreReviewsParser::createFromJson($jsonConfig1);
    $result1 = $parser1->fetch();

    if ($result1['success']) {
        echo "\nFetched " . count($result1['data']) . " reviews\n";
        print_r($result1['data'][0] ?? 'No reviews found');
    } else {
        echo "Error: " . $result1['error'] . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
echo "End example 1\n";


// Example 2: Simple JSON configuration via app url
$jsonConfig2 = '{
    "app_url": "https://apps.apple.com/us/app/youtube/id544007664",
    "parse_from_url": true,
    "country": "us",
    "page": 1,
    "sort_by": "mostrecent"
}';

try {
    $parser2 = AppStoreReviewsParser::createFromJson($jsonConfig2);
    $result2 = $parser2->fetch();

    if ($result2['success']) {
        echo "\nFetched " . count($result2['data']) . " reviews\n";
        print_r($result2['data'][0] ?? 'No reviews found');
    } else {
        echo "Error: " . $result2['error'] . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
echo "End example 2\n";


// Example 3: Complete JSON configuration
$jsonConfig3 = '{
    "app_id": "1476033780",
    "country": "gb",
    "page": 2,
    "sort_by": "mosthelpful",
    "format": "json",
    "timeout": 45,
    "user_agent": "My Custom User Agent 1.0"
}';

try {
    $parser3 = AppStoreReviewsParser::createFromJson($jsonConfig3);
    $result3 = $parser3->fetchAllPages();

    if ($result3['success']) {
        echo "\nFetched " . $result3['meta']['total_reviews'] . " reviews from " . 
             $result3['meta']['total_pages'] . " pages\n";
    } else {
        echo "Error: " . $result3['error'] . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
echo "End example 3\n";


