<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type HlsType from 'hls.js';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    src: string;
    title?: string;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const playbackError = ref<string | null>(null);
const isLoading = ref(true);

let hlsInstance: HlsType | null = null;
let loadGeneration = 0;

function destroyPlayer(): void {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }

    const video = videoRef.value;
    if (video) {
        video.removeAttribute('src');
        video.load();
    }
}

async function initPlayer(): Promise<void> {
    const generation = ++loadGeneration;
    playbackError.value = null;
    isLoading.value = true;

    destroyPlayer();

    const video = videoRef.value;
    const streamUrl = props.src;

    if (!video || !streamUrl) {
        return;
    }

    const isHls = streamUrl.includes('.m3u8');

    if (isHls) {
        try {
            const { default: Hls } = await import('hls.js/light');

            if (generation !== loadGeneration || !videoRef.value) {
                return;
            }

            if (Hls.isSupported()) {
                const hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: false,
                });

                hls.on(Hls.Events.ERROR, (_event, data) => {
                    if (data.fatal) {
                        playbackError.value = 'Unable to load this video stream right now. Please try retrying playback.';
                        hls.destroy();
                        if (hlsInstance === hls) {
                            hlsInstance = null;
                        }
                    }
                });

                hlsInstance = hls;
                hls.loadSource(streamUrl);
                hls.attachMedia(video);
                return;
            }
        } catch {
            if (generation === loadGeneration) {
                playbackError.value = 'Unable to initialise the video player. Please try refreshing the page.';
            }
            return;
        }
    }

    if (video.canPlayType('application/vnd.apple.mpegurl') || !isHls) {
        video.src = streamUrl;
    } else {
        playbackError.value = 'Your browser does not support adaptive HLS video playback.';
    }
}

onMounted(() => {
    void initPlayer();
});

watch(
    () => props.src,
    () => {
        void initPlayer();
    },
);

onBeforeUnmount(() => {
    loadGeneration++;
    destroyPlayer();
});
</script>

<template>
    <div class="space-y-3">
        <div class="relative aspect-video w-full overflow-hidden rounded-xl border border-[#E5E1EB] bg-[#F1EEF5]">
            <video
                ref="videoRef"
                controls
                playsinline
                controlsList="nodownload"
                preload="metadata"
                :aria-label="title || 'Training Video Player'"
                data-etms-hls-player
                :data-src="src"
                class="h-full w-full"
                @loadeddata="isLoading=false"
                @canplay="isLoading=false"
                @waiting="isLoading=true"
                @playing="isLoading=false"
                @error="isLoading=false"
            >
                Your browser does not support HTML5 video playback.
            </video>
            <div
                v-if="isLoading && !playbackError"
                class="pointer-events-none absolute inset-0 grid place-items-center bg-[#F1EEF5]/85"
            >
                <div class="text-center">
                    <div class="mx-auto h-7 w-7 animate-spin rounded-full border-2 border-[#D8D2E3] border-t-[#5942B5]" aria-hidden="true" />
                    <p class="mt-2 text-[12.5px] font-semibold text-[#625D6D]">Loading video lesson...</p>
                </div>
            </div>
        </div>

        <div
            v-if="playbackError"
            role="alert"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#ECC4CA] bg-[#FBECEE] px-4 py-3"
        >
            <p class="text-[13.5px] font-medium text-[#A33C48]">
                {{ playbackError }}
            </p>
            <Button type="button" variant="outline" size="sm" @click="initPlayer">
                Retry Playback
            </Button>
        </div>
    </div>
</template>
