<?php
// Always include session check for header
if (!function_exists('getCurrentUser')) {
    require_once 'init.php';
}

// Get current user info
$current_user = getCurrentUser();
$isHeaderLoggedIn = $current_user !== null;
$headerUsername = $isHeaderLoggedIn ? $current_user['username'] : 'Guest';
$headerUserRole = $isHeaderLoggedIn ? $current_user['role'] : 'guest';

/**
 * PROJECT WEB ROOT CALCULATION
 */
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$project_root_physical = str_replace('\\', '/', realpath(dirname(__FILE__) . '/..'));
$current_script_physical = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
$relative_physical = str_replace($project_root_physical, '', $current_script_physical);
$depth = substr_count(ltrim($relative_physical, '/'), '/');

$web_root = $script_name;
for ($i = 0; $i <= $depth; $i++) { $web_root = dirname($web_root); }
$web_root = rtrim(str_replace('\\', '/', $web_root), '/') . '/';
?>
<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<header class="site-header">
    <div class="container nav-bar">
      <a class="brand" href="<?php echo $web_root; ?>home.php" aria-label="YogaMart home">
        <span class="sun"></span>
        <?php 
          // Detect if we are on a shop page
          $isShopPage = (strpos($script_name, '/shop/') !== false);
        ?>
        <span>Yoga<span class="accent <?php echo $isShopPage ? 'shop-accent' : ''; ?>">Mart</span></span>
      </a>

       <!-- Middle: Navigation -->
    <nav class="main-nav" aria-label="Primary">
      <a href="<?php echo $web_root; ?>home.php">Home</a>
      <?php if ($isHeaderLoggedIn): ?>
        <a href="<?php echo $web_root; ?>courses.php">Courses</a>
        <a href="<?php echo $web_root; ?>videos.php">Videos</a>
      <?php else: ?>
        <a href="javascript:void(0)" onclick="showLoginPrompt('courses')">Courses</a>
        <a href="javascript:void(0)" onclick="showLoginPrompt('videos')">Videos</a>
      <?php endif; ?>
      <a href="<?php echo $web_root; ?>about_us.php">About us</a>
      <a href="<?php echo $web_root; ?>shop/shop.php">Shop</a>
      <?php if ($isHeaderLoggedIn && $headerUserRole === 'tutor'): ?>
        <a href="<?php echo $web_root; ?>tutor/tutor_dashboard.php">Tutor Dashboard</a>
      <?php endif; ?>
      <?php if ($isHeaderLoggedIn): ?>
        <a href="<?php echo $web_root; ?>contact.php">Contact us</a>
      <?php else: ?>
        <a href="javascript:void(0)" onclick="showLoginPrompt('contact')">Contact us</a>
      <?php endif; ?>
    </nav>

    <!-- Right: User Menu -->
    <div class="user-menu">
      <a href="<?php echo $web_root; ?>shop/cart.php" id="cart-link" style="position: relative; margin-right: 15px; color: var(--brand); font-size: 1.2rem; text-decoration: none;" title="Shopping Cart">
          <i class="fas fa-shopping-cart"></i>
          <?php 
          $cart_count = 0;
          if (isset($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $qty) { $cart_count += $qty; } }
          ?>
          <span id="cart-count-badge" style="position: absolute; top: -10px; right: -10px; background: #ff4757; color: white; border-radius: 50%; width: 18px; height: 18px; display: <?php echo ($cart_count > 0) ? 'flex' : 'none'; ?>; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; border: 1px solid white;"><?php echo $cart_count; ?></span>
      </a>

      <?php if ($isHeaderLoggedIn): ?>
        <span class="welcome-text"><?php echo htmlspecialchars($headerUsername); ?></span>
        <div class="profile-icon" onclick="toggleMenu()">
          <div class="avatar-circle">
            <?php
            $raw_pic = $current_user['profile_pic'] ?? '';
            $pic_src = 'https://img.icons8.com/color/48/user-male-circle--v1.png';
            if ($raw_pic && filter_var($raw_pic, FILTER_VALIDATE_URL)) { $pic_src = $raw_pic; }
            elseif ($raw_pic) { $pic_src = $web_root . 'content/users/profile_pictures/' . basename($raw_pic); }
            ?>
            <img src="<?php echo htmlspecialchars($pic_src); ?>" alt="Profile" onerror="this.src='https://img.icons8.com/color/48/user-male-circle--v1.png';">
          </div>
        </div>
        <div id="dropdown" class="dropdown">
          <a href="<?php echo $web_root; ?>Profile.php">👤 Profile</a>
          <a href="<?php echo $web_root; ?>shop/order_history.php">📦 Order History</a>
          <?php if ($headerUserRole === 'admin'): ?>
            <a href="<?php echo $web_root; ?>admin/admin-panel.php">🔧 Admin Panel</a>
          <?php endif; ?>
          <a href="<?php echo $web_root; ?>logout.php">🚪 Logout</a>
        </div>
      <?php else: ?>
        <a href="<?php echo $web_root; ?>login-registration.php" class="login-btn">Login / Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Global Toast Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Global Custom Confirm Modal -->
