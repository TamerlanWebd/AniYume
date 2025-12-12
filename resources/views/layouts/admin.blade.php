<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AniYume Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold">AniYume Admin</span>
                    <div class="ml-10 flex space-x-4">
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded hover:bg-indigo-700">Dashboard</a>
                        <a href="{{ route('admin.anime.index') }}" class="px-3 py-2 rounded hover:bg-indigo-700">Anime</a>
                        <a href="{{ route('admin.tags.index') }}" class="px-3 py-2 rounded hover:bg-indigo-700">Tags</a>
                        <a href="{{ route('admin.import.index') }}" class="px-3 py-2 rounded hover:bg-indigo-700">Import</a>
                        <a href="{{ route('admin.audit-logs') }}" class="px-3 py-2 rounded hover:bg-indigo-700">Audit Logs</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="mr-4">{{ auth()->user()->name }}</span>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-500 rounded hover:bg-red-600">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
