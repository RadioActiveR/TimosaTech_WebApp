<?php
/* INFO: Central CRUD activity log for the Admin Portal "CRUD Log" tab.
 * Every admin create/update/delete/status-change action across products,
 * orders, and (eventually) users writes one row here via log_activity().
 * Separate file since logging is a cross-cutting concern used by multiple
 * entity types, not something that belongs inside product-functions.php
 * or order-admin-functions.php specifically.
 */

// Writes one row to activity_logs. Never throws — a logging failure should
// never block or roll back the actual CRUD action that triggered it, so
// errors are swallowed silently here (swap for error_log() in production).
function log_activity(PDO $pdo, ?string $u_id, string $action, string $entity_type, ?string $entity_id, ?string $detail = null): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (u_id, action, entity_type, entity_id, detail)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$u_id, $action, $entity_type, $entity_id, $detail]);
    } catch (Exception $e) {
        // Swallow — logging must never break the admin action itself.
    }
}

// Fetches logs for the CRUD Log tab, newest first, optionally filtered by
// action, entity_type, and/or a free-text search across detail/entity_id/username.
// Capped at 200 rows for now — add pagination here later if the table grows large.
function get_activity_logs(PDO $pdo, string $action_filter = 'all', string $entity_filter = 'all', string $search = ''): array {
    $sql = "
        SELECT l.*, u.username
        FROM activity_logs l
        LEFT JOIN users u ON l.u_id = u.u_id
        WHERE 1=1
    ";
    $params = [];

    if ($action_filter !== 'all') {
        $sql .= " AND l.action = ?";
        $params[] = $action_filter;
    }

    if ($entity_filter !== 'all') {
        $sql .= " AND l.entity_type = ?";
        $params[] = $entity_filter;
    }

    if ($search !== '') {
        $sql .= " AND (l.detail LIKE ? OR l.entity_id LIKE ? OR u.username LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
    }

    $sql .= " ORDER BY l.created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}