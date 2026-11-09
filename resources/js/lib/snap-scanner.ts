import {
    createMealCameraSession,
    initialMealCameraState,
    type MealCameraSession,
    type MealCameraState,
} from './meal-camera';

interface ScannerMagics {
    $refs: Record<string, HTMLElement | undefined>;
    $dispatch: (event: string, detail?: unknown) => void;
    $nextTick: (callback?: () => void) => Promise<void>;
}

interface SnapScanner {
    open: boolean;
    camera: MealCameraState;
    destroy(): void;
    show(): void;
    close(): void;
    retry(): void;
    playing(): void;
    toggleTorch(): void;
    capture(): Promise<void>;
    pick(input: HTMLInputElement): void;
}

export function snapScanner(): SnapScanner &
    ThisType<SnapScanner & ScannerMagics> {
    let session: MealCameraSession | null = null;

    return {
        open: false,
        camera: { ...initialMealCameraState },

        destroy() {
            session?.destroy();
            session = null;
        },

        show() {
            if (this.open) {
                return;
            }
            this.open = true;
            void this.$nextTick(() => {
                const video = this.$refs.video as HTMLVideoElement | undefined;
                if (!this.open || !video || session) {
                    return;
                }
                session = createMealCameraSession(video, (state) => {
                    this.camera = state;
                });
                session.open();
            });
        },

        close() {
            this.destroy();
            this.open = false;
            this.camera = { ...initialMealCameraState };
        },

        retry() {
            void session?.start();
        },

        playing() {
            session?.playing();
        },

        toggleTorch() {
            void session?.toggleTorch();
        },

        async capture() {
            const photo = await session?.capture();
            if (!photo) {
                return;
            }
            this.close();
            this.$dispatch('snap-photo-picked', { photo });
        },

        pick(input) {
            const photo = input.files?.[0];
            input.value = '';
            if (!photo) {
                return;
            }
            this.close();
            this.$dispatch('snap-photo-picked', { photo });
        },
    };
}
