/* NoidaRankers admin helpers: confirm dialogs, slugs, SEO counters, FAQ rows, rich text editor. */
(function () {
  'use strict';
  var d = document;
  var each = function (selector, fn, root) { Array.prototype.forEach.call((root || d).querySelectorAll(selector), fn); };
  var meta = function (name) { var m = d.querySelector('meta[name="' + name + '"]'); return m ? m.getAttribute('content') : ''; };

  // Confirm before destructive actions
  each('form[data-confirm]', function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });

  // Focus error summary
  var summary = d.querySelector('[data-error-summary]');
  if (summary) { summary.focus(); }

  // Slugs generated from the title until edited by hand
  var slugify = function (text) {
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120);
  };
  each('input[data-slug-from]', function (slug) {
    var source = d.getElementById(slug.getAttribute('data-slug-from'));
    if (!source) { return; }
    var manual = slug.value !== '';
    slug.addEventListener('input', function () { manual = slug.value !== ''; });
    source.addEventListener('input', function () { if (!manual) { slug.value = slugify(source.value); } });
  });

  // Character counters for meta titles and descriptions
  each('[data-counter]', function (field) {
    var limit = parseInt(field.getAttribute('data-counter'), 10);
    var out = d.createElement('p');
    out.className = 'counter';
    out.setAttribute('aria-live', 'polite');
    field.insertAdjacentElement('afterend', out);
    var update = function () {
      var n = field.value.length;
      out.textContent = n + ' / ' + limit + ' characters' + (n > limit ? ' – may be cut off in Google results' : '');
      out.classList.toggle('over', n > limit);
    };
    field.addEventListener('input', update);
    update();
  });

  // FAQ rows
  each('[data-add-faq]', function (button) {
    var rows = d.querySelector('[data-faq-rows]');
    button.hidden = false;
    button.addEventListener('click', function () {
      var all = rows.querySelectorAll('.faq-row');
      var clone = all[all.length - 1].cloneNode(true);
      var n = all.length;
      each('input, textarea', function (el) { el.value = ''; }, clone);
      each('label', function (label) {
        var isQ = /^Question/.test(label.textContent);
        label.textContent = (isQ ? 'Question ' : 'Answer ') + (n + 1);
        var id = (isQ ? 'faq-q-' : 'faq-a-') + n;
        label.setAttribute('for', id);
        var input = label.parentNode.querySelector(isQ ? 'input' : 'textarea');
        if (input) { input.id = id; }
      }, clone);
      rows.appendChild(clone);
      clone.querySelector('input').focus();
    });
  });

  // Rich text editor
  each('textarea[data-editor]', function (textarea) {
    var wrap = d.createElement('div');
    wrap.className = 'editor';
    var toolbar = d.createElement('div');
    toolbar.className = 'editor-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    var labelEl = d.querySelector('label[for="' + textarea.id + '"]');
    toolbar.setAttribute('aria-label', 'Formatting for ' + (labelEl ? labelEl.textContent.trim() : 'content'));
    var area = d.createElement('div');
    area.className = 'editor-area prose';
    area.contentEditable = 'true';
    area.setAttribute('role', 'textbox');
    area.setAttribute('aria-multiline', 'true');
    if (labelEl) {
      labelEl.id = labelEl.id || textarea.id + '-label';
      area.setAttribute('aria-labelledby', labelEl.id);
      labelEl.addEventListener('click', function () { area.focus(); });
    }
    area.innerHTML = textarea.value;
    var status = d.createElement('div');
    status.className = 'editor-status';
    status.setAttribute('aria-live', 'polite');
    var source = false;

    try { d.execCommand('defaultParagraphSeparator', false, 'p'); } catch (err) { /* older browsers */ }

    var sync = function () { if (!source) { textarea.value = area.innerHTML; } };
    var exec = function (cmd, value) { area.focus(); d.execCommand(cmd, false, value || null); sync(); };
    var buttons = [
      ['P', 'Paragraph', function () { exec('formatBlock', '<p>'); }],
      ['H2', 'Heading 2', function () { exec('formatBlock', '<h2>'); }],
      ['H3', 'Heading 3', function () { exec('formatBlock', '<h3>'); }],
      ['B', 'Bold', function () { exec('bold'); }],
      ['I', 'Italic', function () { exec('italic'); }],
      ['• List', 'Bulleted list', function () { exec('insertUnorderedList'); }],
      ['1. List', 'Numbered list', function () { exec('insertOrderedList'); }],
      ['Quote', 'Quote', function () { exec('formatBlock', '<blockquote>'); }],
      ['Link', 'Insert link', function () {
        var url = window.prompt('Link address (https://… or /page):', 'https://');
        if (url && /^(https?:\/\/|\/|mailto:|tel:|#)/.test(url)) { exec('createLink', url); }
      }],
      ['Unlink', 'Remove link', function () { exec('unlink'); }],
      ['Image', 'Upload image', function () { picker.click(); }],
      ['Clear', 'Clear formatting', function () { exec('removeFormat'); }],
      ['HTML', 'Edit HTML source', function (btn) {
        source = !source;
        btn.setAttribute('aria-pressed', source ? 'true' : 'false');
        if (source) {
          textarea.value = area.innerHTML;
          area.hidden = true;
          textarea.hidden = false;
          textarea.focus();
        } else {
          area.innerHTML = textarea.value;
          textarea.hidden = true;
          area.hidden = false;
          area.focus();
        }
      }]
    ];
    buttons.forEach(function (def) {
      var b = d.createElement('button');
      b.type = 'button';
      b.textContent = def[0];
      b.title = def[1];
      b.setAttribute('aria-label', def[1]);
      if (def[0] === 'HTML') { b.setAttribute('aria-pressed', 'false'); }
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () { def[2](b); });
      toolbar.appendChild(b);
    });

    var picker = d.createElement('input');
    picker.type = 'file';
    picker.accept = 'image/jpeg,image/png,image/webp,image/gif';
    picker.hidden = true;
    picker.addEventListener('change', function () {
      if (!picker.files || !picker.files[0]) { return; }
      var data = new FormData();
      data.append('image', picker.files[0]);
      status.textContent = 'Uploading image…';
      fetch(meta('upload-url'), { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-CSRF-Token': meta('csrf-token'), 'Accept': 'application/json' } })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
        .then(function (res) {
          if (!res.ok) { throw new Error(res.json.error || 'Upload failed'); }
          var alt = window.prompt('Describe the image (alt text):', '') || '';
          var img = '<img src="' + res.json.url + '" width="' + res.json.width + '" height="' + res.json.height + '" alt="' + alt.replace(/"/g, '&quot;') + '" loading="lazy">';
          exec('insertHTML', img);
          status.textContent = 'Image inserted.';
        })
        .catch(function (err) { status.textContent = err.message; })
        .then(function () { picker.value = ''; });
    });

    textarea.hidden = true;
    textarea.removeAttribute('required');
    textarea.parentNode.insertBefore(wrap, textarea);
    wrap.appendChild(toolbar);
    wrap.appendChild(area);
    wrap.appendChild(textarea);
    wrap.appendChild(status);
    wrap.appendChild(picker);
    area.addEventListener('input', sync);
    area.addEventListener('blur', sync);
    if (textarea.form) {
      textarea.form.addEventListener('submit', function () { if (!source) { textarea.value = area.innerHTML; } });
    }
  });
})();
