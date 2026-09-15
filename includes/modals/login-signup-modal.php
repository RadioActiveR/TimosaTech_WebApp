<?php
$modal_hidden = isset($pdo) && function_exists('is_modal_hidden') && is_modal_hidden($pdo, 'modal_auth');
/* INFO: Sign Up / Log In modal
 * Included at the bottom of homepage.php. Submits to auth-handler.php,
 * which validates against MySQL and redirects back here. homepage.php
 * reads $auth_error / $auth_tab from the session (set by the handler on
 * failure) BEFORE this file is included, so this file can use them
 * directly to reopen on the right tab and show the right error — this
 * works even with JS disabled.
 */
$active_tab = $auth_tab ?? 'login';
$is_open    = isset($auth_tab);
?>
<div class="auth-overlay<?= $is_open ? ' active' : '' ?>" id="authOverlay">
  <div class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
    <button type="button" class="auth-close" id="authClose" aria-label="Close">&times;</button>

    <?php if ($modal_hidden): ?>
      <div class="center-container">
        <h2 id="authModalTitle" class="hidden"> HIDDEN </h2>
        <h3 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h3>
      </div>
    <?php else: ?>

    <div class="auth-tabs">
      <button type="button" class="auth-tab<?= $active_tab === 'login' ? ' active' : '' ?>" data-target="login">Log In</button>
      <button type="button" class="auth-tab<?= $active_tab === 'signup' ? ' active' : '' ?>" data-target="signup">Sign Up</button>
    </div>

    <!--INFO: Log In -->
    <form class="auth-form<?= $active_tab === 'login' ? ' active' : '' ?>" id="loginForm" data-form="login" method="post" action="../includes/handlers/login-signup-handler.php">
      <h2 id="authModalTitle">Welcome back</h2>
      <p class="auth-subtext">Log in to manage orders and support tickets.</p>

      <?php if ($active_tab === 'login' && !empty($auth_error)): ?>
        <p class="auth-error"><?= htmlspecialchars($auth_error) ?></p>
      <?php endif; ?>

      <input type="hidden" name="action" value="login">
      <input type="hidden" name="redirect_to" class="auth-redirect-to" value="<?= htmlspecialchars($auth_old_input['redirect_to'] ?? '') ?>">

      <div class="auth-field">
        <label for="loginIdentity">Email or Username</label>
        <input type="text" 
            id="loginIdentity" 
            name="identity" 
            placeholder="you@company.com or username" 
            value="<?= htmlspecialchars($auth_old_input['identity'] ?? '') ?>" 
            required>
    </div>
      <div class="auth-field">
        <label for="loginPassword">Password</label>
        <div class="password-wrapper">
          <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
          <button type="button" class="toggle-password" aria-label="Toggle password visibility">
            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle class="eye-open" cx="12" cy="12" r="3"/>
              <line class="eye-slash" x1="3" y1="3" x2="21" y2="21"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="auth-row-between">
        <label><input type="checkbox" name="remember"> Remember me</label>
        <a href="#">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary auth-submit">Log In</button>

      <p class="auth-switch">Don't have an account? <button type="button" data-target="signup">Sign up</button></p>
    </form>

    <!--INFO: Sign Up -->
    <form class="auth-form<?= $active_tab === 'signup' ? ' active' : '' ?>" id="signupForm" data-form="signup" method="post" action="../includes/handlers/login-signup-handler.php">
      <h2>Create your account</h2>
      <p class="auth-subtext">Get faster checkout and order tracking.</p>

      <?php if ($active_tab === 'signup' && !empty($auth_error)): ?>
        <p class="auth-error"><?= htmlspecialchars($auth_error) ?></p>
      <?php endif; ?>

      <input type="hidden" name="action" value="signup">
      <input type="hidden" name="redirect_to" class="auth-redirect-to" value="<?= htmlspecialchars($auth_old_input['redirect_to'] ?? '') ?>">

      <div class="auth-field">
        <label for="signupUsername">Username</label>
        <input type="text" 
            id="signupUsername" 
            name="username" 
            placeholder="janedela23" 
            value="<?= htmlspecialchars($auth_old_input['username'] ?? '') ?>" 
            required>
      </div>

      <div class="auth-field">
        <label for="signupEmail">Email</label>
        <input type="email" 
            id="signupEmail" 
            name="email" 
            placeholder="you@company.com" 
            value="<?= htmlspecialchars($auth_old_input['email'] ?? '') ?>" 
            required>
      </div>
      
      <div class="auth-field">
        <label for="signupPassword">Password</label>
        <div class="password-wrapper">
          <input type="password" id="signupPassword" name="password" placeholder="••••••••" minlength="8" required>
          <button type="button" class="toggle-password" aria-label="Toggle password visibility">
            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle class="eye-open" cx="12" cy="12" r="3"/>
              <line class="eye-slash" x1="3" y1="3" x2="21" y2="21"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="auth-field">
        <label for="signupConfirm">Confirm password</label>
        <div class="password-wrapper">
          <input type="password" id="signupConfirm" name="confirm_password" placeholder="••••••••" minlength="8" required>
          <button type="button" class="toggle-password" aria-label="Toggle password visibility">
            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle class="eye-open" cx="12" cy="12" r="3"/>
              <line class="eye-slash" x1="3" y1="3" x2="21" y2="21"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary auth-submit">Create Account</button>

      <p class="auth-switch">Already have an account? <button type="button" data-target="login">Log in</button></p>
    </form>

    <?php endif; ?>

  </div>
</div>