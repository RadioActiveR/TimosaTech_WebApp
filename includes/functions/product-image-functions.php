<?php
/**
 * Shared, read-only helpers for product photos (product_images table).
 * Used by public pages (shop.php, homepage.php) and the admin dashboard.
 * Write operations (upload/delete) live in
 * admin/includes/functions/product-admin-functions.php instead, since
 * only the admin dashboard ever needs to mutate this data.
 */

function get_product_images($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

// Batch-fetches images for many products in one query (avoids N+1 queries
// on the shop grid). Returns [product_id => [image row, image row, ...]].
function get_product_images_for_ids($pdo, array $product_ids) {
    if (empty($product_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT * FROM product_images
        WHERE product_id IN ($placeholders)
        ORDER BY product_id ASC, sort_order ASC
    ");
    $stmt->execute($product_ids);

    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $grouped[$row['product_id']][] = $row;
    }
    return $grouped;
}

function get_primary_image(array $images) {
    foreach ($images as $image) {
        if (!empty($image['is_primary'])) {
            return $image;
        }
    }
    return $images[0] ?? null;
}

// Builds the array of viewable URLs for a product's gallery, relative to
// a page one level under the project root (true for both /pages/*.php and
// /admin/*.php). Falls back to the shared placeholder for products with
// no images at all, so card/modal markup never has to special-case it.
function get_product_image_urls(array $images, string $fallback = '../assets/images/workstation-rig.png') {
    if (empty($images)) {
        return [$fallback];
    }
    return array_map(fn($img) => '../' . $img['image_path'], $images);
}