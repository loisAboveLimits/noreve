/* Admin JS for VentraConnect Social Login */
(function(){
  function decodeIconPayload(encoded){
    if (!encoded) { return ''; }
    try {
      return decodeURIComponent(escape(atob(encoded)));
    } catch (e) {
      try { return atob(encoded); } catch (err) { return ''; }
    }
  }

  // Helper: attempt to parse JSON, but still expose raw body for debugging.
  function parseJsonOrText(resp) {
    return resp.text().then(function (text) {
      let json = null;
      let ok = false;
      try {
        json = text ? JSON.parse(text) : {};
        ok = true;
      } catch (e) {
        ok = false;
      }
      return {
        ok: ok,
        json: json,
        raw: text || '',
        status: typeof resp.status === 'number' ? resp.status : 0,
      };
    });
  }

  // Determine current provider from the first checked providers[] checkbox
  function getCurrentProvider(){
    const checked = document.querySelector('input[name="ventraconnect_sl_settings[providers][]"]:checked');
    return checked ? checked.value : (window.wscDefaultProvider || 'google');
  }

  function updatePreviewAndPanes() {
    const provider = getCurrentProvider();
    // Subtab panes per provider
    document.querySelectorAll('.wsc-subtab-panes').forEach(container => {
      const activeTab = container.previousElementSibling ? container.previousElementSibling.querySelector('.wsc-subtab.is-active') : null;
      const type = activeTab ? activeTab.getAttribute('data-type') : 'getting-started';
      container.querySelectorAll('.wsc-subtab-pane').forEach(pane => {
        const match = pane.getAttribute('data-provider') === provider && pane.getAttribute('data-type') === type;
        pane.classList.toggle('is-active', match);
        pane.style.display = match ? '' : 'none';
      });
    });
    // Update preview button
    const previewBtn = document.querySelector('.wsc-preview .wsc-buttons a.wsc-button');
    const previewImg = previewBtn ? previewBtn.querySelector('img') : null;
    const previewSpan = previewBtn ? previewBtn.querySelector('span') : null;
    if (previewBtn && previewImg && previewSpan) {
      const slug = provider;
      previewBtn.classList.remove('wsc-button-google','wsc-button-facebook','wsc-button-twitch','wsc-button-reddit');
      previewBtn.classList.add('wsc-button-' + slug);
      previewImg.src = (window.wscAssetsBase || '') + 'assets/img/provider-icons/' + slug + '.svg';
      previewSpan.textContent = 'Continue with ' + slug.charAt(0).toUpperCase() + slug.slice(1);
    }
  }

  function vcslFormatCount(template, count) {
    return String(template || '%d').replace('%d', String(count));
  }

  function vcslSetOverviewPill(pill, text, type) {
    if (!pill) return;
    pill.textContent = text || '';
    pill.classList.remove('wsc-pill-soft-success', 'wsc-pill-soft-info', 'wsc-pill-soft-warning', 'wsc-pill-soft-error');
    pill.classList.add('wsc-pill-soft-' + (type || 'info'));
  }

  function vcslGetOverviewRoot() {
    return document.querySelector('.wsc-overview');
  }

  function vcslGetProviderCheckbox(slug) {
    const escapedSlug = window.CSS && typeof window.CSS.escape === 'function' ? window.CSS.escape(slug) : slug;
    const paneSelector = '.wsc-provider-pane[data-provider="' + escapedSlug + '"] input[name="ventraconnect_sl_settings[providers][]"][value="' + escapedSlug + '"]';
    return document.querySelector(paneSelector) || document.querySelector('input[name="ventraconnect_sl_settings[providers][]"][value="' + escapedSlug + '"]');
  }

  function vcslGetProviderLabel(slug) {
    const escapedSlug = window.CSS && typeof window.CSS.escape === 'function' ? window.CSS.escape(slug) : slug;
    const nameEl = document.querySelector('.wsc-provider-item[data-provider="' + escapedSlug + '"] .name');
    return nameEl ? nameEl.textContent.trim() : slug;
  }

  function vcslIsProviderActive(slug) {
    const checkbox = vcslGetProviderCheckbox(slug);
    if (!checkbox) return false;
    if (slug === 'passkey' && checkbox.disabled) return false;
    return !!checkbox.checked;
  }

  window.vcslRefreshOverviewStatus = function () {
    const root = vcslGetOverviewRoot();
    if (!root) return;

    const activeLabel = root.getAttribute('data-label-active') || 'Active';
    const inactiveLabel = root.getAttribute('data-label-inactive') || 'Inactive';
    const passkeyPhpLabel = root.getAttribute('data-label-passkey-php') || 'Requires PHP 8.2+';
    const passkeyDescriptionSupported = root.getAttribute('data-passkey-description-supported') || '';
    const passkeyDescriptionInactive = root.getAttribute('data-passkey-description-inactive') || '';
    const passkeyDescriptionUnsupported = root.getAttribute('data-passkey-description-unsupported') || '';
    const passkeyDetailUnsupported = root.getAttribute('data-passkey-detail-unsupported') || '';
    const socialActiveTemplate = root.getAttribute('data-social-active-template') || '%d active';
    const socialDescriptionTemplate = root.getAttribute('data-social-description-template') || '%d of 15+ providers active.';
    const socialAvailableTemplate = root.getAttribute('data-social-available-template') || 'No social providers are active yet. Start with Google, or enable any provider below. You can drag providers to control button order.';
    const socialButtonInactive = root.getAttribute('data-social-button-inactive') || 'Set up providers';
    const socialButtonActive = root.getAttribute('data-social-button-active') || 'View all';
    const magicLinkDescriptionActive = root.getAttribute('data-magic-link-description-active') || '';
    const magicLinkDescriptionInactive = root.getAttribute('data-magic-link-description-inactive') || '';
    const otpDescriptionActive = root.getAttribute('data-otp-description-active') || '';
    const otpDescriptionInactive = root.getAttribute('data-otp-description-inactive') || '';
    const noActiveMessage = root.getAttribute('data-no-active-message') || 'No active login methods yet. Enable one from the Login Methods list.';
    const passkeySupported = root.getAttribute('data-passkey-supported') === '1';

    const orderedSlugs = Array.from(document.querySelectorAll('.wsc-providers-list > li[data-provider]')).map(function (item) {
      return String(item.getAttribute('data-provider') || '').trim();
    }).filter(Boolean);

    const socialSlugs = orderedSlugs.filter(function (slug) {
      return slug !== 'passkey' && slug !== 'magic_link' && slug !== 'otp_email';
    });
    const activeSocialSlugs = socialSlugs.filter(vcslIsProviderActive);

    [
      { slug: 'magic_link', type: 'single' },
      { slug: 'otp_email', type: 'single' },
      { slug: 'passkey', type: 'passkey' },
      { slug: 'social', type: 'social' }
    ].forEach(function (entry) {
      const card = root.querySelector('[data-overview-card="' + entry.slug + '"]');
      if (!card) return;
      const pill = card.querySelector('[data-overview-status]');
      const description = card.querySelector('[data-overview-description]');
      const detail = card.querySelector('[data-overview-detail]');
      const actionLink = card.querySelector('.wsc-overview-switch');

      if (entry.type === 'single') {
        const isActive = vcslIsProviderActive(entry.slug);
        vcslSetOverviewPill(pill, isActive ? activeLabel : inactiveLabel, isActive ? 'success' : 'info');
        if (description) {
          if (entry.slug === 'magic_link') {
            description.textContent = isActive ? magicLinkDescriptionActive : magicLinkDescriptionInactive;
          } else if (entry.slug === 'otp_email') {
            description.textContent = isActive ? otpDescriptionActive : otpDescriptionInactive;
          }
        }
        return;
      }

      if (entry.type === 'passkey') {
        const isActive = passkeySupported && vcslIsProviderActive('passkey');
        if (!passkeySupported) {
          vcslSetOverviewPill(pill, passkeyPhpLabel, 'warning');
          if (description) description.textContent = passkeyDescriptionUnsupported;
          if (detail) {
            detail.textContent = passkeyDetailUnsupported;
            detail.hidden = !passkeyDetailUnsupported;
          }
          if (actionLink) actionLink.textContent = 'View requirements';
        } else {
          vcslSetOverviewPill(pill, isActive ? activeLabel : inactiveLabel, isActive ? 'success' : 'info');
          if (description) description.textContent = isActive ? passkeyDescriptionSupported : passkeyDescriptionInactive;
          if (detail) {
            detail.textContent = '';
            detail.hidden = true;
          }
          if (actionLink) actionLink.textContent = 'Configure';
        }
        return;
      }

      if (entry.type === 'social') {
        vcslSetOverviewPill(
          pill,
          vcslFormatCount(socialActiveTemplate, activeSocialSlugs.length),
          activeSocialSlugs.length ? 'success' : 'info'
        );
        if (description) {
          description.textContent = activeSocialSlugs.length
            ? vcslFormatCount(socialDescriptionTemplate, activeSocialSlugs.length)
            : socialAvailableTemplate;
        }
        if (detail) {
          detail.textContent = activeSocialSlugs.length
            ? activeSocialSlugs.map(vcslGetProviderLabel).join(', ')
            : '';
          detail.hidden = !activeSocialSlugs.length;
        }
        if (actionLink) {
          actionLink.textContent = activeSocialSlugs.length ? socialButtonActive : socialButtonInactive;
        }
      }
    });

    const orderBody = root.querySelector('[data-overview-order-body]');
    if (orderBody) {
      const activeRows = orderedSlugs.filter(vcslIsProviderActive);
      orderBody.innerHTML = '';

      if (activeRows.length) {
        const list = document.createElement('ol');
        list.className = 'wsc-overview-order-list';

        activeRows.forEach(function (slug, index) {
          const item = document.createElement('li');
          item.className = 'wsc-overview-order-item';

          const position = document.createElement('span');
          position.className = 'wsc-overview-order-item__position';
          position.textContent = String(index + 1);

          const label = document.createElement('span');
          label.className = 'wsc-overview-order-item__label';
          label.textContent = vcslGetProviderLabel(slug);

          const status = document.createElement('span');
          status.className = 'wsc-pill wsc-pill-sm wsc-pill-soft-success';
          status.textContent = activeLabel;

          item.appendChild(position);
          item.appendChild(label);
          item.appendChild(status);
          list.appendChild(item);
        });

        orderBody.appendChild(list);
      } else {
        const empty = document.createElement('p');
        empty.className = 'wsc-muted';
        empty.textContent = noActiveMessage;
        orderBody.appendChild(empty);
      }
    }

    const passkeyNote = root.querySelector('[data-overview-passkey-note]');
    if (passkeyNote) {
      passkeyNote.hidden = !vcslGetProviderCheckbox('passkey') || passkeySupported || !vcslGetProviderCheckbox('passkey').checked;
    }
  };

  // Listen to providers[] checkbox changes
  document.querySelectorAll('input[name="ventraconnect_sl_settings[providers][]"]').forEach(cb => {
    cb.addEventListener('change', function(){
      updatePreviewAndPanes();
      if (window.vcslRefreshOverviewStatus) {
        window.vcslRefreshOverviewStatus();
      }
    });
  });
  // Initial
  updatePreviewAndPanes();
  if (window.vcslRefreshOverviewStatus) {
    window.vcslRefreshOverviewStatus();
  }

  function vcsResolvePagePickerFromSearchInput(searchInput) {
    if (!searchInput || !searchInput.getAttribute) {
      return null;
    }

    const inlinePicker = searchInput.closest('[data-vcs-page-picker]');
    if (inlinePicker) {
      return inlinePicker;
    }

    const targetId = searchInput.getAttribute('data-vcs-page-picker-target') || '';
    if (targetId) {
      return document.getElementById(targetId);
    }

    return null;
  }

  function vcsGetPagePickerState(picker) {
    if (!picker) {
      return null;
    }

    if (picker._vcsPagePickerState) {
      return picker._vcsPagePickerState;
    }

    const dataScript = picker.querySelector('[data-vcs-page-picker-data]');
    const selectedRoot = picker.querySelector('[data-vcs-page-picker-selected]');
    const resultsRoot = picker.querySelector('[data-vcs-page-picker-results]');
    const selectedEmpty = picker.querySelector('[data-vcs-page-picker-empty-selected]');
    const resultsEmpty = picker.querySelector('[data-vcs-page-picker-empty-results]');
    const searchInput = picker.querySelector('[data-vcs-page-picker-search]');
    const fieldName = picker.getAttribute('data-field-name') || 'ventraconnect_sl_settings[passkey][floating_panel_pages][]';
    let pages = [];

    if (dataScript) {
      try {
        pages = JSON.parse(dataScript.textContent || '[]');
      } catch (e) {
        pages = [];
      }
    }

    const selectedIds = new Set();
    if (selectedRoot) {
      selectedRoot.querySelectorAll('[data-vcs-page-picker-hidden-input]').forEach(function (input) {
        const id = parseInt(input.value, 10);
        if (id > 0) {
          selectedIds.add(id);
        }
      });
    }

    picker._vcsPagePickerState = {
      pages: Array.isArray(pages) ? pages : [],
      selectedIds: selectedIds,
      selectedRoot: selectedRoot,
      resultsRoot: resultsRoot,
      selectedEmpty: selectedEmpty,
      resultsEmpty: resultsEmpty,
      searchInput: searchInput,
      fieldName: fieldName
    };

    return picker._vcsPagePickerState;
  }

  function vcsBuildPageSearchText(page) {
    return [
      page && page.title ? page.title : '',
      page && page.slug ? page.slug : '',
      page && page.id ? String(page.id) : ''
    ].join(' ').toLowerCase();
  }

  function vcsCreatePagePickerButton(label, attrName, pageId, disabled) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'button button-secondary button-small vcs-page-picker__action';
    button.setAttribute(attrName, '1');
    button.setAttribute('data-page-id', String(pageId));
    button.textContent = label;
    if (disabled) {
      button.disabled = true;
    }
    return button;
  }

  function vcsRenderPagePickerSelected(picker) {
    const state = vcsGetPagePickerState(picker);
    if (!state || !state.selectedRoot) return;

    const disabled = !!(state.searchInput && state.searchInput.disabled);
    state.selectedRoot.innerHTML = '';

    const selectedPages = state.pages.filter(function (page) {
      return state.selectedIds.has(parseInt(page.id, 10));
    }).sort(function (a, b) {
      return String(a.title || '').localeCompare(String(b.title || ''));
    });

    selectedPages.forEach(function (page) {
      const item = document.createElement('div');
      item.className = 'vcs-page-picker__selected-item';
      item.setAttribute('data-vcs-page-picker-selected-item', '1');
      item.setAttribute('data-page-id', String(page.id));

      const content = document.createElement('div');
      content.className = 'vcs-page-picker__selected-content';

      const title = document.createElement('span');
      title.className = 'vcs-page-picker__title';
      title.textContent = page.title || ('Page #' + page.id);

      const meta = document.createElement('span');
      meta.className = 'vcs-page-picker__meta';
      meta.textContent = 'ID: ' + String(page.id);

      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = state.fieldName;
      hidden.value = String(page.id);
      hidden.setAttribute('data-vcs-page-picker-hidden-input', '1');

      content.appendChild(title);
      content.appendChild(meta);
      item.appendChild(content);
      item.appendChild(vcsCreatePagePickerButton('Remove', 'data-vcs-page-picker-remove', page.id, disabled));
      item.appendChild(hidden);
      state.selectedRoot.appendChild(item);
    });

    if (state.selectedEmpty) {
      state.selectedEmpty.hidden = state.selectedIds.size > 0;
    }
  }

  function vcsRenderPagePickerResults(picker, query) {
    const state = vcsGetPagePickerState(picker);
    if (!state || !state.resultsRoot) return;

    const disabled = !!(state.searchInput && state.searchInput.disabled);
    const normalizedQuery = String(query || '').trim().toLowerCase();
    const showAll = normalizedQuery === '';
    const results = state.pages.filter(function (page) {
      const pageId = parseInt(page.id, 10);
      if (state.selectedIds.has(pageId)) {
        return false;
      }
      return showAll || vcsBuildPageSearchText(page).indexOf(normalizedQuery) !== -1;
    }).sort(function (a, b) {
      return String(a.title || '').localeCompare(String(b.title || ''));
    });

    state.resultsRoot.innerHTML = '';

    results.forEach(function (page) {
      const item = document.createElement('div');
      item.className = 'vcs-page-picker__result';
      item.setAttribute('data-vcs-page-picker-result', '1');
      item.setAttribute('data-page-id', String(page.id));

      const content = document.createElement('div');
      content.className = 'vcs-page-picker__result-content';

      const title = document.createElement('span');
      title.className = 'vcs-page-picker__title';
      title.textContent = page.title || ('Page #' + page.id);

      const meta = document.createElement('span');
      meta.className = 'vcs-page-picker__meta';
      meta.textContent = page.slug
        ? 'ID: ' + String(page.id) + ' · /' + page.slug
        : 'ID: ' + String(page.id);

      content.appendChild(title);
      content.appendChild(meta);
      item.appendChild(content);
      item.appendChild(vcsCreatePagePickerButton('Add', 'data-vcs-page-picker-add', page.id, disabled));
      state.resultsRoot.appendChild(item);
    });

    if (state.resultsEmpty) {
      state.resultsEmpty.hidden = results.length > 0;
    }
  }

  function vcsRenderPagePicker(picker) {
    const state = vcsGetPagePickerState(picker);
    if (!state) return;
    const query = state.searchInput ? state.searchInput.value || '' : '';
    vcsRenderPagePickerSelected(picker);
    vcsRenderPagePickerResults(picker, query);
  }

  function vcsInitPagePickers(root) {
    (root || document).querySelectorAll('[data-vcs-page-picker]').forEach(function (picker) {
      vcsGetPagePickerState(picker);
      vcsRenderPagePicker(picker);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      vcsInitPagePickers(document);
    });
  } else {
    vcsInitPagePickers(document);
  }

  document.addEventListener('input', function (event) {
    if (!event.target || !event.target.matches || !event.target.matches('[data-vcs-page-picker-search]')) {
      return;
    }

    const picker = vcsResolvePagePickerFromSearchInput(event.target);
    if (!picker) return;

    vcsRenderPagePickerResults(picker, event.target.value || '');
  });

  document.addEventListener('keydown', function (event) {
    if (!event.target || !event.target.matches || !event.target.matches('[data-vcs-page-picker-search]')) {
      return;
    }

    if (event.key !== 'Enter') {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
  });

  document.addEventListener('search', function (event) {
    if (!event.target || !event.target.matches || !event.target.matches('[data-vcs-page-picker-search]')) {
      return;
    }

    const picker = vcsResolvePagePickerFromSearchInput(event.target);
    if (!picker) return;

    vcsRenderPagePickerResults(picker, event.target.value || '');
  });

  document.addEventListener('click', function (event) {
    const addButton = event.target.closest('[data-vcs-page-picker-add]');
    const removeButton = event.target.closest('[data-vcs-page-picker-remove]');
    const actionButton = addButton || removeButton;
    if (!actionButton) {
      return;
    }

    const picker = actionButton.closest('[data-vcs-page-picker]');
    const state = vcsGetPagePickerState(picker);
    const pageId = parseInt(actionButton.getAttribute('data-page-id') || '0', 10);

    if (!state || pageId <= 0) {
      return;
    }

    event.preventDefault();

    if (addButton) {
      state.selectedIds.add(pageId);
    } else {
      state.selectedIds.delete(pageId);
    }

    vcsRenderPagePicker(picker);
  });

  if (window.MutationObserver) {
    const pagePickerObserver = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (!node || node.nodeType !== 1) {
            return;
          }

          if (node.matches && node.matches('[data-vcs-page-picker]')) {
            vcsInitPagePickers(node.parentNode || document);
            return;
          }

          if (node.querySelector && node.querySelector('[data-vcs-page-picker]')) {
            vcsInitPagePickers(node);
          }
        });
      });
    });

    if (document.body) {
      pagePickerObserver.observe(document.body, { childList: true, subtree: true });
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        if (document.body) {
          pagePickerObserver.observe(document.body, { childList: true, subtree: true });
        }
      });
    }
  }
  // Copy buttons (robust: prefer closest code in same row)
  // Robust copy handler: delegated + container-first lookup
