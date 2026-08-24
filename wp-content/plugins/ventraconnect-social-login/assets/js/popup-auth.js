(function(){
    'use strict';
    var POPUP_WIDTH = 600;
    var POPUP_HEIGHT = 700;
    function centerSpecs() {
        var left = Math.max(0, (screen.width - POPUP_WIDTH) / 2);
        var top = Math.max(0, (screen.height - POPUP_HEIGHT) / 2);
        return 'toolbar=0,location=0,status=0,menubar=0,scrollbars=1,resizable=1,width=' + POPUP_WIDTH + ',height=' + POPUP_HEIGHT + ',top=' + top + ',left=' + left;
    }
    function openPopup(url) {
        try {
            var win = window.open(url, 'vcs_oauth', centerSpecs());
            if (!win) { return null; }
            try { win.focus(); } catch (e) {}
            return win;
        } catch (e) {
            return null;
        }
    }
    function handleAnchor(el) {
        if (!el) { return; }
        var href = el.getAttribute('href');
        // Let anchors with no-op href or token providers be handled by auth.js modal logic
        if (!href || href === '#') { return; }
        var provider = el.getAttribute('data-provider') || '';
        if (provider === 'magic_link' || provider === 'otp_email') { return; }
        // Prevent normal navigation
    try { event && event.preventDefault(); } catch (e) {}
    try { event && event.stopImmediatePropagation(); } catch (e) {}
        // Open popup
        var popup = openPopup(href);
        if (!popup) {
            // Popup blocked — show toast with open-in-new-tab action
            showToast('Popup blocked. <a href="' + href + '" target="_blank" rel="noopener">Open in new tab</a>');
            return;
        }

        var closed = false;
        var timer = setInterval(function(){
            try {
                if (popup.closed) { closed = true; clearInterval(timer); }
            } catch (e) {
                // cross-origin access sometimes fails; ignore
            }
        }, 500);

        // Handshake: wait for postMessage from popup bridge
        var handshakeTimeout = setTimeout(function(){
            // If timeout reached and popup still open, show fallback toast
            if (!closed) {
                // Offer an explicit fallback to open the auth URL in a new tab
                var href = popup && popup.location && popup.location.href ? popup.location.href : (href || '#');
                var openLink = (href && href !== '#') ? (' <a href="' + href + '" target="_blank" rel="noopener">Open in new tab</a>') : '';
                showToast('Authentication taking longer than expected.' + openLink + ' <a href="#" onclick="return false;">If nothing happens, close this and try again.</a>');
            }
        }, 30000);

        function onMessage(ev) {
            if (!ev || !ev.data) return;
            var d = ev.data;
            if (typeof d === 'string') {
                try { d = JSON.parse(d); } catch (e) { return; }
            }
            if (d && d.type === 'vcs_oauth_result') {
                // Redirect parent if payload contains redirect
                if (d.redirect) {
                    try { window.location.href = d.redirect; } catch (e) { /* ignore */ }
                }
                // Close any open popup polling
                try { if (popup && !popup.closed) popup.close(); } catch (e) {}
                clearTimeout(handshakeTimeout);
                window.removeEventListener('message', onMessage);
            }
        }
        window.addEventListener('message', onMessage, false);
    }
    function init() {
        // Attach to provider buttons (capture phase to preempt other handlers)
        function findAnchorFromEvent(ev) {
            return ev.target && (ev.target.closest ? ev.target.closest('a[data-provider]') : null);
        }

        // Early preempt: mousedown / touchstart so other handlers that call native navigation
        // can't immediately navigate the main window.
        document.addEventListener('mousedown', function(ev){
            var a = findAnchorFromEvent(ev);
                if (!a) return;
                // Skip token providers and no-op anchors so auth.js can open modals
                var href = a.getAttribute('href');
                var provider = a.getAttribute('data-provider') || '';
                if (!href || href === '#' || provider === 'magic_link' || provider === 'otp_email') { return; }
                try { ev.preventDefault(); ev.stopImmediatePropagation(); } catch(e) {}
                // mark handled to avoid duplicate click handling
                a.setAttribute('data-vcs-handled', '1');
                handleAnchor(a);
        }, true);
        document.addEventListener('touchstart', function(ev){
            var a = findAnchorFromEvent(ev);
            if (!a) return;
            // Skip token providers and no-op anchors
            var href = a.getAttribute('href');
            var provider = a.getAttribute('data-provider') || '';
            if (!href || href === '#' || provider === 'magic_link' || provider === 'otp_email') { return; }
            try { ev.preventDefault(); ev.stopImmediatePropagation(); } catch(e) {}
            a.setAttribute('data-vcs-handled', '1');
            handleAnchor(a);
        }, true);

        document.addEventListener('click', function(ev){
            var a = findAnchorFromEvent(ev);
            if (!a) return;
            // If already handled by mousedown/touchstart, consume the click and ignore
            if (a.getAttribute('data-vcs-handled') === '1') {
                try { ev.preventDefault(); ev.stopImmediatePropagation(); } catch(e) {}
                a.removeAttribute('data-vcs-handled');
                return;
            }
            // Skip token providers and no-op anchors so auth.js can handle them
            var href = a.getAttribute('href');
            var provider = a.getAttribute('data-provider') || '';
            if (!href || href === '#' || provider === 'magic_link' || provider === 'otp_email') { return; }
            // Fallback: handle click if not already handled
            try { ev.preventDefault(); ev.stopImmediatePropagation(); } catch(e) {}
            handleAnchor(a);
        }, true);
    }
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(init, 0);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
    /* Toast helper */
    function showToast(html) {
        try {
            var id = 'vcs-popup-toast';
            var existing = document.getElementById(id);
            if (existing) { existing.parentNode.removeChild(existing); }
            var div = document.createElement('div');
            div.id = id;
            div.style.position = 'fixed';
            div.style.right = '16px';
            div.style.bottom = '16px';
            div.style.background = 'rgba(0,0,0,0.85)';
            div.style.color = '#fff';
            div.style.padding = '12px 16px';
            div.style.borderRadius = '6px';
            div.style.zIndex = 999999;
            div.innerHTML = html;
            document.body.appendChild(div);
            setTimeout(function(){ try{ div.parentNode && div.parentNode.removeChild(div); }catch(e){} }, 8000);
        } catch (e) { /* ignore */ }
    }
})();
