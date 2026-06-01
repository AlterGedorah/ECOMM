-- Farm marketplace upgrade
-- Run this after backing up the database.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'user',
    ADD COLUMN IF NOT EXISTS shop_name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS shop_slug VARCHAR(180) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS farm_location VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS shop_bio TEXT DEFAULT NULL;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS seller_id INT DEFAULT 0;

CREATE INDEX IF NOT EXISTS idx_users_shop_slug ON users (shop_slug);
CREATE INDEX IF NOT EXISTS idx_products_seller_id ON products (seller_id);

-- Optional demo seller account
-- Password hash below is for "farm1234"
INSERT INTO users (name, email, password, role, shop_name, shop_slug, farm_location, shop_bio)
VALUES (
    'Mika Reyes',
    'mika@greenvalley.test',
    '$2y$10$wqV1lCz4T6xgJ5QmQJQpFOT5Q1m6iQ1fQWzW4n9wQy2m5t8oB0jta',
    'seller',
    'Green Valley Farm',
    'green-valley-farm',
    'Laguna, Philippines',
    'Seasonal vegetables, fruit, and pantry crops grown with care.'
);
