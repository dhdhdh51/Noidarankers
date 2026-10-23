<?php
/**
 * @var array<string,int> $counts @var array<int, array<string,mixed>> $recent
 * @var array<string,int> $samples @var array<int, array{0:string,1:bool,2:string}> $checklist
 */
use App\Models\Lead;

$todo = array_filter($checklist, static fn (array $c): bool => !$c[1]);
?>
<div class="admin-stats">
  <a class="tile tile--blue" href="<?= e(url('/admin/leads?status=new')) ?>"><span class="stat-value"><?= $counts['new'] ?></span><span>New leads</span></a>
  <a class="tile tile--slate" href="<?= e(url('/admin/leads')) ?>"><span class="stat-value"><?= $counts['leads'] ?></span><span>Total leads</span></a>
  <a class="tile tile--green" href="<?= e(url('/admin/posts')) ?>"><span class="stat-value"><?= $counts['posts'] ?></span><span>Published posts<?= $counts['drafts'] ? ' (' . $counts['drafts'] . ' drafts)' : '' ?></span></a>
  <a class="tile tile--amber" href="<?= e(url('/admin/subscribers')) ?>"><span class="stat-value"><?= $counts['subscribers'] ?></span><span>Subscribers</span></a>
</div>

<div class="admin-grid">
  <section class="panel" aria-labelledby="recent-heading">
    <div class="panel-head">
      <h2 id="recent-heading">Latest leads</h2>
      <a href="<?= e(url('/admin/leads')) ?>">All leads</a>
    </div>
    <?php if ($recent): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Received</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $lead): ?>
          <tr>
            <td><a href="<?= e(url('/admin/leads/' . $lead['id'])) ?>"><?= e($lead['name']) ?></a><?= $lead['is_sample'] ? ' <span class="badge badge-amber">SAMPLE</span>' : '' ?></td>
            <td><?= e(Lead::TYPES[$lead['type']] ?? $lead['type']) ?></td>
            <td><span class="badge status-<?= e($lead['status']) ?>"><?= e(Lead::STATUSES[$lead['status']] ?? $lead['status']) ?></span></td>
            <td><?= e(format_date((string) $lead['created_at'], 'j M Y, H:i')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="muted">No leads yet. They will appear here as soon as someone fills in a form.</p>
    <?php endif; ?>
  </section>

  <section class="panel" aria-labelledby="launch-heading">
    <div class="panel-head"><h2 id="launch-heading">Launch checklist</h2><span class="muted"><?= count($checklist) - count($todo) ?>/<?= count($checklist) ?> done</span></div>
    <ul class="checks">
      <?php foreach ($checklist as [$label, $ok, $link]): ?>
      <li class="<?= $ok ? 'ok' : 'warn' ?>"><?= icon($ok ? 'check' : 'close') ?>
        <span><?php if (!$ok && $link !== ''): ?><a href="<?= e(url($link)) ?>"><?= e($label) ?></a><?php else: ?><?= e($label) ?><?php endif; ?><?= !$ok && $link === '' ? ' <span class="muted">(config.php)</span>' : '' ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($samples): ?>
  <section class="panel panel-amber" aria-labelledby="sample-heading">
    <h2 id="sample-heading">Sample content</h2>
    <p>The site was installed with example content marked <strong>SAMPLE</strong> so you can see how pages look:</p>
    <ul>
      <?php foreach ($samples as $label => $n): ?><li><?= e($label) ?>: <?= (int) $n ?></li><?php endforeach; ?>
    </ul>
    <p class="muted">Editing and saving an item removes its SAMPLE label, so your changes are never deleted by this button.</p>
    <form method="post" action="<?= e(url('/admin/sample-content/delete')) ?>" data-confirm="Delete all remaining SAMPLE content? This cannot be undone.">
      <?= csrf_field() ?>
      <button class="btn btn-primary" type="submit">Delete all sample content</button>
    </form>
  </section>
  <?php endif; ?>
</div>
