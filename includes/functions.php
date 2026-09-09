<?php
// Function to handle product insertion along with its linked image
function add_product_with_image($pdo, $name, $category, $desc, $price, $stock, $base64_string = null, $mime_type = 'image/png') {
    $image_id = null;

    // 1. If an image is provided, insert it into the images table first
    if (!empty($base64_string)) {
        $stmt_img = $pdo->prepare("INSERT INTO images (image_data, mime_type) VALUES (?, ?)");
        $stmt_img->execute([$base64_string, $mime_type]);
        $image_id = $pdo->lastInsertId();
    }

    // 2. Insert product record linked with image_id
    $stmt_prod = $pdo->prepare("INSERT INTO products (image_id, name, category, description, price, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_prod->execute([$image_id, $name, $category, $desc, $price, $stock]);

    return $pdo->lastInsertId();
}