document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.wsc-copy');
  if (!btn) return;
  e.preventDefault();

  // Scope search to the nearest container first
  const container = btn.closest('.wsc-row, .wsc-card, .wrap') || document;

  const sel = btn.getAttribute('data-copy');
  let target = null;

  if (sel) {
    // Try inside container first, then global as fallback
    target = container.querySelector(sel) || document.querySelector(sel);
  }
  if (!target) {
    target = container.querySelector('code, input[type="text"], textarea');
  }
  if (!target) return;

  // Get text (CODE nodes decode &amp; → & via textContent)
  const text =
    (target.dataset && target.dataset.copySource) ||
    (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA'
      ? (target.value || '')
      : (target.textContent || '')).trim();

  if (!text) return;

  try {
    await navigator.clipboard.writeText(text);
    const old = btn.textContent;
    btn.textContent = btn.getAttribute('data-copied-label') || 'Copied';
    btn.classList.add('copied');
    setTimeout(() => {
      btn.textContent = btn.getAttribute('data-label') || old || 'Copy';
      btn.classList.remove('copied');
    }, 1500);
  } catch {
    // Fallback for old browsers
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  }
});

  // Provider sub-tabs
  document.querySelectorAll('.wsc-subtabs').forEach(subtabs => {
    const tabs = subtabs.querySelectorAll('.wsc-subtab');
    const container = subtabs.nextElementSibling; // panes wrapper
    function activate(tab){
      tabs.forEach(x=>x.classList.remove('is-active'));
      tab.classList.add('is-active');
      const type = tab.getAttribute('data-type');
      const provider = getCurrentProvider();
      container.querySelectorAll('.wsc-subtab-pane').forEach(pane => {
        const match = pane.getAttribute('data-provider') === provider && pane.getAttribute('data-type') === type;
        pane.classList.toggle('is-active', match);
        pane.style.display = match ? '' : 'none';
      });
    }
    tabs.forEach(t=>t.addEventListener('click', function(e){ e.preventDefault(); activate(this); }));
    // initial
    const current = subtabs.querySelector('.wsc-subtab.is-active') || tabs[0];
    if (current) activate(current);
  });

  // Providers grid: open Getting Started
  function selectProvider(slug){
    window.wscDefaultProvider = slug;
    // Navigate to Providers tab with provider pre-selected (PHP will render correct state)
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('page', 'ventraconnect-sl-settings');
      url.searchParams.set('tab', 'providers');
      url.searchParams.set('provider', slug);
      window.location.href = url.toString();
    } catch (e) {
      window.location.href = 'admin.php?page=ventraconnect-sl-settings&tab=providers&provider=' + encodeURIComponent(slug);
    }
  }
  window.wscSelectProvider = selectProvider;
  document.querySelectorAll('.wsc-provider-card .get-started').forEach(btn => {
    btn.addEventListener('click', function(e){ e.preventDefault(); selectProvider(this.getAttribute('data-provider')); });
  });
  document.querySelectorAll('.wsc-provider-card .configure').forEach(btn => {
    btn.addEventListener('click', function(e){
      // Navigate to deep configuration page (server-rendered)
      const slug = this.getAttribute('data-provider');
      const url = new URL(window.location.href);
      url.searchParams.set('view', 'provider');
      url.searchParams.set('provider', slug);
      window.location.href = url.toString();
      e.preventDefault();
    });
  });

  // Remove legacy inline Provider Setup row on General tab
  const generalProviderRow = document.querySelector('#wsc-tab-general .wsc-subtabs');
  if (generalProviderRow) {
    const tr = generalProviderRow.closest('tr');
    if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
  }

  // New Providers dashboard: left list toggles right pane
  function activateOverviewPane(){
    document.querySelectorAll('.wsc-provider-overview-item').forEach(it => {
      it.classList.add('is-active');
    });
    document.querySelectorAll('.wsc-provider-item').forEach(it => {
      it.classList.remove('is-active');
    });
    document.querySelectorAll('.wsc-provider-pane').forEach(pane => {
      const isOverview = pane.hasAttribute('data-overview-panel');
      pane.classList.toggle('is-active', isOverview);
    });
    try {
      const url = new URL(window.location.href);
      url.searchParams.delete('provider');
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  }
  function activateProvider(slug){
    document.querySelectorAll('.wsc-provider-overview-item').forEach(it => {
      it.classList.remove('is-active');
    });
    // Sidebar active state
    document.querySelectorAll('.wsc-provider-item').forEach(it => {
      it.classList.toggle('is-active', it.getAttribute('data-provider') === slug);
    });
    // Panes
    document.querySelectorAll('.wsc-provider-pane').forEach(pane => {
      pane.classList.toggle('is-active', pane.getAttribute('data-provider') === slug);
    });
    // Update URL (no reload)
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('provider', slug);
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  }
  document.querySelectorAll('.wsc-provider-item').forEach(btn => {
    btn.addEventListener('click', function(){ activateProvider(this.getAttribute('data-provider')); });
  });
  document.querySelectorAll('.wsc-provider-overview-item').forEach(btn => {
    btn.addEventListener('click', function(){
      activateOverviewPane();
      if (window.vcslRefreshOverviewStatus) {
        window.vcslRefreshOverviewStatus();
      }
    });
  });
  document.querySelectorAll('.wsc-overview-switch').forEach(link => {
    link.addEventListener('click', function(e){
      const target = this.getAttribute('data-provider-target');
      if (!target) return;
      e.preventDefault();
      activateProvider(target);
    });
  });

  // Reflect Active toggle into left status dot in real time
  document.querySelectorAll('.wsc-provider-pane .wsc-switch-input').forEach(input => {
    input.addEventListener('change', function(){
      const slug = this.closest('.wsc-provider-pane').getAttribute('data-provider');
      const dot = document.querySelector('.wsc-provider-item[data-provider="' + slug + '"] .wsc-dot');
      if (!dot) return;
      dot.classList.toggle('active', this.checked);
      dot.classList.toggle('inactive', !this.checked);
      if (window.vcslRefreshOverviewStatus) {
        window.vcslRefreshOverviewStatus();
      }
    });
  });

  // (Removed) Main tab persistence and hash-based navigation; now PHP drives ?tab= pages.

  // --- Button Style live preview (per provider) ---
  function findPreviewAnchor(fromEl){
    let root = fromEl.closest('.wsc-provider-pane') || fromEl.closest('.wsc-card') || document;
    return root.querySelector('a.vcs-btn');
  }
  function updateProviderPreviewTheme(anchor, themeValue){
    if (!anchor) return;
    anchor.setAttribute('data-theme', themeValue);
    const iconWrap = anchor.querySelector('.vcs-btn__icon');
    if (!iconWrap) return;
    const encodedIcon = anchor.getAttribute('data-icon-' + themeValue);
    if (encodedIcon !== null) {
      const decoded = decodeIconPayload(encodedIcon);
      if (decoded) {
        iconWrap.innerHTML = decoded;
      }
    }
  }
  function syncThemeOverride(toggle){
    const provider = toggle.getAttribute('data-provider') || '';
    const card = toggle.closest('.wsc-card');
    if (!provider || !card) return;
    const radios = card.querySelectorAll('.vcs-provider-theme[data-provider="' + provider + '"]');
    const active = toggle.checked && !toggle.disabled;
    radios.forEach(radio => {
      radio.disabled = !active;
      if (!active) {
        radio.setAttribute('aria-disabled', 'true');
      } else {
        radio.removeAttribute('aria-disabled');
      }
    });
    const previewAnchor = findPreviewAnchor(toggle);
    const globalTheme = toggle.getAttribute('data-global-theme') || 'light';
    const savedThemeAttr = toggle.getAttribute('data-saved-theme') || '';
    if (!active) {
      updateProviderPreviewTheme(previewAnchor, globalTheme);
      return;
    }
    let targetTheme = savedThemeAttr || globalTheme;
    let checkedRadio = card.querySelector('.vcs-provider-theme[data-provider="' + provider + '"]:checked');
    if (checkedRadio && savedThemeAttr && checkedRadio.value !== savedThemeAttr) {
      const savedRadio = Array.from(radios).find(r => r.value === savedThemeAttr);
      if (savedRadio) {
        savedRadio.checked = true;
        checkedRadio = savedRadio;
      }
    }
    if (!checkedRadio) {
      checkedRadio = Array.from(radios).find(r => r.value === targetTheme);
    }
    if (!checkedRadio && radios.length) {
      checkedRadio = radios[0];
      checkedRadio.checked = true;
    }
    if (checkedRadio) {
      targetTheme = checkedRadio.value;
    }
    toggle.setAttribute('data-saved-theme', targetTheme);
    updateProviderPreviewTheme(previewAnchor, targetTheme);
  }
  document.addEventListener('change', function(e){
    const themeInput = e.target.closest('.vcs-provider-theme');
    if (themeInput && !themeInput.disabled) {
      const a = findPreviewAnchor(themeInput);
      updateProviderPreviewTheme(a, themeInput.value);
      const wrap = themeInput.closest('.wsc-card');
      const toggle = wrap ? wrap.querySelector('.vcs-provider-theme-override[data-provider="' + themeInput.dataset.provider + '"]') : null;
      if (toggle) {
        toggle.setAttribute('data-saved-theme', themeInput.value);
      }
    }
    const overrideToggle = e.target.closest('.vcs-provider-theme-override');
    if (overrideToggle) {
      syncThemeOverride(overrideToggle);
    }
  });
  document.querySelectorAll('.vcs-provider-theme-override').forEach(syncThemeOverride);

  if (window.jQuery){
    (function($){
      if ($.fn && $.fn.wpColorPicker) {
        $('.vcs-color-picker').filter(':not(:disabled)').wpColorPicker();
      }

      $(document).on('click', '.vcs-branding-logo-upload', function(e){
        e.preventDefault();
        const $btn = $(this);
        const $input = $btn.closest('.wsc-field__control').find('.vcs-branding-logo-url').first();
        if ($btn.is(':disabled') || $input.is(':disabled') || !$input.length || typeof wp === 'undefined' || !wp.media) return;

        const frame = wp.media({
          title: 'Select logo',
          button: { text: 'Use this image' },
          multiple: false,
          library: { type: 'image' }
        });

        frame.on('select', function(){
          const attachment = frame.state().get('selection').first().toJSON();
          if (attachment && attachment.url) {
            $input.val(attachment.url).trigger('change');
          }
        });

        frame.open();
      });
    })(window.jQuery);
  }
  document.addEventListener('input', function(e){
    const textInput = e.target.closest('.vcs-provider-text');
    if (textInput && !textInput.disabled) {
      const a = findPreviewAnchor(textInput);
      if (a) {
        const lbl = a.querySelector('.vcs-btn__label');
        if (lbl) lbl.textContent = textInput.value || lbl.textContent;
        if (a.classList.contains('vcs-btn--compact')) {
          a.setAttribute('aria-label', textInput.value || '');
        }
      }
    }
  });
  // (Removed) Previously persisted main tab/provider in localStorage. Provider selection now uses ?provider= and full reload.

  // jQuery UI Sortable for providers list (drag handle), init when Providers tab visible
  function initWscSortable(){
    if (!window.jQuery) return;
    (function($){
      const $list = $('.wsc-providers-list');
      if (!$list.length || !$list.is(':visible') || $list.data('sortable-inited')) return;
      if (!$.fn.sortable) return;
      $list.sortable({
        items: '> li',
        axis: 'y',
        tolerance: 'pointer',
        helper: 'clone',
        forcePlaceholderSize: true,
        placeholder: 'ui-sortable-placeholder',
        cancel: 'a, input, select, textarea, label',
        start: function(e, ui){ ui.placeholder.height(ui.item.outerHeight()); },
        update: function(){
          const order = $list.children('li').map(function(){ return $(this).data('provider'); }).get();
          const data = {
            action: 'vc_save_provider_order',
            // Prefer dedicated nonce when available, fallback for backward compatibility
            nonce: (window.VCS_ADMIN && (VCS_ADMIN.provider_order_nonce || VCS_ADMIN.nonce)) || '',
            order: order
          };
          if (window.vcslRefreshOverviewStatus) {
            window.vcslRefreshOverviewStatus();
          }
          $.post((window.VCS_ADMIN && VCS_ADMIN.ajax_url) || (window.ajaxurl || ''), data).done(function(){
            if (window.vcslRefreshOverviewStatus) {
              window.vcslRefreshOverviewStatus();
            }
          });
        }
      });
      $list.data('sortable-inited', true);
      // Do not block Sortable mousedown; allow drag from full LI
    })(window.jQuery);
  }
  // Init sortable when Providers tab is present on this page (PHP-driven tabs)
  if (document.getElementById('wsc-tab-providers')){
    initWscSortable();
  }

  // Provider-level diagnostics (inline) for legacy per-provider card.
  if (window.jQuery){
    (function($){
      $(document).on('click', '.wsc-run-diag', function(e){
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');
        const $wrap = $btn.closest('.wsc-card');
        const $outWrap = $wrap.find('.wsc-diag-results');
        const $pre = $wrap.find('.wsc-diag-pre');
        if (!$pre.length) return;
        $outWrap.show();
        $pre.text('Running diagnostics...');
        $.get(url, function(resp){
          if (resp && resp.success && resp.data && resp.data.lines){
            $pre.text(resp.data.lines.join('\n'));
          } else if (resp && resp.data && resp.data.message){
            $pre.text('Error: ' + resp.data.message);
          } else {
            $pre.text('Unexpected response.');
          }
        }).fail(function(){ $pre.text('Request failed.'); });
      });
    })(window.jQuery);
  }

  // Site-level Diagnostics & Tools tab: run diagnostics + copy blob.
  document.addEventListener('click', function (event) {
    const runBtn = event.target.closest('.wsc-diag-run-site');
    if (runBtn) {
      event.preventDefault();

      const root = runBtn.closest('.wsc-diag-root');
      if (!root) return;

      const nonce = root.getAttribute('data-wsc-diag-nonce') || root.getAttribute('data-wsc-diag-nonce'.replace(/_/g, '_'));
      // Attribute is data-wsc-diag-nonce in markup.
      const diagNonce = root.getAttribute('data-wsc-diag-nonce') || '';
      if (!diagNonce) return;

      const resultsEl = root.querySelector('.wsc-diag-results');
      const eventsEl  = root.querySelector('.wsc-diag-events');
      const blobEl    = root.querySelector('.wsc-diag-support-blob');

      if (resultsEl) {
        resultsEl.textContent = '';
        resultsEl.classList.remove('wsc-diag-results--error');
        resultsEl.classList.add('wsc-diag-results--loading');
        resultsEl.textContent = runBtn.getAttribute('data-wsc-diag-loading-text') || 'Running diagnostics...';
      }

      runBtn.disabled = true;

      const ajaxUrl = (window.VCS_ADMIN && VCS_ADMIN.ajax_url) || window.ajaxurl || '';
      if (!ajaxUrl) {
        if (resultsEl) {
          resultsEl.classList.remove('wsc-diag-results--loading');
          resultsEl.classList.add('wsc-diag-results--error');
          resultsEl.textContent = 'AJAX URL is not available.';
        }
        runBtn.disabled = false;
        return;
      }

      const url = ajaxUrl + '?action=ventraconnect_sl_site_diagnostics&_wpnonce=' + encodeURIComponent(diagNonce);

      function handleError(msg){
        if (resultsEl) {
          resultsEl.classList.remove('wsc-diag-results--loading');
          resultsEl.classList.add('wsc-diag-results--error');
          resultsEl.textContent = msg || 'Diagnostics request failed.';
        }
      }

      function handleDiagResponse(data){
        if (!resultsEl) {
          return;
        }
        resultsEl.classList.remove('wsc-diag-results--loading');
        resultsEl.textContent = '';

        if (!data || !data.success) {
          const msg = data && data.data && data.data.message ? data.data.message : 'Diagnostics request failed.';
          resultsEl.classList.add('wsc-diag-results--error');
          resultsEl.textContent = msg;
          return;
        }

        const payload   = data.data || {};
        const checks    = payload.checks || {};
        const snapshot  = payload.snapshot || {};
        const rootEl    = root;
        const integrationsContainer = rootEl ? rootEl.querySelector('.wsc-diag-integrations') : null;

        const list = document.createElement('ul');
        list.className = 'wsc-diag-checks-list';

        Object.keys(checks).forEach(function (key) {
          const entry = checks[key] || {};
          const ok    = !!entry.ok;
          const detail = entry.detail || '';

          const li = document.createElement('li');
          li.className = ok ? 'wsc-diag-check wsc-diag-check--ok' : 'wsc-diag-check wsc-diag-check--fail';

          const label = document.createElement('strong');
          label.textContent = key.replace(/_/g, ' ');

          li.appendChild(label);
          if (detail) {
            const span = document.createElement('span');
            span.textContent = ' – ' + detail;
            li.appendChild(span);
          }

          list.appendChild(li);
        });

        if (!list.children.length) {
          const p = document.createElement('p');
          p.textContent = 'No diagnostics checks were returned.';
          resultsEl.appendChild(p);
        } else {
          resultsEl.appendChild(list);
        }

        if (eventsEl && Array.isArray(payload.events)) {
          eventsEl.textContent = '';
          if (!payload.events.length) {
            const p = document.createElement('p');
            p.className = 'description';
            p.textContent = 'No diagnostic events are available yet.';
            eventsEl.appendChild(p);
          } else {
            const ul = document.createElement('ul');
            ul.className = 'wsc-diag-events-list';
            payload.events.forEach(function (eventObj) {
              if (!eventObj || typeof eventObj !== 'object') return;
              const li = document.createElement('li');
              const time = eventObj.timestamp || eventObj.time || '';
              const context = eventObj.context || eventObj.provider || '';
              const message = eventObj.message || eventObj.detail || '';
              const parts = [];
              if (time) parts.push('[' + time + ']');
              if (context) parts.push(context + ':');
              if (message) parts.push(message);
              li.textContent = parts.join(' ');
              ul.appendChild(li);
            });
            eventsEl.appendChild(ul);
          }
        }

        if (blobEl && typeof payload.support_blob === 'string') {
          blobEl.value = payload.support_blob;
        }

        // Render Pro integrations status into the dedicated container, if present.
        if (integrationsContainer) {
          const integrations = snapshot && snapshot.integrations ? snapshot.integrations : null;
          renderIntegrationsStatus(integrationsContainer, integrations);
        }
      }

      function renderIntegrationsStatus(container, integrations){
        // Clear previous content.
        while (container.firstChild) {
          container.removeChild(container.firstChild);
        }

        if (!integrations || typeof integrations !== 'object') {
          const p = document.createElement('p');
          p.className = 'description';
          p.textContent = 'No integration data returned.';
          container.appendChild(p);
          return;
        }

        const groups = [
          { key: 'community_memberships', label: 'Community & Memberships' },
          { key: 'courses_lms', label: 'Courses & LMS' }
        ];

        let renderedAnyGroup = false;

        groups.forEach(function(group){
          const groupData = integrations[group.key];
          if (!groupData || typeof groupData !== 'object') {
            return;
          }

          const pluginsObj = groupData.plugins && typeof groupData.plugins === 'object' ? groupData.plugins : {};
          const pluginKeys = Object.keys(pluginsObj);
          const summary    = groupData.summary && typeof groupData.summary === 'object' ? groupData.summary : {};

          const heading = document.createElement('h3');
          heading.className = 'wsc-diag-integrations__heading';
          heading.textContent = group.label;
          container.appendChild(heading);

          const summaryLine = document.createElement('p');
          summaryLine.className = 'description';
          const installedCount = typeof summary.installed_count === 'number' ? summary.installed_count : 0;
          const activeCount    = typeof summary.active_count === 'number' ? summary.active_count : 0;
          const vcEnabledCount = typeof summary.vc_enabled_count === 'number' ? summary.vc_enabled_count : 0;
          summaryLine.textContent =
            'Installed: ' + installedCount + ' • Active: ' + activeCount + ' • VC enabled: ' + vcEnabledCount;
          container.appendChild(summaryLine);

          if (!pluginKeys.length) {
            const empty = document.createElement('p');
            empty.className = 'description';
            empty.textContent = 'No supported integrations detected.';
            container.appendChild(empty);
            renderedAnyGroup = true;
            return;
          }

          const table = document.createElement('table');
          table.className = 'widefat striped wsc-diag-integrations-table';

          const thead = document.createElement('thead');
          const headerRow = document.createElement('tr');
          ['Integration', 'Version', 'Installed', 'Active', 'VC Integration'].forEach(function(col){
            const th = document.createElement('th');
            th.textContent = col;
            headerRow.appendChild(th);
          });
          thead.appendChild(headerRow);
          table.appendChild(thead);

          const tbody = document.createElement('tbody');

          pluginKeys.forEach(function(slug){
            const plugin = pluginsObj[slug] || {};
            const row = document.createElement('tr');

            const labelCell = document.createElement('td');
            labelCell.textContent = plugin.label || slug;
            row.appendChild(labelCell);

            const versionCell = document.createElement('td');
            const rawVersion = plugin.version || '';
            const isInstalled = !!plugin.installed;
            if (rawVersion && isInstalled) {
              versionCell.textContent = String(rawVersion);
            } else {
              versionCell.textContent = '—';
            }
            row.appendChild(versionCell);

            const installedCell = document.createElement('td');
            installedCell.textContent = isInstalled ? 'Yes' : 'No';
            row.appendChild(installedCell);

            const isActive = !!plugin.plugin_active;
            const activeCell = document.createElement('td');
            activeCell.textContent = isActive ? 'Yes' : 'No';
            row.appendChild(activeCell);

            const vcCell = document.createElement('td');
            const vcEnabled = !!plugin.vc_enabled;
            vcCell.textContent = (isActive && vcEnabled) ? 'On' : 'Off';
            row.appendChild(vcCell);

            tbody.appendChild(row);
          });

          table.appendChild(tbody);
          container.appendChild(table);

          renderedAnyGroup = true;
        });

        if (!renderedAnyGroup) {
          const p = document.createElement('p');
          p.className = 'description';
          p.textContent = 'No integration data returned.';
          container.appendChild(p);
        }
      }

      if (window.fetch) {
        fetch(url, { method: 'GET', credentials: 'same-origin' })
          .then(parseJsonOrText)
          .then(function (result) {
            if (!result.ok) {
              const snippet = (result.raw || '').slice(0, 200);
              let msg = 'Non-JSON response received. This usually indicates a PHP notice or fatal error.';
              if (snippet) {
                msg += ' First 200 chars: ' + snippet;
              }
              handleError(msg);
              return;
            }
            handleDiagResponse(result.json);
          })
          .catch(function (err) { handleError(String(err)); })
          .finally(function () { runBtn.disabled = false; });
      } else {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.withCredentials = true;
        xhr.onreadystatechange = function () {
          if (xhr.readyState !== 4) return;
          try {
            const json = JSON.parse(xhr.responseText || '{}');
            handleDiagResponse(json);
          } catch (e) {
            const raw = xhr.responseText || '';
            let msg = 'Non-JSON response received. This usually indicates a PHP notice or fatal error.';
            const snippet = raw ? raw.slice(0, 200) : '';
            if (snippet) {
              msg += ' First 200 chars: ' + snippet;
            }
            handleError(msg);
          }
          runBtn.disabled = false;
        };
        xhr.onerror = function () {
          handleError('Diagnostics request failed.');
          runBtn.disabled = false;
        };
        xhr.send();
      }

      return;
    }

    const copyBtn = event.target.closest('.wsc-diag-copy-support');
    if (copyBtn) {
      event.preventDefault();

      const root = copyBtn.closest('.wsc-diag-root');
      if (!root) return;

      const textarea = root.querySelector('.wsc-diag-support-blob');
      if (!textarea) return;

      const value = textarea.value || '';
      if (!value) return;

      const originalText = copyBtn.textContent;
      function markDone(ok){
        const copiedText = copyBtn.getAttribute('data-wsc-diag-copied-text') || 'Copied!';
        const failedText = copyBtn.getAttribute('data-wsc-diag-copy-failed-text') || 'Copy failed';
        copyBtn.textContent = ok ? copiedText : failedText;
        setTimeout(function () {
          copyBtn.textContent = originalText;
        }, 2000);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(function () {
          markDone(true);
        }).catch(function () {
          markDone(false);
        });
      } else {
        textarea.focus();
        textarea.select();
        try {
          const ok = document.execCommand('copy');
          markDone(ok);
        } catch (e) {
          markDone(false);
        }
      }

      return;
    }

    const testBtn = event.target.closest('.wsc-diag-test-passwordless');
    if (testBtn) {
      event.preventDefault();

      const method = testBtn.getAttribute('data-wsc-diag-method') || '';
      if (!method) return;

      const root = testBtn.closest('.wsc-diag-root');
      if (!root) return;

      const diagNonce = root.getAttribute('data-wsc-diag-nonce') || '';
      if (!diagNonce) return;

      const statusEl = root.querySelector('.wsc-diag-passwordless-status');

      function setStatus(msg, isError) {
        if (!statusEl) return;
        statusEl.textContent = msg || '';
        statusEl.classList.toggle('wsc-diag-passwordless-status--error', !!isError);
      }

      const ajaxUrl = (window.VCS_ADMIN && VCS_ADMIN.ajax_url) || window.ajaxurl || '';
      if (!ajaxUrl) {
        setStatus('Diagnostics AJAX URL is not available.', true);
        return;
      }

      testBtn.disabled = true;
      setStatus(
        testBtn.getAttribute('data-wsc-diag-loading-text') || 'Sending test email...',
        false
      );

      const params = new URLSearchParams();
      params.append('action', 'ventraconnect_sl_diag_passwordless_test');
      params.append('method', method);
      params.append('_wpnonce', diagNonce);

      function handleResponse(data) {
        // Non-object response: assume request completed and advise to check inbox.
        if (!data || typeof data !== 'object') {
          setStatus(
            'Test request completed. Check the admin inbox to confirm delivery.',
            false
          );
          return;
        }

        // Standard WP AJAX error: { success: false, data: { message: '...' } }
        if (data.success === false) {
          var errMsg =
            data.data && typeof data.data === 'object' && data.data.message
              ? String(data.data.message)
              : 'Passwordless test request failed.';
          setStatus(errMsg, true);
          return;
        }

        // Success or other truthy shape.
        var payload =
          (data.data && typeof data.data === 'object' ? data.data : null) || {};
        var msg =
          payload.message ||
          payload.detail ||
          'Test email sent. Check the admin inbox.';
        setStatus(msg, false);
      }

      if (window.fetch) {
        fetch(ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          },
          body: params.toString(),
        })
          .then(parseJsonOrText)
          .then(function (result) {
            if (!result.ok) {
              const snippet = (result.raw || '').slice(0, 200);
              let msg = 'Non-JSON response received. This usually indicates a PHP notice or fatal error.';
              if (snippet) {
                msg += ' First 200 chars: ' + snippet;
              }
              setStatus(msg, true);
              return;
            }
            handleResponse(result.json);
          })
          .catch(function (err) {
            let msg = 'Passwordless test request failed.';
            if (err && err.message) {
              msg += ' ' + String(err.message);
            }
            setStatus(msg, true);
          })
          .finally(function () { testBtn.disabled = false; });
      } else {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxUrl, true);
        xhr.withCredentials = true;
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onreadystatechange = function () {
          if (xhr.readyState !== 4) return;
          try {
            const json = JSON.parse(xhr.responseText || '{}');
            handleResponse(json);
          } catch (e) {
            const raw = xhr.responseText || '';
            let msg = 'Non-JSON response received. This usually indicates a PHP notice or fatal error.';
            const snippet = raw ? raw.slice(0, 200) : '';
            if (snippet) {
              msg += ' First 200 chars: ' + snippet;
            }
            setStatus(msg, true);
          }
          testBtn.disabled = false;
        };
        xhr.onerror = function () {
          setStatus('Passwordless test request failed. Check server logs.', true);
          testBtn.disabled = false;
        };
        xhr.send(params.toString());
      }

      return;
    }
  });
  // Test email buttons for Magic Link / OTP
  if (window.jQuery){
    (function($){
      $(document).on('click', '#vcs-test-magic', function(){
        const email = $(this).data('email') || '';
        const nonce = (window.VCS_AUTH && (VCS_AUTH.nonce || VCS_AUTH.nonce_legacy)) || '';
        const data = { action: 'ventraconnect_sl_magic_link_send', nonce: nonce, email: email, context: 'admin-test' };
        $.post((window.ajaxurl || ''), data).done(function(){ alert('Test magic link sent to ' + email); }).fail(function(){ alert('Failed to send'); });
      });
      $(document).on('click', '#vcs-test-otp', function(){
        const email = $(this).data('email') || '';
        const nonce = (window.VCS_AUTH && (VCS_AUTH.nonce || VCS_AUTH.nonce_legacy)) || '';
        const data = { action: 'ventraconnect_sl_otp_send', nonce: nonce, email: email, context: 'admin-test' };
        $.post((window.ajaxurl || ''), data).done(function(){ alert('Test OTP sent to ' + email); }).fail(function(){ alert('Failed to send'); });
      });
    })(window.jQuery);
  }
  if (window.jQuery){
    (function($){
      $(document).on('click', '.vcs-resync-bulk', function(e){
        e.preventDefault();
        const $btn = $(this);
        if ($btn.hasClass('is-running')) return;
        const ajaxUrl = (window.VCS_ADMIN && VCS_ADMIN.ajax_url) || (window.ajaxurl || '');
        if (!ajaxUrl) return;
        const provider = $btn.data('provider') || '';
        const nonce = $btn.data('nonce') || $btn.data('nonce-legacy') || '';
        const $wrap = $btn.closest('.wsc-resync-tools');
        const $out = $wrap.find('.vcs-resync-output');
        const $pre = $out.find('pre');
        const original = $btn.text();
        const reset = () => { $btn.removeClass('is-running').prop('disabled', false).text(original); };
        $btn.addClass('is-running').prop('disabled', true).text('Resyncing...');
        $out.show();
        $pre.text('Preparing dry-run...');
        $.post(ajaxUrl, { action: 'ventraconnect_sl_resync_bulk', nonce: nonce, provider: provider, dry_run: 1 }).done(function(resp){
          if (!resp || !resp.success || !resp.data) {
            $pre.text((resp && resp.data && resp.data.message) ? resp.data.message : 'Dry-run failed.');
            reset();
            return;
          }
          const total = resp.data.total || 0;
          if (!total) {
            $pre.text('Nothing to resync for this provider.');
            reset();
            return;
          }
          $pre.text('Accounts queued: ' + total + '\nRunning batch...');
          $.post(ajaxUrl, { action: 'ventraconnect_sl_resync_bulk', nonce: nonce, provider: provider, batch: 25 }).done(function(result){
            if (result && result.success && result.data) {
              const data = result.data;
              const processed = data.processed || 0;
              const skipped = data.skipped || 0;
              const remaining = data.remaining || 0;
              let text = 'Processed ' + processed + ' account(s).';
              if (skipped) {
                text += '\nSkipped ' + skipped + ' account(s) with no snapshot.';
              }
              text += remaining ? '\nRemaining (queued for next run): ' + remaining : '\nAll caught up.';
              $pre.text(text);
            } else {
              $pre.text((result && result.data && result.data.message) ? result.data.message : 'Bulk resync failed.');
            }
            reset();
          }).fail(function(){
            $pre.text('Bulk resync request failed.');
            reset();
          });
        }).fail(function(){
          $pre.text('Dry-run request failed.');
          reset();
        });
      });

      $(document).on('click', '.vcs-resync-user', function(e){
        e.preventDefault();
        const $btn = $(this);
        if ($btn.hasClass('is-running')) return;
        const ajaxUrl = window.ajaxurl || '';
        if (!ajaxUrl) return;
        const userId = parseInt($btn.data('user'), 10) || 0;
        if (!userId) return;
        const nonce = $btn.data('nonce') || '';
        const provider = $btn.data('provider') || '';
        const $out = $btn.closest('td').find('.vcs-resync-user-output');
        const original = $btn.text();
        const reset = () => { $btn.removeClass('is-running').prop('disabled', false).text(original); };
        $btn.addClass('is-running').prop('disabled', true).text('Resyncing...');
        $out.show().text('Running resync...');
        $.post(ajaxUrl, { action: 'ventraconnect_sl_profile_resync', nonce: nonce, user_id: userId, provider: provider }).done(function(resp){
          if (resp && resp.success && resp.data) {
            const data = resp.data;
            const fields = (data.fields && data.fields.length) ? data.fields.join(', ') : 'No fields changed';
            const when = data.synced_at || '';
            $out.text('Resync complete. Fields: ' + fields + (when ? ' (at ' + when + ')' : ''));
          } else {
            $out.text((resp && resp.data && resp.data.message) ? resp.data.message : 'Resync failed.');
          }
        }).fail(function(){
          $out.text('Resync request failed.');
        }).always(function(){
          reset();
        });
      });
    })(window.jQuery);
  }
})();

