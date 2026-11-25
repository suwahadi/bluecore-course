<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Course;
use App\Services\Courses\CourseQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CourseCatalog extends Component
{
    use WithPagination;

    public const PER_PAGE = 12;

    private const TERM_LIMIT = 100;

    public const SORTS = ['newest' => 'Terbaru', 'cheapest' => 'Termurah', 'priciest' => 'Termahal', 'popular' => 'Terpopuler'];

    #[Url(except: '')]
    public $q = '';

    #[Url(as: 'cat', except: '')]
    public $category = '';

    #[Url(except: 'newest')]
    public $sort = 'newest';

    public function mount(): void
    {
        $this->q = is_string($this->q) ? mb_substr($this->q, 0, self::TERM_LIMIT) : '';
        $this->sort = is_string($this->sort) && array_key_exists($this->sort, self::SORTS) ? $this->sort : 'newest';
        $this->category = is_string($this->category) && Category::query()->active()->where('slug', $this->category)->exists() ? $this->category : '';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'category', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('q', 'category', 'sort');
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }

    public function render(CourseQuery $courses): View
    {
        $categories = Category::query()->active()->ordered()->get(['id', 'name', 'slug']);
        $term = is_string($this->q) ? mb_substr(trim($this->q), 0, self::TERM_LIMIT) : '';
        $category = is_string($this->category) ? $categories->firstWhere('slug', $this->category) : null;
        $sort = is_string($this->sort) && array_key_exists($this->sort, self::SORTS) ? $this->sort : 'newest';

        $results = $courses->catalog($term, $category, $sort)->paginate(self::PER_PAGE);

        if ($results->isEmpty() && $results->currentPage() > 1) {
            $this->resetPage();
            $results = $courses->catalog($term, $category, $sort)->paginate(self::PER_PAGE, page: 1);
        }

        return view('livewire.course-catalog', [
            'results' => $results,
            'categories' => $categories,
            'selectedCategory' => $category?->slug ?? '',
            'selectedSort' => $sort,
            'totalPublished' => Course::query()->published()->count(),
            'owned' => $courses->ownedProgress(Auth::user(), $results->items()),
        ]);
    }
}
