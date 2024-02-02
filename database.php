<?php

class Database
{
    private static $instance;

    public PDO $db;
    public static $env;

    public function __construct()
    {
        // Create/Get database connection instance
        $this->db = self::getInstance();
    }

    public static function getInstance(): PDO {
        if (!self::$instance) {
            self::$env = parse_ini_file('.env');
            self::$instance = new PDO("mysql:host=". self::$env['DBHOST'] . ";dbname=" . self::$env['DBNAME'], self::$env['DBUSER'], self::$env['DBPASS']);
        }
        return self::$instance;
    }

    // Function to create a unique short code
    public function generateShortCode()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM campaign_urls WHERE short_code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn()) {
            return $this->generateShortCode(); // Ensure uniqueness
        } else {
            return $code;
        }
    }

    // Function to shorten a URL
    public function shortenUrl(string $url)
    {
        $shortCode = $this->generateShortCode();
        $stmt = $this->db->prepare("INSERT INTO campaign_urls (original_url, short_code) VALUES (?, ?)");
        $stmt->execute([$url, $shortCode]);

        return $shortCode;
    }

    // Function to get the original URL for a short code
    public function getOriginalUrl(string $shortCode)
    {
        $stmt = $this->db->prepare("SELECT id, original_url FROM campaign_urls WHERE short_code = ?");
        $stmt->execute([$shortCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row : null;
    }

    // Function to log a click
    public function logClick(int $url_id)
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->db->prepare("INSERT INTO clicks (url_id, ip_address) VALUES (?, ?)");
        $stmt->execute([$url_id, $ipAddress]);
    }

    // Function to get a list of shortened URLs with click statistics
    public function getShortenedUrls(int $offset=0, int $limit=20)
    {
        $stmt = $this->db->query("
            SELECT campaign_urls.id, campaign_urls.original_url, campaign_urls.short_code, COUNT(*) AS clicks, COUNT(DISTINCT clicks.ip_address) AS unique_clicks
            FROM campaign_urls
            JOIN clicks ON campaign_urls.id = clicks.url_id
            GROUP BY campaign_urls.id
            ORDER BY campaign_urls.id DESC
            LIMIT $limit
            OFFSET $offset
        ");
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $res ? $res : null;
    }
}