// (Removed) Hash-based main admin tabs fallback. Tabs now reload with ?tab=.

// --- Toast helper ----------------------------------------------------
function wscShowToast(message, type = 'success') {
  const existing = document.querySelector('.wsc-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = 'wsc-toast wsc-toast--' + type;
  toast.setAttribute('aria-live', 'polite');
  toast.setAttribute('role', 'status');

  const icon = document.createElement('span');
  icon.className = 'wsc-toast__icon';
  icon.textContent = type === 'success' ? '✓' : (type === 'error' ? '⚠' : 'ℹ');

  const text = document.createElement('span');
  text.textContent = message;

  toast.appendChild(icon);
  toast.appendChild(text);
  document.body.appendChild(toast);

  requestAnimationFrame(() => toast.classList.add('is-visible'));
  setTimeout(() => {
    toast.classList.remove('is-visible');
    setTimeout(() => toast.remove(), 200);
  }, 3000);
}

// --- Avatar sync controls: free checkbox ↔ Pro avatar select ---
function vcslSyncAvatarControlsForCheckbox(cb) {
  if (!cb) {
    return;
  }

  const pane = cb.closest('.wsc-provider-pane') || cb.closest('.wsc-card');
  if (!pane) {
    return;
  }

  const name = cb.getAttribute('name') || '';
  const match = name.match(/\[sync_free]\[([^\]]+)]/);
  const slug = match ? match[1] : '';

  if (!slug) {
    return;
  }

  const selector = 'select[name="ventraconnect_sl_settings[sync_pro][' + slug + '][avatar]"]';
  const sel = pane.querySelector(selector);
  if (!sel) {
    return;
  }

  const disablePro = cb.checked;
  sel.disabled = disablePro;
  sel.style.opacity = disablePro ? '0.5' : '';
}

