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

    return (
        <div
            role="group"
            aria-label={t('snap_to_track.index.upload_label')}
            className="flex flex-col gap-4"
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
                <figure className="flex flex-col gap-2 overflow-hidden rounded-2xl border bg-muted/40 p-2">
                    {!previewFailed && (
                        <img
                            src={previewUrl}
                            alt={t('snap_to_track.index.preview_alt')}
                            className="max-h-80 w-full rounded-xl object-contain"
                            onError={() => setPreviewFailed(true)}
                        />
                    )}
                    <figcaption className="px-2 py-1 text-sm text-muted-foreground">
                        {previewFailed
                            ? t('snap_to_track.index.preview_unavailable')
                            : t('snap_to_track.index.preview_hint')}
                    </figcaption>
                </figure>
            ) : (
                <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-muted/40 px-6 py-8 text-center">
                    <Camera
                        className="size-8 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p className="text-sm text-muted-foreground">
                        {t('snap_to_track.camera.hint')}
                    </p>
                </div>
            )}

            <div className="flex flex-col gap-3 sm:flex-row">
                <Button
                    ref={takePhotoRef}
                    type="button"
                    size="touch"
                    className="w-full sm:flex-1"
                    disabled={disabled}
                    onClick={() => setCameraOpen(true)}
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
                    className="w-full sm:flex-1"
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
                onCloseAutoFocus={() => takePhotoRef.current?.focus()}
                onCapture={selectPhoto}
                onChoosePhoto={choosePhoto}
            />
        </div>
    );
}
