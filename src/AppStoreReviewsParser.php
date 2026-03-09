<?php

/**
 * AppStoreReviewsParser - A standalone PHP library for parsing App Store reviews
 *
 * @author fitz0z
 * @version 1.0.0
 */

namespace AppStoreReviewsParser;

use SimpleXMLElement;
use Exception;
use DateTime;

class AppStoreReviewsParser
{
    /**
     * Base URL for iTunes RSS feed
     */
    private const BASE_URL = 'https://itunes.apple.com/%s/rss/customerreviews/page=%d/id=%s/sortby=%s/xml';

    /**
     * Maximum pages allowed by iTunes API
     */
    private const MAX_PAGES = 10;

    /**
     * Maximum reviews per page
     */
    private const MAX_REVIEWS_PER_PAGE = 50;

    /**
     * Default configuration values
     */
    private const DEFAULT_COUNTRY = 'us';
    private const DEFAULT_SORT = 'mostrecent';
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    /**
     * Configuration options
     */
    private array $config = [
        'country' => self::DEFAULT_COUNTRY,
        'page' => 1,
        'sort_by' => self::DEFAULT_SORT,
        'timeout' => self::DEFAULT_TIMEOUT,
        'user_agent' => self::DEFAULT_USER_AGENT,
    ];

    /**
     * App ID to fetch reviews for
     */
    private ?string $appId = null;

    /**
     * App URL to parse App ID from
     */
    private ?string $appUrl = null;

    /**
     * Whether to parse App ID from URL
     */
    private bool $parseFromUrl = false;

    /**
     * Last error message
     */
    private ?string $lastError = null;

    /**
     * Raw response from the API
     */
    private ?string $rawResponse = null;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Create parser instance from JSON configuration
     *
     * @param string $jsonConfig JSON string containing configuration
     * @return self Parser instance
     * @throws Exception If JSON is invalid or required fields are missing
     */
    public static function createFromJson(string $jsonConfig): self
    {
        $config = json_decode($jsonConfig, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON configuration: ' . json_last_error_msg());
        }

        if (!is_array($config)) {
            throw new Exception('Configuration must be a JSON object');
        }

        $parser = new self();

        // Apply configuration
        if (isset($config['app_id'])) {
            $parser->setAppId($config['app_id']);
        }

        if (isset($config['country'])) {
            $parser->setCountry($config['country']);
        }

        if (isset($config['page'])) {
            $parser->setPage($config['page']);
        }

        if (isset($config['sort_by'])) {
            $parser->setSortBy($config['sort_by']);
        }

        if (isset($config['timeout'])) {
            $parser->setTimeout($config['timeout']);
        }

        if (isset($config['user_agent'])) {
            $parser->setUserAgent($config['user_agent']);
        }

        if (isset($config['app_url'])) {
            $parser->setAppUrl($config['app_url']);
        }

        if (isset($config['parse_from_url'])) {
            $parser->setParseFromUrl($config['parse_from_url']);
        }

        return $parser;
    }

    /**
     * Set country code
     *
     * @param string $country Two-letter country code
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->config['country'] = strtolower($country);
        return $this;
    }

    /**
     * Set App ID
     *
     * @param int|string $appId Apple App Store ID
     * @return self
     */
    public function setAppId(int|string $appId): self
    {
        $this->appId = (string) $appId;
        return $this;
    }

    /**
     * Set App URL to parse App ID from
     *
     * @param string $url App Store URL
     * @return self
     */
    public function setAppUrl(string $url): self
    {
        $this->appUrl = $url;
        return $this;
    }

    /**
     * Set whether to parse App ID from URL
     *
     * @param bool $parseFromUrl Whether to parse from URL
     * @return self
     */
    public function setParseFromUrl(bool $parseFromUrl): self
    {
        $this->parseFromUrl = $parseFromUrl;
        return $this;
    }