function vcslSyncAllAvatarControls() {
  const checkboxes = document.querySelectorAll('.vcs-free-avatar-checkbox');
  if (!checkboxes.length) {
    return;
  }
  checkboxes.forEach(function (cb) {
    vcslSyncAvatarControlsForCheckbox(cb);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', vcslSyncAllAvatarControls);
} else {
  vcslSyncAllAvatarControls();
}

document.addEventListener('change', function (event) {
  const target = event.target;
  if (!target || !target.classList) {
    return;
  }
  if (!target.classList.contains('vcs-free-avatar-checkbox')) {
    return;
  }
  vcslSyncAvatarControlsForCheckbox(target);
});

// Emails & Notifications: toggle-controlled field groups
function syncEmailGroup(toggleId, fieldIds, wrapperSelector) {
  var toggle = document.getElementById(toggleId);
  if (!toggle) return;

  var disabled = !toggle.checked;

  fieldIds.forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.disabled = disabled;
  });

  if (wrapperSelector) {
    var wrapper = document.querySelector(wrapperSelector);
    if (wrapper) {
      wrapper.classList.toggle('wsc-field--disabled', disabled);
    }
  }
}

function initEmailFieldToggles() {
  // Linked Account Notifications
  syncEmailGroup(
    'vcsl_linked_email_toggle',
    ['vcsl_linked_email_subject', 'vcsl_linked_email_body'],
    '.vcsl-linked-email-fields'
  );

  // Handling Missing Emails
  syncEmailGroup(
    'vcsl_missing_email_toggle',
    ['vcsl_missing_email_title', 'vcsl_missing_email_message'],
    '.vcsl-missing-email-fields'
  );
}

