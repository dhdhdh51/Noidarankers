<?php
/** @var array<int, array<string,mixed>> $subscribers @var App\Core\Paginator $pager @var string $q @var int $total */
use App\Core\View;
?>
<div class="toolbar">
  <form class="search-form" method="get" action="<?= e(url('/admin/subscribers')) ?>" role="search">
    <label class="sr-only" for="q">Search subscribers</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search email…">
    <button class="btn btn-secondary btn-sm" type="submit">Search</button>
  </form>
  <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/subscribers/export')) ?>">Export CSV</a>
</div>
<div class="panel">
<?php if ($subscribers): ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th scope="col">Email</th><th scope="col">Status</th><th scope="col">Subscribed</th><th scope="col">From page</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($subscribers as $s): ?>
        <tr>
          <td data-label="Email"><?= e($s['email']) ?></td>
          <td data-label="Status"><span class="badge <?= $s['status'] === 'subscribed' ? 'badge-green' : 'badge-slate' ?>"><?= e(ucfirst((string) $s['status'])) ?></span></td>
          <td data-label="Subscribed"><?= e(format_date((string) $s['created_at'], 'j M Y, H:i')) ?></td>
          <td data-label="From page"><?= e($s['source_page']) ?></td>
          <td class="actions">
            <form method="post" action="<?= e(url('/admin/subscribers/' . $s['id'] . '/delete')) ?>" data-confirm="Remove this subscriber?">
              <?= csrf_field() ?><button class="link-button danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= View::partial('partials/pagination', ['pager' => $pager, 'link' => static fn (int $p): string => url('/admin/subscribers') . '?' . http_build_query(array_filter(['q' => $q, 'page' => $p > 1 ? $p : null]))]) ?>
<?php else: ?>
  <p class="muted">No subscribers yet. People can subscribe with the form in the website footer.</p>
<?php endif; ?>
</div>