    /**
     * Parse App ID from App Store URL
     *
     * @param string $url App Store URL
     * @return string|null App ID or null if not found
     */
    private function parseAppIdFromUrl(string $url): ?string
    {
        $pattern = '/\/id(\d+)/i';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Set page number
     *
     * @param int $page Page number (1-10)
     * @return self
     */
    public function setPage(int $page): self
    {
        $this->config['page'] = max(1, min($page, self::MAX_PAGES));
        return $this;
    }

    /**
     * Set sorting option
     *
     * @param string $sort Sort option ('mostrecent' or 'mosthelpful')
     * @return self
     */
    public function setSortBy(string $sort): self
    {
        $validSorts = ['mostrecent', 'mosthelpful'];
        $sort = strtolower($sort);

        if (in_array($sort, $validSorts)) {
            $this->config['sort_by'] = $sort;
        } else {
            $this->lastError = "Invalid sort option. Must be 'mostrecent' or 'mosthelpful'";
        }

        return $this;
    }

    /**
     * Set request timeout
     *
     * @param int $timeout Timeout in seconds
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->config['timeout'] = max(1, $timeout);
        return $this;
    }

    /**
     * Set user agent
     *
     * @param string $userAgent User agent string
     * @return self
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->config['user_agent'] = $userAgent;
        return $this;
    }

    /**
     * Fetch reviews from the iTunes RSS feed
     *
     * @return array Parsed reviews data
     */
    public function fetch(): array
    {
        $this->lastError = null;
        $this->rawResponse = null;

        // Validate required parameters
        if (empty($this->appId)) {
            // Try to parse App ID from URL if parse_from_url is enabled
            if ($this->parseFromUrl && !empty($this->appUrl)) {
                $parsedId = $this->parseAppIdFromUrl($this->appUrl);
                if ($parsedId === null) {
                    $this->lastError = 'Could not parse App ID from URL. Please provide a valid App Store URL.';
                    return $this->formatResponse(false, [], null);
                }
                $this->appId = $parsedId;
            } else {
                $this->lastError = 'App ID is required';
                return $this->formatResponse(false, [], null);
            }
        }

        try {
            // Build URL
            $url = sprintf(
                self::BASE_URL,
                $this->config['country'],
                $this->config['page'],
                $this->appId,
                $this->config['sort_by']
            );

            // Fetch data
            $response = $this->makeRequest($url);

            if ($response === false) {
                return $this->formatResponse(false, [], $this->lastError);
            }

            $this->rawResponse = $response;

            // Parse XML response
            $xml = new SimpleXMLElement($response);

            // Extract reviews
            $reviews = $this->parseReviews($xml);

            // Build metadata
            $meta = $this->buildMeta($xml);

            return $this->formatResponse(true, $reviews, null, $meta);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return $this->formatResponse(false, [], $this->lastError);
        }
    }

    /**
     * Fetch all available pages (up to MAX_PAGES)
     *
     * @return array Combined reviews from all pages
     */
    public function fetchAllPages(): array
    {
        $allReviews = [];
        $currentPage = 1;
        $hasMore = true;
        $lastError = null;

        while ($hasMore && $currentPage <= self::MAX_PAGES) {
            $this->setPage($currentPage);
            $result = $this->fetch();

            if (!$result['success']) {
                $lastError = $result['error'];
                $hasMore = false;
                continue;
            }

            if (empty($result['data'])) {
                $hasMore = false;
                continue;
            }

            $allReviews = array_merge($allReviews, $result['data']);
            $currentPage++;
        }

        // Restore original page
        $this->setPage(1);

        // Build combined metadata
        $meta = [
            'app_id' => $this->appId,
            'country' => $this->config['country'],
            'page' => 1,
            'total_pages' => $currentPage - 1,
            'total_reviews' => count($allReviews),
            'sort_by' => $this->config['sort_by'],
        ];

        return $this->formatResponse(
            !empty($allReviews),
            $allReviews,
            $lastError,
            $meta
        );
    }

    /**
     * Get raw response from the API
     *
     * @return string|null Raw XML response
     */
    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }

    /**
     * Get last error message
     *
     * @return string|null Last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get the URL that will be used for fetching reviews
     *
     * @return string|null URL or null if App ID is not set
     */
    public function getRequestUrl(): ?string
    {
        if (empty($this->appId)) {
            return null;
        }

        return sprintf(
            self::BASE_URL,
            $this->config['country'],
            $this->config['page'],
            $this->appId,
            $this->config['sort_by']
        );
    }

    /**
     * Make HTTP request to iTunes RSS feed
     *
     * @param string $url URL to fetch
     * @return string|false Response body or false on failure
     */
    private function makeRequest(string $url): string|false
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->config['timeout'],
                'user_agent' => $this->config['user_agent'],
                'header' => [
                    'Accept: application/xml',
                ],
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $this->lastError = $error['message'] ?? 'Failed to fetch data from iTunes API';