document.addEventListener('DOMContentLoaded', initEmailFieldToggles);

document.addEventListener('change', function (event) {
  var target = event.target;
  if (!target || !target.id) return;

  if (target.id === 'vcsl_linked_email_toggle') {
    syncEmailGroup(
      'vcsl_linked_email_toggle',
      ['vcsl_linked_email_subject', 'vcsl_linked_email_body'],
      '.vcsl-linked-email-fields'
    );
  }

  if (target.id === 'vcsl_missing_email_toggle') {
    syncEmailGroup(
      'vcsl_missing_email_toggle',
      ['vcsl_missing_email_title', 'vcsl_missing_email_message'],
      '.vcsl-missing-email-fields'
    );
  }
});

// Hybrid AJAX save for per-provider panes with automatic fallback to options.php
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.wsc-save-provider-ajax');
  if (!btn) return;
  e.preventDefault();

  const provider = btn.dataset.provider;
  const pane = btn.closest('.wsc-provider-pane');
  const form = pane ? pane.closest('form[action*="options.php"]') : null;
  if (!form || !provider) return;

  const statusEl = pane.querySelector('.wsc-provider-save-status');
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Saving…';
  if (statusEl) statusEl.textContent = '';

  const formData = new FormData();
  formData.append('action', 'ventraconnect_sl_save_provider_settings');
  formData.append('provider', provider);
  // Use generic admin nonce; allow fallback to window.ajaxurl localization
  formData.append('nonce', (window.VCS_ADMIN && VCS_ADMIN.nonce) || '');

  // Collect only inputs within this provider pane
  pane.querySelectorAll('input, select, textarea').forEach(el => {
    if (!el.name || ['button','submit'].includes(el.type)) return;

    if (el.tagName === 'SELECT' && el.multiple) {
      Array.from(el.selectedOptions || []).forEach(option => {
        formData.append(el.name, option.value);
      });
      return;
    }

    if (el.type === 'checkbox' || el.type === 'radio') {
      if (!el.checked) return;
    }

    formData.append(el.name, el.value);
  });

  let ajaxCompleted = false;
  const fallbackTimeout = setTimeout(() => {
    if (!ajaxCompleted) {
      console.warn('AJAX timeout — falling back to normal submit');
      form.submit();
    }
  }, 6000);

