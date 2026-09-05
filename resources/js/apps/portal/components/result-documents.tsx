import { FileText } from 'lucide-react';
import { useState } from 'react';
import type { PortalResultDocument } from '@/apps/portal/types';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export function PortalResultDocuments({
    documents,
}: {
    documents: PortalResultDocument[];
}) {
    const [selected, setSelected] = useState<PortalResultDocument | null>(null);

    return (
        <>
            <div className="mt-4 flex flex-wrap gap-2">
                {documents.map((document) => (
                    <button
                        key={document.id}
                        type="button"
                        onClick={() => setSelected(document)}
                        className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:underline"
                    >
                        <FileText className="size-4" />
                        {document.name}
                    </button>
                ))}
            </div>
            <Dialog
                open={selected !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelected(null);
                    }
                }}
            >
                <DialogContent className="max-h-[95dvh] overflow-y-auto sm:max-w-5xl">
                    <DialogHeader>
                        <DialogTitle className="pr-6 break-all">
                            {selected?.name}
                        </DialogTitle>
                    </DialogHeader>
                    {selected && (
                        <>
                            {selected.mime_type.startsWith('image/') ? (
                                <img
                                    src={selected.url}
                                    alt={selected.name}
                                    className="max-h-[75dvh] w-full object-contain"
                                />
                            ) : (
                                <iframe
                                    title={selected.name}
                                    src={selected.url}
                                    className="h-[75dvh] w-full rounded border"
                                />
                            )}
                            <a
                                href={selected.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm underline"
                            >
                                Open document in a new tab
                            </a>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
