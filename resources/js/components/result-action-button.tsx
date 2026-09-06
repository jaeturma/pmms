import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/** Click acknowledgement for local actions; request feedback lasts until completion. */
export function ResultActionButton({
    loading = false,
    disabled,
    className,
    onClickCapture,
    ...props
}: ComponentProps<typeof Button> & { loading?: boolean }) {
    const [clicked, setClicked] = useState(false);
    const active = useRef(false);
    const cleanup = useRef<() => void>(() => {});

    useEffect(() => () => cleanup.current(), []);

    const busy = clicked || loading;

    return (
        <Button
            {...props}
            // Keep the initial click enabled through bubbling and native form submission.
            // The capture guard below blocks subsequent clicks while feedback is active.
            disabled={disabled || loading}
            aria-busy={busy}
            aria-disabled={disabled || loading}
            className={cn(
                busy &&
                    "after:size-4 after:shrink-0 after:animate-spin after:rounded-full after:border-2 after:border-current after:border-r-transparent after:content-['']",
                className,
            )}
            onClickCapture={(event) => {
                if (disabled || loading || active.current) {
                    event.preventDefault();
                    event.stopPropagation();

                    return;
                }

                onClickCapture?.(event);

                if (event.defaultPrevented) {
                    return;
                }

                cleanup.current();
                active.current = true;
                setClicked(true);
                let requesting = false;
                const stopStart = router.on('start', () => {
                    requesting = true;
                });
                const finish = () => {
                    cleanup.current();
                    active.current = false;
                    setClicked(false);
                };
                const stopFinish = router.on('finish', finish);
                const timer = window.setTimeout(() => {
                    if (!requesting) {
                        finish();
                    }
                }, 450);
                cleanup.current = () => {
                    window.clearTimeout(timer);
                    stopStart();
                    stopFinish();
                };
            }}
        />
    );
}
