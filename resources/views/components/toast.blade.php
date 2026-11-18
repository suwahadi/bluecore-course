<div class="toast-root" role="status" aria-live="polite">
    @if (session('status'))
        <div class="toast toast--ok" x-data="{ shown: true }" x-init="setTimeout(() => shown = false, 4000)" x-show="shown" x-transition.opacity>
            <span class="toast__icon"><x-icon name="check" :size="16" /></span>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="toast toast--bad" x-data="{ shown: true }" x-init="setTimeout(() => shown = false, 4000)" x-show="shown" x-transition.opacity>
            <span class="toast__icon"><x-icon name="info" :size="16" /></span>
            <span>{{ session('error') }}</span>
        </div>
    @endif
</div>
