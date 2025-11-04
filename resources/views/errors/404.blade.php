<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Seite nicht gefunden</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes bounce {
            0%, 100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
            }
            50% {
                transform: translateY(0);
                animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
            }
        }
        .animate-bounce {
            animation: bounce 1s infinite;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 p-4">
        <div class="w-full max-w-2xl text-center">
            <div class="relative mb-8">
                <div class="animate-bounce text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
                    404
                </div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-6xl">
                    🤔
                </div>
            </div>

            <h1 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                Ups! Diese Seite hat sich versteckt
            </h1>

            <p class="mb-8 text-lg text-gray-600">
                Sieht so aus, als hätte diese Seite ein besseres Versteck gefunden als wir dachten.
                Vielleicht macht sie gerade Urlaub? 🏖️
            </p>

            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 text-left shadow-lg">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    Was du jetzt tun kannst:
                </h2>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start gap-3">
                        <span class="text-xl">🔄</span>
                        <span>Die URL nochmal überprüfen (Tippfehler passieren den Besten!)</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-xl">🏠</span>
                        <span>Zurück zur Startseite gehen und von vorne anfangen</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-xl">🔍</span>
                        <span>Nach dem suchen, was du eigentlich finden wolltest</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-xl">☕</span>
                        <span>Oder eine Kaffeepause machen - das hilft auch manchmal</span>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
                <a
                    href="/"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-white font-medium transition-all hover:scale-105 hover:shadow-xl"
                >
                    🏠 Zurück zur Startseite
                </a>

                <button
                    onclick="history.back()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 transition-all hover:border-gray-400 hover:bg-gray-50"
                >
                    ← Eine Seite zurück
                </button>
            </div>

            <p class="mt-12 text-sm text-gray-500">
                Fehlercode: 404 | Status: Seite spielt Verstecken 🙈
            </p>
        </div>
    </div>
</body>
</html>
