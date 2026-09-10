<?php

$footer_links = [
    "Quick Links" => [
        ["Home", "/TimosaTech/pages/homepage.php"],
        ["Shop Hardware", "/TimosaTech/pages/shop.php"],
        ["About Us", "/TimosaTech/pages/about.php"],
        ["Contact Us", "/TimosaTech/pages/contact.php"],
        ["Privacy Policy", "#"],
    ],
    "Services" => [
        ["Product Sales", "/TimosaTech/pages/services.php"],
        ["Printing Services", "/TimosaTech/pages/services.php"],
        ["Diagnostic Repairs", "/TimosaTech/pages/services.php"],
        ["Managed IT Solutions", "/TimosaTech/pages/services.php"],
    ],
];

?>

<footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col brand-col">
          <div class="logo">
            <img class="img-logo" src="/TimosaTech/assets/images/TimosaTechLogo.png">
            <a class="logoname1">TIMOSA</a><a class="logoname2">TECH</a>
          </div>
          <p>Premium enterprise technology, high-quality printing solutions, computer hardware, and diagnostics support.</p>
          <div class="social-links">
            <a href="https://web.facebook.com/people/Timosa-Tech/61590409082212/" aria-label="Facebook"><?php icon('facebook'); ?></a>
            <a href="https://x.com" aria-label="Twitter"><?php icon('twitter'); ?></a>
            <a href="https://www.instagram.com" aria-label="Instagram"><?php icon('instagram'); ?></a>
            <a href="https://www.linkedin.com" aria-label="LinkedIn"><?php icon('linkedin'); ?></a>
          </div>
        </div>

        <?php foreach ($footer_links as $heading => $links): ?>
          <div class="footer-col">
            <h4><?= htmlspecialchars($heading) ?></h4>
            <ul>
              <?php foreach ($links as [$label, $href]): ?>
                <li><a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>

        <div class="footer-col">
          <h4>Contact Info</h4>
          <p>101 Tech Junction, Suite A</p>
          <p>support@timosatech.com</p>
          <p>+63 977 611 8718</p>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?= date("Y") ?> TimosaTech. All rights reserved. All specifications subject to technical review.</p>
      </div>
    </div>
</footer>