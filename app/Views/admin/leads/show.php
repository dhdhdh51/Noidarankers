<?php
/** @var array<string,mixed> $lead @var array<int, array<string,mixed>> $notes */
use App\Models\Lead;

$tel = tel_href((string) $lead['phone']);
$wa = whatsapp_href((string) $lead['phone'], 'Hi ' . $lead['name'] . ', this is ' . site_name() . ' about your SEO enquiry.');
?>
<p><a href="<?= e(url('/admin/leads')) ?>">&larr; All leads</a></p>
<div class="admin-grid">
  <section class="panel" aria-labelledby="details-heading">
    <div class="panel-head">
      <h2 id="details-heading">Details</h2>
      <span class="badge status-<?= e($lead['status']) ?>"><?= e(Lead::STATUSES[$lead['status']]) ?></span>
    </div>
    <dl class="details">
      <dt>Form</dt><dd><?= e(Lead::TYPES[$lead['type']] ?? $lead['type']) ?><?= $lead['is_sample'] ? ' <span class="badge badge-amber">SAMPLE</span>' : '' ?></dd>
      <dt>Received</dt><dd><?= e(format_date((string) $lead['created_at'], 'l, j M Y \a\t g:i a')) ?></dd>
      <dt>Name</dt><dd><?= e($lead['name']) ?></dd>
      <dt>Phone</dt><dd><?= $tel !== '' ? '<a href="' . e($tel) . '">' . e($lead['phone']) . '</a>' : e($lead['phone']) ?>
        <?php if ($wa !== ''): ?> · <a href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></dd>
      <dt>Email</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd>
      <dt>Website</dt><dd><?php if ($lead['website'] !== ''): ?><a href="<?= e($lead['website']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($lead['website']) ?></a><?php else: ?>–<?php endif; ?></dd>
      <dt>Business type</dt><dd><?= e($lead['business_type']) ?></dd>
      <dt>Message</dt><dd class="pre"><?= $lead['message'] !== '' && $lead['message'] !== null ? nl2br(e($lead['message'])) : '–' ?></dd>
      <dt>Source page</dt><dd><?= e($lead['source_page']) ?></dd>
      <dt>IP address</dt><dd><?= e($lead['ip'] ?: '–') ?></dd>
    </dl>
  </section>

  <div class="stack">
    <section class="panel" aria-labelledby="status-heading">
      <h2 id="status-heading">Status</h2>
      <form class="inline-form" method="post" action="<?= e(url('/admin/leads/' . $lead['id'] . '/status')) ?>">
        <?= csrf_field() ?>
        <label class="sr-only" for="status">Status</label>
        <select id="status" name="status">
          <?php foreach (Lead::STATUSES as $key => $label): ?><option value="<?= e($key) ?>"<?= $lead['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Update status</button>
      </form>
    </section>

    <section class="panel" id="notes" aria-labelledby="notes-heading">
      <h2 id="notes-heading">Notes</h2>
      <form class="form" method="post" action="<?= e(url('/admin/leads/' . $lead['id'] . '/notes')) ?>">
        <?= csrf_field() ?>
        <label class="sr-only" for="note">New note</label>
        <textarea id="note" name="note" rows="3" maxlength="5000" placeholder="e.g. Called, sending proposal on Monday" required></textarea>
        <button class="btn btn-secondary btn-sm" type="submit">Add note</button>
      </form>
      <?php if ($notes): ?>
      <ol class="notes">
        <?php foreach ($notes as $note): ?>
        <li><p class="pre"><?= nl2br(e($note['note'])) ?></p><p class="muted"><?= e(format_date((string) $note['created_at'], 'j M Y, H:i')) ?><?= $note['admin_name'] ? ' · ' . e($note['admin_name']) : '' ?></p></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="danger-heading">
      <h2 id="danger-heading">Delete</h2>
      <form method="post" action="<?= e(url('/admin/leads/' . $lead['id'] . '/delete')) ?>" data-confirm="Delete this lead and its notes? This cannot be undone.">
        <?= csrf_field() ?>
        <button class="btn btn-outline btn-sm danger" type="submit">Delete lead</button>
      </form>
    </section>
  </div>
</div>
