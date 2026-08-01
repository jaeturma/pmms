import { useEffect, useId, useRef } from 'react';

declare global {
    interface Window {
        grecaptcha?: {
            render: (
                container: HTMLElement,
                params: { sitekey: string },
            ) => number;
        };
        onRecaptchaLoad?: () => void;
    }
}

let scriptPromise: Promise<void> | null = null;

// Explicit rendering (`render=explicit`), not the implicit `.g-recaptcha`
// auto-scan — Inertia's client-side navigation can mount this component
// again after the script has already loaded once, and the auto-scan only
// ever runs on the script's own load event.
function loadRecaptchaScript(): Promise<void> {
    if (scriptPromise) {
        return scriptPromise;
    }

    scriptPromise = new Promise((resolve) => {
        window.onRecaptchaLoad = () => resolve();

        const script = document.createElement('script');
        script.src =
            'https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    });

    return scriptPromise;
}

type Props = {
    siteKey: string;
};

/**
 * Renders Google's reCAPTCHA v2 checkbox. The widget injects its own
 * `<textarea name="g-recaptcha-response">` into the container div, which
 * rides along automatically in the surrounding `<Form>`'s submission —
 * no manual wiring needed on the caller's side.
 */
export default function RecaptchaWidget({ siteKey }: Props) {
    const containerRef = useRef<HTMLDivElement>(null);
    const id = useId();

    useEffect(() => {
        let cancelled = false;

        void loadRecaptchaScript().then(() => {
            if (cancelled || !containerRef.current || !window.grecaptcha) {
                return;
            }

            if (containerRef.current.childElementCount === 0) {
                window.grecaptcha.render(containerRef.current, {
                    sitekey: siteKey,
                });
            }
        });

        return () => {
            cancelled = true;
        };
    }, [siteKey]);

    return <div ref={containerRef} id={id} />;
}