const ajaxUrl = (window.VCS_ADMIN && VCS_ADMIN.ajax_url) || window.ajaxurl;

fetch(ajaxUrl, {
  method: 'POST',
  body: formData,
  credentials: 'same-origin',
})
  .then(res => res.json())
  .then(json => {
    ajaxCompleted = true;
    clearTimeout(fallbackTimeout);

      if (json && json.success) {
      // Status message
      if (statusEl) {
        statusEl.textContent =
          (json.data && json.data.message) || 'Settings saved.';
      }

      // Toast
        try {
          wscShowToast('Provider settings saved', 'success');
        } catch (err) {}

  if (window.vcslRefreshOverviewStatus) {
    window.vcslRefreshOverviewStatus();
  }

      // Refresh the Verify Settings link with the latest verify_url
      try {
        const verifyUrl =
          json && json.data && typeof json.data.verify_url === 'string'
            ? json.data.verify_url
            : '';

        if (verifyUrl && pane) {
          const verifyLink = pane.querySelector('.wsc-verify-provider');
          if (verifyLink) {
            verifyLink.setAttribute('href', verifyUrl);
          }
        }
      } catch (err) {}
    } else {
      const msg =
        json && json.data && json.data.message
          ? json.data.message
          : 'Error saving settings.';

      if (statusEl) {
        statusEl.textContent = msg;
      }

      try {
        wscShowToast(msg, 'error');
      } catch (err) {}
    }
  })
  .catch(() => {
    clearTimeout(fallbackTimeout);
    form.submit();
  })
  .finally(() => {
    btn.disabled = false;
    btn.textContent = originalText;
  });
});

