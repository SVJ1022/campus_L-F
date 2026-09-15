-- ============================================================
-- FINDLY — Campus Lost & Found Management System
-- Seed Data (Ganpat University–style sample data)
--
-- Passwords (bcrypt hashes):
--   Admin   -> Admin@123
--   Staff   -> Staff@123
--   Students-> Student@123
-- ============================================================

USE findly;

INSERT INTO category (categoryName) VALUES
('Electronics'),
('Documents/ID Cards'),
('Bags'),
('Accessories'),
('Books/Stationery'),
('Others');

-- Admin / Staff / Students
-- Admin account (role ADMIN)
INSERT INTO user (name, email, password, role, contactNo, enrollmentNo, accountStatus) VALUES
('Rohit Desai', 'admin@ganpatuniversity.ac.in', '$2y$10$0X/aZUnOnMPecTNhtGONkev9VgLeyeSppIS2mlvh44Zi3Mo8ujZNm', 'ADMIN', '9876543210', 'ADM0001', 'ACTIVE');

-- Staff account (role STAFF) — campus security desk officer
INSERT INTO user (name, email, password, role, contactNo, enrollmentNo, accountStatus) VALUES
('Priya Shah', 'staff@ganpatuniversity.ac.in', '$2y$10$scaQMga0v6aqSAb8b9PzfORrMj.s6t.cdEAMTlz3DAfGnK9mDcFze', 'STAFF', '9825012345', 'EMP1023', 'ACTIVE');

-- Two Student accounts
INSERT INTO user (name, email, password, role, contactNo, enrollmentNo, accountStatus) VALUES
('Meet Patel', 'meet.patel@ganpatuniversity.ac.in', '$2y$10$AtxL1A31qeA0TV7BJSUUau7c5.vyGc6jFckzKUO1LUlmDMRLZ9zqG', 'STUDENT', '9099045678', '20BECE1001', 'ACTIVE'),
('Sneha Joshi', 'sneha.joshi@ganpatuniversity.ac.in', '$2y$10$K7aJm5VahzVTuGMRiMRhxOIZNUG.mejMIMxZAzU.qY1HScZic77km', 'STUDENT', '9725567890', '21BECE2034', 'ACTIVE');

-- ============================================================
-- Sample items across LOST / FOUND and different statuses
-- ============================================================

-- 1) LOST item by Meet Patel (PENDING — awaiting admin moderation)
INSERT INTO item
  (title, description, itemType, categoryId, location, itemDate, imageUrl,
   custodyLocation, custodyStatus, status, postedBy, loggedByStaff,
   recipientName, recipientContactNo, recipientEnrollmentNo, claimedAt, resolvedAt)
VALUES
  ('Black Lenovo Laptop Charger', 'Black Lenovo laptop charger (65W) left plugged in Computer Lab 302. Has a small sticker on the block with "M9" written on it.',
   'LOST', 1, 'Computer Lab 302, U & P Umar Institute', '2026-08-18', NULL,
   NULL, NULL, 'PENDING', 3, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2) LOST item by Sneha Joshi (ACTIVE — approved by admin, still open)
INSERT INTO item
  (title, description, itemType, categoryId, location, itemDate, imageUrl,
   custodyLocation, custodyStatus, status, postedBy, loggedByStaff,
   recipientName, recipientContactNo, recipientEnrollmentNo, claimedAt, resolvedAt)
VALUES
  ('Blue College ID Card', 'Lost my blue student ID card with name Sneha Joshi (21BECE2034). Last seen at the main library reading hall.',
   'LOST', 2, 'Main Library, Reading Hall', '2026-08-15', NULL,
   NULL, NULL, 'ACTIVE', 4, NULL, NULL, NULL, NULL, NULL, NULL);

-- 3) FOUND item logged by Staff (PENDING — awaiting admin approval)
INSERT INTO item
  (title, description, itemType, categoryId, location, itemDate, imageUrl,
   custodyLocation, custodyStatus, status, postedBy, loggedByStaff,
   recipientName, recipientContactNo, recipientEnrollmentNo, claimedAt, resolvedAt)
