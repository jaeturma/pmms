import { RotateCcw, RotateCw, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function AthletePhotoInput({
    id,
    label,
    guidance,
    onChange,
    accept = 'image/jpeg,image/png,image/webp',
    document = false,
}: {
    id: string;
    label: string;
    guidance: string;
    onChange: (file: File | null) => void;
    accept?: string;
    document?: boolean;
}) {
    const [source, setSource] = useState<string | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [zoom, setZoom] = useState(1);
    const [offsetX, setOffsetX] = useState(0);
    const [offsetY, setOffsetY] = useState(0);
    const [rotation, setRotation] = useState(0);
    const [fileMessage, setFileMessage] = useState<string | null>(null);
    const imageRef = useRef<HTMLImageElement>(null);

    useEffect(
        () => () => {
            if (source) {
                URL.revokeObjectURL(source);
            }

            if (preview) {
                URL.revokeObjectURL(preview);
            }
        },
        [source, preview],
    );

    const select = (file?: File) => {
        if (!file) {
            return;
        }

        if (document && file.size > 8 * 1024 * 1024) {
            setFileMessage('This document is too large. Maximum upload size is 8 MB per file.');
            onChange(null);
            return;
        }

        setFileMessage(document ? `${(file.size / 1024 / 1024).toFixed(1)} MB selected • Will be optimized` : null);
        if (document) {
            onChange(file);
            return;
        }

        if (document && file.size > 8 * 1024 * 1024) {
            setFileMessage('This document is too large. Maximum upload size is 8 MB per file.');
            onChange(null);
            return;
        }

        setFileMessage(document ? `${(file.size / 1024 / 1024).toFixed(1)} MB selected • Will be optimized` : null);
        if (document) {
            onChange(file);
            return;
        }

        if (document && file.size > 8 * 1024 * 1024) {
            setFileMessage('This document is too large. Maximum upload size is 8 MB per file.');
            onChange(null);
            return;
        }

        setFileMessage(document ? `${(file.size / 1024 / 1024).toFixed(1)} MB selected • Will be optimized` : null);
        if (document) {
            onChange(file);
            return;
        }

        if (source) {
            URL.revokeObjectURL(source);
        }

        setSource(URL.createObjectURL(file));
        setZoom(1);
        setOffsetX(0);
        setOffsetY(0);
        setRotation(0);
    };
    const usePhoto = async () => {
        const image = imageRef.current;

        if (!image) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = 800;
        canvas.height = 1000;
        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        context.fillStyle = '#fff';
        context.fillRect(0, 0, 800, 1000);
        context.save();
        context.translate(400 + offsetX * 4, 500 + offsetY * 5);
        context.rotate((rotation * Math.PI) / 180);
        const rotated = Math.abs(rotation % 180) === 90;
        const width = rotated ? image.naturalHeight : image.naturalWidth;
        const height = rotated ? image.naturalWidth : image.naturalHeight;
        const scale = Math.max(800 / width, 1000 / height) * zoom;
        context.drawImage(
            image,
            (-image.naturalWidth * scale) / 2,
            (-image.naturalHeight * scale) / 2,
            image.naturalWidth * scale,
            image.naturalHeight * scale,
        );
        context.restore();
        let quality = 0.86;
        let blob: Blob | null = null;

        for (let attempt = 0; attempt < 8; attempt++) {
            blob = await new Promise((resolve) =>
                canvas.toBlob(resolve, 'image/jpeg', quality),
            );

            if (blob && blob.size <= 500 * 1024) {
                break;
            }

            quality -= 0.08;
        }

        if (!blob) {
            return;
        }

        const file = new File([blob], `${id}-${Date.now()}.jpg`, {
            type: 'image/jpeg',
        });

        if (preview) {
            URL.revokeObjectURL(preview);
        }

        setPreview(URL.createObjectURL(file));
        onChange(file);
        setSource(null);
    };

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            <p className="text-xs text-muted-foreground">{guidance}</p>
            <Input
                id={id}
                type="file"
                accept={accept}
                onChange={(event) => select(event.target.files?.[0])}
            />
            {document && (
                <p className="text-xs text-muted-foreground">
                    Maximum 8 MB • Automatically optimized for storage
                </p>
            )}
            {fileMessage && (
                <p className="text-xs text-muted-foreground">{fileMessage}</p>
            )}
            {document && (
                <p className="text-xs text-muted-foreground">
                    Maximum 8 MB • Automatically optimized for storage
                </p>
            )}
            {fileMessage && (
                <p className="text-xs text-muted-foreground">{fileMessage}</p>
            )}
            {document && (
                <p className="text-xs text-muted-foreground">
                    Maximum 8 MB • Automatically optimized for storage
                </p>
            )}
            {fileMessage && (
                <p className="text-xs text-muted-foreground">{fileMessage}</p>
            )}
            <div className="flex items-center gap-3">
                {preview && (
                    <img
                        src={preview}
                        alt={`${label} preview`}
                        className="h-28 w-[90px] rounded-md border object-cover"
                        loading="lazy"
                    />
                )}
                {preview && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => document.getElementById(id)?.click()}
                    >
                        <Upload className="size-4" />
                        Replace
                    </Button>
                )}
            </div>
            {source && (
                <Dialog open onOpenChange={(open) => !open && setSource(null)}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Crop {label}</DialogTitle>
                        </DialogHeader>
                        <div className="mx-auto aspect-[4/5] w-full max-w-xs overflow-hidden rounded-lg bg-black">
                            <img
                                ref={imageRef}
                                src={source}
                                alt="Crop preview"
                                className="h-full w-full object-contain"
                                style={{
                                    transform: `translate(${offsetX}px, ${offsetY}px) scale(${zoom}) rotate(${rotation}deg)`,
                                }}
                            />
                        </div>
                        <div className="grid gap-3">
                            <Label>Zoom</Label>
                            <input
                                type="range"
                                min="1"
                                max="3"
                                step="0.05"
                                value={zoom}
                                onChange={(e) =>
                                    setZoom(Number(e.target.value))
                                }
                            />
                            <Label>Horizontal position</Label>
                            <input
                                type="range"
                                min="-100"
                                max="100"
                                value={offsetX}
                                onChange={(e) =>
                                    setOffsetX(Number(e.target.value))
                                }
                            />
                            <Label>Vertical position</Label>
                            <input
                                type="range"
                                min="-100"
                                max="100"
                                value={offsetY}
                                onChange={(e) =>
                                    setOffsetY(Number(e.target.value))
                                }
                            />
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        setRotation((value) => value - 90)
                                    }
                                >
                                    <RotateCcw />
                                    Rotate
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        setRotation((value) => value + 90)
                                    }
                                >
                                    <RotateCw />
                                    Rotate
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setZoom(1);
                                        setOffsetX(0);
                                        setOffsetY(0);
                                        setRotation(0);
                                    }}
                                >
                                    Reset
                                </Button>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setSource(null)}
                            >
                                Cancel
                            </Button>
                            <Button type="button" onClick={usePhoto}>
                                Use Photo
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}
