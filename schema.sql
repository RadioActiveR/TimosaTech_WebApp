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