VALUES
  ('Samsung Galaxy Watch', 'Samsung Galaxy Watch (black, 44mm) found on a bench near the central canteen. Screen has a small crack at the top edge.',
   'FOUND', 1, 'Central Canteen, open area near Juice Corner', '2026-08-17', NULL,
   'Campus Security Office, Block A', 'IN_CUSTODY', 'PENDING', 2, 2,
   NULL, NULL, NULL, NULL, NULL);

-- 4) FOUND item logged by Staff (ACTIVE — approved, currently in custody)
INSERT INTO item
  (title, description, itemType, categoryId, location, itemDate, imageUrl,
   custodyLocation, custodyStatus, status, postedBy, loggedByStaff,
   recipientName, recipientContactNo, recipientEnrollmentNo, claimedAt, resolvedAt)
VALUES
  ('Grey Laptop Bag with Notebooks', 'Grey backpack containing a green notebook, a pen pouch and an engineering drawing booklet, found in MCA Block corridor.',
   'FOUND', 3, 'MCA Block, first floor corridor', '2026-08-12', NULL,
   'Campus Security Office, Block A', 'IN_CUSTODY', 'ACTIVE', 2, 2,
   NULL, NULL, NULL, NULL, NULL);

-- 5) FOUND item, claimed + recipient recorded + resolved (RESOLVED)
INSERT INTO item
  (title, description, itemType, categoryId, location, itemDate, imageUrl,
   custodyLocation, custodyStatus, status, postedBy, loggedByStaff,
   recipientName, recipientContactNo, recipientEnrollmentNo, claimedAt, resolvedAt)
VALUES
  ('Karbonn Power Bank', 'White Karbonn 10000 mAh power bank handed in by a student. Found near Seminar Hall 1 during the placement drive.',
   'FOUND', 1, 'Seminar Hall 1, IT Block', '2026-08-05', NULL,
   'Campus Security Office, Block A', 'HANDED_OVER', 'RESOLVED', 2, 2,
   'Karan Mehta', '9099911223', '20BECE3045', '2026-08-06 11:30:00', '2026-08-06 11:45:00');

-- ============================================================
-- Sample notifications + audit log entries
-- ============================================================

INSERT INTO notification (userId, itemId, message, isRead) VALUES
(3, 1, 'Your lost item report "Black Lenovo Laptop Charger" is awaiting admin approval.', FALSE),
(4, 2, 'Your lost item report "Blue College ID Card" has been approved and is now live.', TRUE),
(2, 4, 'Found item "Grey Laptop Bag with Notebooks" has been approved and is now visible to students.', TRUE),
(2, 5, 'Item "Karbonn Power Bank" was marked as resolved. Handover completed.', TRUE);

INSERT INTO audit_log (userId, itemId, action, oldStatus, newStatus, details, timestamp) VALUES
(2, 3, 'POST', NULL, 'PENDING', 'Found item logged: Samsung Galaxy Watch', '2026-08-17 09:05:00'),
(2, 4, 'POST', NULL, 'PENDING', 'Found item logged: Grey Laptop Bag with Notebooks', '2026-08-12 10:20:00'),
(1, 4, 'MODERATE', 'PENDING', 'ACTIVE', 'Approved found item: Grey Laptop Bag with Notebooks', '2026-08-12 14:10:00'),
(1, 2, 'MODERATE', 'PENDING', 'ACTIVE', 'Approved lost item: Blue College ID Card', '2026-08-15 16:00:00'),
(2, 5, 'POST', NULL, 'PENDING', 'Found item logged: Karbonn Power Bank', '2026-08-05 13:45:00'),
(2, 5, 'CLAIM', 'ACTIVE', 'CLAIMED', 'Item claimed after in-person verification', '2026-08-06 11:30:00'),
(2, 5, 'RECORD_RECIPIENT', 'CLAIMED', 'CLAIMED', 'Recipient details recorded for Karbonn Power Bank', '2026-08-06 11:40:00'),
(2, 5, 'RESOLVE', 'CLAIMED', 'RESOLVED', 'Item marked resolved; handed over to recipient', '2026-08-06 11:45:00');
