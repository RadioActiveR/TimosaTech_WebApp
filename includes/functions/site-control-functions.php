<?php

/**
 * Site Controls - Visibility functions for Pages and Modals
 *
 * Backed by a single table:
 *
 *   CREATE TABLE site_page_visibility (
 *       page_key   VARCHAR(50) PRIMARY KEY,
 *       is_hidden  TINYINT(1) NOT NULL DEFAULT 0,
 *       updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 *   );
 */

function get_controllable_pages(): array {
    return [
        'home'               => 'Homepage',
        'shop'               => 'Shop',
        'services'           => 'Services',
        'about'              => 'About',
        'contact'            => 'Contact',
        'checkout'           => 'Checkout',
        'profile'            => 'Profile',
        'order_confirmation' => 'Order Confirmation',
    ];
}

function get_controllable_modals(): array {
    return [
        'modal_product' => 'Product Modal',
        'modal_cart'    => 'Cart Modal',
        'modal_auth'    => 'Login Signup Modal',
    ];
}

function get_all_page_visibility(PDO $pdo): array {
    $stmt = $pdo->query("SELECT page_key, is_hidden FROM site_page_visibility");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $visibility = [];
    foreach (array_keys(get_controllable_pages()) as $key) {
        $visibility[$key] = isset($rows[$key]) ? (bool) $rows[$key] : false;
    }
    return $visibility;
}

function get_all_modal_visibility(PDO $pdo): array {
    $stmt = $pdo->query("SELECT page_key, is_hidden FROM site_page_visibility");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $visibility = [];
    foreach (array_keys(get_controllable_modals()) as $key) {
        $visibility[$key] = isset($rows[$key]) ? (bool) $rows[$key] : false;
    }
    return $visibility;
}

function is_page_hidden(PDO $pdo, string $page_key): bool {
    try {
        $stmt = $pdo->prepare("SELECT is_hidden FROM site_page_visibility WHERE page_key = ?");
        $stmt->execute([$page_key]);
        $val = $stmt->fetchColumn();
        return $val !== false && (bool) $val;
    } catch (PDOException $e) {
        return false;
    }
}

function is_modal_hidden(PDO $pdo, string $modal_key): bool {
    try {
        $stmt = $pdo->prepare("SELECT is_hidden FROM site_page_visibility WHERE page_key = ?");
        $stmt->execute([$modal_key]);
        $val = $stmt->fetchColumn();
        return $val !== false && (bool) $val;
    } catch (PDOException $e) {
        return false;
    }
}

function set_page_hidden(PDO $pdo, string $page_key, bool $hidden): bool {
    $stmt = $pdo->prepare("
        INSERT INTO site_page_visibility (page_key, is_hidden)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE is_hidden = VALUES(is_hidden)
    ");
    return $stmt->execute([$page_key, $hidden ? 1 : 0]);
}

function set_modal_hidden(PDO $pdo, string $modal_key, bool $hidden): bool {
    $stmt = $pdo->prepare("
        INSERT INTO site_page_visibility (page_key, is_hidden)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE is_hidden = VALUES(is_hidden)
    ");
    return $stmt->execute([$modal_key, $hidden ? 1 : 0]);
}

function set_all_pages_hidden(PDO $pdo, bool $hidden): bool {
    $page_keys = array_keys(get_controllable_pages());

    $pdo->beginTransaction();
    try {
        foreach ($page_keys as $key) {
            set_page_hidden($pdo, $key, $hidden);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function set_all_modals_hidden(PDO $pdo, bool $hidden): bool {
    $modal_keys = array_keys(get_controllable_modals());

    $pdo->beginTransaction();
    try {
        foreach ($modal_keys as $key) {
            set_modal_hidden($pdo, $key, $hidden);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function are_all_pages_hidden(PDO $pdo): bool {
    $visibility = get_all_page_visibility($pdo);
    if (empty($visibility)) {
        return false;
    }
    return !in_array(false, $visibility, true);
}

function are_all_modals_hidden(PDO $pdo): bool {
    $visibility = get_all_modal_visibility($pdo);
    if (empty($visibility)) {
        return false;
    }
    return !in_array(false, $visibility, true);
}