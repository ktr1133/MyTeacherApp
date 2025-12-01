<x-app-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 text-center">
                <div class="mb-6">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    グループが見つかりません
                </h1>
                
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ $message ?? 'この機能を利用するには、グループに所属している必要があります。' }}
                </p>
                
                <div class="space-y-3">
                    <a href="{{ route('dashboard') }}" class="block w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        ダッシュボードに戻る
                    </a>
                    
                    @if(!auth()->user()->group)
                        <a href="{{ route('profile.edit') }}" class="block w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 font-medium rounded-lg transition">
                            グループを作成する
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
