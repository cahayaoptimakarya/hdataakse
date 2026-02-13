<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Aplikasi') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />
        <style>
            :root {
                color-scheme: dark;
            }
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                font-family: "Space Grotesk", sans-serif;
                background:
                    radial-gradient(60rem 60rem at 20% 20%, rgba(59,130,246,0.25), transparent 60%),
                    radial-gradient(50rem 50rem at 80% 10%, rgba(236,72,153,0.2), transparent 55%),
                    radial-gradient(40rem 40rem at 50% 120%, rgba(34,197,94,0.18), transparent 55%),
                    #05070f;
                color: #f8fafc;
                overflow: hidden;
            }
            .bg {
                position: fixed;
                inset: 0;
                pointer-events: none;
                z-index: 0;
            }
            .blob {
                position: absolute;
                width: 320px;
                height: 320px;
                border-radius: 40% 60% 55% 45%;
                filter: blur(50px);
                opacity: 0.6;
                mix-blend-mode: screen;
                animation: drift 16s ease-in-out infinite;
            }
            .blob.blob-1 {
                top: -80px;
                left: -60px;
                background: radial-gradient(circle, rgba(14,165,233,0.9), rgba(14,165,233,0));
            }
            .blob.blob-2 {
                bottom: -100px;
                right: -40px;
                background: radial-gradient(circle, rgba(168,85,247,0.9), rgba(168,85,247,0));
                animation-delay: -6s;
            }
            .scene {
                position: relative;
                width: min(420px, 80vw);
                height: min(420px, 80vw);
                display: grid;
                place-items: center;
                z-index: 1;
            }
            .orbit {
                position: absolute;
                border-radius: 50%;
                border: 1px solid rgba(255,255,255,0.12);
                animation: spin 18s linear infinite;
            }
            .orbit::before {
                content: "";
                position: absolute;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #7dd3fc;
                box-shadow: 0 0 18px rgba(125,211,252,0.8);
                top: -5px;
                left: 50%;
                transform: translateX(-50%);
            }
            .orbit.orbit-a {
                inset: 0;
            }
            .orbit.orbit-b {
                inset: 12%;
                animation-duration: 14s;
                animation-direction: reverse;
            }
            .orbit.orbit-c {
                inset: 26%;
                animation-duration: 10s;
            }
            .pulse {
                position: absolute;
                width: 170px;
                height: 170px;
                border-radius: 50%;
                border: 1px solid rgba(255,255,255,0.15);
                box-shadow: 0 0 50px rgba(56,189,248,0.2);
                animation: pulse 3.2s ease-in-out infinite;
            }
            .login-btn {
                position: relative;
                z-index: 2;
                padding: 16px 36px;
                border-radius: 999px;
                text-decoration: none;
                color: #020617;
                font-weight: 600;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                background: linear-gradient(135deg, #e2e8f0, #f8fafc);
                box-shadow: 0 18px 40px rgba(15,23,42,0.6);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .login-btn::before {
                content: "";
                position: absolute;
                inset: -8px;
                border-radius: inherit;
                background: radial-gradient(circle, rgba(56,189,248,0.45), transparent 65%);
                z-index: -1;
                opacity: 0.7;
                animation: glow 2.8s ease-in-out infinite;
            }
            .login-btn:hover {
                transform: translateY(-2px) scale(1.02);
                box-shadow: 0 22px 50px rgba(15,23,42,0.7);
            }
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
            @keyframes pulse {
                0%, 100% {
                    transform: scale(0.95);
                    opacity: 0.7;
                }
                50% {
                    transform: scale(1.08);
                    opacity: 1;
                }
            }
            @keyframes glow {
                0%, 100% {
                    opacity: 0.5;
                }
                50% {
                    opacity: 0.9;
                }
            }
            @keyframes drift {
                0%, 100% {
                    transform: translate(0, 0) scale(1);
                }
                50% {
                    transform: translate(30px, -20px) scale(1.08);
                }
            }
            @media (prefers-reduced-motion: reduce) {
                .orbit,
                .pulse,
                .login-btn::before,
                .blob {
                    animation: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="bg" aria-hidden="true">
            <span class="blob blob-1"></span>
            <span class="blob blob-2"></span>
        </div>
        <div class="scene">
            <div class="orbit orbit-a"></div>
            <div class="orbit orbit-b"></div>
            <div class="orbit orbit-c"></div>
            <div class="pulse"></div>
            <a class="login-btn" href="{{ route('login') }}" aria-label="Login">Login</a>
        </div>
    </body>
</html>
