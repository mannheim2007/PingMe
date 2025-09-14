CREATE DATABASE uzengeto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uzengeto;


CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL UNIQUE,
password_hash VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE conversations (
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(100) DEFAULT NULL, -- opcionális, pl. csoportnév
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE conversation_participants (
conversation_id INT NOT NULL,
user_id INT NOT NULL,
PRIMARY KEY (conversation_id, user_id),
FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- módosított messages tábla (konverzációhoz kötve)
CREATE TABLE messages (
id INT AUTO_INCREMENT PRIMARY KEY,
conversation_id INT NOT NULL,
user_id INT NOT NULL,
message TEXT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE friends (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  friend_id INT NOT NULL,
  status ENUM('pending','accepted','blocked') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE friend_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id)
);
