@php
    use App\Support\Format;
@endphp
<x-layouts.app :title="$segment->title" :noindex="true">
    <section class="section">
        <div class="wrap learn">
            <p class="muted small">
                @can('view', $course)
                    <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
                @else
                    {{ $course->title }}
                @endcan
            </p>
            <div class="two-col two-col--learn learn__layout">
                <div>
                    <div class="player player--embed">
                        <div data-player data-video-id="{{ $segment->youtube_video_id }}" data-start="{{ $resumeAt }}" data-position-url="{{ route('learn.position', $segment) }}" data-complete-url="{{ route('learn.complete', $segment) }}"></div>
                    </div>
                    <div class="btn-row learn__nav">
                        @if ($previous)
                            <a class="btn" href="{{ route('learn.show', [$course, $previous]) }}">Sebelumnya</a>
                        @else
                            <span class="btn" aria-disabled="true">Sebelumnya</span>
                        @endif
                        <x-form :action="route('learn.complete', $segment)" class="inline-form">
                            <button type="submit" class="btn btn--primary">{{ $next ? 'Tandai selesai dan lanjut' : 'Tandai selesai' }}</button>
                        </x-form>
                        @if ($next)
                            <a class="btn" href="{{ route('learn.show', [$course, $next]) }}">Berikutnya</a>
                        @endif
                    </div>
                    @if ($courseProgress->isComplete())
                        <p class="note learn__note">Kursus selesai. Semua video sudah ditandai selesai. Anda tetap bisa memutar ulang kapan saja.</p>
                    @endif
                    <h1 id="video-title" tabindex="-1">{{ $segment->title }}</h1>
                    @if ($resumeAt > 0)
                        <p class="learn__resume" data-resume>
                            <span>Lanjut dari {{ Format::clock($resumeAt) }}</span>
                            <button type="button" class="btn btn--small" data-restart>Putar dari awal</button>
                        </p>
                    @endif
                    @if ($segment->duration_seconds)
                        <p class="muted learn__duration">Durasi {{ Format::duration($segment->duration_seconds) }}.</p>
                    @endif
                </div>
                <aside>
                    <div class="panel">
                        <div class="progress-line"><span>Progres</span><span class="num">{{ $courseProgress->done() }} dari {{ $courseProgress->total() }} video ({{ $courseProgress->percent() }}%)</span></div>
                        <x-segment-bar :progress="$courseProgress" :current="$index" />
                        <ul class="seg-list learn__list">
                            @foreach ($segments as $item)
                                @php
                                    $row = $progress->get($item->id);
                                    $done = $row?->completed_at !== null;
                                    $playing = $item->id === $segment->id;
                                    $percent = $done ? 100 : min(100, (int) $row?->percent);
                                @endphp
                                <li>
                                    <a href="{{ route('learn.show', [$course, $item]) }}" @if ($playing) aria-current="true" @endif>
                                        <span class="seg-mark" data-done="{{ $done ? 'true' : 'false' }}" @if ($playing) data-playing="true" @endif aria-hidden="true">
                                            @if ($done)
                                                <x-icon name="check" :size="12" />
                                            @elseif ($playing)
                                                <x-icon name="play" :size="10" :solid="true" />
                                            @endif
                                        </span>
                                        <span>{{ $loop->iteration }}. {{ $item->title }}<span class="sr-only">{{ $done ? ', selesai' : ($playing ? ', sedang diputar' : '') }}</span></span>
                                        <span class="seg-dur num">{{ $item->duration_seconds ? Format::duration($item->duration_seconds) : '' }}</span>
                                        <span class="seg-progress" aria-hidden="true"><span style="width: {{ $percent }}%"></span></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @if ($course->materials->isNotEmpty())
                            <div class="pdf-box learn__materials">
                                <h2>Materi PDF</h2>
                                <ul class="list">
                                    @foreach ($course->materials as $material)
                                        <li>
                                            <span class="file-name small"><x-icon name="file" :size="18" /><span>{{ $material->title }}</span></span>
                                            <a class="btn btn--brand btn--small" href="{{ route('materials.download', [$course, $material]) }}"><x-icon name="download" :size="16" />Unduh</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
