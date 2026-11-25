const apiUrl = 'https://www.youtube.com/iframe_api';
const playerHost = 'https://www.youtube-nocookie.com';
const reportEveryMs = 15000;
const minimumShiftSeconds = 5;
const completeFailedText = 'Progres belum tersimpan. Tekan "Tandai selesai" untuk mencoba lagi.';

let apiReady = null;

const loadApi = () => {
    if (window.YT?.Player) {
        return Promise.resolve(window.YT);
    }

    apiReady ??= new Promise((resolve) => {
        const previousCallback = window.onYouTubeIframeAPIReady;

        window.onYouTubeIframeAPIReady = () => {
            previousCallback?.();
            resolve(window.YT);
        };

        const script = document.createElement('script');
        script.src = apiUrl;
        script.async = true;
        document.head.append(script);
    });

    return apiReady;
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const initPlayer = async (element) => {
    const { videoId, positionUrl, completeUrl } = element.dataset;
    let startAt = Number(element.dataset.start) || 0;
    let player = null;
    let reportTimer = null;
    let lastReported = startAt;
    let completing = false;

    const snapshot = () => {
        const duration = Math.floor(player?.getDuration?.() ?? 0);
        const position = Math.floor(player?.getCurrentTime?.() ?? 0);

        return duration > 0 ? { position: Math.min(position, duration), duration } : null;
    };

    const report = (asBeacon = false) => {
        const current = positionUrl ? snapshot() : null;

        if (!current || Math.abs(current.position - lastReported) < minimumShiftSeconds) {
            return;
        }

        lastReported = current.position;

        const body = new FormData();
        body.append('_token', csrfToken());
        body.append('position', String(current.position));
        body.append('duration', String(current.duration));

        if (asBeacon && navigator.sendBeacon) {
            navigator.sendBeacon(positionUrl, body);

            return;
        }

        fetch(positionUrl, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            keepalive: true,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).catch(() => {});
    };

    const complete = async () => {
        if (!completeUrl || completing) {
            return;
        }

        completing = true;

        try {
            const response = await fetch(completeUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const { next_url: nextUrl } = await response.json();

            if (nextUrl) {
                window.location.assign(nextUrl);
            } else {
                window.location.reload();
            }
        } catch {
            completing = false;
            window.dispatchEvent(new CustomEvent('toast', { detail: { tone: 'bad', text: completeFailedText } }));
        }
    };

    const stopReporting = () => {
        clearInterval(reportTimer);
        reportTimer = null;
    };

    document.querySelector('[data-restart]')?.addEventListener('click', (event) => {
        startAt = 0;
        player?.seekTo?.(0, true);
        event.currentTarget.closest('[data-resume]')?.remove();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            report(true);
        }
    });

    window.addEventListener('pagehide', () => report(true));

    const YT = await loadApi();

    player = new YT.Player(element, {
        host: playerHost,
        videoId,
        playerVars: { rel: 0, modestbranding: 1, playsinline: 1, start: startAt },
        events: {
            onStateChange: ({ data }) => {
                if (data === YT.PlayerState.PLAYING) {
                    reportTimer ??= setInterval(report, reportEveryMs);

                    return;
                }

                stopReporting();

                if (data === YT.PlayerState.PAUSED) {
                    report();
                }

                if (data === YT.PlayerState.ENDED) {
                    complete();
                }
            },
        },
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-player]').forEach((element) => initPlayer(element));
});
