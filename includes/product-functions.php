<?php
// Function to handle product insertion along with its linked image
function add_product_with_image($pdo, $name, $category, $desc, $price, $stock, $base64_string = null, $mime_type = 'image/png') {
    $image_id = null;

    if (!empty($base64_string)) {
        $stmt_img = $pdo->prepare("INSERT INTO images (image_data, mime_type) VALUES (?, ?)");
        $stmt_img->execute([$base64_string, $mime_type]);
        $image_id = $pdo->lastInsertId();
    }

    $stmt_prod = $pdo->prepare("INSERT INTO products (image_id, name, category, description, price, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_prod->execute([$image_id, $name, $category, $desc, $price, $stock]);

    return $pdo->lastInsertId();
}

// Function to handle product updating along with image updates
function update_product_with_image($pdo, $product_id, $name, $category, $desc, $price, $stock, $base64_string = null, $mime_type = 'image/png') {
    if (!empty($base64_string)) {
        // Fetch existing image_id
        $stmt = $pdo->prepare("SELECT image_id FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $current_img_id = $stmt->fetchColumn();

        if ($current_img_id) {
            $stmt_img = $pdo->prepare("UPDATE images SET image_data = ?, mime_type = ? WHERE image_id = ?");
            $stmt_img->execute([$base64_string, $mime_type, $current_img_id]);
        } else {
            $stmt_img = $pdo->prepare("INSERT INTO images (image_data, mime_type) VALUES (?, ?)");
            $stmt_img->execute([$base64_string, $mime_type]);
            $image_id = $pdo->lastInsertId();

            $stmt_link = $pdo->prepare("UPDATE products SET image_id = ? WHERE product_id = ?");
            $stmt_link->execute([$image_id, $product_id]);
        }
    }

    $stmt_prod = $pdo->prepare("UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ? WHERE product_id = ?");
    $stmt_prod->execute([$name, $category, $desc, $price, $stock, $product_id]);
}

// Deletes a product. Also:
//  - removes it from any customer's active cart (a cart holding a deleted
//    product would break checkout's stock/price lookups), and
//  - cleans up its linked image row, but only if no other product still uses
//    that same image_id.
// Past orders are unaffected: order_items stores its own copy of
// product_name/unit_price at time of purchase, so historical receipts stay
// intact even after the product itself is gone.
function delete_product($pdo, $product_id) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
        $stmt->execute([$product_id]);

        $stmt = $pdo->prepare("SELECT image_id FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $image_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $deleted = $stmt->rowCount() > 0;

        if ($deleted && $image_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE image_id = ?");
            $stmt->execute([$image_id]);
            if ((int) $stmt->fetchColumn() === 0) {
                $stmt = $pdo->prepare("DELETE FROM images WHERE image_id = ?");
                $stmt->execute([$image_id]);
            }
        }

        $pdo->commit();
        return ['success' => $deleted];
    } catch (Exception $e) {
        $pdo->rollBack();
        // Most likely cause: a foreign key constraint from order_items
        // blocking deletion of a product that's part of past orders.
        return ['success' => false, 'error' => "This product can't be deleted because it's referenced in existing orders."];
    }
}