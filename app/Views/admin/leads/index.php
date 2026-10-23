<?php
/**
 * @var array<int, array<string,mixed>> $leads @var App\Core\Paginator $pager @var string $q @var string $status
 * @var string $type @var array<string,int> $counts @var int $total
 */
use App\Core\View;
use App\Models\Lead;

$query = static fn (array $extra = []): string => http_build_query(array_filter(array_merge(['q' => $q, 'status' => $status, 'type' => $type], $extra)));
?>
<nav class="status-tabs" aria-label="Filter by status">
  <a href="<?= e(url('/admin/leads') . '?' . $query(['status' => null])) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>All (<?= array_sum($counts) ?>)</a>
  <?php foreach (Lead::STATUSES as $key => $label): ?>
  <a href="<?= e(url('/admin/leads') . '?' . $query(['status' => $key])) ?>"<?= $status === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> (<?= (int) ($counts[$key] ?? 0) ?>)</a>
  <?php endforeach; ?>
</nav>

<div class="toolbar">
  <form class="search-form" method="get" action="<?= e(url('/admin/leads')) ?>" role="search">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <label class="sr-only" for="q">Search leads</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email, phone, website…">
    <label class="sr-only" for="type">Form</label>
    <select id="type" name="type">
      <option value="">All forms</option>
      <?php foreach (Lead::TYPES as $key => $label): ?><option value="<?= e($key) ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
  </form>
  <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/leads/export') . '?' . $query()) ?>">Export CSV<?= $total ? ' (' . $total . ')' : '' ?></a>
</div>

<div class="panel">
<?php if ($leads): ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th scope="col">Name</th><th scope="col">Contact</th><th scope="col">Business</th><th scope="col">Form</th><th scope="col">Status</th><th scope="col">Received</th></tr></thead>
      <tbody>
      <?php foreach ($leads as $lead): ?>
        <tr>
          <td><a href="<?= e(url('/admin/leads/' . $lead['id'])) ?>"><?= e($lead['name']) ?></a><?= $lead['is_sample'] ? ' <span class="badge badge-amber">SAMPLE</span>' : '' ?></td>
          <td><?= e($lead['phone']) ?><br><span class="muted"><?= e($lead['email']) ?></span></td>
          <td><?= e($lead['business_type']) ?><?php if ($lead['website'] !== ''): ?><br><span class="muted"><?= e(preg_replace('#^https?://#', '', (string) $lead['website'])) ?></span><?php endif; ?></td>
          <td><?= e(Lead::TYPES[$lead['type']] ?? $lead['type']) ?></td>
          <td><span class="badge status-<?= e($lead['status']) ?>"><?= e(Lead::STATUSES[$lead['status']] ?? $lead['status']) ?></span></td>
          <td><?= e(format_date((string) $lead['created_at'], 'j M Y, H:i')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= View::partial('partials/pagination', ['pager' => $pager, 'link' => static fn (int $p): string => url('/admin/leads') . '?' . $query(['page' => $p > 1 ? $p : null])]) ?>
<?php else: ?>
  <p class="muted">No leads match these filters.</p>
<?php endif; ?>
</div>
