/* Frontend auth for Magic Link and OTP (Email) */
(function(){
  function qs(sel, ctx){ return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx){ return Array.from((ctx || document).querySelectorAll(sel)); }

  function modal(options){
    const opts = (typeof options === 'string') ? { body: options } : (options || {});
    const wrap = document.createElement('div');
    wrap.className = 'vcs-modal-wrap vcsl-auth-modal-overlay';
    const titleMarkup = opts.title ? `<header class="vcs-modal__header"><h3>${opts.title}</h3>${opts.subtitle ? `<p>${opts.subtitle}</p>` : ''}</header>` : '';
    wrap.innerHTML = `<div class="vcs-modal vcsl-auth-modal" role="dialog" aria-modal="true"><div class="vcs-modal__card"><button class="vcs-modal__close" aria-label="Close">&times;</button>${titleMarkup}<div class="vcs-modal__notice" aria-live="assertive" hidden></div><div class="vcs-modal__body">${opts.body || ''}</div></div></div>`;
    const previouslyFocused = document.activeElement;
    document.body.appendChild(wrap);
    document.body.classList.add('vcs-modal-open');
    if (typeof opts.onCreate === 'function') {
      try { opts.onCreate(wrap); } catch (e) {}
    }
    const close = () => {
      if (typeof opts.onClose === 'function') {
        try { opts.onClose(wrap); } catch (e) {}
      }
      wrap.remove();
      document.body.classList.remove('vcs-modal-open');
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
        try { previouslyFocused.focus(); } catch (e) {}
      }
      document.removeEventListener('keydown', onKey);
    };
    const onKey = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
      }
    };
    document.addEventListener('keydown', onKey);
    wrap.addEventListener('click', event => {
      if (event.target === wrap || event.target.classList.contains('vcs-modal__close')) {
        close();
      }
    });
    setTimeout(() => {
      const firstInput = qs('input, button', wrap);
      if (firstInput) {
        try { firstInput.focus(); } catch (e) {}
      }
    }, 0);
    wrap._vcsClose = close;
    return wrap;
  }

  function notice(modalEl, message, tone){
    const area = modalEl.querySelector('.vcs-modal__notice');
    if (!area) { return; }
    if (!message) {
      area.innerHTML = '';
      area.hidden = true;
      return;
    }
    const intent = tone || 'info';
    area.innerHTML = `<div class="vcs-alert vcs-alert--${intent}"><span>${message}</span></div>`;
    area.hidden = false;
  }

  function formatError(resp, fallback){
    if (!resp || typeof resp !== 'object') { return fallback; }

    const data = resp && typeof resp.data === 'object' ? resp.data : null;
    let code = (data && data.code) ? data.code : '';
    if (!code && typeof resp.error === 'string') {
      code = resp.error;
    }

    const dataMessage = data && typeof data.message === 'string' ? data.message : null;
    const topMessage = typeof resp.message === 'string' ? resp.message : null;

    // Friendly override for registration_mode = login_only
    if (code === 'ventraconnect_sl_registration_login_only') {
      return 'This login method is only for existing accounts. Please sign up using another option.';
    }

    // Throttling helper (uses WP-style data.retry_after)
    if (code === 'throttled' && data && data.retry_after){
      const secs = Number(data.retry_after) || 0;
      if (secs > 0){
        const mins = Math.ceil(secs / 60);
        return `Please wait ${mins} minute${mins === 1 ? '' : 's'} before trying again.`;
      }
    }

    // Pro-only guard
    if (code === 'pro_required' || dataMessage === 'pro_required' || topMessage === 'pro_required') {
      return 'Upgrade to Pro to unlock this feature.';
    }

    // WordPress-style error: success === false with data.message
    if (resp.success === false && dataMessage) {
      return dataMessage;
    }

    // Generic data.message
    if (dataMessage) {
      return dataMessage;
    }

    // Top-level message (our custom { ok:false, message:'...' } cases)
    if (topMessage) {
      return topMessage;
    }

    return fallback;
  }

  function ajax(action, data){
    const payload = Object.assign({}, data || {}, {
      action,
      // Prefer canonical nonce, fall back to legacy localized nonce for compatibility
      nonce: (window.VCS_AUTH && (window.VCS_AUTH.nonce || window.VCS_AUTH.nonce_legacy)) || ''
    });
    return fetch((window.VCS_AUTH && window.VCS_AUTH.ajax_url) || (window.ajaxurl || ''), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams(payload)
    }).then(response => response.json());
  }



  function currentRedirect(){
    try {
      const url = new URL(window.location.href);
      const redirectParam = (url.searchParams.get('redirect_to') || '').trim();
      if (redirectParam) {
        return redirectParam;
      }
      // Fallback: current page URL without query string or hash.
      return url.origin + url.pathname;
    } catch (e) {
      // Extremely defensive: try a simple string-based fallback before giving up.
      try {
        const href = String(window.location && window.location.href ? window.location.href : '');
        if (href) {
          const cleaned = href.split('#')[0].split('?')[0];
          return cleaned || '';
        }
      } catch (e2) {}
      return '';
    }
  }

  // Broadcast channel for login notifications across tabs (canonical key preferred, legacy fallback)
  const LOGIN_BC_NAME = (window.VCS_AUTH && (window.VCS_AUTH.login_channel || window.VCS_AUTH.login_channel_legacy)) || 'ventraconnect_sl_login_channel';
  const LOGIN_BC = (typeof window.BroadcastChannel === 'function') ? new BroadcastChannel(LOGIN_BC_NAME) : null;
  // If the page has a login marker we notify other tabs and clean the URL marker
  try {
    const _u = new URL(window.location.href);
    const loggedInMarker = (window.VCS_AUTH && (window.VCS_AUTH.logged_in_query || window.VCS_AUTH.logged_in_query_legacy)) || 'ventraconnect_sl_logged_in';
    if (_u.searchParams.get(loggedInMarker) === '1'){
      try { if (LOGIN_BC) LOGIN_BC.postMessage({ type: 'VCS_LOGGED_IN' }); } catch (e) {}
      try { _u.searchParams.delete(loggedInMarker); window.history.replaceState({}, document.title, _u.toString()); } catch (e) {}
    }
    // If the page has a magic link error marker, surface a friendly message and clean the URL
    const magicErrorParam = (window.VCS_AUTH && (window.VCS_AUTH.magic_error_param || window.VCS_AUTH.magic_error_param_legacy)) || 'ventraconnect_sl_magic_error';
    const magicErr = _u.searchParams.get(magicErrorParam);
    if (magicErr === 'ip_mismatch'){
      try {
        // Show a small modal with guidance
        const errModal = modal({ title: 'Magic link not accepted', body: '<div class="vcs-modal__content"><p>This sign-in link can only be used from the IP address that requested it. If you switched networks (for example from mobile data to Wi‑Fi), request a new magic link.</p><div class="vcs-actions"><button class="button button-primary" onclick="this.closest(\'.vcs-modal-wrap\').remove();">Close</button></div></div>' });
      } catch (e) { /* ignore */ }
      try { _u.searchParams.delete('vcs_magic_error'); window.history.replaceState({}, document.title, _u.toString()); } catch (e) {}
    }
  } catch (e) { /* ignore */ }

  function setButtonState(btn, loading){
    if (!btn) { return; }
    const defaultLabel = btn.dataset.defaultLabel || btn.textContent;
    if (!btn.dataset.defaultLabel){ btn.dataset.defaultLabel = defaultLabel; }
    if (loading){
      btn.disabled = true;
      const loadingLabel = btn.dataset.loading || defaultLabel;
      btn.textContent = loadingLabel;
    } else {
      btn.disabled = false;
      btn.textContent = defaultLabel;
    }
  }

  function formatDuration(totalSeconds){
    const secs = Math.max(0, Math.floor(Number(totalSeconds) || 0));
    const minutes = Math.floor(secs / 60);
    const remainder = secs % 60;
    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
  }

  const TAB_ID_REGISTRY = {};
  function getStableTabId(key){
    if (!TAB_ID_REGISTRY[key]) {
      TAB_ID_REGISTRY[key] = `${Date.now()}-${String(Math.random()).slice(2)}`;
    }
    return TAB_ID_REGISTRY[key];
  }

  const BC_REGISTRY = {};
  function getBroadcastChannel(name){
    if (typeof window.BroadcastChannel !== 'function') { return null; }
    if (!BC_REGISTRY[name]) {
      BC_REGISTRY[name] = new BroadcastChannel(name);
    }
    return BC_REGISTRY[name];
  }

  function createCountdown(modalEl){
    const wrapper = qs('[data-countdown]', modalEl);
    const timeEl = wrapper ? qs('[data-countdown-time]', wrapper) : null;
    const labelEl = wrapper ? qs('.vcs-countdown__label', wrapper) : null;
    const baseLabel = labelEl ? labelEl.textContent : '';
    let timerId = null;
    return {
      start(seconds){
        if (!wrapper || !timeEl) { return; }
        const total = Number(seconds) || 0;
        if (timerId) { clearInterval(timerId); timerId = null; }
        if (labelEl){
          labelEl.textContent = baseLabel || labelEl.textContent || 'Expires in';
        }
        if (total <= 0){
          wrapper.hidden = true;
          wrapper.classList.remove('is-active', 'is-expired');
          wrapper.dataset.state = 'idle';
          return;
        }
        const endTime = Date.now() + total * 1000;
        wrapper.hidden = false;
        wrapper.classList.add('is-active');
        wrapper.classList.remove('is-expired');
        wrapper.dataset.state = 'running';
        const tick = () => {
          const diff = Math.max(0, Math.round((endTime - Date.now()) / 1000));
          timeEl.textContent = formatDuration(diff);
          if (diff <= 0){
            wrapper.classList.add('is-expired');
            wrapper.dataset.state = 'expired';
            if (labelEl){ labelEl.textContent = 'Expired'; }
            wrapper.dispatchEvent(new CustomEvent('vcs:countdown-expired', { bubbles: true }));
            clearInterval(timerId);
            timerId = null;
          }
        };
        tick();
        timerId = setInterval(tick, 1000);
      },
      stop(){
        if (timerId){
          clearInterval(timerId);
          timerId = null;
        }
        if (wrapper){
          wrapper.classList.remove('is-active', 'is-expired');
          wrapper.dataset.state = 'idle';
        }
        if (labelEl){
          labelEl.textContent = baseLabel;
        }
      }
    };
  }

  // Magic Link flow
  document.addEventListener('click', function(event){
    const trigger = event.target.closest('a.vcs-btn--magic-link');
    if (!trigger) {
      return;
    }
    event.preventDefault();
    const context = (trigger.closest('.wsc-buttons')?.getAttribute('data-wsc-context')) || 'login';
      let countdown;
      // helper: session storage key and cross-tab channel for magic links
      const MAGIC_STORAGE_KEY = (window.VCS_AUTH && (window.VCS_AUTH.magic_storage_key || window.VCS_AUTH.magic_storage_key_legacy)) || 'ventraconnect_sl_magic_state';
      const MAGIC_TAB_ID = getStableTabId('magic');
      const instanceId = `${MAGIC_TAB_ID}:${Date.now()}-${String(Math.random()).slice(2)}`;
      const MAGIC_BC_NAME = (window.VCS_AUTH && (window.VCS_AUTH.magic_channel || window.VCS_AUTH.magic_channel_legacy)) || 'ventraconnect_sl_magic_channel';
      const MAGIC_BC = getBroadcastChannel(MAGIC_BC_NAME);
      function saveMagicState(email, requestedAt, serverExpiresAt){
        try {
          const data = {
            email: String(email || ''),
            requestedAt: Number(requestedAt) || 0,
            serverExpiresAt: Number(serverExpiresAt) || 0
          };
          window.sessionStorage.setItem(MAGIC_STORAGE_KEY, JSON.stringify(data));
          try {
            if (MAGIC_BC){ MAGIC_BC.postMessage({ type: 'MAGIC_REQUESTED', email: data.email, serverExpiresAt: data.serverExpiresAt, senderId: MAGIC_TAB_ID, instanceId }); }
          } catch (e) { /* ignore */ }
        } catch (e) { /* ignore */ }
      }
      function loadMagicState(){
        try {
          const raw = window.sessionStorage.getItem(MAGIC_STORAGE_KEY);
          if (!raw) return null;
          const parsed = JSON.parse(raw);
          if (!parsed || !parsed.email || !parsed.serverExpiresAt) return null;
          return parsed;
        } catch (e) { return null; }
      }
      function clearMagicState(){
        try { window.sessionStorage.removeItem(MAGIC_STORAGE_KEY); } catch (e) {}
      }
      let modalEl = null;
      let form = null;
      let submitBtn = null;
      function restoreMagicState(){
        try {
          if (!modalEl || !modalEl.isConnected) { return false; }
          const state = loadMagicState();
          if (!state || !state.email || !state.serverExpiresAt) { return false; }
          const now = Date.now();
          const serverExpiresAt = Number(state.serverExpiresAt) || 0;
          if (serverExpiresAt <= now){
            clearMagicState();
            return false;
          }
          const remaining = Math.round((serverExpiresAt - now) / 1000);
          if (countdown){ countdown.start(remaining); }
          const emailInput = modalEl.querySelector('input[name="email"]');
          if (emailInput){
            if (!emailInput.value){ emailInput.value = state.email; }
          }
          if (submitBtn){
            if (!submitBtn.dataset.originalLabel){
              submitBtn.dataset.originalLabel = submitBtn.dataset.defaultLabel || submitBtn.textContent || 'Send link';
            }
            submitBtn.dataset.defaultLabel = 'Send link again';
            submitBtn.textContent = 'Send link again';
          }
          return true;
        } catch (e) { return false; }
      }
      function resetMagicUi(){
        if (!modalEl) { return; }
        const emailInput = modalEl.querySelector('input[name="email"]');
        if (emailInput){
          emailInput.disabled = false;
          emailInput.readOnly = false;
        }
        if (submitBtn){
          const original =
            submitBtn.dataset.originalLabel ||
            submitBtn.dataset.defaultLabel ||
            submitBtn.textContent ||
            'Send link';
          submitBtn.dataset.defaultLabel = original;
          submitBtn.textContent = original;
          submitBtn.disabled = false;
        }
      }
      const bcHandler = function(ev){
        try {
          const d = ev && ev.data;
          if (!d || d.type !== 'MAGIC_REQUESTED') { return; }
          const sameTab = d.senderId && d.senderId === MAGIC_TAB_ID;
          const sameInstance = sameTab && d.instanceId && d.instanceId === instanceId;
          if (sameInstance) { return; }
          if (sameTab){
            restoreMagicState();
            return;
          }
          clearMagicState();
          resetMagicUi();
        } catch (e) { /* ignore */ }
      };
      modalEl = modal({
        title: 'Email Magic Link',
        subtitle: 'We\'ll send a secure, single-use link to finish signing in.',
        body: '<div class="vcs-modal__content"><div class="vcs-countdown" data-countdown hidden><span class="vcs-countdown__label">Link active for</span><span class="vcs-countdown__time" data-countdown-time>00:00</span></div><form class="vcs-form vcs-form--magic" novalidate><div class="vcs-field"><label>Email address</label><input type="email" name="email" required placeholder="you@example.com"></div><div class="vcs-actions"><button type="submit" class="button button-primary" data-loading="Sending&hellip;">Send link</button></div></form></div>',
        onCreate(el){
          countdown = createCountdown(el);
          // Listen for login notifications from other tabs
          try {
            if (LOGIN_BC){
              LOGIN_BC.addEventListener('message', function onLogin(ev){
                try {
                  const d = ev && ev.data;
                  if (!d || d.type !== 'VCS_LOGGED_IN') return;
                  // Show success message and close this modal after a short delay
                  try { notice(el, 'Signed in — closing&hellip;', 'success'); } catch (e) {}
                  setTimeout(function(){ try { if (el && el._vcsClose) el._vcsClose(); } catch (e) {} }, 700);
                } catch (e) { /* ignore */ }
              });
            }
          } catch (e) { /* ignore */ }
        },
        onClose(){
          if (countdown){ countdown.stop(); countdown = null; }
          if (MAGIC_BC){
            try { MAGIC_BC.removeEventListener('message', bcHandler); } catch (e) { /* ignore */ }
          }
          modalEl = null;
          form = null;
          submitBtn = null;
          // no explicit removal of LOGIN_BC listener as it's harmless; channels persist globally
        }
      });
      form = qs('form.vcs-form--magic', modalEl);
      submitBtn = qs('button[type="submit"]', form);
      if (submitBtn && !submitBtn.dataset.originalLabel){
        submitBtn.dataset.originalLabel = submitBtn.dataset.defaultLabel || submitBtn.textContent || 'Send link';
      }
      restoreMagicState();
      if (MAGIC_BC){
        try { MAGIC_BC.addEventListener('message', bcHandler); } catch (e) { /* ignore */ }
      }
      modalEl.addEventListener('vcs:countdown-expired', (ev) => {
        if (!modalEl.contains(ev.target)) { return; }
        notice(modalEl, 'This link has expired. Send a new magic link to continue.', 'error');
        // Re-enable the Send link button when countdown expires so user can request again
        try { setButtonState(submitBtn, false); } catch (e) { /* ignore */ }
        try { clearMagicState(); } catch (e) { /* ignore */ }
        resetMagicUi();
      });
      form.addEventListener('submit', function(e){
        e.preventDefault();
        const email = (form.email.value || '').trim();
        if (!email) { form.email.focus(); return; }

        setButtonState(submitBtn, true);

        ajax('ventraconnect_sl_magic_link_send', {
          email,
          context,
          redirect_to: currentRedirect(),
          origin_url: window.location.href
        }).then(resp => {
          if (resp && resp.success){
            const expiresIn = Number(resp?.data?.expires_in) || 0;
            const serverExpiresAtFromResp = Number(resp?.data?.server_expires_at) || 0;
            const serverNowFromResp = Number(resp?.data?.server_now) || 0;
            const requestedAt = Date.now();
            const serverNow = serverNowFromResp || requestedAt;
            const serverExpiresAt = serverExpiresAtFromResp || (expiresIn ? (serverNow + expiresIn * 1000) : 0);

            try { saveMagicState(email, requestedAt, serverExpiresAt); } catch (e) {}
            const applied = restoreMagicState();
            if (!applied && countdown){
              const remaining = serverExpiresAt
                ? Math.max(0, Math.round((serverExpiresAt - Date.now()) / 1000))
                : expiresIn;
              countdown.start(remaining);
            }

            const ttl = expiresIn ? ` It expires in <strong>${formatDuration(expiresIn)}</strong>.` : '';
            notice(modalEl, `Check your inbox. We just sent a sign-in link.${ttl}`, 'success');
            try { submitBtn.textContent = 'Send link again'; } catch (e) {}
            try { form.reset(); } catch (e) {}
          } else {
            const msg = formatError(resp, 'We could not send the link. Please try again.');
            notice(modalEl, msg, 'error');
          }
        }).catch(() => {
          notice(modalEl, 'Request failed. Please try again.', 'error');
        }).finally(() => {
          setButtonState(submitBtn, false);
        });
      });
  });

  // OTP Email flow
  document.addEventListener('click', function(event){
    const trigger = event.target.closest('a.vcs-btn--otp');
    if (!trigger) {
      return;
    }
    event.preventDefault();
    const context = (trigger.closest('.wsc-buttons')?.getAttribute('data-wsc-context')) || 'login';
      let countdown;
      const STORAGE_KEY = (window.VCS_AUTH && (window.VCS_AUTH.otp_storage_key || window.VCS_AUTH.otp_storage_key_legacy)) || 'vcs_otp_state';
      const TAB_ID = getStableTabId('otp');
      const instanceId = `${TAB_ID}:${Date.now()}-${String(Math.random()).slice(2)}`;
      const OTP_BC_NAME = (window.VCS_AUTH && (window.VCS_AUTH.otp_channel || window.VCS_AUTH.otp_channel_legacy)) || 'vcs_otp_channel';
      const bc = getBroadcastChannel(OTP_BC_NAME);
      function saveOtpState(email, requestedAt, serverExpiresAt){
        try {
          const data = {
            email: String(email || ''),
            requestedAt: Number(requestedAt) || 0,
            serverExpiresAt: Number(serverExpiresAt) || 0
          };
          window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
          try {
            if (bc){ bc.postMessage({ type: 'OTP_REQUESTED', email: data.email, serverExpiresAt: data.serverExpiresAt, senderId: TAB_ID, instanceId }); }
          } catch (e) { /* ignore */ }
        } catch (e) { /* ignore */ }
      }
      function loadOtpState(){
        try {
          const raw = window.sessionStorage.getItem(STORAGE_KEY);
          if (!raw) return null;
          const parsed = JSON.parse(raw);
          if (!parsed || !parsed.email || !parsed.serverExpiresAt) return null;
          return parsed;
        } catch (e) { return null; }
      }
      function clearOtpState(){
        try { window.sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
      }
      let modalEl = null;
      let form = null;
      let sendBtn = null;
      let verifySection = null;
      function ensureRefs(){
        if (!modalEl || !modalEl.isConnected) { return false; }
        if (!form){ form = qs('form.vcs-form--otp', modalEl); }
        if (!form) { return false; }
        if (!sendBtn){ sendBtn = qs('button.send', form); }
        if (!verifySection){ verifySection = qs('.vcs-verify', form); }
        return true;
      }
      function restoreOtpState(){
        if (!ensureRefs()) { return false; }
        try {
          const state = loadOtpState();
          if (!state || !state.email || !state.serverExpiresAt) { return false; }
          const now = Date.now();
          const serverExpiresAt = Number(state.serverExpiresAt) || 0;
          if (serverExpiresAt <= now){
            clearOtpState();
            return false;
          }
          const remaining = Math.round((serverExpiresAt - now) / 1000);
          if (countdown){ countdown.start(remaining); }
          if (verifySection){ verifySection.hidden = false; }
          const emailInput = form.email;
          if (emailInput){
            if (!emailInput.value){ emailInput.value = state.email; }
          }
          const codeInput = form.code;
          if (codeInput){
            try { codeInput.focus(); } catch (e) {}
          }
          if (sendBtn){
            if (!sendBtn.dataset.originalLabel){
              sendBtn.dataset.originalLabel = sendBtn.dataset.defaultLabel || sendBtn.textContent || 'Send code';
            }
            sendBtn.dataset.defaultLabel = 'Resend code';
            sendBtn.textContent = 'Resend code';
            sendBtn.disabled = false;
          }
          return true;
        } catch (e) { return false; }
      }
      function resetOtpUi(options){
        if (!ensureRefs()) { return; }
        const opts = options || {};
        if (verifySection){ verifySection.hidden = !!opts.hideVerify; }
        const emailInput = form.email;
        if (emailInput){
          emailInput.readOnly = false;
          if (opts.clearEmail){ try { emailInput.value = ''; } catch (e) {} }
        }
        if (sendBtn){
          const original = sendBtn.dataset.originalLabel || sendBtn.dataset.defaultLabel || sendBtn.textContent || 'Send code';
          const label = opts.keepResend ? 'Resend code' : original;
          sendBtn.dataset.defaultLabel = label;
          sendBtn.textContent = label;
          sendBtn.disabled = false;
        }
      }
      const bcHandler = function(ev){
        try {
          const d = ev && ev.data;
          if (!d || d.type !== 'OTP_REQUESTED') { return; }
          const sameTab = d.senderId && d.senderId === TAB_ID;
          const sameInstance = sameTab && d.instanceId && d.instanceId === instanceId;
          if (sameInstance) { return; }
          if (sameTab){
            restoreOtpState();
            return;
          }
          clearOtpState();
          if (countdown){ countdown.stop(); }
          resetOtpUi({ hideVerify: true, clearEmail: false });
        } catch (e) { /* ignore */ }
      };
      modalEl = modal({
        title: 'Email one-time code',
        subtitle: 'We\'ll send a short verification code to your inbox. Enter it below to continue.',
        body: '<div class="vcs-modal__content"><div class="vcs-countdown" data-countdown hidden><span class="vcs-countdown__label">Code expires in</span><span class="vcs-countdown__time" data-countdown-time>00:00</span></div><form class="vcs-form vcs-form--otp" novalidate><div class="vcs-field"><label>Email address</label><input type="email" name="email" required placeholder="you@example.com"></div><div class="vcs-actions"><button type="button" class="button button-primary send" data-loading="Sending&hellip;">Send code</button></div><div class="vcs-verify" hidden><div class="vcs-field"><label>Verification code</label><input type="text" name="code" inputmode="numeric" pattern="[0-9]{4,8}" required placeholder="Enter the code"></div><div class="vcs-actions"><button type="submit" class="button button-primary" data-loading="Verifying&hellip;">Verify &amp; continue</button></div><p class="vcs-hint">Didn&apos;t get the email? Check spam or tap &ldquo;Send code&rdquo; again to request a new one.</p></div></form></div>',
        onCreate(el){
          countdown = createCountdown(el);
        },
        onClose(){
          if (countdown){ countdown.stop(); countdown = null; }
          if (bc){
            try { bc.removeEventListener('message', bcHandler); } catch (e) { /* ignore */ }
          }
          modalEl = null;
          form = null;
          sendBtn = null;
          verifySection = null;
        }
      });
      ensureRefs();
      if (sendBtn && !sendBtn.dataset.originalLabel){
        sendBtn.dataset.originalLabel = sendBtn.dataset.defaultLabel || sendBtn.textContent || 'Send code';
      }
      restoreOtpState();
      if (bc){
        try { bc.addEventListener('message', bcHandler); } catch (e) { /* ignore */ }
      }
      modalEl.addEventListener('vcs:countdown-expired', (ev) => {
        if (!modalEl.contains(ev.target)) { return; }
        notice(modalEl, 'Your code expired. Tap "Resend code" to get a fresh one.', 'error');
        try { clearOtpState(); } catch (e) {}
        if (countdown){ countdown.stop(); }
        resetOtpUi({ keepEmail: true, keepResend: true, hideVerify: true });
      });
      sendBtn.addEventListener('click', function(){
        if (!ensureRefs()) { return; }
        const email = (form.email.value || '').trim();
        if (!email) { form.email.focus(); return; }

        setButtonState(sendBtn, true);

        ajax('ventraconnect_sl_otp_send', { email, context }).then(resp => {
          if (resp && resp.success){
            const expiresIn = Number(resp?.data?.expires_in) || 0;
            const serverExpiresAtFromResp = Number(resp?.data?.server_expires_at) || 0;
            const serverNowFromResp = Number(resp?.data?.server_now) || 0;
            const requestedAt = Date.now();
            const serverNow = serverNowFromResp || requestedAt;
            const serverExpiresAt = serverExpiresAtFromResp || (expiresIn ? (serverNow + expiresIn * 1000) : 0);

            try { saveOtpState(email, requestedAt, serverExpiresAt); } catch (e) {}
            const applied = restoreOtpState();
            if (!applied && countdown){
              const remaining = serverExpiresAt
                ? Math.max(0, Math.round((serverExpiresAt - Date.now()) / 1000))
                : expiresIn;
              countdown.start(remaining);
            }

            const ttl = expiresIn ? ` The code expires in <strong>${formatDuration(expiresIn)}</strong>.` : '';
            notice(modalEl, `We emailed you a verification code.${ttl}`, 'success');
            if (verifySection){ verifySection.hidden = false; }
            if (form.code){
              try { form.code.focus(); } catch (e) {}
            }
          } else {
            const code = resp && resp.data && resp.data.code ? resp.data.code : '';
            if (code === 'throttled' && resp.data && resp.data.retry_after){
              const secs = Number(resp.data.retry_after) || 0;
              const mins = Math.ceil(secs / 60);
              notice(modalEl, `You're sending codes too quickly. Please wait ${mins} minute${mins === 1 ? '' : 's'}.`, 'error');
            } else {
              const msg = formatError(resp, 'We could not send the code. Please try again.');
              notice(modalEl, msg, 'error');
            }
          }
        }).catch(() => {
          notice(modalEl, 'Request failed. Please try again.', 'error');
        }).finally(() => {
          setButtonState(sendBtn, false);
        });
      });

      form.addEventListener('submit', function(e){
        e.preventDefault();
        if (!ensureRefs()) { return; }
        const email = (form.email.value || '').trim();
        const code = (form.code.value || '').trim();
        if (!email || !code) { return; }
        const verifyBtn = qs('button[type="submit"]', form);
        setButtonState(verifyBtn, true);
        ajax('ventraconnect_sl_otp_verify', { email, code, redirect_to: currentRedirect() }).then(resp => {
          if (resp && resp.success && resp.data && resp.data.redirect){
            try { clearOtpState(); } catch (e) {}
            try { notice(modalEl, 'Code verified. Redirecting&hellip;', 'success'); } catch (e) {}
            setTimeout(function(){ try { window.location.href = resp.data.redirect; } catch (e) {} }, 700);
            return;
          }
          const codeStatus = resp && resp.data && resp.data.code ? resp.data.code : '';
          if (codeStatus === 'expired' || codeStatus === 'max_attempts' || codeStatus === 'used'){
            try { clearOtpState(); } catch (e) {}
            resetOtpUi({ keepEmail: true, keepResend: true, hideVerify: true });
          }
          const msg = formatError(resp, 'Invalid or expired code. Please try again.');
          notice(modalEl, msg, 'error');
        }).catch(() => notice(modalEl, 'Request failed. Please try again.', 'error')).finally(() => {
          setButtonState(verifyBtn, false);
        });
      });
  });
  function positionLoginButtons(){
    // Handle both the main login form and the register form.
    const mapping = [
      { formId: 'loginform', selector: '[data-vcs-login-buttons]' },
      { formId: 'registerform', selector: '[data-wsc-context="wp_register"]' }
    ];
    mapping.forEach(({ formId, selector }) => {
      const form = document.getElementById(formId);
      if (!form) { return; }
      const buttons = form.querySelector(selector) || document.querySelector(selector) || null;
      if (!buttons) { return; }
      const submitRow = form.querySelector('p.submit');
      if (!submitRow) { return; }
      let divider = form.querySelector('.vcs-login-divider[data-vcs-divider]');
      if (!divider) {
        const label = buttons.getAttribute('data-vcs-divider-label') || 'OR';
        divider = document.createElement('div');
        divider.className = 'vcs-login-divider';
        divider.setAttribute('data-vcs-divider', '1');
        divider.setAttribute('role', 'presentation');
        divider.innerHTML = `<span>${label}</span>`;
      }
      submitRow.insertAdjacentElement('afterend', divider);
      divider.insertAdjacentElement('afterend', buttons);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', positionLoginButtons);
  } else {
    positionLoginButtons();
  }
})();

// Generic VentraConnect error overlay for blocked new account creation.
(function () {
  function showVcslErrorOverlay(msg) {
    if (!msg) {
      return;
    }

    // Avoid duplicates.
    if (document.querySelector('.vcsl-error-overlay')) {
      return;
    }

    var overlay = document.createElement('div');
    overlay.className = 'vcsl-error-overlay';
    overlay.innerHTML =
      '<div class="vcsl-error-overlay__inner">' +
        '<div class="vcsl-error-overlay__title">Social login notice</div>' +
        '<p class="vcsl-error-overlay__message"></p>' +
        '<button type="button" class="vcsl-error-overlay__close">OK</button>' +
      '</div>';

    document.body.appendChild(overlay);

    var msgEl = overlay.querySelector('.vcsl-error-overlay__message');
    if (msgEl) {
      msgEl.textContent = msg;
    }

    // Clear notice triggers once after overlay is rendered.
    clearNoticeTriggersOnce();

    var btn = overlay.querySelector('.vcsl-error-overlay__close');
    if (btn) {
      btn.addEventListener('click', function () {
        overlay.remove();
      });
    }
  }

  function clearNoticeTriggersOnce() {
    // Run once per page load.
    if (window.__vcslNoticeCleared) {
      return;
    }
    window.__vcslNoticeCleared = true;

    try {
      var url = new URL(window.location.href);
      var keys = [
        'vcsl_err',
        'vcs_ld_err',
        'ventraconnect_sl_notice',
        'ventraconnect_sl_err'
      ];
      for (var i = 0; i < keys.length; i++) {
        url.searchParams.delete(keys[i]);
      }
      window.history.replaceState({}, document.title, url.toString());
    } catch (e) {
      // Fail silently.
    }

    try { window.sessionStorage.removeItem('vcsl_err'); } catch (e) {}
    try { window.localStorage.removeItem('vcsl_err'); } catch (e) {}
  }

  function onReady() {
    try {
      var params = new URLSearchParams(window.location.search);
      var code =
        params.get('vcsl_err') ||
        params.get('ventraconnect_sl_err') ||
        params.get('vcs_ld_err') ||
        '';

      if (code === 'new_account_blocked' || code === 'core_new_account_blocked') {
        var msg = 'Account not found. Please register before logging in.';
        showVcslErrorOverlay(msg);
      }
    } catch (e) {
      // Fail silently.
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }

  // Listen for OAuth popup bridge messages indicating blocked new account creation.
  if (!window.__vcslOauthMsgListenerAttached) {
    window.__vcslOauthMsgListenerAttached = true;
    try {
      window.addEventListener('message', function (event) {
        if (!event || typeof event.origin !== 'string') {
          return;
        }

        if (event.origin !== window.location.origin) {
          return;
        }

        var data = event && event.data;
        if (!data || typeof data !== 'object') {
          return;
        }
        if (data.type !== 'vcs_oauth_result') {
          return;
        }
        if (data.vcsl_err !== 'new_account_blocked') {
          return;
        }

        var msg = 'Account not found. Please register before logging in.';
        showVcslErrorOverlay(msg);
        clearNoticeTriggersOnce();
      });
    } catch (e) {
      // Fail silently.
    }
  }
})();
