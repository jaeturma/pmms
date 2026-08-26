@php($errorStatus = $displayStatus ?? 500)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Temporarily Under Maintenance | {{ config('app.name', 'PMMS') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #172033; background: radial-gradient(circle at top, #eff6ff 0, #f8fafc 45%, #eef2f7 100%); }
        main { width: min(100%, 660px); padding: clamp(28px, 6vw, 52px); text-align: center; background: rgba(255, 255, 255, .96); border: 1px solid #dbe4f0; border-radius: 24px; box-shadow: 0 24px 70px rgba(15, 23, 42, .12); }
        .logo { width: 92px; height: 92px; margin: 0 auto 22px; padding: 14px; object-fit: contain; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 24px; }
        .eyebrow { margin: 0 0 10px; color: #1d4ed8; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; color: #0f172a; font-size: clamp(28px, 5vw, 42px); line-height: 1.12; letter-spacing: -.025em; }
        .lead { max-width: 540px; margin: 18px auto 0; color: #475569; font-size: 17px; line-height: 1.75; }
        .note { max-width: 520px; margin: 16px auto 0; color: #64748b; font-size: 14px; line-height: 1.65; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 28px; }
        a, button { min-height: 44px; padding: 11px 20px; border-radius: 10px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        button { color: #fff; background: #1d4ed8; border: 1px solid #1d4ed8; }
        a { color: #1e3a8a; background: #fff; border: 1px solid #cbd5e1; }
        button:hover { background: #1e40af; }
        a:hover { background: #f8fafc; }
        footer { margin-top: 30px; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <main role="main" aria-labelledby="maintenance-title">
        <img class="logo" src="/favicon.svg" alt="{{ config('app.name', 'PMMS') }} system logo">
        <p class="eyebrow">Temporary maintenance</p>
        <h1 id="maintenance-title">We’ll be back shortly.</h1>
        <p class="lead">
            The {{ config('app.name', 'PMMS') }} site is temporarily unavailable while our team performs maintenance and restores normal service.
            We apologize for the inconvenience and appreciate your patience.
        </p>
        <p class="note">
            Your information remains safe. Please wait a few moments and try again. If the interruption continues, contact your ICT administrator and include the page you were trying to open.
        </p>
        <div class="actions">
            <button type="button" onclick="window.location.reload()">Try again</button>
            <a href="/">Return to home page</a>
        </div>
        <footer>Error {{ $errorStatus }} &middot; Service temporarily unavailable</footer>
    </main>
</body>
</html>
