<x-layouts.app :title="$page->title" :noindex="! $page->is_published">
    @if ($videoId)
        @include('pages.landing-body')
    @else
        <div class="lead-gate">
            <div class="lead-gate__page" inert aria-hidden="true">
                @include('pages.landing-body')
            </div>
            <div class="lead-gate__layer">
                <div class="wrap lead-gate__frame">
                    <section class="lead-gate__dialog" role="dialog" aria-labelledby="lead-gate-title" x-data="{ tab: '{{ $activeForm }}' }">
                        <h1 id="lead-gate-title">Satu Langkah Lagi, Lalu Langsung Tonton</h1>
                        <p class="lead-gate__intro">Buat akun gratis untuk membuka videonya. Akunmu juga menyimpan video ini agar bisa ditonton ulang kapan saja.</p>
                        <div class="lead-gate__tabs" role="tablist" aria-label="Daftar atau masuk" x-on:keydown.arrow-right.prevent="tab = tab === 'register' ? 'login' : 'register'; $nextTick(() => $el.querySelector('[aria-selected=true]').focus())" x-on:keydown.arrow-left.prevent="tab = tab === 'register' ? 'login' : 'register'; $nextTick(() => $el.querySelector('[aria-selected=true]').focus())">
                            @foreach (['register' => 'Daftar', 'login' => 'Masuk'] as $key => $label)
                                <button type="button" role="tab" id="lead-tab-{{ $key }}" aria-controls="lead-panel-{{ $key }}" aria-selected="{{ $activeForm === $key ? 'true' : 'false' }}" @if ($activeForm !== $key) tabindex="-1" @endif x-bind:aria-selected="(tab === '{{ $key }}').toString()" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1" x-on:click="tab = '{{ $key }}'">{{ $label }}</button>
                            @endforeach
                        </div>
                        <x-form :action="route('register.store')" :columns="2" id="lead-panel-register" role="tabpanel" aria-labelledby="lead-tab-register" x-bind:hidden="tab !== 'register'" :hidden="$activeForm !== 'register'">
                            <input type="hidden" name="next" value="/{{ $page->slug }}">
                            <input type="hidden" name="_form" value="register">
                            @include('auth.partials.register-fields', ['prefix' => 'lead-register-', 'bag' => $activeForm === 'register' ? 'default' : 'none'])
                            <div class="btn-row field--full">
                                <button type="submit" class="btn btn--primary btn--block">Daftar &amp; tonton gratis</button>
                            </div>
                            <p class="lead-gate__legal field--full">Gratis, tanpa kartu kredit. Email dipakai untuk akunmu dan informasi belajar dari {{ \App\Support\Settings::get('site_name') }}.</p>
                            <x-legal-consent class="lead-gate__legal field--full" />
                        </x-form>
                        <x-form :action="route('login.store')" id="lead-panel-login" role="tabpanel" aria-labelledby="lead-tab-login" x-bind:hidden="tab !== 'login'" :hidden="$activeForm !== 'login'">
                            <input type="hidden" name="next" value="/{{ $page->slug }}">
                            <input type="hidden" name="_form" value="login">
                            @include('auth.partials.login-fields', ['prefix' => 'lead-login-', 'bag' => $activeForm === 'login' ? 'default' : 'none'])
                            <div class="btn-row">
                                <button type="submit" class="btn btn--primary btn--block">Masuk dan tonton</button>
                            </div>
                            <p class="lead-gate__legal"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
                        </x-form>
                    </section>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