            // Check for rate limiting
            if (strpos($this->lastError, '429') !== false) {
                $this->lastError = 'Rate limit exceeded. Please try again later.';
            }
        }

        return $response;
    }

    /**
     * Parse reviews from XML response
     *
     * @param SimpleXMLElement $xml XML response
     * @return array Array of parsed reviews
     */
    private function parseReviews(SimpleXMLElement $xml): array
    {
        $reviews = [];

        // Register namespace for iTunes elements
        $xml->registerXPathNamespace('im', 'http://itunes.apple.com/rss');
        $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        // Find all entry elements (reviews) - entries are now in default namespace
        $entries = $xml->xpath('//atom:entry');

        if ($entries === false) {
            return $reviews;
        }

        foreach ($entries as $entry) {
            $entry->registerXPathNamespace('im', 'http://itunes.apple.com/rss');
            $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

            // Extract rating from im:rating element
            $rating = $entry->xpath('.//im:rating');
            $ratingValue = !empty($rating) ? (int) $rating[0] : 0;

            // Extract version from im:version element
            $version = $entry->xpath('.//im:version');
            $versionValue = !empty($version) ? (string) $version[0] : '';

            // Extract vote count from im:voteCount element
            $voteCount = $entry->xpath('.//im:voteCount');
            $voteCountValue = !empty($voteCount) ? (int) $voteCount[0] : 0;

            // Extract vote sum from im:voteSum element
            $voteSum = $entry->xpath('.//im:voteSum');
            $voteSumValue = !empty($voteSum) ? (int) $voteSum[0] : 0;

            // Extract content - get the text content, not the HTML version
            $contentElements = $entry->xpath('.//atom:content[@type="text"]');
            $contentValue = !empty($contentElements) ? (string) $contentElements[0] : '';

            // If no text content found, try to get the first content element
            if (empty($contentValue)) {
                $contentElements = $entry->xpath('.//atom:content');
                $contentValue = !empty($contentElements) ? (string) $contentElements[0] : '';
            }

            // Extract title
            $titleElements = $entry->xpath('.//atom:title');
            $titleValue = !empty($titleElements) ? (string) $titleElements[0] : '';

            // Extract author name
            $authorElements = $entry->xpath('.//atom:author/atom:name');
            $authorValue = !empty($authorElements) ? (string) $authorElements[0] : '';

            // Extract ID
            $idElements = $entry->xpath('.//atom:id');
            $idValue = !empty($idElements) ? (string) $idElements[0] : '';

            // Extract updated date
            $updatedElements = $entry->xpath('.//atom:updated');
            $dateValue = !empty($updatedElements) ? (string) $updatedElements[0] : '';

            $review = [
                'id' => $idValue,
                'title' => $titleValue,
                'content' => $contentValue,
                'rating' => $ratingValue,
                'author' => $authorValue,
                'version' => $versionValue,
                'vote_count' => $voteCountValue,
                'vote_sum' => $voteSumValue,
                'country' => $this->config['country'],
            ];

            // Parse date
            if (!empty($dateValue)) {
                try {
                    $dateObj = new DateTime($dateValue);
                    $review['date'] = $dateObj->format('c');
                } catch (Exception $e) {
                    $review['date'] = $dateValue;
                }
            } else {
                $review['date'] = '';
            }

            $reviews[] = $review;
        }

        return $reviews;
    }

    /**
     * Build metadata from XML response
     *
     * @param SimpleXMLElement $xml XML response
     * @return array Metadata array
     */
    private function buildMeta(SimpleXMLElement $xml): array
    {
        $meta = [
            'app_id' => $this->appId,
            'country' => $this->config['country'],
            'page' => $this->config['page'],
            'total_pages' => self::MAX_PAGES,
            'total_reviews' => count($this->parseReviews($xml)),
            'sort_by' => $this->config['sort_by'],
        ];

        return $meta;
    }

    /**
     * Format response as JSON
     *
     * @param bool $success Whether the request was successful
     * @param array $data Reviews data
     * @param string|null $error Error message if any
     * @param array|null $meta Metadata
     * @return array Formatted JSON response
     */
    private function formatResponse(
        bool $success,
        array $data,
        ?string $error = null,
        ?array $meta = null
    ): array {
        $response = [
            'success' => $success,
            'data' => $data,
            'error' => $error,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return $response;
    }
}