// Open "Verify Settings" links in a popup window (with graceful fallback)
document.addEventListener('click', function (event) {
  const verifyLink = event.target.closest('.wsc-verify-provider');
  if (!verifyLink) {
    return;
  }

  const url = verifyLink.getAttribute('href');
  if (!url) {
    return;
  }

  event.preventDefault();

  const provider = verifyLink.getAttribute('data-provider') || 'provider';
  const name = 'ventraconnect_sl_verify_' + provider;

  const width = 700;
  const height = 700;
  const left = Math.round((window.screen.width - width) / 2);
  const top = Math.round((window.screen.height - height) / 2);

  const features = [
    'width=' + width,
    'height=' + height,
    'left=' + left,
    'top=' + top,
    'scrollbars=yes',
    'resizable=yes'
  ].join(',');

  const win = window.open(url, name, features);

  if (!win) {
    window.location.href = url;
  }
});

// Collapsible "Getting Started" panels on the Providers tab
document.addEventListener('click', function (event) {
  const toggle = event.target.closest('.wsc-help-toggle');
  if (!toggle) {
    return;
  }

  const targetId = toggle.getAttribute('aria-controls');
  if (!targetId) {
    return;
  }

  const panel = document.getElementById(targetId);
  if (!panel) {
    return;
  }

  const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
  const nextState = !isExpanded;

  // Update ARIA state
  toggle.setAttribute('aria-expanded', nextState ? 'true' : 'false');

  // Show/hide the panel
  panel.hidden = !nextState;

  // Update button label
  const showLabel = toggle.getAttribute('data-label-show') || 'Show setup guide';
  const hideLabel = toggle.getAttribute('data-label-hide') || 'Hide setup guide';
  toggle.textContent = nextState ? hideLabel : showLabel;
});
