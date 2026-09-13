<?php
require_once __DIR__ . '/../../../includes/functions/product-image-functions.php';

// Absolute path to the folder product photos are saved into.
define('PRODUCT_IMAGE_DIR', __DIR__ . '/../../../assets/images/products/');
// The same location, but as the DB-stored relative path (what
// get_product_image_urls() prefixes with '../').
define('PRODUCT_IMAGE_DB_PREFIX', 'assets/images/products/');

const ALLOWED_PRODUCT_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

// Normalizes PHP's awkward $_FILES['images'] shape (from a
// <input type="file" name="images[]" multiple>) into a flat list of
// [name, type, tmp_name, error, size] entries — one per selected file —
// skipping any empty slots.
function normalize_uploaded_files_array($files) {
    $normalized = [];

    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue; // Empty file input slot — not an actual upload attempt
        }
        $normalized[] = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
    }

    return $normalized;
}

// Saves one uploaded file to disk after validating its *real* content type
// (never trust the client-supplied MIME type). Returns
// ['image_path' => ..., 'mime_type' => ...] on success, or null on any
// failure (bad upload, disallowed type, write failure).
function save_uploaded_product_image(array $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $real_mime = function_exists('mime_content_type')
        ? mime_content_type($file['tmp_name'])
        : $file['type'];

    if (!isset(ALLOWED_PRODUCT_IMAGE_TYPES[$real_mime])) {
        return null;
    }

    if (!is_dir(PRODUCT_IMAGE_DIR)) {
        mkdir(PRODUCT_IMAGE_DIR, 0755, true);
    }

    $ext      = ALLOWED_PRODUCT_IMAGE_TYPES[$real_mime];
    $filename = 'p' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest     = PRODUCT_IMAGE_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return [
        'image_path' => PRODUCT_IMAGE_DB_PREFIX . $filename,
        'mime_type'  => $real_mime,
    ];
}

// Deletes the on-disk file for a stored image_path. Safe to call on a
// path that no longer exists.
function delete_product_image_file(string $image_path) {
    $full_path = __DIR__ . '/../../../' . $image_path;
    if (is_file($full_path)) {
        @unlink($full_path);
    }
}

// Creates a product and saves any uploaded images for it. The first
// successfully-saved image is marked primary (used for card thumbnails).
function add_product_with_images($pdo, $name, $category, $desc, $price, $stock, array $uploaded_files) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (name, category, description, price, stock)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category, $desc, $price, $stock]);
        $product_id = $pdo->lastInsertId();

        $sort_order = 0;
        foreach (normalize_uploaded_files_array($uploaded_files) as $file) {
            $saved = save_uploaded_product_image($file);
            if ($saved) {
                $ins = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_path, mime_type, sort_order, is_primary)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $ins->execute([$product_id, $saved['image_path'], $saved['mime_type'], $sort_order, $sort_order === 0 ? 1 : 0]);
                $sort_order++;
            }
        }

        $pdo->commit();
        return $product_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Updates a product's fields, deletes any images the admin checked for
// removal, appends any newly-uploaded images, and makes sure exactly one
// image stays marked primary.
function update_product_with_images($pdo, $product_id, $name, $category, $desc, $price, $stock, array $uploaded_files, array $remove_image_ids = []) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?
            WHERE product_id = ?
        ");
        $stmt->execute([$name, $category, $desc, $price, $stock, $product_id]);

        $files_to_delete = [];

        if (!empty($remove_image_ids)) {
            $placeholders = implode(',', array_fill(0, count($remove_image_ids), '?'));

            $sel = $pdo->prepare("
                SELECT image_path FROM product_images
                WHERE product_id = ? AND product_image_id IN ($placeholders)
            ");
            $sel->execute(array_merge([$product_id], $remove_image_ids));
            $files_to_delete = $sel->fetchAll(PDO::FETCH_COLUMN);

            $del = $pdo->prepare("
                DELETE FROM product_images
                WHERE product_id = ? AND product_image_id IN ($placeholders)
            ");
            $del->execute(array_merge([$product_id], $remove_image_ids));
        }

        $stmt2 = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?");
        $stmt2->execute([$product_id]);
        $sort_order = ((int) $stmt2->fetchColumn()) + 1;

        foreach (normalize_uploaded_files_array($uploaded_files) as $file) {
            $saved = save_uploaded_product_image($file);
            if ($saved) {
                $ins = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_path, mime_type, sort_order, is_primary)
                    VALUES (?, ?, ?, ?, 0)
                ");
                $ins->execute([$product_id, $saved['image_path'], $saved['mime_type'], $sort_order]);
                $sort_order++;
            }
        }

        // If removing images took away the primary one (or there never was
        // one), promote whichever image now sorts first.
        $check = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
        $check->execute([$product_id]);
        if ((int) $check->fetchColumn() === 0) {
            $first = $pdo->prepare("SELECT product_image_id FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
            $first->execute([$product_id]);
            $first_id = $first->fetchColumn();
            if ($first_id) {
                $setPrimary = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE product_image_id = ?");
                $setPrimary->execute([$first_id]);
            }
        }

        $pdo->commit();

        // Only touch the filesystem after the DB transaction succeeds
        foreach ($files_to_delete as $path) {
            delete_product_image_file($path);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Deletes a product. Also:
//  - removes it from any customer's active cart (a cart holding a deleted
//    product would break checkout's stock/price lookups),
//  - removes its product_images rows (via ON DELETE CASCADE) and the
//    actual image files on disk.
// Past orders are unaffected: order_items stores its own copy of
// product_name/unit_price at time of purchase, so historical receipts stay
// intact even after the product itself is gone.
function delete_product($pdo, $product_id) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $image_paths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
        $stmt->execute([$product_id]);

        // product_images rows cascade automatically via the FK
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $deleted = $stmt->rowCount() > 0;

        $pdo->commit();

        if ($deleted) {
            foreach ($image_paths as $path) {
                delete_product_image_file($path);
            }
        }

        return ['success' => $deleted];
    } catch (Exception $e) {
        $pdo->rollBack();
        // Most likely cause: a foreign key constraint from order_items
        // blocking deletion of a product that's part of past orders.
        return ['success' => false, 'error' => "This product can't be deleted because it's referenced in existing orders."];
    }
}