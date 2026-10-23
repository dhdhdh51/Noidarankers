/* NoidaRankers front-end script (deferred). Everything works without it;
   this only improves the mobile menu, forms and loads analytics late. */
(function () {
  'use strict';
  var d = document;

  // Mobile navigation toggle
  var toggle = d.querySelector('.nav-toggle');
  var nav = d.getElementById('primary-nav');
  if (toggle && nav) {
    var setOpen = function (open) {
      nav.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    toggle.addEventListener('click', function () {
      setOpen(!nav.classList.contains('is-open'));
    });
    d.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        setOpen(false);
        toggle.focus();
      }
    });
  }

  // Prevent double submissions and show progress
  var forms = d.querySelectorAll('form');
  Array.prototype.forEach.call(forms, function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('[type="submit"]');
      if (!button) { return; }
      window.setTimeout(function () {
        button.setAttribute('data-label', button.textContent);
        button.disabled = true;
        button.textContent = 'Sending…';
      }, 0);
    });
  });
  // Re-enable buttons when the page is restored from the back/forward cache
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) { return; }
    Array.prototype.forEach.call(d.querySelectorAll('[data-label]'), function (button) {
      button.disabled = false;
      button.textContent = button.getAttribute('data-label');
    });
  });

  // Move focus to the error summary after a failed submission
  var summary = d.querySelector('[data-error-summary]');
  if (summary) { summary.focus(); }

  // Google Analytics 4: only when an ID is set in Admin > Settings.
  // Loaded after the first interaction (or 5 seconds) to protect page speed.
  var gaId = d.documentElement.getAttribute('data-ga');
  if (gaId) {
    var loaded = false;
    var loadGa = function () {
      if (loaded) { return; }
      loaded = true;
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { window.dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', gaId);
      var s = d.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
      d.head.appendChild(s);
    };
    ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function (type) {
      window.addEventListener(type, loadGa, { once: true, passive: true });
    });
    window.addEventListener('load', function () { window.setTimeout(loadGa, 5000); });
  }
})();
