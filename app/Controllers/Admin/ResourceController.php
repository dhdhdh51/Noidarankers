<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\HtmlSanitizer;
use App\Core\HttpException;
use App\Core\ImageProcessor;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Str;
use App\Core\Validator;

/**
 * Field-definition driven CRUD used by all content managers.
 *
 * Field options: label, type (text|textarea|editor|select|checkbox|number|slug|image|datetime|url|email),
 * required, max, min, help, options, default, rows, counter, from (slug source), full, section.
 */
abstract class ResourceController extends AdminController
{
    abstract protected function table(): string;

    /** URL segment, e.g. "posts" -> /admin/posts */
    abstract protected function path(): string;

    abstract protected function singular(): string;

    abstract protected function plural(): string;

    /** @return array<string, array<string, mixed>> */
    abstract protected function fields(): array;

    /** @return array<string, string> column => heading */
    abstract protected function columns(): array;

    protected function orderBy(): string
    {
        return 'id DESC';
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return [];
    }

    /** @param array<string, mixed> $row */
    protected function publicUrl(array $row): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    protected function reservedSlugs(): array
    {
        return [];
    }

    /** Extra rows/fields rendered below the generic form. @param array<string, mixed>|null $row @param array<string, string> $errors */
    protected function extraForm(?array $row, array $errors): string
    {
        return '';
    }

    /** Validate/transform extra data. @param array<string, mixed> $data @param array<string, mixed>|null $existing @param array<string, string> $errors @return array<string, mixed> */
    protected function beforeSave(array $data, ?array $existing, array &$errors): array
    {
        return $data;
    }

    protected function afterSave(int $id): void
    {
    }

    /** @param array<string, mixed> $row */
    protected function beforeDelete(array $row): void
    {
        foreach ($this->fields() as $name => $field) {
            if (($field['type'] ?? '') === 'image') {
                ImageProcessor::delete((string) ($row[$name] ?? ''));
            }
        }
    }

    // ------------------------------------------------------------------ actions

