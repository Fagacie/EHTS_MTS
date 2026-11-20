/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}

declare module 'lottie-web/build/player/lottie_light' {
    import type { LottiePlayer } from 'lottie-web';
    const lottie: LottiePlayer;
    export default lottie;
}

declare module 'hls.js/light' {
    import Hls from 'hls.js';
    export * from 'hls.js';
    export default Hls;
}
