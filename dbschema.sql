USE 'awesome';

CREATE TABLE campaign_urls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_url TEXT,
    short_code VARCHAR(8) UNIQUE
);

CREATE TABLE clicks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    url_id INT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
