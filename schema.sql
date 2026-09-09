CREATE TABLE IF NOT EXISTS users (
  u_id          VARCHAR(50)  NOT NULL PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_profiles (
  profile_id    INT AUTO_INCREMENT PRIMARY KEY,
  u_id          VARCHAR(50)  NOT NULL UNIQUE,
  full_name     VARCHAR(100) DEFAULT NULL,
  phone_number  VARCHAR(20)  DEFAULT NULL,
  address_line1 VARCHAR(255) DEFAULT NULL,
  address_line2 VARCHAR(255) DEFAULT NULL,
  city          VARCHAR(100) DEFAULT NULL,
  province      VARCHAR(100) DEFAULT NULL,
  postal_code   VARCHAR(20)  DEFAULT NULL,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_user_profile 
    FOREIGN KEY (u_id) 
    REFERENCES users(u_id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
);

UPDATE users SET role = 'admin' WHERE email = 'corneliustimosa@gmail.com';

CREATE TABLE IF NOT EXISTS products (
  product_id   INT AUTO_INCREMENT PRIMARY KEY,
  image_id     INT DEFAULT NULL,
  name         VARCHAR(150)   NOT NULL,
  category     VARCHAR(50)    NOT NULL,
  description  TEXT           DEFAULT NULL,
  price        DECIMAL(10,2)  NOT NULL,
  stock        INT            NOT NULL DEFAULT 0,
  created_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (image_id) REFERENCES images(image_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS images (
  image_id   INT AUTO_INCREMENT PRIMARY KEY,
  image_data LONGTEXT NOT NULL,
  mime_type  VARCHAR(50) DEFAULT 'image/png',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cart_items (
  cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
  u_id         VARCHAR(50) NOT NULL,
  product_id   INT NOT NULL,
  quantity     INT NOT NULL DEFAULT 1,
  added_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uniq_user_product (u_id, product_id),
  FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
  order_id       VARCHAR(50) NOT NULL PRIMARY KEY, 
  u_id           VARCHAR(50) NOT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  phone_number   VARCHAR(20)  NOT NULL,
  address_line1  VARCHAR(255) NOT NULL,
  address_line2  VARCHAR(255) DEFAULT NULL,
  city           VARCHAR(100) NOT NULL,
  province       VARCHAR(100) NOT NULL,
  postal_code    VARCHAR(20)  NOT NULL,
  payment_method VARCHAR(50)  NOT NULL,              
  status         VARCHAR(30)  NOT NULL DEFAULT 'pending', 
  subtotal       DECIMAL(10,2) NOT NULL,
  shipping_fee   DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount   DECIMAL(10,2) NOT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id      VARCHAR(50) NOT NULL,
  product_id    INT DEFAULT NULL,
  product_name  VARCHAR(150) NOT NULL,  
  unit_price    DECIMAL(10,2) NOT NULL,  
  quantity      INT NOT NULL,
  subtotal      DECIMAL(10,2) NOT NULL,

  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_status_log (
  log_id      INT AUTO_INCREMENT PRIMARY KEY,
  order_id    VARCHAR(50) NOT NULL,
  old_status  VARCHAR(30) DEFAULT NULL,
  new_status  VARCHAR(30) NOT NULL,
  changed_by  VARCHAR(50) DEFAULT NULL,
  changed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(u_id) ON DELETE SET NULL
);

CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    u_id VARCHAR(64) NULL,
    action VARCHAR(20) NOT NULL,
    entity_type VARCHAR(30) NOT NULL,
    entity_id VARCHAR(64) NULL,
    detail TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_logs_created_at (created_at),
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE SET NULL
);