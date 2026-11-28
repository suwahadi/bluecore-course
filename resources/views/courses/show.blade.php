@php
    use App\Enums\CourseStatus;
    use App\Support\Format;
    use App\Support\Markdown;
    use Illuminate\Support\Str;
    $draft = $course->status !== CourseStatus::Published;
    $checkoutPath = '/courses/'.$course->slug.'/checkout';
    $thumbnailUrl = $course->thumbnail_path ? Storage::disk('public')->url($course->thumbnail_path) : null;
@endphp
<x-layouts.app :title="$course->title" :description="$course->short_description" :image="$thumbnailUrl" :noindex="$draft">
    <section class="section">
        <div class="wrap course-layout">
            <div class="course-layout__head">
                @if ($draft)
                    <p class="note note--warn course-note">Kursus ini masih draf. Hanya admin yang bisa melihat halaman ini.</p>
                @endif
                <p class="course-eyebrow">{{ $course->level ? 'Kursus untuk '.Str::lower($course->level) : $course->category?->name }}</p>
                <h1>{{ $course->title }}</h1>
                @if (filled($course->short_description))
                    <p class="course-lead">{{ $course->short_description }}</p>
                @endif
                <x-course-meta :course="$course" :participants="true" variant="strip" class="course-facts" />
            </div>

            <aside class="course-layout__aside">
                <div class="panel buybox sticky">
                    <div class="buybox__media">
                        @if ($thumbnailUrl)
                            <img class="thumb" src="{{ $thumbnailUrl }}" alt="Thumbnail {{ $course->title }}" width="1200" height="675">
                        @else
                            <x-thumbnail-placeholder :title="$course->title" />
                        @endif
                    </div>
                    <div class="buybox__body">
                        <div class="price-block"><x-price :course="$course" /></div>
                        <p class="muted small buybox__hint">Bayar sekali, akses selamanya.</p>
                        @if ($adminFee > 0 && ! $canLearn && ! $openOrder)
                            <p class="small buybox__total">Total pembayaran {{ Format::rupiah($course->price + $adminFee) }}, sudah termasuk biaya admin {{ Format::rupiah($adminFee) }}.</p>
                        @endif
                        <div class="buybox__cta">
                            @if ($canLearn)
                                <a class="btn btn--primary btn--block" href="{{ route('learn.index', $course) }}">Lanjutkan belajar</a>
                                @if ($progress)
                                    <div class="buybox__progress">
                                        <div class="progress-line"><span>Progres</span><span class="num">{{ $progress->done() }} dari {{ $progress->total() }} video</span></div>
                                        <x-segment-bar :progress="$progress" :thin="true" />
                                    </div>
                                @endif
                            @elseif (! auth()->check())
                                <a class="btn btn--primary btn--block" href="{{ url('/register?next='.urlencode($checkoutPath)) }}">BELI KURSUS SEKARANG</a>
                                <p class="muted small buybox__hint">Sudah punya akun? <a href="{{ url('/login?next='.urlencode($checkoutPath)) }}">Masuk</a></p>
                            @elseif ($openOrder)
                                <a class="btn btn--primary btn--block" href="{{ url('/order/'.$openOrder->uuid) }}">Selesaikan pembayaran</a>
                            @else
                                <a class="btn btn--primary btn--block" href="{{ url($checkoutPath) }}">BELI KURSUS SEKARANG</a>
                            @endif
                        </div>
                        @unless ($canLearn)
                            <p class="muted small buybox__hint">Kursus terbuka otomatis setelah pembayaran terverifikasi.</p>
                            <p class="buybox__urgency">Belajar sebelum beli bibit, bukan setelah ikan bermasalah.</p>
                        @endunless
                    </div>
                </div>
            </aside>

            <div class="course-layout__body">
                <div class="panel">
                    <h2 class="panel-title">Tentang kursus ini</h2>
                    <div class="course-description prose">{!! Markdown::html($course->description) !!}</div>
                </div>
                <div class="panel" id="daftar-video">
                    <h2 class="panel-title">Daftar video</h2>
                    <ul class="list">
                        @foreach ($course->segments as $segment)
                            <li><span>{{ $loop->iteration }}. {{ $segment->title }}</span><span class="muted small nowrap">{{ $segment->duration_seconds ? Format::duration($segment->duration_seconds) : '' }}</span></li>
                        @endforeach
                    </ul>
                </div>
                @if ($course->materials->isNotEmpty())
                    <div class="panel panel--pdf">
                        <h2 class="panel-title">Materi PDF</h2>
                        <ul class="list">
                            @foreach ($course->materials as $material)
                                <li><span class="file-name"><x-icon name="file" :size="20" /><span>{{ $material->title }}</span></span><span class="muted small nowrap">{{ Format::fileSize($material->file_size) }}</span></li>
                            @endforeach
                        </ul>
                        <p class="muted small">{{ $canLearn ? 'Unduh dari halaman belajar.' : 'Bisa diunduh setelah punya akses.' }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
