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

// TODO: swap these placeholder numbers/dates for the real ones
$stats = [
    ["value" => "2014",    "label" => "Founded In"],
    ["value" => "12,400+", "label" => "Repairs Completed"],
    ["value" => "300+",    "label" => "Business Clients"],
    ["value" => "24/7",    "label" => "Support Line"],
];

$milestones = [
    [
        "year"  => "2014",
        "title" => "A two-desk repair counter",
        "desc"  => "Opened fixing prebuilt towers, printers, and the occasional server that \"just needs a new PSU.\"",
    ],
    [
        "year"  => "2017",
        "title" => "From repairs to networks",
        "desc"  => "Small offices started asking us to wire and secure their networks, so we built a dedicated networking team.",
    ],
    [
        "year"  => "2020",
        "title" => "The store went online",
        "desc"  => "Launched the online catalog and started stocking enterprise-grade hardware for same-week delivery.",
    ],
    [
        "year"  => "2026",
        "title" => "A full service floor",
        "desc"  => "Certified technicians, a parts warehouse, and support contracts for businesses that can't afford downtime.",
    ],
];

$process_steps = [
    ["num" => "01", "title" => "Diagnose", "desc" => "Board-level triage before we ever quote a repair or a build.", "icon" => "tool"],
    ["num" => "02", "title" => "Source",   "desc" => "Vetted, enterprise-grade components — no gray-market parts.", "icon" => "badge"],
    ["num" => "03", "title" => "Deploy",   "desc" => "Installed, configured, and stress-tested on site or in-house.", "icon" => "network"],
    ["num" => "04", "title" => "Support",  "desc" => "Ongoing monitoring with a direct line to the engineer who built it.", "icon" => "headset"],
];

$credentials = [
    [
        "title" => "Authorized Hardware Reseller",
        "desc"  => "Sourced through manufacturer and distributor partner programs — not gray-market resale.",
        "icon"  => "badge",
    ],
    [
        "title" => "Certified Network Technicians",
        "desc"  => "Trained and certified on structured cabling, switching, and firewall configuration.",
        "icon"  => "network",
    ],
    [
        "title" => "Manufacturer Warranty Support",
        "desc"  => "Warranty claims and RMAs are handled in-house, so you're not stuck between us and the maker.",
        "icon"  => "shield",
    ],
    [
        "title" => "On-Site & Remote Diagnostics",
        "desc"  => "Available at our shop, on-site at your business, or over a support call.",
        "icon"  => "monitor",
    ],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/master.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart-modal.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="stylesheet" href="../assets/css/about.css">
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
    <section class="about-hero">
      <div class="container about-hero-grid">
        <div class="about-hero-text">
          <span class="section-tag">ABOUT TIMOSA TECH</span>
          <h1>Built by engineers who fix what they sell.</h1>
          <p>We didn't start as a store — we started as a repair bench. That's still how we think: every product we carry is something our own technicians have opened up, diagnosed, and stood behind.</p>
          <div class="about-hero-buttons">
            <a href="shop.php" class="btn btn-primary">Browse Catalog →</a>
            <a href="contact.php" class="btn btn-secondary">Get In Touch</a>
          </div>
        </div>

        <div class="about-hero-visual">
          <div class="about-bench-panel">
            <div class="about-bench-header">
              <span class="about-bench-dot"></span>
              <span>BENCH STATUS</span>
            </div>
            <ul class="about-bench-list">
              <li><span>Diagnostic check</span><span class="about-bench-pass">Passed</span></li>
              <li><span>Board-level test</span><span class="about-bench-pass">Passed</span></li>
              <li><span>24-hour stress test</span><span class="about-bench-pass">Passed</span></li>
              <li><span>Cleared for deployment</span><span class="about-bench-pass">Passed</span></li>
            </ul>
            <div class="about-bench-footer">Every unit that leaves our floor clears this checklist first.</div>
          </div>
        </div>
      </div>
    </section>

    <!-- STAT STRIP -->
    <section class="about-stat-strip">
      <div class="container">
        <?php foreach ($stats as $stat): ?>
          <div class="about-stat-item">
            <div class="about-stat-value"><?= htmlspecialchars($stat['value']) ?></div>
            <div class="about-stat-label"><?= htmlspecialchars($stat['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- STORY & TIMELINE -->
    <section class="about-story">
      <div class="container about-story-grid">
        <div class="about-story-text">
          <span class="section-tag">HOW WE GOT HERE</span>
          <h2>A repair shop that grew into a full IT partner</h2>
          <p>Timosa Tech started small: two desks, a soldering iron, and a line of customers whose printers and towers everyone else had given up on. We got good at the parts other shops skipped — board-level diagnosis, not just swapping boxes.</p>
          <p>That habit is why businesses still call us first when something breaks, and why we've been able to grow into networking, enterprise sales, and long-term support without losing the bench-level instincts we started with.</p>
          <p class="about-pull-quote">"We'd rather explain the fix than sell you a replacement."</p>
        </div>

        <div class="about-timeline">
          <?php foreach ($milestones as $milestone): ?>
            <div class="about-timeline-item">
              <span class="about-timeline-marker"></span>
              <div class="about-timeline-year"><?= htmlspecialchars($milestone['year']) ?></div>
              <h4><?= htmlspecialchars($milestone['title']) ?></h4>
              <p><?= htmlspecialchars($milestone['desc']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- HOW WE WORK -->
    <section class="about-process">
      <div class="container">
        <div class="section-header-center">
          <span class="section-tag">HOW WE WORK</span>
          <h2>The same process, every job</h2>
        </div>

        <div class="about-process-row">
          <?php foreach ($process_steps as $step): ?>
            <div class="about-process-step">
              <div class="about-process-icon">
                <?php icon($step['icon']); ?>
                <span class="about-process-num"><?= htmlspecialchars($step['num']) ?></span>
              </div>
              <h3><?= htmlspecialchars($step['title']) ?></h3>
              <p><?= htmlspecialchars($step['desc']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CREDENTIALS -->
    <section class="about-credentials">
      <div class="container">
        <div class="section-header-center">
          <span class="section-tag">CREDENTIALS</span>
          <h2>What backs that up</h2>
        </div>
        <div class="about-credentials-grid">
          <?php foreach ($credentials as $credential): ?>
            <div class="about-credential-card">
              <div class="about-credential-icon"><?php icon($credential['icon']); ?></div>
              <h3><?= htmlspecialchars($credential['title']) ?></h3>
              <p><?= htmlspecialchars($credential['desc']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CALL TO ACTION -->
    <section class="cta-banner">
      <div class="container">
        <h2>Want to see how we work up close?</h2>
        <p>Bring in a machine, a network problem, or just a question. Our certified engineers are ready to take a look.</p>
        <a href="contact.php" class="btn-cta">
          SCHEDULE A WALKTHROUGH
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="4" width="16" height="12" rx="2"></rect>
            <path d="M9 20l3-4h8"></path>
          </svg>
        </a>
      </div>
    </section>

  <?php endif; ?>

  </main>

  <!-- INFO: FOOTER SECTION -->
  <?php
    require_once __DIR__ . '/../components/footer.php';
  ?>

  <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
  <?php include __DIR__ . '/../includes/modals/login-signup-modal.php'; ?>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/auth.js"></script>
  <script src="../assets/js/cart.js"></script>

</body>
</html>