// Soft-refresh the payment page until the order leaves the "awaiting_payment"
// state. We don't poll an API endpoint - we just reload the page itself every
// 20s. This is intentionally minimal: zero JSON surface, no exposed endpoints.
(function () {
    'use strict';
    var badge = document.querySelector('.badge');
    var status = badge ? badge.textContent.trim() : '';
    if (status === 'awaiting payment' || status === 'new') {
        setTimeout(function () { window.location.reload(); }, 20000);
    }

    // One-click copy of the deposit address.
    var addr = document.getElementById('depositAddr');
    if (addr) {
        addr.style.cursor = 'pointer';
        addr.title = 'Click to copy';
        addr.addEventListener('click', function () {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(addr.textContent.trim()).then(function () {
                var prev = addr.textContent;
                addr.textContent = 'copied ✓';
                setTimeout(function () { addr.textContent = prev; }, 1200);
            });
        });
    }
})();
