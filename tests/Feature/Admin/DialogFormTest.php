<?php

use App\Http\Requests\Admin\DialogRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class DialogFormProbeRequest extends DialogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'min:2'],
            'segments.*.url' => ['sometimes', 'required', 'url'],
        ];
    }
}

const DIALOG_TEMPLATE = <<<'BLADE'
<x-admin.dialog-form dialog="category-new" action="/_dialog" title="Kategori baru" submit-label="Simpan kategori">
    <x-field name="name" label="Nama" />
    <x-slug-field source="name" :auto="true" />
</x-admin.dialog-form>
<x-admin.dialog-form dialog="category-5" action="/_dialog" method="PUT" title="Ubah kategori" submit-label="Simpan kategori">
    <x-field name="name" label="Nama" value="Lama" />
    <x-slug-field source="name" value="lama" />
    <x-field name="is_active" label="Status" type="select" :options="['1' => 'Aktif', '0' => 'Nonaktif']" :value="true" />
</x-admin.dialog-form>
<x-form action="/_dialog">
    <x-field name="segments[0][url]" label="URL YouTube" />
</x-form>
BLADE;

beforeEach(function () {
    Route::middleware('web')->match(['POST', 'PUT'], '/_dialog', fn (DialogFormProbeRequest $request) => 'ok');
    Route::middleware('web')->get('/_dialog', fn () => Blade::render(DIALOG_TEMPLATE));
});

function inputTag(string $html, string $id): string
{
    preg_match('#<input id="'.preg_quote($id, '#').'"[^>]*>#', $html, $match);

    return $match[0] ?? '';
}

it('scopes old input and errors to the dialog that failed', function () {
    $this->from('/_dialog')->put('/_dialog', ['_modal' => 'category-5', 'name' => 'X', 'slug' => 'tulisan-saya', 'is_active' => '0']);

    $html = $this->get('/_dialog')->getContent();

    expect(inputTag($html, 'category-5-name'))->toContain('value="X"')->toContain('aria-invalid="true"')
        ->and(inputTag($html, 'category-new-name'))->not->toContain('value="X"')->not->toContain('aria-invalid')
        ->and($html)->toContain('<option value="0" selected>Nonaktif</option>')
        ->and(substr_count($html, "x-data=\"{ open: true }\""))->toBe(1)
        ->and($html)->toContain('id="category-5-name-error"');
});

it('keeps the page form clear of dialog input', function () {
    $this->from('/_dialog')->put('/_dialog', ['_modal' => 'category-5', 'name' => 'X']);

    expect($this->get('/_dialog')->getContent())->not->toContain('id="segments-0-url-error"');
});

it('reads bracketed field names with dot keys', function () {
    $this->from('/_dialog')->post('/_dialog', ['segments' => [['url' => 'bukan tautan']]]);

    $html = $this->get('/_dialog')->getContent();

    expect(inputTag($html, 'segments-0-url'))->toContain('value="bukan tautan"')->toContain('aria-invalid="true"');
});

it('keeps a manual slug after a validation error', function () {
    $html = $this->get('/_dialog')->getContent();
    expect($html)->toContain("slugField('name', true)");

    $this->from('/_dialog')->post('/_dialog', ['_modal' => 'category-new', 'name' => 'X', 'slug' => 'tulisan-saya']);

    expect($this->get('/_dialog')->getContent())->not->toContain("slugField('name', true)")
        ->toContain('value="tulisan-saya"');
});

it('renders icon buttons with an accessible name', function () {
    $html = (string) $this->blade('<x-admin.icon-button icon="pencil" label="Ubah kategori" opens="category-5" />');

    expect($html)->toContain('aria-label="Ubah kategori"')
        ->toContain('title="Ubah kategori"')
        ->toContain('type="button"')
        ->toContain("\$dispatch('open-modal', 'category-5')");
});

it('renders action buttons as small spoofed forms', function () {
    $html = (string) $this->blade('<x-admin.action-button action="/admin/x" method="PATCH" icon="power" label="Nonaktifkan" :disabled="true"><input type="hidden" name="direction" value="up"></x-admin.action-button>');

    expect($html)->toContain('action="/admin/x"')
        ->toContain('name="_method" value="PATCH"')
        ->toContain('name="direction" value="up"')
        ->toContain('aria-label="Nonaktifkan"')
        ->toContain('disabled');
});

it('renders sort buttons that are disabled at the ends', function () {
    $html = (string) $this->blade('<x-admin.sort-buttons action="/admin/categories/1/move" :first="true" :last="false" />');

    expect($html)->toContain('aria-label="Naikkan urutan"')
        ->toContain('aria-label="Turunkan urutan"')
        ->toContain('value="up"')
        ->toContain('value="down"')
        ->and(substr_count($html, 'disabled="disabled"'))->toBe(1);
});

it('renders a confirm action with its own dialog', function () {
    $html = (string) $this->blade('<x-admin.confirm-action dialog="delete-category-5" action="/admin/categories/5" label="Hapus kategori" title="Hapus kategori?" message="Kategori Lele akan dihapus permanen." confirm-label="Ya, hapus" />');

    expect($html)->toContain('aria-label="Hapus kategori"')
        ->toContain('Kategori Lele akan dihapus permanen.')
        ->toContain('name="_method" value="DELETE"')
        ->toContain('Ya, hapus')
        ->toContain('Batal');
});

it('renders a checkbox that follows its dialog input', function () {
    $html = (string) $this->blade('<x-checkbox name="show_in_footer" label="Tampilkan di footer" :checked="true" />');

    expect($html)->toContain('type="checkbox"')->toContain('name="show_in_footer"')->toContain('checked')->toContain('Tampilkan di footer');
});
