# Timosa Tech - Web Application

A full-stack e-commerce and IT services web application built with PHP, MySQL, and JavaScript. Timosa Tech provides an online platform for hardware product sales, commercial printing requests, repair diagnostics, and network enterprise services.

---

## Features

### Public & E-Commerce
* **Dynamic Homepage & Service Showcase:** Highlights company specialization, featured products, key operational features, and live performance metrics[cite: 1].
* **Product Catalog & Quick Views:** Interactive shop front with category filtering, detailed product modal popups, and quick add-to-cart capabilities[cite: 1].
* **Shopping Cart & Checkout System:** Real-time cart counter, dynamic item management, session-persistent carts, dynamic checkout handling, and order confirmation tracking[cite: 1, 2].
* **User Accounts & Profiles:** Secure authentication (login/signup) modals, session state management, user profile controls, and order history tracking[cite: 1, 2].

### Admin & System Management
* **Admin Portal (`admin/`):** Dedicated administrative dashboard for product inventory management, order fulfillment tracking, and system activity logging.
* **Site Control & Maintenance:** Dynamically toggle page visibilities and manage custom maintenance modes (`page-veil`) across the platform[cite: 1, 2].

---

## Tech Stack

* **Backend:** PHP 8.2.12
* **Database:** MySQL / MariaDB via PDO (`pdo`)[cite: 1, 2]
* **Frontend:** HTML5, CSS3 (CSS Variables, Modular Stylesheet Architecture), Vanilla JavaScript (ES6)[cite: 1, 2]
* **Local Server Environment:** XAMPP / WampServer / Apache[cite: 1]

---

## Directory & File Structure

```text
TimosaTech/
├── .gitignore
├── index.php
├── README.md
│
├── admin/
│   ├── admin-portal.php
│   ├── assets/
│   │   └── css/
│   └── includes/
│       └── functions/
│           ├── activity-log-admin-functions.php
│           ├── order-admin-functions.php
│           └── product-admin-functions.php
│
├── assets/
│   ├── css/
│   │   ├── about.css
│   │   ├── admin-portal.css
│   │   ├── cart-modal.css
│   │   ├── checkout.css
│   │   ├── footer.css
│   │   ├── header.css
│   │   ├── master.css
│   │   ├── order-confirmation.css
│   │   ├── page-veil.css
│   │   ├── profile.css
│   │   ├── services.css
│   │   ├── shop-cards.css
│   │   ├── styles.css
│   │   └── variables.css
│   ├── images/
│   │   ├── products/
│   │   ├── office-laserjet.png
│   │   ├── server-room.png
│   │   ├── timosa-router.png
│   │   ├── TimosaTechLogo.png
│   │   └── workstation-rig.png
│   └── js/
│       ├── auth.js
│       ├── cart.js
│       └── product-modal.js
│
├── components/
│   ├── footer.php
│   └── header.php
│
├── config/
│   └── db.php
│
├── helpers/
│   └── icons.php
│
├── includes/
│   ├── functions/
│   │   ├── cart-functions.php
│   │   ├── order-functions.php
│   │   ├── site-control-functions.php
│   │   └── user-profile-functions.php
│   ├── handlers/
│   │   ├── cart-handler.php
│   │   ├── login-signup-handler.php
│   │   ├── logout-handler.php
│   │   ├── order-handler.php
│   │   └── profile-handler.php
│   └── modals/
│       ├── cart-modal.php
│       ├── login-signup-modal.php
│       └── product-modal.php
│
└── pages/
    ├── about.php
    ├── checkout.php
    ├── contact.php
    ├── homepage.php
    ├── order-confirmation.php
    ├── profile.php
    ├── services.php
    └── shop.php