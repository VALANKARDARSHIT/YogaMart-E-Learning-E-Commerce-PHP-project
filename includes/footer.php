    </main> <!-- End of #main-content -->

<footer class="site-footer">
      <div class="container">
        <p>© 2025 YogaMart. Made with good vibes.</p>
        <nav class="foot-links" aria-label="Footer">
          <a href="home.php">Home</a>
          <a href="courses.php">Courses</a>
          <a href="videos.php">Videos</a>
          <a href="about_us.php">About Us</a>
          <a href="contact.php">Contact Us</a>
        </nav>
      </div>
    </footer>
  
    <!-- Scroll to Top Button -->
    <div id="scrollToTop" class="scroll-to-top" title="Go to top">
      ↑
    </div>
  
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const scrollBtn = document.getElementById('scrollToTop');
        
        // Show button when user scrolls down 300px
        window.addEventListener('scroll', function() {
          if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
          } else {
            scrollBtn.classList.remove('show');
          }
        });
        
        // Scroll to top when clicked
        scrollBtn.addEventListener('click', function() {
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
      });
    </script>
  
