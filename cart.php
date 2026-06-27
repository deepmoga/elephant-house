<?php
require_once __DIR__ . '/includes/header.php';
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<div class="page-header">
    <div class="container">
        <h1>Shopping Cart</h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> / Shopping Cart
        </div>
    </div>
</div>

<section class="section">
    <div class="container">
        <?php if (empty($cart)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-shopping-cart" style="font-size:70px;color:var(--text-muted);margin-bottom:20px;display:block;"></i>
            <h3 style="color:var(--text-light);margin-bottom:10px;">Your cart is empty</h3>
            <p style="color:var(--text-muted);margin-bottom:25px;">Browse our products and add items to your cart.</p>
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn-view-all">Browse Categories <i class="fas fa-arrow-right" style="margin-left:8px;"></i></a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <div class="card">
                    <div class="card-body cart-table-wrap" style="padding:0;">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $id => $item):
                                    $lineTotal = $item['price'] * $item['quantity'];
                                ?>
                                <tr class="cart-row" data-id="<?php echo htmlspecialchars($id); ?>">
                                    <td class="cart-product">
                                        <div style="display:flex;align-items:center;gap:15px;">
                                            <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width:70px;height:70px;object-fit:contain;border-radius:8px;background:var(--cream-dark);">
                                            <?php endif; ?>
                                            <div>
                                                <strong style="font-size:14px;"><?php echo htmlspecialchars($item['name']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <div class="quantity-selector" style="margin:0;">
                                            <button type="button" class="qty-btn cart-qty-minus" data-id="<?php echo htmlspecialchars($id); ?>">-</button>
                                            <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="qty-input cart-qty-input" data-id="<?php echo htmlspecialchars($id); ?>">
                                            <button type="button" class="qty-btn cart-qty-plus" data-id="<?php echo htmlspecialchars($id); ?>">+</button>
                                        </div>
                                    </td>
                                    <td class="cart-line-total"><strong>$<?php echo number_format($lineTotal, 2); ?></strong></td>
                                    <td>
                                        <button class="cart-remove" data-id="<?php echo htmlspecialchars($id); ?>" title="Remove">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="cart-summary">
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-family:'Playfair Display',serif;font-size:20px;color:var(--primary);margin-bottom:20px;">Order Summary</h3>
                        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px;color:var(--text-light);">
                            <span>Subtotal (<?php echo array_sum(array_column($cart, 'quantity')); ?> items)</span>
                            <span id="cartSubtotal"><strong>$<?php echo number_format($subtotal, 2); ?></strong></span>
                        </div>
                        <hr style="border:none;border-top:1px solid var(--border);margin:15px 0;">
                        <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;color:var(--primary);">
                            <span>Total</span>
                            <span id="cartTotal">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn-add-cart" style="width:100%;justify-content:center;margin-top:20px;text-decoration:none;">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                        <a href="<?php echo SITE_URL; ?>/categories.php" style="display:block;text-align:center;margin-top:15px;color:var(--primary);font-size:14px;">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
