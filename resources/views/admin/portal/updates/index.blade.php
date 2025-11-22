<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('アプリ更新履歴管理') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- フィルター -->
        <div class="mb-6 admin-card p-4">
            <form method="GET" action="{{ route('admin.portal.updates.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">アプリ名</label>
                    <select name="app_name" id="app_name" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">全て</option>
                        <option value="MyTeacher" {{ request('app_name') === 'MyTeacher' ? 'selected' : '' }}>MyTeacher</option>
                        <option value="KeepItSimple" {{ request('app_name') === 'KeepItSimple' ? 'selected' : '' }}>KeepItSimple</option>
                    </select>
                </div>

                <div>
                    <label for="is_major" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メジャーアップデート</label>
                    <select name="is_major" id="is_major" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">全て</option>
                        <option value="1" {{ request('is_major') === '1' ? 'selected' : '' }}>はい</option>
                        <option value="0" {{ request('is_major') === '0' ? 'selected' : '' }}>いいえ</option>
                    </select>
                </div>

                <div>
                    <label for="released_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">リリース日（開始）</label>
                    <input type="date" name="released_from" id="released_from" value="{{ request('released_from') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="released_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">リリース日（終了）</label>
                    <input type="date" name="released_to" id="released_to" value="{{ request('released_to') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- 新規登録ボタン -->
        <div class="mb-4">
            <a href="{{ route('admin.portal.updates.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                新規登録
            </a>
        </div>

        <!-- 更新履歴テーブル -->
        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">アプリ</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">バージョン</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">タイトル</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">メジャー</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">リリース日</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($updates as $update)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $update->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <span class="px-2 py-1 text-xs rounded {{ $update->app_name === 'MyTeacher' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                        {{ $update->app_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $update->version }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ Str::limit($update->title, 40) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if ($update->is_major)
                                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 font-bold">
                                            Major
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Minor
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ \Carbon\Carbon::parse($update->release_date)->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="{{ route('admin.portal.updates.edit', $update) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">編集</a>

                                    <form method="POST" action="{{ route('admin.portal.updates.destroy', $update) }}" class="inline" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('本当に削除しますか?', () => { event.target.submit(); }); } else { if (confirm('本当に削除しますか?')) { event.target.submit(); } }">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">削除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    更新履歴が登録されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            @if ($updates->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $updates->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
