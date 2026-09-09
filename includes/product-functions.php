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