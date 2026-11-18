<header class="site-header" x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false">
    <div class="wrap site-header__inner">
        <a class="brand" href="{{ url('/') }}" aria-label="{{ $siteName }}, ke beranda">
            <img class="brand__logo" src="{{ $logoUrl }}" alt="" width="60" height="36">
            <span class="brand__text">
                <span class="brand__name">{{ $siteName }}</span>
                @if ($slogan)
                    <span class="brand__slogan">{{ $slogan }}</span>
                @endif
            </span>
        </a>
        <nav class="nav" aria-label="Navigasi utama">
            @foreach ($links as $link)
                <a href="{{ url($link['path']) }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            @if ($user)
                <div class="account" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" class="account__toggle{{ $onAccountPage ? ' is-current' : '' }}" aria-controls="account-menu" aria-expanded="false" :aria-expanded="open.toString()" @click="open = ! open">
                        <span class="account__avatar" aria-hidden="true">{{ $initials() }}</span>
                        <span class="account__name">{{ $user->name }}</span>
                        <x-icon name="chevron" :size="16" />
                    </button>
                    <div class="account__menu" id="account-menu" x-show="open" x-cloak>
                        <p class="account__who">{{ $user->name }}<span>{{ $user->email }}</span></p>
                        @foreach ($accountLinks as $link)
                            <a href="{{ url($link['path']) }}" @if ($link['current']) aria-current="page" @endif><x-icon :name="$link['icon']" />{{ $link['label'] }}</a>
                        @endforeach
                        <div class="account__sep" role="separator"></div>
                        <form method="POST" action="{{ url('/logout') }}">
                            @csrf
                            <button type="submit" class="account__logout"><x-icon name="logout" />Keluar</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ url('/login') }}" @if (request()->is('login')) aria-current="page" @endif>Masuk</a>
                <a href="{{ url('/register') }}" class="btn btn--brand btn--small">Daftar</a>
            @endif
        </nav>
        <button type="button" class="nav-toggle" aria-label="Buka menu" aria-controls="nav-mobile" aria-expanded="false" :aria-expanded="navOpen.toString()" @click="navOpen = ! navOpen">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>
    <nav class="nav-mobile" id="nav-mobile" data-open="false" :data-open="navOpen.toString()" aria-label="Navigasi ponsel">
        @foreach ($links as $link)
            <a href="{{ url($link['path']) }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a>
        @endforeach
        @if ($user)
            @foreach ($accountLinks as $link)
                <a href="{{ url($link['path']) }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            <span class="nav-mobile__sep" role="separator"></span>
            <form method="POST" action="{{ url('/logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        @else
            <a href="{{ url('/login') }}">Masuk</a>
            <a href="{{ url('/register') }}" class="btn btn--brand btn--small">Daftar</a>
        @endif
    </nav>
</header>
