<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Fehler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 2s linear infinite;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-red-50 via-white to-orange-50 p-4">
        <div class="w-full max-w-2xl text-center">
            <div class="relative mb-8">
                <div class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-orange-600">
                    500
                </div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <div class="text-6xl animate-spin">⚙️</div>
                </div>
            </div>

            <h1 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                Unser Server hatte einen kleinen Nervenzusammenbruch
            </h1>

            <p class="mb-8 text-lg text-gray-600">
                Keine Panik! Unsere besten Techniker (und ein sehr motivierter Praktikant)
                sind bereits dabei, das Problem zu lösen. 🔧
            </p>

            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 text-left shadow-lg">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    Was ist passiert?
                </h2>
                <div class="space-y-3 text-gray-600">
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🎲</span>
                        <span>Irgendwo in den Tiefen unseres Servers ist etwas Unvorhergesehenes passiert.</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🔬</span>
                        <span>Wir untersuchen den Fall gerade mit größter Sorgfalt (und viel Kaffee).</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">⏱️</span>
                        <span>Das Problem ist hoffentlich nur vorübergehend!</span>
                    </p>
                </div>
            </div>

            <div class="mb-8 rounded-xl border border-blue-200 bg-blue-50 p-6 text-left">
                <h2 class="mb-4 text-lg font-semibold text-blue-900">
                    💡 Was du jetzt tun kannst:
                </h2>
                <ul class="space-y-2 text-blue-800">
                    <li>• Die Seite in ein paar Minuten nochmal versuchen</li>
                    <li>• Einen Kaffee trinken und entspannen ☕</li>
                    <li>• Zur Startseite zurückkehren</li>
                    <li>• Wenn das Problem weiterhin besteht, kontaktiere uns</li>
                </ul>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
                <a
                    href="/"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-red-600 to-orange-600 px-6 py-3 text-white font-medium transition-all hover:scale-105 hover:shadow-xl"
                >
                    🏠 Zurück zur Startseite
                </a>

                <button
                    onclick="window.location.reload()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 transition-all hover:border-gray-400 hover:bg-gray-50"
                >
                    🔄 Seite neu laden
                </button>
            </div>

            <p class="mt-12 text-sm text-gray-500">
                Fehlercode: 500 | Status: Server macht gerade Yoga 🧘
            </p>
        </div>
    </div>
</body>
</html>
