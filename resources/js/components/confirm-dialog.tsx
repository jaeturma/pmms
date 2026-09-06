import type { ReactNode } from 'react';
import { ResultActionButton } from '@/components/result-action-button';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    trigger?: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
    processing?: boolean;
    actionFeedback?: boolean;
    onConfirm: () => void;
};

export function ConfirmDialog({
    trigger,
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    destructive = false,
    processing = false,
    actionFeedback = false,
    onConfirm,
}: Props) {
    const ActionButton = actionFeedback ? ResultActionButton : Button;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <ActionButton variant="secondary" disabled={processing}>
                            {cancelLabel}
                        </ActionButton>
                    </DialogClose>
                    <ActionButton
                        variant={destructive ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {confirmLabel}
                    </ActionButton>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
