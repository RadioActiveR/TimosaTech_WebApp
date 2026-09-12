<?php
/* INFO: Turns the user's current cart into a placed order.
 * Everything happens in a single transaction: if the stock decrement fails
 * for any item (someone else bought the last unit in the meantime), the
 * whole order is rolled back and nothing is charged/created.
 *
 * INFO (cart select-before-checkout): $cart_items may now be a *subset* of
 * the user's cart (only the items they checked off in the cart modal), so
 * on success we only delete those specific cart_item_id rows — anything the
 * user left unchecked stays in their cart untouched.
 */
function create_order(PDO $pdo, string $u_id, array $details, array $cart_items): ?string {
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $shipping_fee = 0.00; // flat/free for now — adjust here if you add shipping tiers later
    $total = $subtotal + $shipping_fee;

    $order_id = uniqid('ord_', true);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO orders
              (order_id, u_id, recipient_name, phone_number, address_line1, address_line2,
               city, province, postal_code, payment_method, status, subtotal, shipping_fee, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
        ");
        $stmt->execute([
            $order_id, $u_id,
            $details['recipient_name'], $details['phone_number'],
            $details['address_line1'], $details['address_line2'] ?: null,
            $details['city'], $details['province'], $details['postal_code'],
            $details['payment_method'],
            $subtotal, $shipping_fee, $total,
        ]);

        $stmt_item = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt_stock = $pdo->prepare("
            UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?
        ");

        $ordered_cart_item_ids = [];

        foreach ($cart_items as $item) {
            $stmt_item->execute([
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                round($item['price'] * $item['quantity'], 2),
            ]);

            // Re-check stock at the DB level (not just the earlier PHP-side check) to
            // guard against a race with another customer checking out at the same time.
            $stmt_stock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            if ($stmt_stock->rowCount() === 0) {
                throw new RuntimeException('Insufficient stock for product #' . $item['product_id']);
            }

            $ordered_cart_item_ids[] = $item['cart_item_id'];
        }

        // Only remove the cart rows that were actually part of this order —
        // anything the user left unchecked in the cart modal stays put.
        if (!empty($ordered_cart_item_ids)) {
            $placeholders = implode(',', array_fill(0, count($ordered_cart_item_ids), '?'));
            $stmt_clear = $pdo->prepare("
                DELETE FROM cart_items WHERE u_id = ? AND cart_item_id IN ($placeholders)
            ");
            $stmt_clear->execute(array_merge([$u_id], $ordered_cart_item_ids));
        }

        $pdo->commit();
        return $order_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        return null;
    }
}