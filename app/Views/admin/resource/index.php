<?php
/**
 * @var App\Controllers\Admin\ResourceController $resource @var array<int, array<string,mixed>> $rows
 * @var App\Core\Paginator $pager @var string $q @var bool $searchable
 */
use App\Core\View;

$base = $resource->basePath();
$columns = $resource->listColumns();
?>
<div class="toolbar">
  <a class="btn btn-primary" href="<?= e(url($base . '/new')) ?>">Add <?= e($resource->singularLabel()) ?></a>
  <?php if ($searchable): ?>
  <form class="search-form" method="get" action="<?= e(url($base)) ?>" role="search">
    <label class="sr-only" for="q">Search</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search…">
    <button class="btn btn-secondary btn-sm" type="submit">Search</button>
  </form>
  <?php endif; ?>
</div>

<div class="panel">
<?php if ($rows): ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <?php foreach ($columns as $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach; ?>
          <th scope="col"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <?php $first = true; foreach (array_keys($columns) as $column): ?>
          <td><?php if ($first): ?><a href="<?= e(url($base . '/' . $row['id'] . '/edit')) ?>"><?= $resource->cell($column, $row) ?></a><?php else: ?><?= $resource->cell($column, $row) ?><?php endif; $first = false; ?></td>
          <?php endforeach; ?>
          <td class="actions">
            <?php $view = $resource->viewUrl($row); if ($view !== null): ?>
            <a href="<?= e($view) ?>" target="_blank" rel="noopener">View<span class="sr-only"> (opens in a new tab)</span></a>
            <?php endif; ?>
            <a href="<?= e(url($base . '/' . $row['id'] . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(url($base . '/' . $row['id'] . '/delete')) ?>" data-confirm="Delete this <?= e($resource->singularLabel()) ?>? This cannot be undone.">
              <?= csrf_field() ?>
              <button class="link-button danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= View::partial('partials/pagination', ['pager' => $pager, 'link' => static fn (int $p): string => url($base) . '?' . http_build_query(array_filter(['q' => $q, 'page' => $p > 1 ? $p : null]))]) ?>
<?php else: ?>
  <p class="muted"><?= $q !== '' ? 'Nothing matches your search.' : 'Nothing here yet.' ?></p>
<?php endif; ?>
</div>
