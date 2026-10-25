import InputError from '@/components/input-error';
import { CameraCaptureDialog } from '@/components/snap-to-track/camera-capture-dialog';
import { Button } from '@/components/ui/button';
import { Camera, ImagePlus, RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface MealPhotoPickerProps {
    photo: File | null;
    onChange: (photo: File) => void;
    disabled: boolean;
    error?: string;
}

export function MealPhotoPicker({
    photo,
    onChange,
    disabled,
    error,
}: MealPhotoPickerProps) {
    const { t } = useTranslation('common');
    const inputRef = useRef<HTMLInputElement>(null);
    const [cameraOpen, setCameraOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [previewFailed, setPreviewFailed] = useState(false);
    const takePhotoRef = useRef<HTMLButtonElement>(null);
    const cameraTriggerRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!photo) {
            setPreviewUrl(null);
            return;
        }
        const url = URL.createObjectURL(photo);
        setPreviewUrl(url);
        setPreviewFailed(false);
        return () => URL.revokeObjectURL(url);
    }, [photo]);

    function selectPhoto(file: File): void {
        onChange(file);
        setCameraOpen(false);
    }

    function choosePhoto(): void {
        inputRef.current?.click();
    }

    function openCamera(trigger: HTMLButtonElement): void {
        cameraTriggerRef.current = trigger;
        setCameraOpen(true);
    }

    function restoreCameraFocus(): void {
        const trigger = cameraTriggerRef.current;
        (trigger?.isConnected ? trigger : takePhotoRef.current)?.focus();
    }

    return (
        <div
            role="group"
            aria-label={t('snap_to_track.index.upload_label')}
            className="flex flex-col gap-5"
        >
            <input
                ref={inputRef}
                id="snap-photo"
                type="file"
                accept="image/*"
                aria-label={t('snap_to_track.index.upload_label')}
                aria-invalid={!!error}
                aria-describedby={error ? 'snap-photo-error' : undefined}
                disabled={disabled}
                className="sr-only"
                tabIndex={-1}
                onChange={(event) => {
                    const file = event.currentTarget.files?.[0];
                    if (file) {
                        selectPhoto(file);
                    }
                    event.currentTarget.value = '';
                }}
            />

            {photo && previewUrl ? (
                <figure className="flex flex-col gap-3">
                    {!previewFailed && (
                        <img
                            src={previewUrl}
                            alt={t('snap_to_track.index.preview_alt')}
                            className="aspect-4/3 max-h-96 w-full rounded-3xl bg-muted/50 object-contain"
                            onError={() => setPreviewFailed(true)}
                        />
                    )}
                    <figcaption className="text-center text-sm text-muted-foreground">
                        {previewFailed
                            ? t('snap_to_track.index.preview_unavailable')
                            : t('snap_to_track.index.preview_hint')}
                    </figcaption>
                </figure>
            ) : (
                <button
                    type="button"
                    aria-label={t('snap_to_track.camera.title')}
                    aria-describedby="snap-camera-hint"
                    aria-haspopup="dialog"
                    disabled={disabled}
                    onClick={(event) => openCamera(event.currentTarget)}
                    className="relative flex min-h-64 cursor-pointer touch-manipulation flex-col items-center justify-center gap-4 rounded-3xl bg-muted/50 px-8 py-12 text-center transition-colors outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 sm:min-h-80"
                >
                    <span
                        className="pointer-events-none absolute inset-5 text-primary/30"
                        aria-hidden="true"
                    >
                        <span className="absolute top-0 left-0 size-6 rounded-tl-xl border-t-2 border-l-2" />
                        <span className="absolute top-0 right-0 size-6 rounded-tr-xl border-t-2 border-r-2" />
                        <span className="absolute bottom-0 left-0 size-6 rounded-bl-xl border-b-2 border-l-2" />
                        <span className="absolute right-0 bottom-0 size-6 rounded-br-xl border-r-2 border-b-2" />
                    </span>
                    <Camera
                        className="size-10 text-primary/70"
                        aria-hidden="true"
                    />
                    <span
                        id="snap-camera-hint"
                        className="text-sm text-muted-foreground"
                    >
                        {t('snap_to_track.camera.hint')}
                    </span>
                </button>
            )}

            <div className="flex flex-col gap-3 sm:flex-row">
                <Button
                    ref={takePhotoRef}
                    type="button"
                    variant="secondary"
                    size="touch"
                    className="w-full shadow-none sm:flex-1"
                    disabled={disabled}
                    onClick={(event) => openCamera(event.currentTarget)}
                >
                    {photo ? (
                        <RotateCcw data-icon="inline-start" />
                    ) : (
                        <Camera data-icon="inline-start" />
                    )}
                    {t(
                        `snap_to_track.index.${photo ? 'retake' : 'take_photo'}`,
                    )}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="touch"
                    className="w-full shadow-none sm:flex-1"
                    disabled={disabled}
                    onClick={choosePhoto}
                >
                    <ImagePlus data-icon="inline-start" />
                    {t(
                        `snap_to_track.index.${photo ? 'replace' : 'choose_photo'}`,
                    )}
                </Button>
            </div>
            <InputError id="snap-photo-error" message={error} />

            <CameraCaptureDialog
                open={cameraOpen && !disabled}
                onOpenChange={setCameraOpen}
                onCloseAutoFocus={restoreCameraFocus}
                onCapture={selectPhoto}
                onChoosePhoto={choosePhoto}
            />
        </div>
    );
}
