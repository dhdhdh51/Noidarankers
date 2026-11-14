<?php
/**
 * @var array<int,array<string,mixed>> $prospects @var App\Core\Paginator $pager
 * @var string $q @var string $status @var array<string,int> $counts @var int $total @var bool $configured
 */
use App\Core\View;
use App\Models\MapLead;

$query = static fn (array $extra = []): string => http_build_query(array_filter(array_merge(['q' => $q, 'status' => $status], $extra)));
?>
<div class="btn-row" style="margin-bottom:1rem">
  <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/lead-finder/settings')) ?>"><?= icon('gear') ?> API key settings</a>
</div>

<?php if (!$configured): ?>
<div class="alert alert-error" role="alert">
  Lead Finder needs a Google Places API key. <a href="<?= e(url('/admin/lead-finder/settings')) ?>">Add it here →</a>
</div>
<?php endif; ?>

<p class="muted">Find local businesses on Google Maps — name, phone, website and rating — and save them as prospects to contact. Data comes from Google's official Places API (not scraping).</p>

<form class="form" method="post" action="<?= e(url('/admin/lead-finder/search')) ?>">
  <?= csrf_field() ?>
  <fieldset class="panel">
    <legend>Search Google Maps</legend>
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1">
        <label for="f-query">What &amp; where</label>
        <input id="f-query" name="query" type="text" maxlength="200" placeholder="e.g. dentists in Noida · gyms in Greater Noida · CA firms in Delhi" required <?= $configured ? '' : 'disabled' ?>>
        <p class="hint">Type the business type and the area together, just like you would in Google Maps.</p>
      </div>
      <div class="field">
        <label for="f-max">How many</label>
        <select id="f-max" name="max">
          <option value="20" selected>Up to 20</option>
          <option value="40">Up to 40</option>
          <option value="60">Up to 60 (max)</option>
        </select>
      </div>
      <div class="field">
        <label for="f-rating">Minimum rating</label>
        <select id="f-rating" name="min_rating">
          <option value="0" selected>Any</option>
          <option value="3">3.0+</option>
          <option value="3.5">3.5+</option>
          <option value="4">4.0+</option>
          <option value="4.5">4.5+</option>
        </select>
      </div>
      <div class="field">
        <label for="f-rank">Sort by</label>
        <select id="f-rank" name="rank">
          <option value="RELEVANCE" selected>Relevance</option>
          <option value="DISTANCE">Distance</option>
        </select>
      </div>
      <input type="hidden" name="region" value="IN">
    </div>
    <div class="btn-row">
      <button class="btn btn-primary" type="submit" <?= $configured ? '' : 'disabled' ?>><?= icon('search') ?> Find leads</button>
      <span class="muted">Results are saved below as prospects (duplicates are skipped automatically).</span>
    </div>
  </fieldset>
</form>

<h2>Saved prospects</h2>

<nav class="status-tabs" aria-label="Filter by status">
  <a href="<?= e(url('/admin/lead-finder') . '?' . $query(['status' => null])) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>All (<?= array_sum($counts) ?>)</a>
  <?php foreach (MapLead::STATUSES as $key => $label): ?>
  <a href="<?= e(url('/admin/lead-finder') . '?' . $query(['status' => $key])) ?>"<?= $status === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> (<?= (int) ($counts[$key] ?? 0) ?>)</a>
  <?php endforeach; ?>
</nav>

<div class="toolbar">
  <form class="search-form" method="get" action="<?= e(url('/admin/lead-finder')) ?>" role="search">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <label class="sr-only" for="q">Search prospects</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, phone, website, area…">
    <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
  </form>
  <a class="btn btn-outline btn-sm" href="<?= e(url('/admin/lead-finder/export') . '?' . $query()) ?>">Export CSV<?= $total ? ' (' . $total . ')' : '' ?></a>
</div>

<div class="panel">
<?php if ($prospects): ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th scope="col">Business</th><th scope="col">Phone</th><th scope="col">Website</th><th scope="col">Rating</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($prospects as $p): ?>
        <?php $tel = preg_replace('/[^0-9+]/', '', (string) $p['phone']); ?>
        <tr>
          <td data-label="Business">
            <strong><?= e($p['name']) ?></strong>
            <?php if ($p['maps_url'] !== ''): ?> <a href="<?= e($p['maps_url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Open in Google Maps" title="Open in Google Maps"><?= icon('map-pin') ?></a><?php endif; ?>
            <?php if ($p['category'] !== '' || $p['address'] !== ''): ?><br><span class="muted"><?= e(trim($p['category'] . ($p['category'] !== '' && $p['address'] !== '' ? ' · ' : '') . $p['address'])) ?></span><?php endif; ?>
          </td>
          <td data-label="Phone"><?php if ($tel !== ''): ?><a href="tel:<?= e($tel) ?>"><?= e($p['phone']) ?></a><?php else: ?><span class="muted">—</span><?php endif; ?></td>
          <td data-label="Website"><?php if ($p['website'] !== ''): ?><a href="<?= e($p['website']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e(preg_replace('#^https?://(www\.)?#', '', (string) $p['website'])) ?></a><?php else: ?><span class="muted">—</span><?php endif; ?></td>
          <td data-label="Rating"><?php if ($p['rating'] !== null): ?><?= e(number_format((float) $p['rating'], 1)) ?> <span class="muted">(<?= (int) $p['reviews'] ?>)</span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
          <td data-label="Status">
            <form class="inline-form" method="post" action="<?= e(url('/admin/lead-finder/' . $p['id'] . '/status')) ?>" data-status-form>
              <?= csrf_field() ?>
              <label class="sr-only" for="st-<?= (int) $p['id'] ?>">Status</label>
              <select id="st-<?= (int) $p['id'] ?>" name="status" data-autosubmit>
                <?php foreach (MapLead::STATUSES as $sk => $sl): ?><option value="<?= e($sk) ?>"<?= $p['status'] === $sk ? ' selected' : '' ?>><?= e($sl) ?></option><?php endforeach; ?>
              </select>
              <noscript><button class="btn btn-secondary btn-sm" type="submit">Set</button></noscript>
            </form>
          </td>
          <td data-label="Actions">
            <form method="post" action="<?= e(url('/admin/lead-finder/' . $p['id'] . '/delete')) ?>" data-confirm="Delete this prospect?">
              <?= csrf_field() ?>
              <button class="btn btn-outline btn-sm danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= View::partial('partials/pagination', ['pager' => $pager, 'link' => static fn (int $p): string => url('/admin/lead-finder') . '?' . $query(['page' => $p > 1 ? $p : null])]) ?>
<?php else: ?>
  <p class="muted">No prospects yet. Run a search above to find leads from Google Maps.</p>
<?php endif; ?>
</div>

<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  Array.prototype.forEach.call(document.querySelectorAll('select[data-autosubmit]'), function (sel) {
    sel.addEventListener('change', function () {
      var f = sel.closest('form');
      if (f) { f.submit(); }
    });
  });
})();
</script>
