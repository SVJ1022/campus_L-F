-- ============================================================
-- FINDLY — Campus Lost & Found Management System
-- Database Schema (MySQL 8+)
-- Source of truth: import this file on a fresh MySQL instance.
-- ============================================================

CREATE DATABASE IF NOT EXISTS findly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE findly;

CREATE TABLE user (
  userId INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('STUDENT','STAFF','ADMIN') NOT NULL DEFAULT 'STUDENT',
  contactNo VARCHAR(15),
  enrollmentNo VARCHAR(30) NULL,
  accountStatus ENUM('ACTIVE','INACTIVE','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE category (
  categoryId INT AUTO_INCREMENT PRIMARY KEY,
  categoryName VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE item (
  itemId INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  itemType ENUM('LOST','FOUND') NOT NULL,
  categoryId INT NOT NULL,
  location VARCHAR(200) NOT NULL,
  itemDate DATE NOT NULL,
  imageUrl VARCHAR(255) NULL,
  custodyLocation VARCHAR(200) NULL,
  custodyStatus ENUM('IN_CUSTODY','MOVED','HANDED_OVER') NULL,
  status ENUM('PENDING','ACTIVE','CLAIMED','RESOLVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  postedBy INT NOT NULL,
  loggedByStaff INT NULL,
  recipientName VARCHAR(100) NULL,
  recipientContactNo VARCHAR(15) NULL,
  recipientEnrollmentNo VARCHAR(30) NULL,
  claimedAt DATETIME NULL,
  resolvedAt DATETIME NULL,
  createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updatedAt DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoryId) REFERENCES category(categoryId),
  FOREIGN KEY (postedBy) REFERENCES user(userId),
  FOREIGN KEY (loggedByStaff) REFERENCES user(userId),
  INDEX idx_item_status (status),
  INDEX idx_item_type (itemType),
  INDEX idx_item_category (categoryId),
  INDEX idx_item_date (itemDate)
);

CREATE TABLE notification (
  notificationId INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  itemId INT NULL,
  message VARCHAR(255) NOT NULL,
  isRead BOOLEAN NOT NULL DEFAULT FALSE,
  createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userId) REFERENCES user(userId),
  FOREIGN KEY (itemId) REFERENCES item(itemId)
);

CREATE TABLE audit_log (
  auditId INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  itemId INT NULL,
  action VARCHAR(100) NOT NULL,
  oldStatus VARCHAR(30) NULL,
  newStatus VARCHAR(30) NULL,
  details TEXT NULL,
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userId) REFERENCES user(userId),
  FOREIGN KEY (itemId) REFERENCES item(itemId)
);
