/**
 * rcm-init.js
 * 
 * Handles rendering of multiple explicit reCAPTCHA v2 widgets safely.
 * - Works even if another plugin already loaded reCAPTCHA v3.
 * - Ensures grecaptcha script loads once (handled by PHP loader).
 * - Renders widgets with unique IDs, binds hidden input rcm_widget_id.
 */

(function () {
    var siteKey = (window.wmr_settings && window.wmr_settings.site_key) ? window.wmr_settings.site_key : '';

    if (!siteKey) {
        console.warn('WMR: No reCAPTCHA site key found.');
        return;
    }

    var widgets = {}; // map: widget_id -> grecaptcha widget index

    /**
     * Called by the Google API once grecaptcha is ready (render=explicit).
     * Or triggered manually by loader if grecaptcha already existed.
     */
    window.wmrOnload = function () {
        window.wmr_grecaptcha_loaded = true;
        renderAll();
    };

    /**
     * Finds all placeholders (.rcm-widget) that haven’t been rendered yet.
     */
    function getPlaceholders() {
        return document.querySelectorAll('.wmr-widget');
    }

    /**
     * Render all available widget containers.
     */
    function renderAll() {
        if (typeof grecaptcha === 'undefined') {
            // Retry until grecaptcha becomes available
            setTimeout(renderAll, 300);
            return;
        }

        var placeholders = getPlaceholders();
        placeholders.forEach(function (node) {
            var id = node.getAttribute('data-wmr-id');
            if (!id || widgets[id]) return;

            // Create hidden input for tracking which widget was rendered
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'wmr_widget_id';
            hidden.value = id;
            node.parentNode.insertBefore(hidden, node.nextSibling);

            try {
                var widgetIndex = grecaptcha.render(node, {
                    'sitekey': siteKey,
                    'theme': 'light'
                });
                widgets[id] = widgetIndex;
            } catch (err) {
                console.warn('WMR: Failed to render reCAPTCHA widget for ID:', id, err);
            }
        });
    }

    /**
     * Run renderAll when DOM is ready (and grecaptcha is loaded).
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            if (window.wmr_grecaptcha_loaded) {
                renderAll();
            }
        });
    } else {
        if (window.wmr_grecaptcha_loaded) {
            renderAll();
        }
    }

    // Retry rendering a few times in case script loads late.
    setTimeout(renderAll, 1000);
    setTimeout(renderAll, 3000);
})();
