/**
 * Zieex Interaction.js / LiveComponent
 * Prevents default page reloads on links, forms, and buttons.
 * Handles AJAX navigation, form submissions, and partial page updates.
 * v1.0.0
 */

(function () {
  'use strict';

  const Zieex = {
    csrfToken: null,
    history: [],
    loaderEl: null,
    morphEnabled: true,

    init() {
      this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
      this.createLoader();
      this.bindLinks();
      this.bindForms();
      this.bindButtons();
      this.handlePopState();

      // Push initial state
      window.history.replaceState({ url: location.href, title: document.title }, document.title);
    },

    // ── Loader ───────────────────────────────────────────────
    createLoader() {
      this.loaderEl = document.createElement('div');
      this.loaderEl.id = 'zx-loader';
      Object.assign(this.loaderEl.style, {
        position: 'fixed', top: 0, left: 0, width: '0%', height: '3px',
        background: 'var(--zx-accent, #6366f1)', zIndex: 9999,
        transition: 'width 0.2s ease', display: 'none',
      });
      document.body.appendChild(this.loaderEl);
    },

    showLoader() {
      this.loaderEl.style.display = 'block';
      this.loaderEl.style.width = '40%';
    },

    progressLoader() {
      this.loaderEl.style.width = '80%';
    },

    hideLoader() {
      this.loaderEl.style.width = '100%';
      setTimeout(() => {
        this.loaderEl.style.width = '0%';
        this.loaderEl.style.display = 'none';
      }, 250);
    },

    // ── Links ────────────────────────────────────────────────
    bindLinks() {
      document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') ||
          href.startsWith('tel:') || link.hasAttribute('download') ||
          link.hasAttribute('target') || link.hasAttribute('data-no-ajax') ||
          !this.isSameOrigin(href)) return;

        e.preventDefault();
        this.navigate(href);
      });
    },

    // ── Forms ────────────────────────────────────────────────
    bindForms() {
      document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.hasAttribute('data-no-ajax')) return;

        e.preventDefault();
        this.submitForm(form);
      });
    },

    // ── Buttons ──────────────────────────────────────────────
    bindButtons() {
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.getAttribute('data-action');
        const method = (btn.getAttribute('data-method') || 'POST').toUpperCase();
        const target = btn.getAttribute('data-target') || null;
        const confirm = btn.getAttribute('data-confirm');

        if (confirm && !window.confirm(confirm)) return;

        e.preventDefault();
        this.request(action, method, {}, target);
      });
    },

    // ── Navigation ───────────────────────────────────────────
    async navigate(url) {
      this.showLoader();
      try {
        const html = await this.fetch(url, 'GET');
        this.progressLoader();
        this.updatePage(html, url);
        window.history.pushState({ url, title: document.title }, document.title, url);
      } catch (err) {
        window.location.href = url; // fallback
      } finally {
        this.hideLoader();
      }
    },

    // ── Form Submit ──────────────────────────────────────────
    async submitForm(form) {
      const method  = (form.method || 'POST').toUpperCase();
      const action  = form.action || window.location.href;
      const target  = form.getAttribute('data-target') || null;
      const isFile  = form.enctype === 'multipart/form-data';

      this.showLoader();
      this.setSubmitState(form, true);

      try {
        let body;
        const headers = {
          'X-Zieex-Request': '1',
          'X-CSRF-Token': this.csrfToken,
        };

        if (isFile) {
          body = new FormData(form);
        } else {
          headers['Content-Type'] = 'application/json';
          const data = Object.fromEntries(new FormData(form));
          body = JSON.stringify(data);
        }

        const response = await fetch(action, { method, headers, body });
        const text = await response.text();

        this.progressLoader();

        let json = null;
        try { json = JSON.parse(text); } catch {}

        if (json) {
          this.handleJsonResponse(json, form, response.status);
        } else {
          if (target) {
            document.querySelector(target).innerHTML = text;
          } else {
            this.updatePage(text, response.url || action);
          }
        }

        if (response.redirected) {
          this.navigate(response.url);
        }

      } catch (err) {
        console.error('[Zieex] Form error:', err);
        this.dispatchEvent('zx:error', { error: err, form });
      } finally {
        this.setSubmitState(form, false);
        this.hideLoader();
      }
    },

    // ── AJAX Request ─────────────────────────────────────────
    async request(url, method = 'GET', data = {}, target = null) {
      this.showLoader();
      try {
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            'X-Zieex-Request': '1',
            'X-CSRF-Token': this.csrfToken,
          },
          body: method !== 'GET' ? JSON.stringify(data) : undefined,
        });

        const text = await response.text();
        this.progressLoader();

        let json = null;
        try { json = JSON.parse(text); } catch {}

        if (json) {
          this.handleJsonResponse(json, null, response.status);
        } else if (target) {
          const el = document.querySelector(target);
          if (el) el.innerHTML = text;
        }
      } finally {
        this.hideLoader();
      }
    },

    // ── JSON response handler ─────────────────────────────────
    handleJsonResponse(json, form, status) {
      if (json.redirect) {
        return this.navigate(json.redirect);
      }

      if (json.errors && form) {
        this.showFormErrors(form, json.errors);
      }

      if (json.flash) {
        this.showFlash(json.flash.type || 'info', json.flash.message);
      }

      if (json.html) {
        const target = json.target ? document.querySelector(json.target) : null;
        if (target) target.innerHTML = json.html;
      }

      this.dispatchEvent('zx:response', { json, status });
    },

    // ── Full page update ─────────────────────────────────────
    updatePage(html, url) {
      const parser  = new DOMParser();
      const newDoc  = parser.parseFromString(html, 'text/html');
      const newBody = newDoc.body;
      const newTitle = newDoc.title;

      document.title = newTitle;
      document.body.innerHTML = newBody.innerHTML;

      this.init(); // re-bind
      this.dispatchEvent('zx:navigate', { url });
      window.scrollTo(0, 0);
    },

    // ── Flash messages ───────────────────────────────────────
    showFlash(type, message) {
      let container = document.getElementById('zx-flash');
      if (!container) {
        container = document.createElement('div');
        container.id = 'zx-flash';
        Object.assign(container.style, {
          position: 'fixed', top: '1rem', right: '1rem',
          zIndex: 10000, display: 'flex', flexDirection: 'column', gap: '0.5rem',
        });
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.className = `zx-toast zx-toast--${type}`;
      toast.innerHTML = message;
      Object.assign(toast.style, {
        padding: '0.75rem 1.25rem',
        borderRadius: '6px',
        background: type === 'success' ? '#a6e3a1' : type === 'error' ? '#f38ba8' : '#89b4fa',
        color: '#1e1e2e',
        fontWeight: '600',
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
        animation: 'slideIn 0.25s ease',
        cursor: 'pointer',
      });

      toast.addEventListener('click', () => toast.remove());
      container.appendChild(toast);
      setTimeout(() => toast.remove(), 5000);
    },

    // ── Form errors ──────────────────────────────────────────
    showFormErrors(form, errors) {
      // Clear old errors
      form.querySelectorAll('.zx-field-error').forEach(el => el.remove());
      form.querySelectorAll('.zx-input-error').forEach(el => el.classList.remove('zx-input-error'));

      for (const [field, message] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (!input) continue;
        input.classList.add('zx-input-error');
        const err = document.createElement('span');
        err.className = 'zx-field-error';
        err.style.cssText = 'color:#f38ba8;font-size:0.8rem;display:block;margin-top:2px';
        err.textContent = message;
        input.insertAdjacentElement('afterend', err);
      }
    },

    // ── Helpers ──────────────────────────────────────────────
    async fetch(url, method = 'GET') {
      const res = await fetch(url, {
        method,
        headers: { 'X-Zieex-Request': '1', 'X-CSRF-Token': this.csrfToken },
      });
      return res.text();
    },

    isSameOrigin(url) {
      try {
        const parsed = new URL(url, location.href);
        return parsed.origin === location.origin;
      } catch { return false; }
    },

    setSubmitState(form, disabled) {
      const btn = form.querySelector('[type="submit"]');
      if (btn) {
        btn.disabled = disabled;
        btn.dataset.original ??= btn.textContent;
        btn.textContent = disabled ? (btn.dataset.loading || 'Loading...') : btn.dataset.original;
      }
    },

    handlePopState() {
      window.addEventListener('popstate', (e) => {
        if (e.state?.url) {
          this.navigate(e.state.url);
        }
      });
    },

    dispatchEvent(name, detail = {}) {
      document.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }));
    },
  };

  // LiveComponent - reactive data binding
  const LiveComponent = {
    components: new Map(),

    register(name, definition) {
      this.components.set(name, definition);
    },

    mount() {
      document.querySelectorAll('[data-live]').forEach(el => {
        const name = el.getAttribute('data-live');
        const def  = this.components.get(name);
        if (!def) return;

        const state  = { ...(def.data?.() || {}) };
        const methods = def.methods || {};

        // Two-way binding
        el.querySelectorAll('[data-model]').forEach(input => {
          const key = input.getAttribute('data-model');
          input.value = state[key] ?? '';
          input.addEventListener('input', () => {
            state[key] = input.type === 'checkbox' ? input.checked : input.value;
            this.render(el, def, state);
          });
        });

        // Method bindings
        el.querySelectorAll('[data-on]').forEach(btn => {
          const [event, method] = btn.getAttribute('data-on').split(':');
          btn.addEventListener(event, () => {
            if (methods[method]) {
              methods[method].call(state);
              this.render(el, def, state);
            }
          });
        });
      });
    },

    render(el, def, state) {
      if (def.render) {
        const newHtml = def.render.call(state);
        el.innerHTML = newHtml;
        this.mount(); // re-mount children
      }
    },
  };

  // Expose globally
  window.Zieex = Zieex;
  window.LiveComponent = LiveComponent;

  // Auto-init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { Zieex.init(); LiveComponent.mount(); });
  } else {
    Zieex.init();
    LiveComponent.mount();
  }
})();
