@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <h2 class="mt-6 text-center text-6xl font-extrabold text-gray-900 dark:text-white">
                403
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                アクセスが拒否されました
            </p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">
                このページへのアクセス権限がありません。<br>
                @if(isset($exception) && $exception->getMessage())
                    {{ $exception->getMessage() }}
                @endif
            </p>
        </div>
        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                ダッシュボードに戻る
            </a>
        </div>
    </div>
</div>
@endsection