    public function index(): string
    {
        $q = Request::query('q');
        $page = max(1, (int) Request::query('page', '1'));
        $where = '1=1';
        $params = [];
        if ($q !== '' && $this->searchColumns()) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $parts = [];
            foreach ($this->searchColumns() as $column) {
                $parts[] = "`$column` LIKE ?";
                $params[] = $like;
            }
            $where = '(' . implode(' OR ', $parts) . ')';
        }
        $table = $this->table();
        $total = (int) Database::value("SELECT COUNT(*) FROM `$table` WHERE $where", $params);
        $pager = new Paginator($total, 25, $page);
        $rows = Database::all("SELECT * FROM `$table` WHERE $where ORDER BY " . $this->orderBy() . ' LIMIT ? OFFSET ?',
            array_merge($params, [25, $pager->offset]));
        return $this->admin('resource/index', [
            'rows' => $rows,
            'pager' => $pager,
            'q' => $q,
            'resource' => $this,
            'searchable' => $this->searchColumns() !== [],
        ], $this->plural());
    }

    public function create(): string
    {
        $row = [];
        foreach ($this->fields() as $name => $field) {
            $row[$name] = $field['default'] ?? '';
        }
        return $this->form(null, $row, []);
    }

    public function store(): string
    {
        return $this->save(null);
    }

    public function edit(string $id): string
    {
        $row = $this->find($this->id($id));
        return $this->form($row, $row, []);
    }

    public function update(string $id): string
    {
        return $this->save($this->find($this->id($id)));
    }

    public function destroy(string $id): void
    {
        $row = $this->find($this->id($id));
        $this->beforeDelete($row);
        Database::delete($this->table(), ['id' => (int) $row['id']]);
        $this->success(ucfirst($this->singular()) . ' deleted.');
        redirect(url('/admin/' . $this->path()));
    }

    // ------------------------------------------------------------------ helpers used by views

    public function basePath(): string
    {
        return '/admin/' . $this->path();
    }

    public function singularLabel(): string
    {
        return $this->singular();
    }

    /** @return array<string, string> */
    public function listColumns(): array
    {
        return $this->columns();
    }

    /** @param array<string, mixed> $row */
    public function viewUrl(array $row): ?string
    {
        return $this->publicUrl($row);
    }

    /** HTML for one table cell (escaped). @param array<string, mixed> $row */
    public function cell(string $column, array $row): string
    {
        $value = $row[$column] ?? '';
        if ($column === 'status') {
            $class = in_array($value, ['published', 'subscribed'], true) ? 'badge-green' : 'badge-slate';
            return '<span class="badge ' . $class . '">' . e(ucfirst((string) $value)) . '</span>';
        }
        if (in_array($column, ['created_at', 'updated_at', 'published_at'], true)) {
            return e(format_date((string) $value, 'j M Y, H:i'));
        }
        if (in_array($column, ['is_featured'], true)) {
            return (int) $value === 1 ? '<span class="badge badge-blue">Yes</span>' : '';
        }
        $html = e(excerpt((string) $value, 80));
        if ($column === $this->titleColumn() && !empty($row['is_sample'])) {
            $html .= ' <span class="badge badge-amber">SAMPLE</span>';
        }
        return $html;
    }

    public function titleColumn(): string
    {
        return (string) array_key_first($this->columns());
    }

    // ------------------------------------------------------------------ internals

    /** @return array<string, mixed> */
    protected function find(int $id): array
    {
        $row = Database::one('SELECT * FROM `' . $this->table() . '` WHERE id = ?', [$id]);
        if ($row === null) {
            throw new HttpException(404);
        }
        return $row;
    }

    /** @param array<string, mixed>|null $existing @param array<string, mixed> $values @param array<string, string> $errors */
    protected function form(?array $existing, array $values, array $errors): string
    {
        $name = $existing ? 'Edit ' . $this->singular() : 'Add ' . $this->singular();
        return $this->admin('resource/form', [
            'resource' => $this,
            'fields' => $this->fields(),
            'values' => $values,
            'errors' => $errors,
            'existing' => $existing,
            'extra' => $this->extraForm($existing, $errors),
            'action' => url($this->basePath() . ($existing ? '/' . $existing['id'] : '')),
        ], $name);
    }

    /** @param array<string, mixed>|null $existing */
    protected function save(?array $existing): string
    {
        $fields = $this->fields();
        $input = [];
        $data = [];
        $errors = [];

        foreach ($fields as $name => $field) {
            $type = $field['type'] ?? 'text';
            if ($type === 'image') {
                continue;
            }
            $label = (string) ($field['label'] ?? $name);
            if ($type === 'checkbox') {
                $input[$name] = Request::input($name) === '1' ? 1 : 0;
                $data[$name] = $input[$name];
                continue;
            }
            $value = $type === 'editor' ? Request::raw($name) : Request::input($name);
            $input[$name] = $value;

            if ($type === 'slug') {
                $value = Str::slug($value !== '' ? $value : Request::input((string) ($field['from'] ?? 'title')));
                $input[$name] = $value;
            }
            if (!empty($field['required']) && trim($value) === '') {
                $errors[$name] = $label . ' is required.';
                continue;
            }
            if (isset($field['max']) && mb_strlen($value) > (int) $field['max']) {
                $errors[$name] = sprintf('%s must be %d characters or fewer.', $label, (int) $field['max']);
                continue;
            }
            switch ($type) {
                case 'number':
                    $v = new Validator([$name => $value === '' ? '0' : $value]);
                    $v->integer($name, (int) ($field['min'] ?? -100000), (int) ($field['max_value'] ?? 100000), $label);
                    if ($v->fails()) {
                        $errors[$name] = $v->errors()[$name];
                    }
                    $value = (int) $value;
                    break;
                case 'select':
                    $options = array_map('strval', array_keys((array) ($field['options'] ?? [])));
                    if ($value !== '' && !in_array($value, $options, true)) {
                        $errors[$name] = 'Please choose a valid option for ' . strtolower($label) . '.';
                    }
                    if ($value === '' && !empty($field['nullable'])) {
                        $value = null;
                    }
                    break;
                case 'slug':
                    if ($value === '') {
                        $errors[$name] = $label . ' is required.';
                    } else {
                        $value = Str::uniqueSlug($this->table(), $value, (int) ($existing['id'] ?? 0), $this->reservedSlugs());
                    }
                    break;
                case 'url':
                    if ($value !== '') {
                        $normalised = Validator::normaliseUrl($value);
                        if ($normalised === null) {
                            $errors[$name] = 'Please enter a valid web address.';
                        } else {
                            $value = $normalised;
                        }
                    }
                    break;
                case 'email':
                    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$name] = 'Please enter a valid email address.';
                    }
                    break;
                case 'datetime':
                    if ($value === '') {
                        $value = null;
                    } else {
                        $ts = strtotime(str_replace('T', ' ', $value));
                        if ($ts === false) {
                            $errors[$name] = 'Please enter a valid date and time.';
                        } else {
                            $value = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    break;
                case 'editor':
                    $value = HtmlSanitizer::clean($value);
                    break;
            }
            $data[$name] = $value;
        }

        if (!$errors) {
            $data = $this->beforeSave($data, $existing, $errors);
        }
        if (!$errors) {
            foreach ($fields as $name => $field) {
                if (($field['type'] ?? '') !== 'image') {
                    continue;
                }
                $file = $_FILES[$name] ?? null;
                $remove = Request::input('remove_' . $name) === '1';
                if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $image = ImageProcessor::upload($file, (string) ($data['slug'] ?? $data['title'] ?? $name));
                        if ($existing) {
                            ImageProcessor::delete((string) ($existing[$name] ?? ''));
                        }
                        $data[$name] = $image['path'];
                        $data[$name . '_width'] = $image['width'];
                        $data[$name . '_height'] = $image['height'];
                    } catch (\RuntimeException $e) {
                        $errors[$name] = $e->getMessage();
                    }
                } elseif ($remove && $existing) {
                    ImageProcessor::delete((string) ($existing[$name] ?? ''));
                    $data[$name] = '';
                    $data[$name . '_width'] = 0;
                    $data[$name . '_height'] = 0;
                }
            }
        }

        if ($errors) {
            http_response_code(422);
            $values = array_merge($existing ?? [], $input);
            return $this->form($existing, $values, $errors);
        }

        if ($existing) {
            Database::update($this->table(), $data, ['id' => (int) $existing['id']]);
            $id = (int) $existing['id'];
        } else {
            $id = Database::insert($this->table(), $data);
        }
        $this->afterSave($id);
        $this->success(ucfirst($this->singular()) . ($existing ? ' updated.' : ' created.'));
        redirect(url($this->basePath() . '/' . $id . '/edit'));
        return '';
    }
}
