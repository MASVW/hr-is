<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'Assign PIC' }}</title>

    {{-- Tailwind CDN + custom primary palette --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'media',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',
                            500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554'
                        },
                    },
                    boxShadow: {
                        card: '0 8px 30px rgb(0 0 0 / 0.06)',
                    }
                }
            }
        }
    </script>

    @livewireStyles
</head>
<body class="min-h-screen antialiased text-slate-900 dark:text-white">
<div class="min-h-screen grid place-items-center px-4
              bg-gradient-to-br from-slate-50 via-white to-slate-100
              dark:from-slate-950 dark:via-slate-900 dark:to-slate-800">
    {{ $slot }}
</div>

@livewireScripts
</body>
</html>
