import Hls from 'hls.js';

function initEtmsVideoPlayers() {
    const players = document.querySelectorAll('video[data-etms-hls-player]');

    players.forEach((video) => {
        if (video.dataset.etmsPlayerInitialized === '1') {
            return;
        }

        const streamUrl = video.dataset.src;
        if (!streamUrl) {
            return;
        }

        video.dataset.etmsPlayerInitialized = '1';

        const isHls = streamUrl.includes('.m3u8');

        if (isHls && Hls.isSupported()) {
            const hls = new Hls({
                enableWorker: true,
                lowLatencyMode: false,
            });
            hls.loadSource(streamUrl);
            hls.attachMedia(video);
            return;
        }

        if (video.canPlayType('application/vnd.apple.mpegurl') || !isHls) {
            video.src = streamUrl;
        }
    });
}

document.addEventListener('DOMContentLoaded', initEtmsVideoPlayers);
document.addEventListener('livewire:navigated', initEtmsVideoPlayers);
