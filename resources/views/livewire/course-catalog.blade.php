<div>
    <div class="page-head">
        <div>
            <h1>Semua kursus</h1>
            <p class="muted">{{ $totalPublished }} kursus tersedia. Cari judul, saring kategori, atau urutkan.</p>
        </div>
    </div>
    <form class="toolbar" role="search" x-data x-on:submit.prevent>
        <div class="field">
            <label for="catalog-q">Cari judul</label>
            <input id="catalog-q" type="search" wire:model.live.debounce.300ms="q" placeholder="misalnya lele" autocomplete="off">
        </div>
        <div class="field">
            <label for="catalog-cat">Kategori</label>
            <select id="catalog-cat" wire:model.live="category">
                <option value="" @selected($selectedCategory === '')>Semua kategori</option>
                @foreach ($categories as $option)
                    <option value="{{ $option->slug }}" @selected($selectedCategory === $option->slug)>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="catalog-sort">Urutkan</label>
            <select id="catalog-sort" wire:model.live="sort">
                @foreach (\App\Livewire\CourseCatalog::SORTS as $value => $label)
                    <option value="{{ $value }}" @selected($selectedSort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if ($results->isEmpty())
        <x-empty-state message="Tidak ada kursus yang cocok.">
            <button type="button" class="btn" wire:click="clearFilters">Hapus pencarian dan filter</button>
        </x-empty-state>
    @else
        <div class="course-grid">
            @foreach ($results as $course)
                <x-course-card :course="$course" :owned="isset($owned[$course->id])" :progress="$owned[$course->id] ?? null" wire:key="course-{{ $course->id }}" />
            @endforeach
        </div>
        {{ $results->links() }}
    @endif
</div>
