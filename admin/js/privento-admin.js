/**
 * Privento Admin — Control Panel JS
 * Version: 1.0.0
 */
(function ($) {
  'use strict';

  const cfg = window.PriventoAdmin || {};
  const ajax = cfg.ajaxUrl || ajaxurl;
  const nonce = cfg.nonce || '';
  const i18n = cfg.i18n || {};

  /* ──────────────────────────────────────────────────────────
     Toast notifications
     ────────────────────────────────────────────────────────── */
  const Toast = {
    el: null,

    init() {
      this.el = document.createElement('div');
      this.el.className = 'prv-toast';
      document.body.appendChild(this.el);
    },

    show(msg, type = 'success', duration = 3000) {
      if (!this.el) this.init();
      this.el.textContent = msg;
      this.el.className = `prv-toast prv-toast--${type}`;
      // Force reflow
      void this.el.offsetWidth;
      this.el.classList.add('prv-toast--show');
      clearTimeout(this._timer);
      this._timer = setTimeout(() => {
        this.el.classList.remove('prv-toast--show');
      }, duration);
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg)   { this.show(msg, 'error'); },
  };

  /* ──────────────────────────────────────────────────────────
     Generic form serializer
     Handles: text, textarea, select, checkbox, radio, range,
     and hidden inputs.
     ────────────────────────────────────────────────────────── */
  function serializeForm(form) {
    const data = {};

    // 1. Use FormData as the base (captures text, textarea, select, range, radio)
    const fd = new FormData(form);
    fd.forEach((val, key) => { data[key] = val; });

    // 2. Unchecked checkboxes are absent from FormData — default them to '0'
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      if (!(cb.name in data)) data[cb.name] = '0';
    });

    return data;
  }

  /* ──────────────────────────────────────────────────────────
     Save settings via AJAX
     ────────────────────────────────────────────────────────── */
  function saveForm(formSelector, btnSelector) {
    const form = document.querySelector(formSelector);
    const btn  = document.querySelector(btnSelector);
    if (!form || !btn) return;

    btn.addEventListener('click', async () => {
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = i18n.saving || 'Saving…';

      const settings = serializeForm(form);

      try {
        const res = await fetch(ajax, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action:   'privento_save_settings',
            nonce,
            settings: JSON.stringify(settings),
          }),
        });

        const data = await res.json();

        if (data.success) {
          Toast.success(data.data?.message || i18n.saved || 'Settings saved!');
        } else {
          Toast.error(data.data || i18n.error || 'Something went wrong.');
        }
      } catch (err) {
        Toast.error(i18n.error || 'Network error. Please try again.');
        console.error('[Privento] Save error:', err);
      } finally {
        btn.disabled = false;
        btn.textContent = originalText;
      }
    });
  }

  /* ──────────────────────────────────────────────────────────
     Colour studio (Design tab)
     Swatch triggers write into hidden inputs, so the generic
     serializer picks the colours up like any other field.
     ────────────────────────────────────────────────────────── */
  const CS_PALETTE = [
    '#ffffff', '#fafaf7', '#f0ede8', '#e0ddd8', '#c9c4b6', '#9e9e99',
    '#6b6b66', '#3c3c37', '#1a1a18', '#2179b6', '#1b5f8f', '#14406b',
    '#8ca383', '#6e8265', '#1a6b4a', '#14523a', '#b32d2e', '#8a1f20',
  ];

  function csHex(value) {
    let v = String(value || '').trim().toLowerCase().replace(/^#/, '');
    if (/^[0-9a-f]{3}$/.test(v)) v = v.split('').map(c => c + c).join('');
    return /^[0-9a-f]{6}$/.test(v) ? '#' + v : null;
  }

  function csNormalize(raw, allowTransparent) {
    const v = String(raw || '').trim().toLowerCase();
    if (allowTransparent && (v === 'transparent' || v === 'inherit')) return v;
    return csHex(v);
  }

  function csLuminance(hex) {
    const ch = [1, 3, 5].map(i => {
      const c = parseInt(hex.substr(i, 2), 16) / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * ch[0] + 0.7152 * ch[1] + 0.0722 * ch[2];
  }

  function csRatio(a, b) {
    const l1 = csLuminance(a);
    const l2 = csLuminance(b);
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  }

  function initColorStudio() {
    const root = document.getElementById('prv-color-studio');
    if (!root) return;

    let defaults = {};
    try { defaults = JSON.parse(root.dataset.defaults || '{}'); } catch (e) {}

    let themeColors = [];
    try { themeColors = JSON.parse(root.dataset.themeColors || '[]'); } catch (e) {}
    const themeLabel = root.dataset.themeLabel || '';

    const fields  = new Map();
    const initial = {};

    root.querySelectorAll('.prv-cs-field').forEach(wrap => {
      const key = wrap.dataset.key;
      const field = {
        wrap,
        allowTransparent: wrap.dataset.transparent === '1',
        input:   wrap.querySelector('input[type="hidden"]'),
        chip:    wrap.querySelector('.prv-cs-chip'),
        hex:     wrap.querySelector('.prv-cs-hex'),
        trigger: wrap.querySelector('.prv-cs-trigger'),
        pop:     null,
      };
      fields.set(key, field);
      initial[key] = field.input.value;
    });

    const get = key => (fields.has(key) ? fields.get(key).input.value : '');

    const preview  = root.querySelector('#prv-cs-preview');
    const pvInner  = root.querySelector('.prv-cs__preview-inner');
    const pvTitle  = root.querySelector('.prv-cs__pv-title');
    const pvBody   = root.querySelector('.prv-cs__pv-body');
    const pvLink   = root.querySelector('.prv-cs__pv-link');
    const pvAccept = root.querySelector('.prv-cs__pv-btn--accept');
    const pvReject = root.querySelector('.prv-cs__pv-btn--reject');
    const pvSett   = root.querySelector('.prv-cs__pv-btn--settings');

    function paint(key) {
      const field = fields.get(key);
      if (!field) return;

      const val = field.input.value;
      const hex = csHex(val);

      field.chip.classList.toggle('is-transparent', !hex);
      field.chip.style.background = hex || '';
      field.hex.textContent = val;

      if (field.pop) {
        field.pop.querySelectorAll('.prv-cs-swatch').forEach(sw => {
          sw.classList.toggle('is-active', (sw.dataset.color || '') === (hex || val));
        });
        field.pop.querySelector('.prv-cs-pop__input').value = val.replace('#', '');
      }
    }

    function refresh() {
      pvInner.style.background  = get('color_bg');
      pvTitle.style.color       = get('color_text');
      pvBody.style.color        = get('color_text');
      pvLink.style.color        = get('color_link');
      const chip = (el, bg, txt, hoverBg, hoverTxt) => {
        el.style.setProperty('--pv-bg', get(bg));
        el.style.setProperty('--pv-fg', get(txt));
        el.style.setProperty('--pv-bg-hover', get(hoverBg));
        el.style.setProperty('--pv-fg-hover', get(hoverTxt));
      };
      chip(pvAccept, 'color_btn_accept_bg', 'color_btn_accept_txt', 'color_accept_hover', 'color_accept_hover_txt');
      chip(pvReject, 'color_btn_reject_bg', 'color_btn_reject_txt', 'color_reject_hover', 'color_reject_hover_txt');
      chip(pvSett, 'color_btn_settings_bg', 'color_btn_settings_txt', 'color_settings_hover', 'color_settings_hover_txt');

      root.querySelectorAll('.prv-cs__check').forEach(row => {
        const fg  = csHex(get(row.dataset.fg));
        const bg  = csHex(get(row.dataset.bg));
        const dot = row.querySelector('.prv-cs__check-dot');
        const out = row.querySelector('.prv-cs__check-ratio');

        if (!fg || !bg) {
          dot.textContent = '?';
          dot.style.background = '#9c9c92';
          out.textContent = '—';
          return;
        }

        const r = csRatio(fg, bg);
        dot.textContent = r >= 4.5 ? '✓' : '!';
        dot.style.background = r >= 4.5 ? '#2f5d47' : (r >= 3 ? '#b8860b' : '#b32d2e');
        out.textContent = r.toFixed(2) + ':1';
      });
    }

    function set(key, raw) {
      const field = fields.get(key);
      if (!field) return false;
      const val = csNormalize(raw, field.allowTransparent);
      if (!val) return false;
      field.input.value = val;
      paint(key);
      refresh();
      return true;
    }

    function applySet(map) {
      Object.keys(map || {}).forEach(key => {
        const field = fields.get(key);
        if (!field) return;
        const val = csNormalize(map[key], field.allowTransparent);
        if (val) {
          field.input.value = val;
          paint(key);
        }
      });
      refresh();
    }

    function closePop() {
      fields.forEach(field => {
        if (field.pop) {
          field.pop.remove();
          field.pop = null;
          field.trigger.setAttribute('aria-expanded', 'false');
        }
      });
    }

    function openPop(key) {
      const field = fields.get(key);
      const wasOpen = !!field.pop;
      closePop();
      if (wasOpen) return;

      const pop = document.createElement('div');
      pop.className = 'prv-cs-pop';
      pop.setAttribute('role', 'dialog');

      const swatch = (color, label) => {
        const sw = document.createElement('button');
        sw.type = 'button';
        sw.className = 'prv-cs-swatch' + ('transparent' === color ? ' is-transparent' : '');
        sw.dataset.color = color;
        sw.title = label || color;
        if ('transparent' !== color) sw.style.background = color;
        sw.addEventListener('click', () => {
          set(key, color);
          closePop();
          field.trigger.focus();
        });
        return sw;
      };

      const grid = document.createElement('div');
      grid.className = 'prv-cs-pop__grid';

      const colors = field.allowTransparent ? ['transparent'].concat(CS_PALETTE) : CS_PALETTE.slice();
      colors.forEach(color => grid.appendChild(swatch(color)));

      const foot = document.createElement('div');
      foot.className = 'prv-cs-pop__foot';
      foot.innerHTML = '<span class="prv-cs-pop__hash">#</span>'
        + '<input type="text" class="prv-cs-pop__input" spellcheck="false">'
        + '<button type="button" class="prv-cs-pop__ok">OK</button>';

      pop.appendChild(grid);

      // The site's own palette, when there is one, as a second labelled row.
      if (themeColors.length) {
        const label = document.createElement('span');
        label.className = 'prv-cs-pop__label';
        label.textContent = themeLabel;

        const themeGrid = document.createElement('div');
        themeGrid.className = 'prv-cs-pop__grid';
        themeColors.forEach(c => {
          themeGrid.appendChild(swatch(c.color, (c.name ? c.name + ' — ' : '') + c.color));
        });

        pop.appendChild(label);
        pop.appendChild(themeGrid);
      }

      pop.appendChild(foot);
      field.wrap.appendChild(pop);
      field.pop = pop;
      field.trigger.setAttribute('aria-expanded', 'true');

      // Flip the popover when it would spill past the card's right edge.
      if (field.wrap.getBoundingClientRect().left + 196 > root.getBoundingClientRect().right) {
        pop.classList.add('prv-cs-pop--right');
      }

      const input  = pop.querySelector('.prv-cs-pop__input');
      const commit = () => {
        if (!set(key, input.value)) input.value = field.input.value.replace('#', '');
      };

      input.addEventListener('change', commit);
      input.addEventListener('keydown', e => {
        if ('Enter' === e.key) {
          e.preventDefault();
          commit();
          closePop();
          field.trigger.focus();
        }
      });
      pop.querySelector('.prv-cs-pop__ok').addEventListener('click', () => {
        commit();
        closePop();
        field.trigger.focus();
      });

      paint(key);
      input.focus();
      input.select();
    }

    fields.forEach((field, key) => {
      field.trigger.addEventListener('click', e => {
        e.stopPropagation();
        openPop(key);
      });
    });

    document.addEventListener('click', e => {
      if (!e.target.closest('.prv-cs-field')) closePop();
    });
    document.addEventListener('keydown', e => {
      if ('Escape' === e.key) closePop();
    });

    root.querySelectorAll('.prv-cs__preset').forEach(btn => {
      btn.addEventListener('click', () => {
        let colors = {};
        try { colors = JSON.parse(btn.dataset.colors || '{}'); } catch (e) {}
        closePop();
        applySet(colors);
      });
    });

    root.querySelector('.prv-cs__reset').addEventListener('click', () => {
      closePop();
      applySet(defaults);
    });

    root.querySelector('.prv-cs__cancel').addEventListener('click', () => {
      closePop();
      applySet(initial);
    });

    root.querySelectorAll('.prv-cs__view').forEach(btn => {
      btn.addEventListener('click', () => {
        root.querySelectorAll('.prv-cs__view').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        preview.classList.toggle('is-mobile', 'mobile' === btn.dataset.view);
      });
    });

    root.querySelector('.prv-cs__save').addEventListener('click', () => {
      closePop();
      const mainSave = document.getElementById('prv-save-design');
      if (mainSave) mainSave.click();
    });

    refresh();
  }

  /* ──────────────────────────────────────────────────────────
     Range sliders — live value display
     ────────────────────────────────────────────────────────── */
  function initSliders() {
    document.querySelectorAll('.prv-slider').forEach(slider => {
      // Find value display: first try data-val-id, then next sibling .prv-slider__value
      const valEl = ( slider.dataset.valId ? document.getElementById(slider.dataset.valId) : null )
                 || slider.closest('.prv-slider-row')?.querySelector('.prv-slider__value');
      if (!valEl) return;

      const updateVal = () => {
        valEl.textContent = slider.value + 'px';
      };

      slider.addEventListener('input', updateVal);
      updateVal();
    });
  }

  /* ──────────────────────────────────────────────────────────
     Position picker — radio→visual-button
     ────────────────────────────────────────────────────────── */
  function initPositionPicker() {
    document.querySelectorAll('.prv-position-option input[type="radio"]').forEach(radio => {
      radio.addEventListener('change', () => {
        document.querySelectorAll('.prv-position-option').forEach(opt => {
          opt.classList.remove('prv-position-option--active');
        });
        radio.closest('.prv-position-option').classList.add('prv-position-option--active');
      });
    });
  }

  /* ──────────────────────────────────────────────────────────
     Cookie scanner
     ────────────────────────────────────────────────────────── */
  function initScanner() {
    const btn = document.getElementById('prv-run-scan');
    if (!btn) return;

    btn.addEventListener('click', async () => {
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = i18n.scanning || 'Scanning…';

      try {
        const res = await fetch(ajax, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'privento_scan_cookies',
            nonce,
          }),
        });

        const data = await res.json();

        if (data.success) {
          Toast.success(`${i18n.scanComplete || 'Scan complete.'} ${data.data.count} cookies detected.`);
          // Reload to show results
          setTimeout(() => window.location.reload(), 1200);
        } else {
          Toast.error(i18n.error || 'Scan failed.');
        }
      } catch (err) {
        Toast.error(i18n.error || 'Network error.');
      } finally {
        btn.disabled = false;
        btn.textContent = originalText;
      }
    });
  }

  /* ──────────────────────────────────────────────────────────
     Regenerate Cookie Policy
     ────────────────────────────────────────────────────────── */
  function initPolicyRegenerate() {
    const btn = document.getElementById('privento-generate-policy');
    if (!btn) return;

    btn.addEventListener('click', async () => {
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Generating…';

      try {
        const res = await fetch(ajax, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'privento_generate_policy', nonce }),
        });

        const data = await res.json();

        if (data.success) {
          Toast.success(data.data?.message || 'Cookie Policy page created!');
          // Reload the page so the status indicator updates
          setTimeout(() => window.location.reload(), 1200);
        } else {
          Toast.error(i18n.error || 'Failed to generate policy.');
        }
      } catch {
        Toast.error(i18n.error || 'Network error.');
      } finally {
        btn.disabled = false;
        btn.textContent = originalText;
      }
    });
  }

  /* ──────────────────────────────────────────────────────────
     Export CSV
     ────────────────────────────────────────────────────────── */
  function initExport() {
    const btn = document.getElementById('prv-export-logs');
    if (!btn) return;

    btn.addEventListener('click', () => {
      if (!confirm(i18n.confirmExport || 'Export all consent logs as CSV?')) return;
      const url = new URL(ajax);
      url.searchParams.set('action', 'privento_export_logs');
      url.searchParams.set('nonce', nonce);
      window.location.href = url.toString();
    });
  }

  /* ──────────────────────────────────────────────────────────
     Boot
     ────────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', () => {
    Toast.init();
    initColorStudio();

  // Position picker — update is-active class on radio change
  document.querySelectorAll('.prv-pos-option input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.prv-pos-option').forEach(el => el.classList.remove('is-active'));
      radio.closest('.prv-pos-option').classList.add('is-active');
    });
  });
    initSliders();
    initPositionPicker();
    initScanner();
    initPolicyRegenerate();
    initExport();

    // Bind save buttons to their forms
    saveForm('#prv-design-form',       '#prv-save-design');
    saveForm('#prv-texts-form',        '#prv-save-texts');
    saveForm('#prv-integrations-form', '#prv-save-integrations');
    saveForm('#prv-settings-form',     '#prv-save-settings');
    saveForm('#prv-policy-form',       '#prv-save-policy');

    // Policy intro text — read from TinyMCE if active
    const saveIntroBtn = document.getElementById('prv-save-policy-intro');
    if (saveIntroBtn) {
      saveIntroBtn.addEventListener('click', async () => {
        let introText = '';
        if (typeof tinyMCE !== 'undefined') {
          const ed = tinyMCE.get('privento_policy_intro');
          if (ed) introText = ed.getContent();
        }
        if (!introText) {
          const ta = document.getElementById('privento_policy_intro');
          if (ta) introText = ta.value;
        }
        const originalText = saveIntroBtn.textContent;
        saveIntroBtn.disabled = true;
        saveIntroBtn.textContent = i18n.saving || 'Saving…';
        try {
          const res = await fetch(ajax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'privento_save_settings',
              nonce,
              settings: JSON.stringify({ policy_intro_text: introText }),
            }),
          });
          const data = await res.json();
          if (data.success) Toast.success(data.data?.message || 'Saved!');
          else Toast.error(i18n.error || 'Error saving.');
        } catch { Toast.error(i18n.error || 'Network error.'); }
        finally { saveIntroBtn.disabled = false; saveIntroBtn.textContent = originalText; }
      });
    }

    // Copy shortcode button
    document.querySelectorAll('.privento-copy-shortcode').forEach(btn => {
      btn.addEventListener('click', () => {
        const text = btn.dataset.copy || '';
        navigator.clipboard?.writeText(text).then(() => {
          const orig = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(() => { btn.textContent = orig; }, 1500);
        });
      });
    });
  });

})(jQuery);
