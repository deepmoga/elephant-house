document.addEventListener('DOMContentLoaded', function () {

    // ---- Site URL detection ----
    var siteUrl = '';
    var metaBase = document.querySelector('link[rel="stylesheet"][href*="/css/style.css"]');
    if (metaBase) {
        var href = metaBase.getAttribute('href');
        siteUrl = href.split('/css/style.css')[0];
    }

    // ---- Mobile Nav Toggle ----
    var navToggle = document.querySelector('.nav-toggle');
    var navLinks = document.querySelector('.nav-links');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
            var icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
    }

    // ---- Hero Slider ----
    var slides = document.querySelectorAll('.hero-slide');
    var dots = document.querySelectorAll('.slider-dot');
    var prevBtn = document.querySelector('.slider-arrow.prev');
    var nextBtn = document.querySelector('.slider-arrow.next');
    var currentSlide = 0;
    var slideInterval;

    function showSlide(index) {
        if (slides.length === 0) return;
        slides.forEach(function(s) { s.classList.remove('active'); });
        dots.forEach(function(d) { d.classList.remove('active'); });
        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    function nextSlideFn() { showSlide(currentSlide + 1); }
    function startSlider() { if (slides.length > 1) slideInterval = setInterval(nextSlideFn, 5000); }
    function stopSlider() { clearInterval(slideInterval); }

    if (prevBtn) prevBtn.addEventListener('click', function () { stopSlider(); showSlide(currentSlide - 1); startSlider(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { stopSlider(); showSlide(currentSlide + 1); startSlider(); });
    dots.forEach(function (dot, index) { dot.addEventListener('click', function () { stopSlider(); showSlide(index); startSlider(); }); });
    startSlider();

    // ---- Scroll Animations ----
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.category-card, .product-card, .offer-card, .feature-item, .blog-card, .faq-item').forEach(function (el) {
        el.style.opacity = '0';
        observer.observe(el);
    });

    // ---- Sticky Header Shadow ----
    var header = document.querySelector('.main-header');
    if (header) {
        window.addEventListener('scroll', function () {
            header.style.boxShadow = window.scrollY > 100 ? '0 4px 30px rgba(181,45,49,0.12)' : '0 2px 20px rgba(181,45,49,0.08)';
        });
    }

    // ---- Back to Top ----
    var backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function () {
            backToTop.style.display = window.scrollY > 400 ? 'flex' : 'none';
        });
        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ---- Update Cart Badge ----
    function updateCartBadge(count) {
        var badge = document.getElementById('cartBadge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // ---- Show Toast Notification ----
    function showToast(message, type) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:20px;right:20px;padding:15px 25px;border-radius:10px;color:#fff;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 5px 20px rgba(0,0,0,0.2);transition:opacity 0.4s;';
        toast.style.background = type === 'success' ? '#28a745' : '#dc3545';
        toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '" style="margin-right:8px;"></i>' + message;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 400);
        }, 3000);
    }

    // ---- AJAX helper ----
    function cartAjax(data, callback) {
        var formData = new FormData();
        for (var key in data) {
            formData.append(key, data[key]);
        }
        fetch(siteUrl + '/api/cart.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) { callback(json); })
            .catch(function(err) {
                console.error('Cart error:', err);
                showToast('Something went wrong. Please try again.', 'error');
            });
    }

    // ---- Add to Cart (product page) ----
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-add-cart[data-id]');
        if (!btn) return;
        e.preventDefault();

        var qtyInput = document.getElementById('productQty');
        var qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

        cartAjax({
            action: 'add',
            product_id: btn.dataset.id,
            name: btn.dataset.name,
            image: btn.dataset.image,
            price: btn.dataset.price,
            quantity: qty
        }, function(data) {
            if (data.success) {
                updateCartBadge(data.cart_count);
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Error adding to cart', 'error');
            }
        });
    });

    // ---- Product page quantity +/- ----
    document.addEventListener('click', function(e) {
        var minusBtn = e.target.closest('.qty-minus');
        var plusBtn = e.target.closest('.qty-plus');

        if (minusBtn) {
            var input = minusBtn.parentElement.querySelector('.qty-input');
            if (input && parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
        }
        if (plusBtn) {
            var input2 = plusBtn.parentElement.querySelector('.qty-input');
            if (input2 && parseInt(input2.value) < 99) input2.value = parseInt(input2.value) + 1;
        }
    });

    // ---- Cart page: quantity update ----
    document.addEventListener('click', function(e) {
        var minusBtn = e.target.closest('.cart-qty-minus');
        var plusBtn = e.target.closest('.cart-qty-plus');
        var id, input, val;

        if (minusBtn) {
            id = minusBtn.dataset.id;
            input = document.querySelector('.cart-qty-input[data-id="' + id + '"]');
            val = Math.max(1, parseInt(input.value) - 1);
            input.value = val;
            cartAjax({ action: 'update', product_id: id, quantity: val }, function(data) {
                if (data.success) { updateCartBadge(data.cart_count); location.reload(); }
            });
        }

        if (plusBtn) {
            id = plusBtn.dataset.id;
            input = document.querySelector('.cart-qty-input[data-id="' + id + '"]');
            val = parseInt(input.value) + 1;
            input.value = val;
            cartAjax({ action: 'update', product_id: id, quantity: val }, function(data) {
                if (data.success) { updateCartBadge(data.cart_count); location.reload(); }
            });
        }
    });

    // ---- Cart page: remove item ----
    document.addEventListener('click', function(e) {
        var removeBtn = e.target.closest('.cart-remove');
        if (!removeBtn) return;

        var id = removeBtn.dataset.id;
        cartAjax({ action: 'remove', product_id: id }, function(data) {
            if (data.success) {
                updateCartBadge(data.cart_count);
                var row = document.querySelector('.cart-row[data-id="' + id + '"]');
                if (row) row.remove();
                if (data.cart_count === 0) {
                    location.reload();
                } else {
                    var subtotalEl = document.getElementById('cartSubtotal');
                    var totalEl = document.getElementById('cartTotal');
                    if (subtotalEl) subtotalEl.innerHTML = '<strong>$' + data.cart_total + '</strong>';
                    if (totalEl) totalEl.textContent = '$' + data.cart_total;
                }
            }
        });
    });
});
