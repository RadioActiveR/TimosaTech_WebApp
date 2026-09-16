<?php
/* INFO: User profile and transaction history helper functions.
 * Handles reading and updating user profile data (used for pre-filling checkout)
 * and fetching order history with line items for proof of purchase.
 */

// Fetches user profile data merged with account credentials (username, email)
function get_user_profile(PDO $pdo, string $u_id): ?array {
    $stmt = $pdo->prepare("
        SELECT u.username, u.email, u.role, u.created_at AS account_created,
               p.full_name, p.phone_number,
               p.address_line1, p.address_line2, p.city, p.province, p.postal_code
        FROM users u
        LEFT JOIN user_profiles p ON u.u_id = p.u_id
        WHERE u.u_id = ?
    ");
    $stmt->execute([$u_id]);
    $profile = $stmt->fetch();
    return $profile ?: null;
}

// Updates or creates user profile personal information and default shipping address
function update_user_profile(PDO $pdo, string $u_id, array $data): bool {
    $stmt = $pdo->prepare("
        INSERT INTO user_profiles (u_id, full_name, phone_number, address_line1, address_line2, city, province, postal_code)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            phone_number = VALUES(phone_number),
            address_line1 = VALUES(address_line1),
            address_line2 = VALUES(address_line2),
            city = VALUES(city),
            province = VALUES(province),
            postal_code = VALUES(postal_code)
    ");
    
    return $stmt->execute([
        $u_id,
        $data['full_name'] ?? null,
        $data['phone_number'] ?? null,
        $data['address_line1'] ?? null,
        $data['address_line2'] ?? null,
        $data['city'] ?? null,
        $data['province'] ?? null,
        $data['postal_code'] ?? null
    ]);
}

/**
 * Validates a username against format rules and a reserved-word list.
 * Shared by signup (login-signup-handler.php) and profile updates
 * (update_username() below), so the rules only need to change in one place.
 * Returns null if valid, or a user-facing error string if not.
 */
function validate_username(string $username): ?string {
    $length = strlen($username);

    if ($length < 3 || $length > 20) {
        return 'Username must be between 3 and 20 characters.';
    }

    // Letters, numbers, and underscores only. Must start with a letter.
    // (No emojis, spaces, dashes, or other punctuation.)
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $username)) {
        return 'Username must start with a letter and can only contain letters, numbers, and underscores.';
    }

    // Reserved words (case-insensitive, exact match only — "admin123" is
    // still allowed, only "admin" itself is blocked)
    $reserved = [
        'admin', 'administrator', 'root', 'superuser', 'moderator', 'mod',
        'support', 'help', 'system', 'staff', 'owner', 'timosatech',
        'null', 'undefined', 'test', 'guest', 'anonymous', 'api'
    ];
    if (in_array(strtolower($username), $reserved, true)) {
        return 'That username is reserved. Please choose a different one.';
    }

    return null;
}

// Changes a user's username after validating format, reserved words, and uniqueness.
// Returns ['success' => bool, 'error' => string] so the caller can show
// a specific message rather than a generic failure.
function update_username(PDO $pdo, string $u_id, string $new_username): array {
    $new_username = trim($new_username);

    $error = validate_username($new_username);
    if ($error !== null) {
        return ['success' => false, 'error' => $error];
    }

    // Uniqueness check excludes the user's own current row so re-submitting
    // the same username isn't rejected as "taken".
    $stmt = $pdo->prepare("SELECT u_id FROM users WHERE username = ? AND u_id != ?");
    $stmt->execute([$new_username, $u_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'That username is already taken.'];
    }

    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE u_id = ?");
    $success = $stmt->execute([$new_username, $u_id]);

    return ['success' => $success, 'error' => $success ? '' : 'Failed to update username. Please try again.'];
}

// Retrieves all past orders placed by the user, newest first
function get_user_orders(PDO $pdo, string $u_id): array {
    $stmt = $pdo->prepare("
        SELECT order_id, recipient_name, phone_number, address_line1, address_line2,
               city, province, postal_code, payment_method, status,
               subtotal, shipping_fee, total_amount, created_at
        FROM orders
        WHERE u_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$u_id]);
    return $stmt->fetchAll();
}

// Retrieves line items for a specific user order (scoped to u_id for security)
function get_user_order_items(PDO $pdo, string $order_id, string $u_id): array {
    $stmt = $pdo->prepare("
        SELECT oi.* 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE oi.order_id = ? AND o.u_id = ?
    ");
    $stmt->execute([$order_id, $u_id]);
    return $stmt->fetchAll();
}