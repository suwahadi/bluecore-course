<div>
    <div class="page-head">
        <h1>Semua kursus</h1>
    </div>
    <form class="catalog-toolbar" role="search" x-data="{ filtersOpen: false }" x-on:submit.prevent>
        <div class="field field--icon catalog-toolbar__search">
            <x-icon name="search" />
            <input id="catalog-q" type="search" wire:model.live.debounce.300ms="q" placeholder="Cari judul kursus" aria-label="Cari judul kursus" autocomplete="off">
        </div>
        <button type="button" class="btn catalog-toolbar__toggle" aria-controls="catalog-filters" aria-expanded="false" x-bind:aria-expanded="filtersOpen.toString()" x-on:click="filtersOpen = ! filtersOpen">
            <x-icon name="sliders" />
            <span>Filter @if ($activeFilters)<span class="num">({{ $activeFilters }})</span>@endif</span>
        </button>
        <div id="catalog-filters" class="catalog-toolbar__filters" x-bind:class="{ 'is-open': filtersOpen }">
            <div class="field field--icon">
                <x-icon name="folder" />
                <select id="catalog-cat" wire:model.live="category" aria-label="Kategori">
                    <option value="" @selected($selectedCategory === '')>Semua kategori</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->slug }}" @selected($selectedCategory === $option->slug)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field field--icon">
                <x-icon name="sort" />
                <select id="catalog-sort" wire:model.live="sort" aria-label="Urutkan">
                    @foreach (\App\Livewire\CourseCatalog::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($selectedSort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
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
