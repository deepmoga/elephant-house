<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / Contact Us
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;">
            <div class="content-area" style="background:var(--white);padding:40px;border-radius:var(--radius);box-shadow:0 2px 15px var(--shadow);">
                <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:20px;">Get in Touch</h2>
                <p style="color:var(--text-light);margin-bottom:30px;">We'd love to hear from you. Visit our store or reach out to us.</p>

                <div style="display:flex;flex-direction:column;gap:25px;">
                    <?php if (!empty($settings['address'])): ?>
                    <div style="display:flex;gap:15px;align-items:flex-start;">
                        <div style="width:50px;height:50px;border-radius:50%;background:var(--cream);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-map-marker-alt" style="color:var(--primary);font-size:20px;"></i>
                        </div>
                        <div>
                            <h4 style="color:var(--primary);margin-bottom:5px;">Our Address</h4>
                            <p style="color:var(--text-light);font-size:14px;"><?php echo htmlspecialchars($settings['address']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($settings['phone'])): ?>
                    <div style="display:flex;gap:15px;align-items:flex-start;">
                        <div style="width:50px;height:50px;border-radius:50%;background:var(--cream);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-phone" style="color:var(--primary);font-size:20px;"></i>
                        </div>
                        <div>
                            <h4 style="color:var(--primary);margin-bottom:5px;">Phone</h4>
                            <p style="color:var(--text-light);font-size:14px;"><a href="tel:<?php echo htmlspecialchars($settings['phone']); ?>" style="color:var(--primary-light);"><?php echo htmlspecialchars($settings['phone']); ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($settings['email'])): ?>
                    <div style="display:flex;gap:15px;align-items:flex-start;">
                        <div style="width:50px;height:50px;border-radius:50%;background:var(--cream);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-envelope" style="color:var(--primary);font-size:20px;"></i>
                        </div>
                        <div>
                            <h4 style="color:var(--primary);margin-bottom:5px;">Email</h4>
                            <p style="color:var(--text-light);font-size:14px;"><a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>" style="color:var(--primary-light);"><?php echo htmlspecialchars($settings['email']); ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($settings['opening_hours'])): ?>
                    <div style="display:flex;gap:15px;align-items:flex-start;">
                        <div style="width:50px;height:50px;border-radius:50%;background:var(--cream);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-clock" style="color:var(--primary);font-size:20px;"></i>
                        </div>
                        <div>
                            <h4 style="color:var(--primary);margin-bottom:5px;">Opening Hours</h4>
                            <p style="color:var(--text-light);font-size:14px;"><?php echo htmlspecialchars($settings['opening_hours']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:30px;display:flex;gap:12px;">
                    <?php if (!empty($settings['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" target="_blank" style="width:45px;height:45px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:var(--white);font-size:18px;"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['instagram']); ?>" target="_blank" style="width:45px;height:45px;border-radius:50%;background:var(--warm);display:flex;align-items:center;justify-content:center;color:var(--white);font-size:18px;"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background:var(--white);border-radius:var(--radius);box-shadow:0 2px 15px var(--shadow);overflow:hidden;min-height:400px;">
                <?php if (!empty($settings['google_maps'])): ?>
                <iframe src="<?php echo htmlspecialchars($settings['google_maps']); ?>" width="100%" height="100%" style="border:0;min-height:400px;" allowfullscreen="" loading="lazy"></iframe>
                <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:center;height:100%;background:var(--cream);color:var(--text-muted);flex-direction:column;gap:10px;">
                    <i class="fas fa-map" style="font-size:60px;"></i>
                    <p>Map will be displayed here</p>
                    <p style="font-size:12px;">Configure Google Maps embed URL in admin settings</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
@media (max-width: 768px) {
    .section > .container > div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
