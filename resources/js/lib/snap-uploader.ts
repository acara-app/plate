import { keepSnapPhoto, prepareSnapPhoto } from './snap-to-track-photo';

const POLL_MS = 100;
const PAGE_LOAD_WAIT_MS = 4000;
const SLOW_VERIFICATION_MS = 7000;

interface VerificationApi {
    render(
        container: HTMLElement,
        options: Record<string, unknown>,
    ): string | null | undefined;
    reset(widget?: string): void;
    remove(widget: string): void;
}

declare global {
    interface Window {
        turnstile?: VerificationApi;
        acaraTrack?: (
            event: string,
            properties?: Record<string, unknown>,
        ) => void;
    }
}

interface UploadProgressEvent {
    detail: { progress: number };
}

interface UploaderWire {
    turnstileToken: string | null;
    $upload(
        name: string,
        file: File,
        finish: () => void,
        error: () => void,
        progress: (event: UploadProgressEvent) => void,
        cancel: () => void,
    ): void;
}

interface UploaderMagics {
    $refs: Record<string, HTMLElement | undefined>;
    $wire: UploaderWire;
    $watch(
        expression: string,
        callback: (value: unknown, previous: unknown) => void,
    ): void;
}

interface SnapUploader {
    uploading: boolean;
    verifying: boolean;
    verificationVisible: boolean;
    uploadFailed: boolean;
    progress: number;
    init(): void;
    destroy(): void;
    renderVerification(): void;
    showVerification(): void;
    verified(): Promise<void>;
    pickPhoto(): void;
    sendPhoto(input: HTMLInputElement): void;
    sendFile(picked: File | undefined): Promise<void>;
}

export function snapUploader(
    siteKey: string | null,
): SnapUploader & ThisType<SnapUploader & UploaderMagics> {
    let widget: string | null | undefined;
    let scriptPoll: number | undefined;
    let slowTimer: number | undefined;
    let active = true;
    let revealed = false;

    return {
        uploading: false,
        verifying: false,
        verificationVisible: false,
        uploadFailed: false,
        progress: 0,

        init() {
            if (!siteKey) {
                return;
            }

            const startedAt = Date.now();

            scriptPoll = window.setInterval(() => {
                const pageLoaded =
                    document.readyState === 'complete' ||
                    Date.now() - startedAt > PAGE_LOAD_WAIT_MS;

                if (!window.turnstile || !pageLoaded) {
                    return;
                }
                window.clearInterval(scriptPoll);
                this.renderVerification();
            }, POLL_MS);

            this.$watch('$wire.turnstileToken', (token, previous) => {
                if (previous && !token && widget) {
                    window.turnstile?.reset(widget);
                }
            });
        },

        destroy() {
            active = false;
            window.clearInterval(scriptPoll);
            window.clearTimeout(slowTimer);
            if (widget) {
                window.turnstile?.remove(widget);
                widget = undefined;
            }
        },

        renderVerification() {
            const container = this.$refs.verification;
            if (!active || !container || !window.turnstile) {
                return;
            }
            if (widget) {
                window.turnstile.remove(widget);
            }
            widget = window.turnstile.render(container, {
                sitekey: siteKey,
                theme: 'dark',
                appearance: revealed ? 'always' : 'interaction-only',
                callback: (token: string) => {
                    this.$wire.turnstileToken = token;
                },
                'before-interactive-callback': () => {
                    this.verificationVisible = true;
                },
                'after-interactive-callback': () => {
                    this.verificationVisible = revealed;
                },
                'error-callback': () => {
                    window.setTimeout(() => this.showVerification());

                    return true;
                },
            });

            window.clearTimeout(slowTimer);
            if (!revealed) {
                slowTimer = window.setTimeout(() => {
                    if (
                        !this.$wire.turnstileToken &&
                        !this.verificationVisible
                    ) {
                        this.showVerification();
                    }
                }, SLOW_VERIFICATION_MS);
            }
        },

        showVerification() {
            if (revealed) {
                return;
            }
            revealed = true;
            this.verificationVisible = true;
            this.renderVerification();
        },

        verified() {
            return new Promise((resolve) => {
                const check = window.setInterval(() => {
                    if (active && !this.$wire.turnstileToken) {
                        return;
                    }
                    window.clearInterval(check);
                    if (active) {
                        resolve();
                    }
                }, POLL_MS);
            });
        },

        pickPhoto() {
            this.$refs.photoUpload?.click();
        },

        sendPhoto(input) {
            const picked = input.files?.[0];
            input.value = '';
            void this.sendFile(picked);
        },

        async sendFile(picked) {
            if (!picked || this.uploading || this.verifying) {
                return;
            }
            this.uploadFailed = false;
            this.progress = 0;
            window.acaraTrack?.('snap_to_track_upload_selected', {
                source: 'public_snap_to_track',
            });
            keepSnapPhoto(picked);
            const prepared = prepareSnapPhoto(picked);

            if (siteKey && !this.$wire.turnstileToken) {
                this.verifying = true;
                await this.verified();
                this.verifying = false;
            }

            this.uploading = true;
            this.$wire.$upload(
                'photo',
                await prepared,
                () => {
                    this.uploading = false;
                    this.progress = 0;
                },
                () => {
                    this.uploading = false;
                    this.uploadFailed = true;
                    this.progress = 0;
                    this.$wire.turnstileToken = null;
                },
                (event) => {
                    this.progress = event.detail.progress;
                },
                () => {
                    this.uploading = false;
                    this.progress = 0;
                },
            );
        },
    };
}
