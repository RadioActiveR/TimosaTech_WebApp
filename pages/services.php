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
    $page_hidden  = is_page_hidden($pdo, 'services');

    $page_title = "Timosa Tech - Services";
    $current_page = 'services';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="icon" type="image/png" href="../assets/images/TimosaTechLogo.png">
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart-modal.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="stylesheet" href="../assets/css/services.css">
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

      <!-- HERO -->
      <section class="svc-hero">
        <div class="container">
          <span class="section-tag">WHAT WE DO</span>
          <h1>Hardware, printing, networks, and support — handled by the same technicians.</h1>
          <p>Beyond the store, our floor runs four core services. Jump to the one you need, or read through all of them below.</p>
          <div class="svc-quicknav">
            <a href="#repair">Repair &amp; Maintenance</a>
            <a href="#printing">Printing Services</a>
            <a href="#networking">Networking &amp; IT</a>
            <a href="#consultation">Online Consultation</a>
          </div>
        </div>
      </section>

      <!-- REPAIR & MAINTENANCE -->
      <section class="svc-block" id="repair">
        <div class="container">
          <div class="svc-content">
            <span class="section-tag">HARDWARE REPAIR</span>
            <h2>Repair &amp; Maintenance</h2>
            <p>Board-level diagnostics for desktops, laptops, printers, and servers — not just parts-swapping. If it can be fixed, we'll tell you what's wrong and what it costs before we touch anything.</p>
            <ul class="svc-steps">
              <li><span class="svc-step-num">1</span><span><strong>Tell us what's wrong.</strong> Describe the device and the issue, or book a drop-off/on-site slot.</span></li>
              <li><span class="svc-step-num">2</span><span><strong>Get a free diagnostic quote.</strong> No repair starts until you approve the price.</span></li>
              <li><span class="svc-step-num">3</span><span><strong>We repair it.</strong> In-shop for most devices, on-site for servers and networking gear.</span></li>
              <li><span class="svc-step-num">4</span><span><strong>Pick up or we deliver.</strong> Every repair includes a short warranty on the work performed.</span></li>
            </ul>
            <a href="contact.php" class="btn btn-primary">Request a Repair</a>
          </div>
          <div class="svc-visual">
            <div class="svc-visual-icon"><?php icon('tool'); ?></div>
            <div class="svc-visual-facts">
              <div class="svc-fact-row"><span>Devices</span><span>PCs, laptops, printers, servers</span></div>
              <div class="svc-fact-row"><span>Diagnostic</span><span>Free, quote before repair</span></div>
              <div class="svc-fact-row"><span>Typical turnaround</span><span>2–3 business days</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- PRINTING SERVICES -->
      <section class="svc-block reverse" id="printing">
        <div class="container">
          <div class="svc-content">
            <span class="section-tag">PRINT &amp; PRODUCTION</span>
            <h2>Printing Services</h2>
            <p>Commercial large-format and blueprint printing, high-volume production runs, and finishing — for one-off jobs or standing business accounts.</p>
            <ul class="svc-steps">
              <li><span class="svc-step-num">1</span><span><strong>Upload your files.</strong> Tell us paper size, quantity, color, and finishing.</span></li>
              <li><span class="svc-step-num">2</span><span><strong>Get a quote.</strong> Standard jobs are priced instantly; custom runs are reviewed first.</span></li>
              <li><span class="svc-step-num">3</span><span><strong>We print it.</strong> Production and finishing handled in-house.</span></li>
              <li><span class="svc-step-num">4</span><span><strong>Pick up or delivery.</strong> Available for bulk and business orders.</span></li>
            </ul>
            <a href="contact.php" class="btn btn-primary">Request a Print Quote</a>
          </div>
          <div class="svc-visual">
            <div class="svc-visual-icon"><?php icon('printer'); ?></div>
            <div class="svc-visual-facts">
              <div class="svc-fact-row"><span>Formats</span><span>Up to large-format / blueprint</span></div>
              <div class="svc-fact-row"><span>Finishing</span><span>Binding, lamination, cutting</span></div>
              <div class="svc-fact-row"><span>Volume</span><span>Single copies to production runs</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- NETWORKING & IT SERVICES -->
      <section class="svc-block" id="networking">
        <div class="container">
          <div class="svc-content">
            <span class="section-tag">INFRASTRUCTURE</span>
            <h2>Networking &amp; IT Services</h2>
            <p>Structured cabling, secure firewall configuration, and automated cloud backups — built to keep small and mid-size business operations running without surprises.</p>
            <ul class="svc-steps">
              <li><span class="svc-step-num">1</span><span><strong>Site assessment.</strong> We look at your current setup and what it needs to support.</span></li>
              <li><span class="svc-step-num">2</span><span><strong>Proposal &amp; quote.</strong> Equipment, cabling, and labor scoped up front.</span></li>
              <li><span class="svc-step-num">3</span><span><strong>Installation.</strong> Cabling, hardware, firewall, and backup configuration.</span></li>
              <li><span class="svc-step-num">4</span><span><strong>Handover &amp; documentation.</strong> You get a working network and a record of how it's built.</span></li>
            </ul>
            <a href="contact.php" class="btn btn-primary">Request a Network Assessment</a>
          </div>
          <div class="svc-visual">
            <div class="svc-visual-icon"><?php icon('network'); ?></div>
            <div class="svc-visual-facts">
              <div class="svc-fact-row"><span>Cabling</span><span>Structured, rack &amp; patch</span></div>
              <div class="svc-fact-row"><span>Security</span><span>Firewall setup &amp; hardening</span></div>
              <div class="svc-fact-row"><span>Backups</span><span>Automated, cloud &amp; local</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ONLINE CONSULTATION -->
      <section class="svc-block reverse" id="consultation">
        <div class="container">
          <div class="svc-content">
            <div class="svc-badge-row">
              <span class="section-tag" style="margin-bottom:0;">REMOTE SUPPORT</span>
              <span class="svc-new-tag">NEW</span>
            </div>
            <h2>Online Consultation</h2>
            <p>Not every problem needs a shop visit. Talk to a technician over video call or screen-share for troubleshooting, purchase advice, or planning an upgrade — wherever you are.</p>
            <ul class="svc-steps">
              <li><span class="svc-step-num">1</span><span><strong>Tell us the issue.</strong> Reach out with what you're trying to solve or decide on.</span></li>
              <li><span class="svc-step-num">2</span><span><strong>We schedule a session.</strong> Video call, screen-share, or phone — whichever fits.</span></li>
              <li><span class="svc-step-num">3</span><span><strong>Live troubleshooting or guidance.</strong> Real-time help from a certified technician.</span></li>
              <li><span class="svc-step-num">4</span><span><strong>Follow-up notes.</strong> A summary of what was covered and any next steps.</span></li>
            </ul>
            <a href="contact.php" class="btn btn-primary">Book a Consultation</a>
          </div>
          <div class="svc-visual">
            <div class="svc-visual-icon"><?php icon('headset'); ?></div>
            <div class="svc-visual-facts">
              <div class="svc-fact-row"><span>Format</span><span>Video call, screen-share, or phone</span></div>
              <div class="svc-fact-row"><span>Good for</span><span>Troubleshooting &amp; purchase advice</span></div>
              <div class="svc-fact-row"><span>Availability</span><span>By appointment</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- NOT SURE WHICH SERVICE -->
      <section class="svc-help-note">
        <div class="container">
          <h3>Not sure which service you need?</h3>
          <p>Send us a message with what's going on and we'll point you to the right one — or set up a consultation to figure it out together.</p>
          <a href="contact.php" class="btn btn-outline">Contact Us</a>
        </div>
      </section>

      <!-- CALL TO ACTION -->
      <section class="cta-banner">
        <div class="container">
          <h2>Ready to Upgrade Your Infrastructure?</h2>
          <p>Whether you need system diagnostic support, custom network deployment, or enterprise hardware, our certified engineers are ready to assist you.</p>
          <a href="contact.php" class="btn-cta">
            REQUEST SUPPORT NOW
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="4" width="16" height="12" rx="2"></rect>
              <path d="M9 20l3-4h8"></path>
            </svg>
          </a>
        </div>
      </section>

    <?php endif; ?>

  <!-- INFO: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

    </main>

  <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
  <?php include __DIR__ . '/../includes/modals/login-signup-modal.php'; ?>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/auth.js"></script>
  <script src="../assets/js/cart.js"></script>

</body>
</html>