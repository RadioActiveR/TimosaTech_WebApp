# Timosa Tech - Web Application

A full-stack e-commerce and IT services web application built with PHP, MySQL, and JavaScript. Timosa Tech provides an online platform for hardware product sales, commercial printing requests, repair diagnostics, and network enterprise services.

---

## Features

### Public & E-Commerce
* **Dynamic Homepage & Service Showcase:** Highlights company specialization, featured products, key operational features, and live performance metrics.
* **Product Catalog & Quick Views:** Interactive shop front with category filtering, detailed product modal popups with multi-image galleries, and quick add-to-cart capabilities.
* **Shopping Cart & Checkout System:** Real-time cart counter, dynamic item management, session-persistent carts, dynamic checkout handling, and order confirmation tracking.
* **User Accounts & Profiles:** Secure authentication (login/signup) modals, session state management, user profile controls, and order history tracking.
* **Context-Aware Post-Login Redirects:** Logging in from the header returns visitors to the page they were on; logging in from a guest add-to-cart prompt (homepage or shop) sends them to the shop page to continue.
* **Live Support Chat:** A local Ollama-powered bot answers visitor questions by default and escalates to a human admin the moment a visitor asks for one. Available both as a site-wide floating widget and as a larger dedicated panel on the Contact page, alongside a traditional written contact form that feeds into the same conversation queue.

### Admin & System Management
* **Admin Portal (`admin/`):** Dedicated administrative dashboard for product inventory management, order fulfillment tracking, a metrics overview, and system activity logging.
* **Metrics Dashboard:** At-a-glance totals for users, products, and orders, plus revenue metrics — Total Revenue (completed orders only), Potential Revenue (orders still in flight), and Shop/Hardware Revenue.
* **Customer Support Chat Tab:** Admins can view and reply to live chat conversations, hand a conversation back to the bot, and delete conversations for cleanup.
* **Site Control & Maintenance:** Dynamically toggle visibility across three categories — Pages, Modals, and Widgets (including the support chat widget) — with per-item and "hide all" master switches, powering a custom maintenance mode ("Content Veil") across the platform.

---

## Tech Stack

* **Backend:** PHP 8.2.12
* **Database:** MySQL / MariaDB via PDO (`pdo`)
* **AI / Chat:** Local Ollama LLM for the support chat bot, with human hand-off via the admin dashboard
* **Frontend:** HTML5, CSS3 (CSS Variables, Modular Stylesheet Architecture), Vanilla JavaScript (ES6)
* **Local Server Environment:** XAMPP / WampServer / Apache

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
│   │   ├── css/
│   │   │   └── admin-portal.css
│   │   └── js/
│   │       └── admin-chat.js
│   └── includes/
│       ├── functions/
│       │   ├── activity-log-admin-functions.php
│       │   ├── order-admin-functions.php
│       │   └── product-admin-functions.php
│       └── handlers/
│           └── admin-chat-handler.php
│
├── assets/
│   ├── css/
│   │   ├── about.css
│   │   ├── cart-modal.css
│   │   ├── chat-widget.css
│   │   ├── checkout.css
│   │   ├── contact.css
│   │   ├── content-veil.css
│   │   ├── footer.css
│   │   ├── header.css
│   │   ├── master.css
│   │   ├── order-confirmation.css
│   │   ├── product-modal.css
│   │   ├── profile.css
│   │   ├── services.css
│   │   ├── shop-cards.css
│   │   ├── shop.css
│   │   ├── styles.css
│   │   └── variables.css
│   ├── images/
│   │   ├── office-laserjet.png
│   │   ├── server-room.png
│   │   ├── timosa-router.png
│   │   ├── TimosaTechLogo.png
│   │   ├── workstation-rig.png
│   │   └── products/
│   │       ├── p1_d53e269a.png
│   │       ├── p2_7b98df1f.png
│   │       ├── p3_ec9866da.png
│   │       ├── p4_ab917c25.png
│   │       ├── p5_bf01571b.jpg
│   │       ├── p6_28ff6dd2.jpg
│   │       ├── p7_248f6631.jpg
│   │       ├── p8_c8517a42.jpg
│   │       ├── p10_fb3c9884.jpg
│   │       ├── p11_3ae73696.jpg
│   │       ├── p12_f5d9a02b.jpg
│   │       ├── p13_577ba792.jpg
│   │       ├── p14_071e46f7.jpg
│   │       ├── p15_9d460716.png
│   │       ├── p16_6984d8ac.png
│   │       ├── p905b66bfb7a7.jpg
│   │       └── pa31b6668598a.jpg
│   └── js/
│       ├── auth.js
│       ├── cart.js
│       ├── chat-widget.js
│       ├── contact-chat.js
│       ├── copy-order-id.js
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
│   │   ├── chat-functions.php
│   │   ├── ollama-functions.php
│   │   ├── order-functions.php
│   │   ├── product-image-functions.php
│   │   ├── site-control-functions.php
│   │   └── user-profile-functions.php
│   ├── handlers/
│   │   ├── cart-handler.php
│   │   ├── chat-handler.php
│   │   ├── login-signup-handler.php
│   │   ├── logout-handler.php
│   │   ├── order-handler.php
│   │   └── profile-handler.php
│   ├── modals/
│   │   ├── cart-modal.php
│   │   ├── login-signup-modal.php
│   │   └── product-modal.php
│   └── widgets/
│       ├── chat-widget.php
│       └── contact-chat-panel.php
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
```