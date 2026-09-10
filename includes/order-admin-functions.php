<?php
/* INFO: Order management helpers for the Admin Portal "Orders" tab.
 * Kept separate from order-functions.php (which only handles turning a
 * cart into a new order at checkout) so admin CRUD logic lives on its own.
 */

// List orders for the admin table, optionally filtered by status and/or a
// free-text search across order id, recipient name, username, and email.
function get_orders_admin(PDO $pdo, string $status_filter = 'all', string $search = ''): array {
    $sql = "
        SELECT o.*, u.username, u.email
        FROM orders o
        JOIN users u ON o.u_id = u.u_id
        WHERE 1=1
    ";
    $params = [];

    if ($status_filter !== 'all') {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
    }

    if ($search !== '') {
        $sql .= " AND (o.order_id LIKE ? OR o.recipient_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like, $like);
    }

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Single order + the customer's account info, for the detail panel.
function get_order_admin(PDO $pdo, string $order_id): ?array {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email
        FROM orders o
        JOIN users u ON o.u_id = u.u_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    return $order ?: null;
}

function get_order_items_admin(PDO $pdo, string $order_id): array {
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

// Status change history, newest first, with the admin username who made each change.
function get_order_status_log(PDO $pdo, string $order_id): array {
    $stmt = $pdo->prepare("
        SELECT l.*, u.username AS changed_by_username
        FROM order_status_log l
        LEFT JOIN users u ON l.changed_by = u.u_id
        WHERE l.order_id = ?
        ORDER BY l.changed_at DESC
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

// Updates an order's status and writes an entry to order_status_log in the
// same transaction, so the audit trail can never drift from the actual status.
function update_order_status(PDO $pdo, string $order_id, string $new_status, string $changed_by): bool {
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $old_status = $stmt->fetchColumn();

    if ($old_status === false) {
        return false; // order doesn't exist
    }
    if ($old_status === $new_status) {
        return true; // nothing to do
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);

        $stmt = $pdo->prepare("
            INSERT INTO order_status_log (order_id, old_status, new_status, changed_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $old_status, $new_status, $changed_by]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Deletes an order outright. order_items and order_status_log rows cascade
// via the FOREIGN KEY ... ON DELETE CASCADE constraints in schema.sql.
function delete_order_admin(PDO $pdo, string $order_id): bool {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
    return $stmt->execute([$order_id]);
}