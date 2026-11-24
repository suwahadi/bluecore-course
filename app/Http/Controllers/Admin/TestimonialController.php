<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SortOrderRequest;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Testimonial;
use App\Services\Ordering\SortOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(private SortOrderService $sorter)
    {
    }

    public function index(): View
    {
        return view('admin.testimonials.index', [
            'testimonials' => Testimonial::query()->ordered()->paginate(20),
            'nextPosition' => Testimonial::query()->count() + 1,
        ]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $this->save(new Testimonial, $request->validated());

        return back()->with('status', 'Testimoni dibuat.');
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->save($testimonial, $request->validated());

        return back()->with('status', 'Testimoni disimpan.');
    }

    public function move(SortOrderRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->sorter->move($testimonial, $request->validated('direction'));

        return back();
    }

    public function toggle(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['is_visible' => ! $testimonial->is_visible]);

        return back()->with('status', 'Testimoni '.$testimonial->name.($testimonial->is_visible ? ' ditampilkan.' : ' disembunyikan.'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('status', 'Testimoni dihapus.');
    }

    private function save(Testimonial $testimonial, array $data): void
    {
        DB::transaction(function () use ($testimonial, $data) {
            $testimonial->fill(Arr::except($data, 'sort_order'));
            $this->sorter->placeAt($testimonial, (int) $data['sort_order']);
        });
    }
}
