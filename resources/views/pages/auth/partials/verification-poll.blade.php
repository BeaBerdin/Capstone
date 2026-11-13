<script>
(() => {
    const panel = document.querySelector('[data-verification-status-url]');
    if (!panel) return;

    const { verificationStatusUrl, verificationDashboardUrl, verificationLoginUrl } = panel.dataset;
    let timer = null;
    let request = null;
    let stopped = false;

    const pause = () => {
        stopped = true;
        window.clearInterval(timer);
        timer = null;
        request?.abort();
    };

    const cleanup = () => {
        pause();
        window.removeEventListener('focus', check);
        window.removeEventListener('pageshow', start);
        window.removeEventListener('pagehide', pause);
        document.removeEventListener('visibilitychange', check);
        document.removeEventListener('livewire:navigating', cleanup);
    };

    const navigate = (url) => {
        cleanup();
        window.location.replace(url);
    };

    async function check() {
        if (stopped || document.hidden || request) return;
        request = new AbortController();

        try {
            const response = await fetch(verificationStatusUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: request.signal,
            });

            if (stopped) return;

            if (response.redirected || response.status === 401 || response.status === 419) {
                navigate(verificationLoginUrl);
                return;
            }

            if (!response.ok) return;
            const status = await response.json();
            if (!stopped && status.verified === true) navigate(verificationDashboardUrl);
        } catch {
            // Retry on the next interval/focus after a temporary network error.
        } finally {
            request = null;
        }
    }

    function start() {
        stopped = false;
        if (timer === null) timer = window.setInterval(check, 5000);
        void check();
    }

    window.addEventListener('focus', check);
    window.addEventListener('pageshow', start);
    window.addEventListener('pagehide', pause);
    document.addEventListener('visibilitychange', check);
    document.addEventListener('livewire:navigating', cleanup);
    start();
})();
</script>
