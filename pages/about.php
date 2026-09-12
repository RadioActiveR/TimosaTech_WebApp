<!-- UNFINISHED! WORK IN PROGRESS -->

<!-- UNFINISHED! WORK IN PROGRESS -->

<?php

    session_start();

    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/functions/cart-functions.php'; 
    require_once __DIR__ . '/../includes/functions/site-control-functions.php';
    require_once __DIR__ . '/../helpers/icons.php';

    $is_logged_in = isset($_SESSION['u_id']);
    $user_name    = $_SESSION['username'] ?? '';
    $is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
    $cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;
    $page_hidden  = is_page_hidden($pdo, 'about');

    $page_title = "Timosa Tech - About Us";
    $current_page = 'about';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
</head>

<body>

  <!-- NAVBAR -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

    <main>

    <?php if ($page_hidden): ?>
      <h1 class="hidden"> HIDDEN </h1>
    <?php else: ?>
      <!-- TODO: About page content goes here -->
    <?php endif; ?>

  <!-- INFO: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

    </main>
</body>