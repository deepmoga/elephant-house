<?php
require_once __DIR__ . '/includes/header.php';
$faqs = getActiveFaqs();
?>

<div class="page-header">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>">Home</a> / FAQ</div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (empty($faqs)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-question-circle" style="font-size:60px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);">No FAQs available yet</h3>
        </div>
        <?php else: ?>
        <div class="faq-list">
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-inner"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
