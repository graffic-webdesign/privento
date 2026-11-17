/**
 * Privento — front-end consent engine
 */
(function () {
  'use strict';

  const cfg         = window.PriventoConfig || {};
  const COOKIE_NAME = cfg.cookieName  || 'privento_consent';
  const EXPIRY_DAYS = cfg.expiryDays  || 180;
  const CATEGORIES  = Object.keys(cfg.categories || { necessary:1, preferences:1, statistics:1, marketing:1 });
  const REST_URL    = cfg.restUrl     || '';
  const VERSION     = cfg.cookieVersion || '1';

  /* ── Cookie helpers ──────────────────────────── */
  const Cookie = {
    get(name) {
      const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
      if (!m) return null;
      try { return JSON.parse(atob(decodeURIComponent(m[1]))); } catch { return null; }
    },
    set(name, value, days) {
      const exp = new Date(Date.now() + days * 864e5).toUTCString();
      document.cookie = `${name}=${encodeURIComponent(btoa(JSON.stringify(value)))}; expires=${exp}; path=/; SameSite=Lax`;
    },
    remove(name) {
      document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
    },
    isExpired(data) {
      return !data || !data.timestamp || (Date.now() - data.timestamp) > EXPIRY_DAYS * 864e5;
    }
  };

  /* ── Consent state ───────────────────────────── */
  const Consent = {
    data: null,
    load() {
      const raw = Cookie.get(COOKIE_NAME);
      // A cookie written under another version is a consent to something else.
      this.data = (raw && raw.version === VERSION && !Cookie.isExpired(raw)) ? raw : null;
      return this.data;
    },
    has(cat)      { return this.data && Array.isArray(this.data.consented) && this.data.consented.includes(cat); },
    wasDecided()  { return !!this.data; },
    save(consented, rejected) {
      const payload = { consented, rejected, version: VERSION, timestamp: Date.now() };
      Cookie.set(COOKIE_NAME, payload, EXPIRY_DAYS);
      this.data = payload;
      this._persist(consented, rejected);
    },
    revoke() { Cookie.remove(COOKIE_NAME); this.data = null; },
    _persist(consented, rejected) {
      if (!REST_URL) return;
      // No X-WP-Nonce: the endpoint is public, and a nonce baked into a cached
      // page goes stale — WordPress then rejects the request and nothing is logged.
      fetch(REST_URL + '/consent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ consented, rejected }),
        keepalive: true,
      }).catch(() => {});
    },
  };

  /* ── Google Consent Mode v2 ──────────────────── */
  const GCM = {
    update(consented) {
      if (!cfg.gcmEnabled || typeof window.gtag !== 'function') return;
      const stat = consented.includes('statistics')  ? 'granted' : 'denied';
      const mkt  = consented.includes('marketing')   ? 'granted' : 'denied';
      const pref = consented.includes('preferences') ? 'granted' : 'denied';
      window.gtag('consent', 'update', {
        analytics_storage:      stat,
        ad_storage:             mkt,
        ad_user_data:           mkt,
        ad_personalization:     mkt,
        personalization_storage: pref,
        functionality_storage:  'granted',
        security_storage:       'granted',
      });
      if (window.dataLayer) window.dataLayer.push({ event: 'privento_consent_update', consent_categories: consented });
    },
  };

  /* ── Script unblocking ───────────────────────── */
  const ScriptLoader = {
    unblock(consented) {
      document.querySelectorAll('script[data-privento-blocked]').forEach(el => {
        if (!consented.includes(el.dataset.priventoCategory)) return;
        const s = document.createElement('script');
        [...el.attributes].forEach(a => {
          if (['data-privento-blocked','data-privento-category','data-src'].includes(a.name)) return;
          if (a.name === 'type' && a.value === 'text/plain') return;
          s.setAttribute(a.name, a.value);
        });
        if (el.dataset.src) s.src = el.dataset.src;
        else s.textContent = el.textContent;
        el.parentNode.replaceChild(s, el);
      });
      document.querySelectorAll('iframe[data-privento-blocked]').forEach(el => {
        if (consented.includes(el.dataset.priventoCategory) && el.dataset.src) {
          el.src = el.dataset.src;
          el.removeAttribute('data-privento-blocked');
        }
      });
    },
    loadGA4(id) {
      if (!id || document.querySelector(`script[src*="gtag/js?id=${id}"]`)) return;
      const s = document.createElement('script');
      s.async = true; s.src = `https://www.googletagmanager.com/gtag/js?id=${id}`;
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      function gtag(){window.dataLayer.push(arguments);}
      window.gtag = window.gtag || gtag;
      gtag('js', new Date()); gtag('config', id, { anonymize_ip: true });
    },
  };

  /* ── Banner ──────────────────────────────────── */
  const Banner = {
    el:        null,
    overlay:   null,
    panel:     null,
    panelOpen: false,

    init() {
      this.el      = document.getElementById('privento-banner');
      this.overlay = document.getElementById('privento-overlay');
      this.panel   = document.getElementById('privento-settings-panel');
      if (!this.el) return;
      // Apply WCAG / focus ring classes based on settings
      if (cfg.wcag)       this.el.classList.add('privento--wcag');
      if (cfg.focusRing)  this.el.classList.add('privento--focus-ring');
      this._bindEvents();
    },

    /* Slide up from bottom */
    show() {
      if (!this.el) return;
      this._lastFocus = document.activeElement;
      this.el.setAttribute('aria-hidden', 'false');
      // Focus the dialog container, not a button — focusing a button paints
      // the WCAG focus ring on it the moment the banner opens. First Tab
      // still lands on the first button with its ring intact.
      this.el.focus({ preventScroll: true });
    },

    /* Slide back down */
    hide() {
      if (!this.el) return;
      // Move focus out BEFORE setting aria-hidden — hiding an ancestor of the
      // focused element is blocked for assistive technology (Chrome warns).
      if (this.el.contains(document.activeElement)) {
        if (this._lastFocus && typeof this._lastFocus.focus === 'function' && !this.el.contains(this._lastFocus)) {
          this._lastFocus.focus();
        }
        // _lastFocus.focus() can be a no-op (e.g. <body> without tabindex) —
        // verify it actually moved focus out before falling back to blur().
        if (this.el.contains(document.activeElement)) {
          document.activeElement.blur();
        }
      }
      this._lastFocus = null;
      this.el.setAttribute('aria-hidden', 'true');
      this._closePanel();
    },

    // Toggles are only ever read (in saveCustom), never written — without this,
    // whatever the browser happens to be showing (its own form-restore state
    // from an earlier session) stays displayed regardless of what was actually
    // saved. Re-sync from the real consent data every time the panel opens.
    syncToggles() {
      if (!this.panel) return;
      const consented = (Consent.data && Consent.data.consented) || ['necessary'];
      this.panel.querySelectorAll('.privento-toggle__input').forEach(cb => {
        cb.checked = consented.includes(cb.value);
      });
    },

    _openPanel() {
      if (!this.panel) return;
      this.syncToggles();
      this.panelOpen = true;
      this.panel.classList.add('is-open');
      if (this.overlay) this.overlay.classList.add('is-open');
      // Hide the main action buttons — panel footer takes over
      if (this.el) this.el.classList.add('panel-open');
    },

    _closePanel() {
      this.panelOpen = false;
      if (this.panel)   this.panel.classList.remove('is-open');
      if (this.overlay) this.overlay.classList.remove('is-open');
      if (this.el) this.el.classList.remove('panel-open');
    },

    _bindEvents() {
      const on = (id, fn) => { const el = document.getElementById(id); if (el) el.addEventListener('click', fn); };
      on('privento-accept-all',    () => this.acceptAll());
      on('privento-reject-all',    () => this.rejectAll());
      on('privento-open-settings', () => this.panelOpen ? this._closePanel() : this._openPanel());
      on('privento-save-prefs',    () => this.saveCustom());
      // Panel footer buttons
      on('privento-panel-accept',  () => this.acceptAll());
      on('privento-panel-reject',  () => this.rejectAll());

      // Category rows expand on click or Enter/Space. The consent toggle sits
      // inside the row and must not open it.
      const toggleCategory = e => {
        const header = e.target.closest('.privento-category__header');
        if (!header || e.target.closest('.privento-toggle')) return;
        // A page cached before 1.7.0 still has the old inline handlers on the
        // row; let those do the work rather than toggling twice.
        if (header.hasAttribute('onclick')) return;
        if (e.type === 'keydown') {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
        }
        const open = header.closest('.privento-category').classList.toggle('is-open');
        header.setAttribute('aria-expanded', open ? 'true' : 'false');
      };
      this.el.addEventListener('click', toggleCategory);
      this.el.addEventListener('keydown', toggleCategory);

      // Overlay click closes panel but keeps banner
      if (this.overlay) this.overlay.addEventListener('click', () => this._closePanel());

      // Floating button + revoke links
      document.addEventListener('click', e => {
        if (e.target.closest('.privento-floating-btn') || e.target.closest('.privento-revoke-link')) {
          e.preventDefault();
          // Both entry points are "manage settings" — go straight to the panel
          // instead of showing the banner and making the visitor click again.
          this.show();
          this._openPanel();
        }
      });

      // ESC
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
          if (this.panelOpen) this._closePanel();
          else if (this.el && this.el.getAttribute('aria-hidden') === 'false' && Consent.wasDecided()) this.hide();
        }
      });

      // Trap Tab inside the dialog while it is open (banner is aria-modal)
      document.addEventListener('keydown', e => {
        if (e.key !== 'Tab' || !this.el || this.el.getAttribute('aria-hidden') !== 'false') return;
        const focusable = [...this.el.querySelectorAll('button, a[href], input, [tabindex="0"]')]
          .filter(n => n.offsetParent !== null);
        if (!focusable.length) return;
        const first = focusable[0];
        const last  = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && (document.activeElement === last || !this.el.contains(document.activeElement))) {
          e.preventDefault();
          first.focus();
        }
      });
    },

    acceptAll() {
      const consented = [...CATEGORIES];
      const rejected  = [];
      Consent.save(consented, rejected);
      GCM.update(consented);
      ScriptLoader.unblock(consented);
      ScriptLoader.loadGA4(cfg.ga4Id);
      this.hide();
      this._dispatch('privento:accepted', { consented, rejected });
    },

    rejectAll() {
      const consented = ['necessary'];
      const rejected  = CATEGORIES.filter(c => c !== 'necessary');
      Consent.save(consented, rejected);
      GCM.update(consented);
      this.hide();
      this._dispatch('privento:rejected', { consented, rejected });
    },

    saveCustom() {
      const consented = ['necessary'];
      const rejected  = [];
      document.querySelectorAll('.privento-toggle__input').forEach(cb => {
        if (cb.value !== 'necessary') {
          (cb.checked ? consented : rejected).push(cb.value);
        }
      });
      Consent.save(consented, rejected);
      GCM.update(consented);
      ScriptLoader.unblock(consented);
      if (consented.includes('statistics')) ScriptLoader.loadGA4(cfg.ga4Id);
      this.hide();
      this._dispatch('privento:saved', { consented, rejected });
    },

    _dispatch(name, detail) {
      document.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }));
    },
  };

  /* ── Boot ────────────────────────────────────── */
  function boot() {
    Consent.load();
    if (Consent.wasDecided()) {
      GCM.update(Consent.data.consented);
      ScriptLoader.unblock(Consent.data.consented);
      if (Consent.has('statistics')) ScriptLoader.loadGA4(cfg.ga4Id);
      Banner.init();
      return;
    }
    Banner.init();
    // Country detection is server-side (CF-IPCountry and similar headers);
    // geoAllowed is true whenever no header was available.
    if (!cfg.geoTargeting || cfg.geoAllowed !== false) {
      setTimeout(() => Banner.show(), 600);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  /* ── Public API ──────────────────────────────── */
  window.Privento = {
    acceptAll:  () => Banner.acceptAll(),
    rejectAll:  () => Banner.rejectAll(),
    show:       () => Banner.show(),
    hide:       () => Banner.hide(),
    hasConsent: (cat) => Consent.has(cat),
    getConsent: () => Consent.data,
    revoke()    { Consent.revoke(); Banner.show(); },
  };

})();
