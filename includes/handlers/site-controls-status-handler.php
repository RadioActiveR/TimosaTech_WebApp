<?php
/**
 * Public, unauthenticated endpoint: reports a single "version" value that
 * changes whenever any site-controls toggle (page/modal/widget visibility)
 * is flipped in the admin panel. Polled by
 * assets/js/site-controls-watcher.js on every public page so a visitor's
 * browser can detect a change and reload automatically, instead of
 * needing a manual refresh to see updated Content Veil state.
 *
 * Deliberately no login check — visitors, not just admins, need to poll
 * this. It only ever reveals a timestamp, never the actual hidden/shown
 * state of anything, so there's nothing sensitive to gate here.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions/site-control-functions.php';

echo json_encode([
    'version' => get_site_controls_version($pdo),
]);