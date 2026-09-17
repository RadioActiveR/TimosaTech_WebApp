<!-- INFO: contact page -->

<?php

    session_start();

    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/functions/cart-functions.php';
    require_once __DIR__ . '/../includes/functions/site-control-functions.php';
    require_once __DIR__ . '/../includes/functions/chat-functions.php';
    require_once __DIR__ . '/../helpers/icons.php';

    $is_logged_in = isset($_SESSION['u_id']);
    $user_name    = $_SESSION['username'] ?? '';
    $is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
    $cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;
    $page_hidden  = is_page_hidden($pdo, 'contact');

    $page_title = "Timosa Tech - Contact";
    $current_page = 'contact';

    $contact_submitted = false;
    $contact_form_error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'], $_POST['message'])) {
        $cf_name    = trim($_POST['name']);
        $cf_email   = trim($_POST['email']);
        $cf_phone   = trim($_POST['phone'] ?? '');
        $cf_subject = trim($_POST['subject'] ?? 'general');
        $cf_message = trim($_POST['message']);

        if ($cf_name !== '' && $cf_email !== '' && $cf_message !== '') {
            $guest_token = $is_logged_in ? null : get_or_create_guest_token();
            $conversation = get_or_create_conversation($pdo, $is_logged_in ? $_SESSION['u_id'] : null, $guest_token);

            if (empty($conversation['visitor_name'])) {
                $stmt = $pdo->prepare("UPDATE chat_conversations SET visitor_name = ? WHERE conversation_id = ?");
                $stmt->execute([$cf_name, $conversation['conversation_id']]);
            }

            $subject_labels = [
                'general'      => 'General Inquiry',
                'repair'       => 'Repair & Maintenance',
                'printing'     => 'Printing Services',
                'networking'   => 'Networking & IT Services',
                'consultation' => 'Online Consultation',
                'order'        => 'An Existing Order',
            ];

            $form_note = "[Contact Form — " . ($subject_labels[$cf_subject] ?? 'General Inquiry') . "]\n"
                       . "From: $cf_name ($cf_email" . ($cf_phone !== '' ? ", $cf_phone" : '') . ")\n\n"
                       . $cf_message;

            add_chat_message($pdo, $conversation['conversation_id'], 'visitor', $is_logged_in ? $_SESSION['u_id'] : null, $form_note);
            set_conversation_status($pdo, $conversation['conversation_id'], 'pending_human');

            $contact_submitted = true;
        } else {
            $contact_form_error = "Please fill in your name, email, and message.";
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="icon" type="image/png" href="../assets/images/TimosaTechLogo.png">
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/content-veil.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart-modal.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="stylesheet" href="../assets/css/contact.css">
</head>

<body>

  <!-- SECTION: NAVBAR -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

    <main>

    <?php if ($page_hidden): ?>
      <div class="center-container">
        <h1 class="hidden"> HIDDEN </h1>
        <h2 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h2>
      </div>
    <?php else: ?>

      <!-- SECTION: HERO -->
      <section class="contact-hero">
        <div class="container">
          <span class="section-tag">GET IN TOUCH</span>
          <h1>We're happy to help</h1>
          <p>Questions about a product, a repair, a print job, or anything else — send us a message, or use the chat bubble in the corner for something quick.</p>
        </div>
      </section>

      <div class="container contact-layout">
        <!-- INFO: LIVE CHAT (bigger than the floating widget) -->
        <?php include __DIR__ . '/../includes/widgets/contact-chat-panel.php'; ?>

        <!-- SECTION: CONTACT INFO -->
        <div>
          <div class="contact-info-card">
            <h3>Contact Details</h3>

            <div class="contact-info-row">
              <div class="contact-info-icon"><?php icon('map-pin'); ?></div>
              <div>
                <h4>Visit Us</h4>
                <p>101 Tech Junction, Suite A</p>
              </div>
            </div>

            <div class="contact-info-row">
              <div class="contact-info-icon"><?php icon('phone'); ?></div>
              <div>
                <h4>Call Us</h4>
                <a href="tel:+639776118718">+63 977 611 8718</a>
              </div>
            </div>

            <div class="contact-info-row">
              <div class="contact-info-icon"><?php icon('mail'); ?></div>
              <div>
                <h4>Email Us</h4>
                <a href="mailto:support@timosatech.com">support@timosatech.com</a>
              </div>
            </div>
          </div>

          <div class="contact-info-card">
            <h3><span class="contact-hours-icon"><?php icon('clock'); ?></span>Business Hours</h3>
            <div class="contact-hours-row"><span>Monday – Friday</span><span>8:00 AM – 6:00 PM</span></div>
            <div class="contact-hours-row"><span>Saturday</span><span>9:00 AM – 3:00 PM</span></div>
            <div class="contact-hours-row"><span>Sunday</span><span>Closed</span></div>
          </div>

          <div class="contact-map">
            <!-- <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d413.8489618578667!2d123.30427041274346!3d9.314027727132178!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1789379527804!5m2!1sen!2sph" 
              width="600" 
              height="450" 
              style="border:0;" 
              allowfullscreen="" 
              loading="lazy" 
              title="TimosaTech location" 
              referrerpolicy="strict-origin-when-cross-origin">
            </iframe> -->
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1661.1505822245115!2d123.30323839873297!3d9.312424593205213!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33ab6f1dfcce85c5%3A0x1a8ce1f20edd6e00!2sNegros%20Oriental%20State%20University%20-%20Main%20Campus!5e0!3m2!1sen!2sph!4v1789379696326!5m2!1sen!2sph" 
              width="600" 
              height="450" 
              style="border:0;" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="strict-origin-when-cross-origin">
            </iframe>
          </div>
        </div>
      </div>

      <!-- SECTION: WRITTEN MESSAGE (secondary option to live chat) -->
      <section class="contact-form-section">
        <div class="container">
          <div class="section-header-center">
            <span class="section-tag">PREFER TO WRITE IT OUT?</span>
            <h2>Send a Message</h2>
            <p style="color: var(--text-muted); max-width: 50ch; margin: 10px auto 0;">This goes straight to our team's inbox — same place your live chat messages land — so you'll hear back even if no one's online right now.</p>
          </div>

          <div class="contact-form-card" style="max-width: 640px; margin: 0 auto;">
            <?php if ($contact_submitted): ?>
              <div class="alert alert-success">Thanks, <?= htmlspecialchars($cf_name) ?>! We've got your message and will get back to you soon.</div>
            <?php else: ?>
              <?php if ($contact_form_error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($contact_form_error) ?></div>
              <?php endif; ?>
              <form class="contact-form" method="post">
                <div class="form-grid-2">
                  <div class="form-group">
                    <label for="contact_name">Full Name</label>
                    <input type="text" id="contact_name" name="name" class="form-control" placeholder="Juan Dela Cruz" required>
                  </div>
                  <div class="form-group">
                    <label for="contact_email">Email</label>
                    <input type="email" id="contact_email" name="email" class="form-control" placeholder="you@example.com" required>
                  </div>
                </div>

                <div class="form-grid-2">
                  <div class="form-group">
                    <label for="contact_phone">Phone Number (Optional)</label>
                    <input type="text" id="contact_phone" name="phone" class="form-control" placeholder="+63 900 000 0000">
                  </div>
                  <div class="form-group">
                    <label for="contact_subject">Subject</label>
                    <select id="contact_subject" name="subject" class="form-control">
                      <option value="general">General Inquiry</option>
                      <option value="repair">Repair &amp; Maintenance</option>
                      <option value="printing">Printing Services</option>
                      <option value="networking">Networking &amp; IT Services</option>
                      <option value="consultation">Online Consultation</option>
                      <option value="order">An Existing Order</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label for="contact_message">Message</label>
                  <textarea id="contact_message" name="message" class="form-control" rows="5" placeholder="Tell us what's going on..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Send Message</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- SECTION: CALL TO ACTION -->
      <section class="cta-banner">
        <div class="container">
          <h2>Not sure which service you need?</h2>
          <p>Have a look at everything we offer, or just send us a message above and we'll point you in the right direction.</p>
          <a href="services.php" class="btn-cta">
            VIEW OUR SERVICES
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="4" width="16" height="12" rx="2"></rect>
              <path d="M9 20l3-4h8"></path>
            </svg>
          </a>
        </div>
      </section>

    <?php endif; ?>

  <!-- SECTION: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

  <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
  <?php include __DIR__ . '/../includes/modals/login-signup-modal.php'; ?>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/auth.js"></script>
  <script src="../assets/js/cart.js"></script>
  <script src="../assets/js/contact-chat.js"></script>

    </main>
</body>