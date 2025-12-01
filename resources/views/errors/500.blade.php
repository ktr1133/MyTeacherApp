@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <h2 class="mt-6 text-center text-6xl font-extrabold text-gray-900">
                500
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                サーバーエラーが発生しました
            </p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700">
                申し訳ございません。サーバーでエラーが発生しました。<br>
                しばらく時間をおいてから再度お試しください。
            </p>
        </div>
        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                ダッシュボードに戻る
            </a>
        </div>
        @if(config('app.debug'))
        <div class="mt-8 text-left">
            <details class="bg-red-50 border border-red-200 rounded-md p-4">
                <summary class="cursor-pointer font-semibold text-red-800">デバッグ情報（開発環境のみ表示）</summary>
                <div class="mt-4 text-xs text-red-700">
                    @if(isset($exception))
                        <p class="font-bold">エラーメッセージ:</p>
                        <p class="mb-4">{{ $exception->getMessage() }}</p>
                        <p class="font-bold">ファイル:</p>
                        <p class="mb-4">{{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
                        <p class="font-bold">スタックトレース:</p>
                        <pre class="whitespace-pre-wrap">{{ $exception->getTraceAsString() }}</pre>
                    @endif
                </div>
            </details>
        </div>
        @endif
    </div>
</div>
@endsection
