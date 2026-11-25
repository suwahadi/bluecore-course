@php
    $messages = array_values(array_filter([
        session('status') ? ['tone' => 'ok', 'text' => __(session('status'))] : null,
        session('error') ? ['tone' => 'bad', 'text' => session('error')] : null,
    ]));
@endphp
<div class="toast-root" role="status" aria-live="polite" x-data="{ messages: [] }" x-on:toast.window="messages = [$event.detail]; setTimeout(() => messages = [], 4000)" x-init="setTimeout(() => { messages = {{ \Illuminate\Support\Js::from($messages) }}; setTimeout(() => messages = [], 4000) }, 150)">
    <template x-for="message in messages" :key="message.text">
        <div class="toast" x-bind:class="message.tone === 'ok' ? 'toast--ok' : 'toast--bad'" x-transition.opacity>
            <span class="toast__icon">
                <x-icon name="check" :size="16" x-show="message.tone === 'ok'" />
                <x-icon name="info" :size="16" x-show="message.tone !== 'ok'" />
            </span>
            <span x-text="message.text"></span>
        </div>
    </template>
</div>
