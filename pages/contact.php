<!-- UNFINISHED! WORK IN PROGRESS -->

<?php

    session_start();

    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/cart-functions.php';
    require_once __DIR__ . '/../helpers/icons.php';

    $is_logged_in = isset($_SESSION['u_id']);
    $user_name    = $_SESSION['username'] ?? '';
    $is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
    $cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;

    $page_title = "Timosa Tech - Contact";
    $current_page = 'contact';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
  <link rel="stylesheet" href="../styles/styles.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
</head>

<body>

  <!-- NAVBAR -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

    <main>

    <h1 class="hidden"> HIDDEN </h1>

  <!-- INFO: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

    </main>
</body>