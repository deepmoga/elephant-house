<!-- Features Bar -->
<section class="features-bar">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-store"></i>
                <h4>Wide Selection</h4>
                <p>Authentic Sri Lankan & Asian products</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-leaf"></i>
                <h4>Fresh & Quality</h4>
                <p>Premium quality guaranteed</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-tags"></i>
                <h4>Best Prices</h4>
                <p>Competitive pricing always</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-heart"></i>
                <h4>Loved by Community</h4>
                <p>Trusted by thousands of families</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-section">
                <h3><?php echo htmlspecialchars($settings['site_name'] ?? 'Elephant House'); ?></h3>
                <p>Your one-stop destination for authentic Sri Lankan and Asian groceries. We bring the taste of home to your doorstep with premium quality products sourced directly from trusted suppliers.</p>
                <div class="footer-social">
                    <?php if (!empty($settings['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/categories.php"><i class="fas fa-chevron-right"></i> Categories</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/page.php?slug=about-us"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/blogs.php"><i class="fas fa-chevron-right"></i> Blog</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Policies</h3>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/page.php?slug=privacy-policy"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/page.php?slug=terms-conditions"><i class="fas fa-chevron-right"></i> Terms & Conditions</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <?php if (!empty($settings['address'])): ?>
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($settings['address']); ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['phone'])): ?>
                    <li><a href="tel:<?php echo htmlspecialchars($settings['phone']); ?>"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['phone']); ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['email'])): ?>
                    <li><a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['email']); ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($settings['opening_hours'])): ?>
                    <li><a href="#"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($settings['opening_hours']); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <?php echo htmlspecialchars($settings['footer_text'] ?? '© 2026 Elephant House. All Rights Reserved.'); ?>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button id="backToTop" style="display:none;position:fixed;bottom:30px;right:30px;width:50px;height:50px;border-radius:50%;background:var(--primary);color:var(--white);border:none;cursor:pointer;font-size:20px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:999;align-items:center;justify-content:center;transition:var(--transition);">
    <i class="fas fa-arrow-up"></i>
</button>

<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
</body>
</html>