<div id="custom-confirm-overlay" class="custom-confirm-overlay" style="display: none;">
  <div class="custom-confirm-modal">
    <div class="confirm-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="confirm-title" id="confirm-title">Are you sure?</div>
    <div class="confirm-text" id="confirm-text">This action cannot be undone.</div>
    <div class="confirm-btns">
      <button id="confirm-cancel" class="confirm-btn confirm-no"><i class="fas fa-times"></i> Cancel</button>
      <button id="confirm-ok" class="confirm-btn confirm-yes"><i class="fas fa-check"></i> Yes, Proceed</button>
    </div>
  </div>
</div>

<!-- Global Login Required Popup -->
<div id="loginPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 20000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2.5rem; border-radius: 20px; text-align: center; max-width: 400px; margin: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
    <h3 style="color: #ff6b6b; margin-bottom: 1rem; font-size: 1.5rem;">🔒 Login Required</h3>
    <p id="loginPopupText" style="color: #666; margin-bottom: 2rem; line-height: 1.5;">You need to login to access this feature.</p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <a href="<?php echo $web_root; ?>login-registration.php" style="background: #4a7c59; color: white; padding: 0.8rem 1.8rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: 0.3s;">Login / Register</a>
      <button onclick="document.getElementById('loginPopup').style.display='none'" style="background: #f0f0f0; color: #666; border: none; padding: 0.8rem 1.8rem; border-radius: 25px; cursor: pointer; font-weight: 600;">Cancel</button>
    </div>
  </div>
</div>

<div id="top-progress-bar" style="position: fixed; top: 0; left: 0; height: 3px; background: #4a7c59; width: 0%; z-index: 10001; transition: width 0.3s ease, opacity 0.3s ease; opacity: 0;"></div>

<!-- Main content wrapper for SPA navigation -->
<main id="main-content">

