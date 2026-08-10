<?php
// includes/functions.php

/**
 * Normalizes email address (lowercase and trim).
 */
function normalizeEmail(string $email): string {
    return strtolower(trim($email));
}

/**
 * Validates if the email belongs to allowed Ganpat University domains.
 */
function isValidGanpatEmail(string $email): bool {
    $allowedDomains = ['gnu.ac.in', 'ganpatuniversity.ac.in'];
    $parts = explode('@', $email);
    
    if (count($parts) !== 2) {
        return false;
    }
    
    $domain = strtolower($parts[1]);
    return in_array($domain, $allowedDomains, true);
}

/**
 * Checks if an email already exists in the database.
 */
function emailExists(PDO $pdo, string $email): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return (bool) $stmt->fetchColumn();
}
