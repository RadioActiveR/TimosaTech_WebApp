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