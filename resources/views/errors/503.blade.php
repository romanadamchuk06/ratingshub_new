<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Wartungsarbeiten</title>
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
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        .animate-bounce {
            animation: bounce 1s infinite;
        }
        .animate-ping {
            animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-purple-50 via-white to-pink-50 p-4">
        <div class="w-full max-w-2xl text-center">
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <svg class="h-32 w-32 text-purple-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="absolute -right-2 -top-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white animate-ping opacity-75">
                        </div>
                        <div class="absolute top-0 flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white">
                            🔧
                        </div>
                    </div>
                </div>
            </div>

            <h1 class="mb-4 text-4xl font-bold text-gray-900 md:text-5xl">
                Wartungsarbeiten
            </h1>

            <p class="mb-8 text-xl text-gray-600">
                Wir machen uns gerade noch schöner! 💅
            </p>

            <div class="mb-8 rounded-xl border border-purple-200 bg-purple-50 p-6 text-left shadow-lg">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-purple-900">
                    ☕ Was passiert gerade?
                </h2>
                <div class="space-y-3 text-purple-800">
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🔧</span>
                        <span>Wir führen geplante Wartungsarbeiten durch, um dir noch besseren Service zu bieten.</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">⚡</span>
                        <span>Neue Features werden installiert und bestehende optimiert.</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🚀</span>
                        <span>Wir sind bald wieder zurück - versprochen!</span>
                    </p>
                </div>
            </div>

            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 text-left">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    💡 Während du wartest, könntest du:
                </h2>
                <ul class="space-y-2 text-gray-600">
                    <li>☕ Einen Kaffee machen (oder Tee, wir urteilen nicht)</li>
                    <li>🧘 Eine kurze Meditation einlegen</li>
                    <li>🎮 Ein schnelles Spiel zocken</li>
                    <li>📱 Deine Social Media checken</li>
                    <li>🌱 Die Pflanzen gießen (die haben es verdient!)</li>
                    <li>🎵 Dein Lieblingslied hören</li>
                </ul>
            </div>

            <div class="rounded-xl border-2 border-dashed border-purple-300 bg-purple-50/50 p-6">
                <p class="text-gray-700">
                    <strong>Keine Sorge!</strong> Deine Daten sind sicher und wir sind gleich wieder für dich da.
                    Die Seite wird automatisch neu geladen, sobald wir fertig sind. ✨
                </p>
            </div>

            <p class="mt-12 text-sm text-gray-500">
                Status: Server beim Friseur 💈 | Dauert nicht mehr lange!
            </p>
        </div>
    </div>

    <script>
        // Auto-reload after 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