<script id="yoga-spa-logic">
  // Global Notification System
  window.showToast = function(message, type = 'info', duration = 5000) {
      const container = document.getElementById('toast-container');
      if (!container) return;
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      
      let icon = 'info-circle';
      if (type === 'success') icon = 'check-circle';
      if (type === 'error') icon = 'exclamation-circle';
      if (type === 'warning') icon = 'exclamation-triangle';
      
      toast.innerHTML = `
          <i class="fas fa-${icon}"></i>
          <div class="toast-message">${message}</div>
      `;
      
      container.appendChild(toast);
      setTimeout(() => toast.classList.add('show'), 10);
      setTimeout(() => {
          toast.classList.remove('show');
          setTimeout(() => toast.remove(), 300);
      }, duration);
  };

  // Override window.alert
  window.alert = (msg) => {
      const type = (msg.toLowerCase().includes('success') || msg.toLowerCase().includes('successful')) ? 'success' : 
                   (msg.toLowerCase().includes('error') || msg.toLowerCase().includes('fail') || msg.toLowerCase().includes('invalid') || msg.toLowerCase().includes('required')) ? 'error' : 'info';
      window.showToast(msg, type);
  };

  // Custom Confirm Dialog
  window.customConfirm = function(title, text) {
      return new Promise((resolve) => {
          const overlay = document.getElementById('custom-confirm-overlay');
          const titleEl = document.getElementById('confirm-title');
          const textEl = document.getElementById('confirm-text');
          const okBtn = document.getElementById('confirm-ok');
          const cancelBtn = document.getElementById('confirm-cancel');
          
          if (!overlay) { resolve(confirm(text)); return; }

          titleEl.textContent = title || 'Are you sure?';
          textEl.textContent = text || 'This action cannot be undone.';
          
          overlay.style.display = 'flex';
          setTimeout(() => overlay.classList.add('show'), 10);
          
          const close = (result) => {
              overlay.classList.remove('show');
              setTimeout(() => {
                  overlay.style.display = 'none';
                  resolve(result);
              }, 300);
          };
          
          okBtn.onclick = () => close(true);
          cancelBtn.onclick = () => close(false);
          overlay.onclick = (e) => { if (e.target === overlay) close(false); };
      });
  };

  // Global Confirm Interceptor
  if (!window.confirmInterceptorAdded) {
      document.addEventListener('click', async (e) => {
          const target = e.target.closest('[onclick*="confirm("]');
          if (target && !target.dataset.confirmIntercepted) {
              e.preventDefault();
              e.stopImmediatePropagation();
              const match = target.getAttribute('onclick').match(/confirm\(['"](.*?)['"]\)/);
              const message = match ? match[1] : 'Are you sure you want to proceed?';
              const confirmed = await window.customConfirm('Action Required', message);
              if (confirmed) {
                  target.dataset.confirmIntercepted = 'true';
                  if (target.tagName === 'A') window.location.href = target.href;
                  else target.click();
              }
          }
      }, true);
      window.confirmInterceptorAdded = true;
  }

  (function() {
      const progressBar = document.getElementById('top-progress-bar');
      let currentAbortController = null;
      let progressTimeout, opacityTimeout;
      let lastNavigatedUrl = window.location.href;

      function updateProgress(p) { 
          if (p < 100) {
              clearTimeout(progressTimeout);
              clearTimeout(opacityTimeout);
          }
          if (progressBar) {
              progressBar.style.opacity = '1'; 
              progressBar.style.width = p + '%';
              if (p >= 100) {
                  progressTimeout = setTimeout(() => { 
                      progressBar.style.opacity = '0'; 
                      opacityTimeout = setTimeout(() => progressBar.style.width = '0%', 300); 
                  }, 300);
              }
          }
      }

      async function navigateTo(url, push = true) {
          if (url === lastNavigatedUrl && push) return;
          
          if (currentAbortController) {
              currentAbortController.abort();
          }

          currentAbortController = new AbortController();
          const signal = currentAbortController.signal;
          lastNavigatedUrl = url;

          updateProgress(30);
          try {
              const res = await fetch(url, { signal });
              if (signal.aborted) return; 

              updateProgress(70);
              const finalUrl = res.url;
              const html = await res.text();
              if (signal.aborted) return; 

              const doc = new DOMParser().parseFromString(html, 'text/html');
              if (signal.aborted) return; 

              const newMain = doc.querySelector('#main-content');
              const currMain = document.querySelector('#main-content');
              
              if (newMain && currMain) {
                  if (signal.aborted) return; 

                  if (push) history.pushState({ url: finalUrl }, doc.title, finalUrl);
                  
                  currMain.innerHTML = newMain.innerHTML;
                  document.title = doc.title;
                  
                  const scripts = doc.querySelectorAll('script');
                  scripts.forEach(s => {
                      if (s.id === 'yoga-spa-logic' || (s.src && document.querySelector(`script[src="${s.src}"]`))) return;
                      
                      const ns = document.createElement('script');
                      Array.from(s.attributes).forEach(a => ns.setAttribute(a.name, a.value));
                      ns.appendChild(document.createTextNode(s.innerHTML));
                      document.body.appendChild(ns);
                  });

                  // Update styles
                  const newLinks = doc.querySelectorAll('link[rel="stylesheet"]');
                  newLinks.forEach(l => {
                      const href = l.getAttribute('href');
                      if (href) {
                          const baseHref = href.split('?')[0];
                          const existing = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
                                              .find(el => (el.getAttribute('href') || '').split('?')[0] === baseHref);
                          
                          if (!existing) {
                              const nl = document.createElement('link');
                              Array.from(l.attributes).forEach(a => nl.setAttribute(a.name, a.value));
                              document.head.appendChild(nl);
                          } else if (existing.getAttribute('href') !== href) {
                              // Update version if it changed
                              existing.setAttribute('href', href);
                          }
                      }
                  });

                  // Update logo color based on URL
                  const logoAccent = document.querySelector('.brand .accent');
                  if (logoAccent) {
                      if (finalUrl.includes('/shop/')) {
                          logoAccent.classList.add('shop-accent');
                      } else {
                          logoAccent.classList.remove('shop-accent');
                      }
                  }

                  // Update inline styles
                  const newStyles = doc.querySelectorAll('style');
                  // Remove old page-specific styles to prevent conflicts
                  document.querySelectorAll('style[data-page-style]').forEach(s => s.remove());
                  
                  newStyles.forEach(s => {
                      const ns = document.createElement('style');
                      ns.setAttribute('data-page-style', 'true');
                      ns.textContent = s.textContent;
                      document.head.appendChild(ns);
                  });
                  
                  window.scrollTo(0, 0);
                  updateProgress(100);
              } else { 
                  window.location.href = finalUrl; 
              }
          } catch (e) { 
              if (e.name === 'AbortError') return;
              console.error(e); 
              window.location.href = url; 
          } finally {
              if (currentAbortController && currentAbortController.signal === signal) {
                  currentAbortController = null;
              }
          }
      }

      if (!window.spaListenerAdded) {
          document.addEventListener('click', e => {
              const l = e.target.closest('a');
              if (!l) return;
              if (l.hostname !== window.location.hostname || !l.href || l.href.startsWith('#') || l.href.startsWith('javascript') || l.onclick || l.getAttribute('target')) return;

              const h = l.getAttribute('href');
              const isTutorOrAdmin = h && (h.includes('tutor/') || h.includes('admin/') || window.location.pathname.includes('/tutor/') || window.location.pathname.includes('/admin/'));

              if (!isTutorOrAdmin) {
                  e.preventDefault(); 
                  navigateTo(l.href);
              }
          });
          window.addEventListener('popstate', e => { 
              if (e.state && e.state.url) navigateTo(e.state.url, false); 
              else window.location.reload(); 
          });
          window.spaListenerAdded = true;
      }
  })();

  function toggleMenu() { const d = document.getElementById("dropdown"); if (d) d.style.display = (d.style.display === "flex") ? "none" : "flex"; }
  window.addEventListener("click", e => { const d = document.getElementById("dropdown"); if (d && !e.target.closest(".user-menu")) d.style.display = "none"; });
  
  function showLoginPrompt(type) {
    const p = document.getElementById('loginPopup');
    const t = document.getElementById('loginPopupText');
    if (!p) return;
    if (type === 'courses') t.textContent = 'Please login to access our premium courses and programs.';
    else if (type === 'videos') t.textContent = 'Join our community to access our complete yoga video library.';
    else if (type === 'contact') t.textContent = 'Please login to contact our instructors directly.';
    p.style.display = 'flex';
  }

  function showLoading(text = 'Processing...') {
    const overlay = document.getElementById('loadingOverlay');
    const textEl = document.getElementById('loadingText');
    if (overlay && textEl) {
      textEl.textContent = text;
      overlay.classList.add('show');
    }
  }

  function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
      overlay.classList.remove('show');
    }
  }

  // Show loading on standard form submissions
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form && !form.getAttribute('data-no-loading')) {
      showLoading('Please wait...');
    }
  });

  function updateCartCount(count) {
    const badge = document.getElementById('cart-count-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = (count > 0) ? 'flex' : 'none';
    }
  }
</script>

<!-- Global Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner"></div>
    <div class="loading-text" id="loadingText">Processing...</div>
</div>