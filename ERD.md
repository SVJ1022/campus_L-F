# FINDLY — Entity Relationship Diagram (textual)

## Entities

### user
| Column            | Type                          | Notes                                |
|-------------------|-------------------------------|--------------------------------------|
| `userId`          | INT PK AI                     |                                      |
| `name`            | VARCHAR(100) NOT NULL         | Full name                            |
| `email`           | VARCHAR(150) UNIQUE NOT NULL  | Institute email, login handle        |
| `password`        | VARCHAR(255) NOT NULL         | bcrypt hash only                     |
| `role`            | ENUM(STUDENT,STAFF,ADMIN)     | Default STUDENT                      |
| `contactNo`       | VARCHAR(15)                   |                                      |
| `enrollmentNo`    | VARCHAR(30) NULL              | Student enrollment / employee number |
| `accountStatus`   | ENUM(ACTIVE,INACTIVE,SUSPENDED)| Default ACTIVE                       |
| `createdAt`       | DATETIME                      |                                      |

### category
| Column          | Type                  |
|-----------------|-----------------------|
| `categoryId`    | INT PK AI             |
| `categoryName`  | VARCHAR(80) UNIQUE    |

### item
| Column                 | Type                                            | Notes                              |
|------------------------|-------------------------------------------------|------------------------------------|
| `itemId`               | INT PK AI                                       |                                    |
| `title`                | VARCHAR(150) NOT NULL                           |                                    |
| `description`          | TEXT NOT NULL                                   |                                    |
| `itemType`             | ENUM(LOST,FOUND) NOT NULL                       |                                    |
| `categoryId`           | INT FK → category.categoryId                    |                                    |
| `location`             | VARCHAR(200) NOT NULL                           | Found-at / last-seen location      |
| `itemDate`             | DATE NOT NULL                                   |                                    |
| `imageUrl`             | VARCHAR(255) NULL                               | Relative to backend/uploads        |
| `custodyLocation`      | VARCHAR(200) NULL                               | FOUND only                         |
| `custodyStatus`        | ENUM(IN_CUSTODY,MOVED,HANDED_OVER) NULL         | FOUND only                         |
| `status`               | ENUM(PENDING,ACTIVE,CLAIMED,RESOLVED,REJECTED)  | Default PENDING                    |
| `postedBy`             | INT FK → user.userId                            | Whoever created the report         |
| `loggedByStaff`        | INT FK → user.userId NULL                       | FOUND items, staff/admin who logged|
| `recipientName`        | VARCHAR(100) NULL                               | **Private** — never sent to Student|
| `recipientContactNo`   | VARCHAR(15) NULL                                | **Private**                        |
| `recipientEnrollmentNo`| VARCHAR(30) NULL                                | **Private**                        |
| `claimedAt`            | DATETIME NULL                                   |                                    |
| `resolvedAt`           | DATETIME NULL                                   |                                    |
| `createdAt`            | DATETIME                                        |                                    |
| `updatedAt`            | DATETIME NULL ON UPDATE                         |                                    |

### notification
| Column             | Type                     | Notes                          |
|--------------------|--------------------------|--------------------------------|
| `notificationId`   | INT PK AI                |                                |
| `userId`           | INT FK → user.userId     | Recipient (only that user sees it) |
| `itemId`           | INT FK → item.itemId NULL| Optional related item          |
| `message`          | VARCHAR(255) NOT NULL    |                                |
| `isRead`           | BOOLEAN default FALSE    |                                |
| `createdAt`        | DATETIME                 |                                |

### audit_log
| Column       | Type                    | Notes                        |
|--------------|-------------------------|------------------------------|
| `auditId`    | INT PK AI               |                              |
| `userId`     | INT FK → user.userId    | Actor                        |
| `itemId`     | INT FK → item.itemId NULL |                             |
| `action`     | VARCHAR(100)            | POST, MODERATE, CLAIM, RESOLVE… |
| `oldStatus`  | VARCHAR(30) NULL        |                              |
| `newStatus`  | VARCHAR(30) NULL        |                              |
| `details`    | TEXT NULL               |                              |
| `timestamp`  | DATETIME                |                              |

## Relationships

```
user 1 ─── n item          (postedBy)         — anyone can create a LOST report
user 1 ─── n item          (loggedByStaff)    — staff/admin who logs a FOUND item
category 1 ─── n item      (categoryId)
item 1 ─── n notification  (itemId, optional)
user 1 ─── n notification  (userId)           — notification is per-user
user 1 ─── n audit_log     (userId)
item 1 ─── n audit_log     (itemId, optional)
```

## Lifecycle (state machine for `item.status`)

- LOST report:  PENDING → ACTIVE (approved by admin) ｜ PENDING → REJECTED
- FOUND report: PENDING → ACTIVE (approved by admin) ｜ PENDING → REJECTED
- FOUND active: ACTIVE → CLAIMED (in-person verification) → CLAIMED (+ recipient recorded, private) → RESOLVED (handover)
- Delete: only a LOST report still in PENDING or ACTIVE owned by the reporter can be deleted.

> **Privacy invariant:** `recipientName`, `recipientContactNo`, `recipientEnrollmentNo`
> are stripped from every API response that reaches a Student session or an
> unauthenticated request (enforced in `backend/src/Item/ItemController::sanitizeItem`).