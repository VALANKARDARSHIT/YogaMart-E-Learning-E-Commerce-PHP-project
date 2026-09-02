<?php
// Include session check for authentication
require_once 'includes/init.php';
$isLoggedIn = checkSession();
$user = getUserInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us - YogaMart</title>
  <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
  <style>
    .contact-image {
      width: 100%;
      height: 150px;
      background: linear-gradient(135deg, #74b9ff, #0984e3);
      border-radius: 12px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      position: relative;
      overflow: hidden;
    }
    .contact-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(116, 185, 255, 0.1), rgba(9, 132, 227, 0.1));
      z-index: 1;
    }
    .contact-image .emoji {
      position: relative;
      z-index: 2;
    }
    .faq-image {
      width: 100%;
      height: 100px;
      background: linear-gradient(135deg, #fd79a8, #e84393);
      border-radius: 12px;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      position: relative;
      overflow: hidden;
    }
    .faq-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(253, 121, 168, 0.1), rgba(232, 67, 147, 0.1));
      z-index: 1;
    }
    .faq-image .emoji {
      position: relative;
      z-index: 2;
    }
    
    /* Login Required Overlay */
    .login-required-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    
    .login-prompt {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      text-align: center;
      max-width: 400px;
      margin: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .login-prompt h3 {
      color: #2d5a3d;
      margin-bottom: 1rem;
    }
    
    .login-prompt p {
      color: #666;
      margin-bottom: 1.5rem;
    }
    
    .btn-login {
      background: #4a7c59;
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 25px;
      text-decoration: none;
      display: inline-block;
      margin-right: 1rem;
      transition: background 0.3s;
    }
    
    .btn-login:hover {
      background: #3d6b4a;
    }
    
    .btn-home {
      background: #f0f0f0;
      color: #666;
      padding: 0.75rem 1.5rem;
      border-radius: 25px;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s;
    }
    
    .btn-home:hover {
      background: #e0e0e0;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <?php include 'includes/header.php'; ?>
  
  <?php if (!$isLoggedIn): ?>
  <!-- Login Required Overlay -->
  <div class="login-required-overlay">
    <div class="login-prompt">
      <h3>📞 Login Required</h3>
      <p>Please log in to contact us and get personalized support for your yoga journey!</p>
      <a href="login-registration.php" class="btn-login">Login / Register</a>
      <a href="home.php" class="btn-home">Back to Home</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Hero Section -->
  <section class="hero">
    <div class="container">
      <div class="hero__inner">
        <div class="hero__text">
          <h1>Get in Touch with <span class="accent">YogaMart</span></h1>
          <p>Ready to start your yoga journey? Have questions about our classes? We'd love to hear from you. Reach out and let's connect!</p>
          <div class="badges">
            <span class="badge">📞 Quick Response</span>
            <span class="badge">💬 Personal Support</span>
            <span class="badge">🤝 Community Focused</span>
          </div>
        </div>
        <div class="hero__art">
          <div class="blob b1"></div>
          <div class="blob b2"></div>
          <div class="blob b3"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <main>
    <section class="section">
      <div class="container">
        <header class="section__head">
          <h2>Contact Us</h2>
          <p class="muted">We'd love to hear from you ✨</p>
        </header>

        <div class="grid" style="grid-template-columns: 2fr 1fr; gap: var(--gap);">
          <!-- Enhanced Contact Form -->
          <div class="card" style="padding: 24px;">
            <div class="contact-image">
              <span class="emoji">📬</span>
            </div>
            <form id="contactForm" data-no-loading="true">
            <h3>Send us a message</h3>
            <div style="margin-bottom: 16px;">
              <label for="name">Full Name *</label>
              <input type="text" id="name" name="name" required style="width:100%; padding:12px; border:2px solid #ffd166; border-radius: var(--radius); margin-top:4px;" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
            </div>
            <div style="margin-bottom: 16px;">
              <label for="email">Email Address *</label>
              <input type="email" id="email" name="email" required style="width:100%; padding:12px; border:2px solid #ffd166; border-radius: var(--radius); margin-top:4px; background-color: <?php echo $isLoggedIn ? '#f0f0f0' : 'white'; ?>;" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" <?php echo $isLoggedIn ? 'readonly' : ''; ?>>
              <?php if ($isLoggedIn): ?>
                <small style="color: #666;">This is your registered email address.</small>
              <?php endif; ?>
            </div>
            <div style="margin-bottom: 16px;">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" style="width:100%; padding:12px; border:2px solid #ffd166; border-radius: var(--radius); margin-top:4px;">
            </div>
            <div style="margin-bottom: 16px;">
              <label for="subject">Subject *</label>
              <select id="subject" name="subject" required style="width:100%; padding:12px; border:2px solid #ffd166; border-radius: var(--radius); margin-top:4px;">
                <option value="">Select a topic</option>
                <option value="class-info">Class Information</option>
                <option value="membership">Membership & Pricing</option>
                <option value="private-session">Private Sessions</option>
                <option value="events">Workshops & Events</option>
                <option value="feedback">Feedback & Suggestions</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div style="margin-bottom: 16px;">
              <label for="message">Message *</label>
              <textarea id="message" name="message" rows="6" required style="width:100%; padding:12px; border:2px solid #ffd166; border-radius: var(--radius); margin-top:4px; resize: vertical;" placeholder="Tell us how we can help you..."></textarea>
            </div>
            <div style="margin-bottom: 16px;">
              <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="newsletter" style="margin:0;">
                <span>Subscribe to our newsletter for updates on classes and events</span>
              </label>
            </div>
            <button type="submit" class="btn btn--primary" style="margin-top:10px;" id="submitBtn">Send Message</button>
            </form>
          </div>

          <!-- Enhanced Contact Info -->
          <div class="card" style="padding: 24px;">
            <div class="contact-image">
              <span class="emoji">📱</span>
            </div>
            <h3>Get in touch</h3>
            <div style="margin-bottom: 20px;">
              <h4>📍 Visit Our Studio</h4>
              <p><strong>Address:</strong><br>123 Yoga Street<br>Wellness District<br>Mumbai, Maharashtra 400001<br>India</p>
            </div>
            <div style="margin-bottom: 20px;">
              <h4>📞 Contact Information</h4>
              <p><strong>Phone:</strong><br>+91 98765 43210</p>
              <p><strong>Email:</strong><br>hello@yogamart.com</p>
              <p><strong>Emergency:</strong><br>+91 98765 43211</p>
            </div>
            <div style="margin-bottom: 20px;">
              <h4>🕒 Studio Hours</h4>
              <p><strong>Monday - Friday:</strong><br>6:00 AM - 9:00 PM</p>
              <p><strong>Saturday:</strong><br>7:00 AM - 8:00 PM</p>
              <p><strong>Sunday:</strong><br>8:00 AM - 6:00 PM</p>
            </div>
            <div style="margin-bottom: 20px;">
              <h4>🚗 Parking</h4>
              <p>Free parking available for all students. Street parking also available nearby.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <!-- <section class="section section--alt">
      <div class="container">
        <div class="section__head">
          <h2>Find Us</h2>
          <p class="muted">Located in the heart of Wellness District</p>
        </div>
        <div class="card">
          <div style="width: 100%; height: 300px; background: linear-gradient(135deg, #ffecd2, #fcb69f); border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <p style="color: #6b7280; font-style: italic;">📍 Interactive Map Coming Soon<br>123 Yoga Street, Wellness District, Mumbai</p>
          </div>
          <div style="text-align: center;">
            <h4>Getting Here</h4>
            <p><strong>By Metro:</strong> 5-minute walk from Wellness Station (Blue Line)</p>
            <p><strong>By Bus:</strong> Routes 123, 456, and 789 stop nearby</p>
            <p><strong>By Car:</strong> 15-minute drive from city center via NH-48</p>
          </div>
        </div>
      </div>
    </section> -->

    <!-- FAQ Section -->
    <section class="section">
      <div class="container">
        <div class="section__head">
          <h2>Frequently Asked Questions</h2>
          <p class="muted">Quick answers to common questions</p>
        </div>
        <div class="grid grid--cards">
          <div class="card">
            <div class="faq-image">
              <span class="emoji">🧘‍♀️</span>
            </div>
            <div class="card__body">
              <h3>Do I need to bring my own mat?</h3>
              <p>We provide mats for all classes, but you're welcome to bring your own if you prefer. We also have blocks, straps, and other props available.</p>
            </div>
          </div>
          <div class="card">
            <div class="faq-image">
              <span class="emoji">🌱</span>
            </div>
            <div class="card__body">
              <h3>I'm a complete beginner. Is that okay?</h3>
              <p>Absolutely! We welcome students of all levels. Our beginner-friendly classes and experienced instructors will guide you every step of the way.</p>
            </div>
          </div>
          <div class="card">
            <div class="faq-image">
              <span class="emoji">👗</span>
            </div>
            <div class="card__body">
              <h3>What should I wear?</h3>
              <p>Comfortable, stretchy clothing that allows free movement. Most students wear leggings or shorts with a t-shirt or tank top. Bare feet are preferred.</p>
            </div>
          </div>
          <div class="card">
            <div class="faq-image">
              <span class="emoji">🤰</span>
            </div>
            <div class="card__body">
              <h3>Can I join a class if I'm pregnant?</h3>
              <p>Yes, we offer prenatal yoga classes. Please inform your instructor of your pregnancy and consult with your healthcare provider first.</p>
            </div>
          </div>
          <div class="card">
            <div class="faq-image">
              <span class="emoji">👥</span>
            </div>
            <div class="card__body">
              <h3>Do you offer private sessions?</h3>
              <p>Yes! We offer one-on-one private sessions tailored to your specific needs and goals. Contact us to schedule.</p>
            </div>
          </div>
          <div class="card">
            <div class="faq-image">
              <span class="emoji">📅</span>
            </div>
            <div class="card__body">
              <h3>What's your cancellation policy?</h3>
              <p>We require 2-hour notice for class cancellations. Late cancellations or no-shows may be charged the full class fee.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Social Media & Newsletter -->
    <section class="section section--alt">
      <div class="container">
        <div style="text-align: center; padding: 36px 0;">
          <h2>Stay Connected</h2>
          <p class="muted" style="margin-bottom: 24px;">Follow us for daily inspiration, class updates, and wellness tips</p>
          <div class="cta-row">
            <a href="#" class="btn btn--ghost">📘 Facebook</a>
            <a href="#" class="btn btn--ghost">📷 Instagram</a>
            <a href="#" class="btn btn--ghost">🐦 Twitter</a>
            <a href="#" class="btn btn--ghost">📺 YouTube</a>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <?php include 'includes/footer.php';?>

  <script>
    document.getElementById('contactForm').addEventListener('submit', function(event) {
      event.preventDefault();

      const form = event.target;
      const submitBtn = document.getElementById('submitBtn');
      const formData = new FormData(form);

      // Simple form validation
      if (!formData.get('name') || !formData.get('email') || !formData.get('subject') || !formData.get('message')) {
        if (typeof showGlobalToast === 'function') {
          showGlobalToast('Please fill in all required fields.', 'error');
        } else {
          alert('Please fill in all required fields.');
        }
        return;
      }

      // Disable button and show loading state
      const originalBtnText = submitBtn.innerText;
      submitBtn.disabled = true;
      submitBtn.innerText = 'Sending...';

      // Send AJAX request
      fetch('process_contact.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data && data.status === 'success') {
          const successMsg = data.message || 'Thank you for your message! We\'ll get back to you within 24 hours.';
          if (typeof showGlobalToast === 'function') {
            showGlobalToast(successMsg, 'success');
          } else {
            alert(successMsg);
          }
          form.reset();
        } else {
          const errorMsg = (data && (data.message || data.error)) || 'Sorry, there was an error processing your request.';
          if (typeof showGlobalToast === 'function') {
            showGlobalToast(errorMsg, 'error');
          } else {
            alert(errorMsg);
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        const errorMsg = 'An unexpected error occurred. Please try again later.';
        if (typeof showGlobalToast === 'function') {
          showGlobalToast(errorMsg, 'error');
        } else {
          alert(errorMsg);
        }
      })
      .finally(() => {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerText = originalBtnText;
      });
    });
  </script>

</body>
</html>



