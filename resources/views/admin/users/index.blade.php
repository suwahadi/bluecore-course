@php
    use App\Enums\UserRole;
    use App\Support\Format;
    $activeFilters = array_filter($filters, fn ($value) => is_string($value) && $value !== '');
    $exportUrl = route('admin.users.export', $activeFilters);
@endphp
<x-admin.layout title="Pengguna">
    <form method="GET" action="{{ route('admin.users.index') }}" class="toolbar toolbar--users">
        <div class="field">
            <label for="filter-q">Cari nama atau email</label>
            <input id="filter-q" type="search" name="q" value="{{ is_string($filters['q'] ?? null) ? $filters['q'] : '' }}">
        </div>
        <div class="field">
            <label for="filter-role">Peran</label>
            <select id="filter-role" name="role" x-data x-on:change="$el.form.requestSubmit()">
                <option value="">Semua</option>
                @foreach (UserRole::cases() as $role)
                    <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="toolbar__actions">
            <button type="submit" class="btn">Terapkan</button>
            @if ($activeFilters !== [])
                <a class="btn" href="{{ route('admin.users.index') }}">Hapus filter</a>
            @endif
            <a class="btn" href="{{ $exportUrl }}">Ekspor CSV</a>
        </div>
    </form>
    <p class="muted small admin-summary">{{ $userCount }} pengguna terdaftar, {{ $adminCount }} di antaranya admin.</p>

    @if ($users->isEmpty())
        <x-empty-state message="Tidak ada pengguna yang cocok dengan pencarian ini.">
            <a class="btn" href="{{ route('admin.users.index') }}">Hapus filter</a>
        </x-empty-state>
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Pengguna</th><th>Peran</th><th>Verifikasi</th><th class="num">Kursus</th><th class="num">Total belanja</th><th>Terdaftar</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if ($user->is(auth()->user()))
                                    <span class="badge">Anda</span>
                                @endif
                                <div class="muted small">{{ $user->email }}</div>
                                @if ($user->phone)
                                    <div class="muted small">{{ $user->phone }}</div>
                                @endif
                            </td>
                            <td><span class="{{ $user->isAdmin() ? 'badge badge--brand' : 'badge' }}">{{ $user->role->label() }}</span></td>
                            <td>
                                @if ($user->email_verified_at)
                                    <span class="badge badge--ok">Terverifikasi</span>
                                @else
                                    <span class="badge badge--warn">Belum verifikasi</span>
                                @endif
                            </td>
                            <td class="num">{{ $user->enrollments_count }}</td>
                            <td class="num">{{ Format::rupiah((int) $user->paid_total) }}</td>
                            <td class="muted">{{ Format::datetime($user->created_at) }}</td>
                            <td>
                                <div class="btn-row">
                                    <x-admin.icon-button icon="pencil" label="Edit pengguna" :opens="'user-'.$user->id" />
                                    <x-admin.icon-button icon="key" label="Beri akses kursus" :opens="'grant-'.$user->id" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    @endif

    @foreach ($users as $user)
        @include('admin.users.dialogs')
    @endforeach
</x-admin.layout>
