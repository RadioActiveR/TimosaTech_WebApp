<?php

/**
 * Site Controls - Visibility functions for Pages, Modals, and Widgets
 *
 * Backed by a single table:
 *
 *   CREATE TABLE site_page_visibility (
 *       page_key   VARCHAR(50) PRIMARY KEY,
 *       is_hidden  TINYINT(1) NOT NULL DEFAULT 0,
 *       updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 *   );
 *
 * Despite the table name, `page_key` doubles as the identifier for
 * modals (e.g. "modal_auth") and widgets (e.g. "widget_chat") too — it's
 * really just a generic hideable-thing key.
 */

function get_controllable_pages(): array {
    return [
        'home'               => 'Homepage',
        'shop'               => 'Shop',
        'services'           => 'Services',
        'about'              => 'About',
        'contact'            => 'Contact',
        'profile'            => 'Profile',
        'checkout'           => 'Checkout',
        'order_confirmation' => 'Order Confirmation',
    ];
}

function get_controllable_modals(): array {
    return [
        'modal_auth'    => 'Login/Signup Modal',
        'modal_product' => 'Product Modal',
        'modal_cart'    => 'Cart Modal',
    ];
}

function get_controllable_widgets(): array {
    return [
        'widget_chat' => 'Support Chat Widget',
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

function get_all_widget_visibility(PDO $pdo): array {
    $stmt = $pdo->query("SELECT page_key, is_hidden FROM site_page_visibility");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $visibility = [];
    foreach (array_keys(get_controllable_widgets()) as $key) {
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

function is_widget_hidden(PDO $pdo, string $widget_key): bool {
    try {
        $stmt = $pdo->prepare("SELECT is_hidden FROM site_page_visibility WHERE page_key = ?");
        $stmt->execute([$widget_key]);
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

function set_widget_hidden(PDO $pdo, string $widget_key, bool $hidden): bool {
    $stmt = $pdo->prepare("
        INSERT INTO site_page_visibility (page_key, is_hidden)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE is_hidden = VALUES(is_hidden)
    ");
    return $stmt->execute([$widget_key, $hidden ? 1 : 0]);
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

function set_all_widgets_hidden(PDO $pdo, bool $hidden): bool {
    $widget_keys = array_keys(get_controllable_widgets());

    $pdo->beginTransaction();
    try {
        foreach ($widget_keys as $key) {
            set_widget_hidden($pdo, $key, $hidden);
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

function are_all_widgets_hidden(PDO $pdo): bool {
    $visibility = get_all_widget_visibility($pdo);
    if (empty($visibility)) {
        return false;
    }
    return !in_array(false, $visibility, true);
}

// A single cheap value that changes whenever ANY row in
// site_page_visibility is inserted or updated — an admin flipping any
// individual page/modal/widget toggle, or one of the "hide all" bulk
// switches, both touch this. Used by a public, unauthenticated endpoint
// so a visitor's browser can detect "something changed" with one small
// query, without needing to poll every individual key, and reload
// automatically instead of continuing to show stale content (or a stale
// Content Veil) until they manually refresh.
function get_site_controls_version(PDO $pdo): string {
    try {
        $stmt = $pdo->query("SELECT MAX(updated_at) FROM site_page_visibility");
        return (string) ($stmt->fetchColumn() ?: 'none');
    } catch (PDOException $e) {
        return 'none';
    }
}