@extends('layouts.admin')

@section('title', 'Create Tag - AniYume Admin')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Create New Tag</h1>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.tags.store') }}" method="POST" class="bg-white rounded-lg shadow p-6 max-w-2xl">
    @csrf

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Tag Name *</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
               placeholder="e.g., Action, Comedy, Sci-Fi">
        <p class="mt-2 text-sm text-gray-500">The slug will be generated automatically</p>
    </div>

    <div class="flex justify-end space-x-4">
        <a href="{{ route('admin.tags.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Cancel
        </a>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            Create Tag
        </button>
    </div>
</form>
@endsection
