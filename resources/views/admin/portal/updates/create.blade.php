<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('アプリ更新履歴登録') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-card p-6">
            <form method="POST" action="{{ route('admin.portal.updates.store') }}" class="space-y-6">
                @csrf

                <!-- アプリ名 -->
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        アプリ名 <span class="text-red-500">*</span>
                    </label>
                    <select name="app_name" id="app_name" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">選択してください</option>
                        <option value="MyTeacher" {{ old('app_name') === 'MyTeacher' ? 'selected' : '' }}>MyTeacher</option>
                        <option value="KeepItSimple" {{ old('app_name') === 'KeepItSimple' ? 'selected' : '' }}>KeepItSimple</option>
                    </select>
                </div>

                <!-- バージョン -->
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        バージョン <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="version" id="version" value="{{ old('version') }}" required maxlength="20" placeholder="例: 1.2.3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- タイトル -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        タイトル <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="255" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 説明 -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        説明 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" id="description" rows="8" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">HTMLタグが使用できます。</p>
                </div>

                <!-- リリース日 -->
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        リリース日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="release_date" id="release_date" value="{{ old('release_date', date('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- メジャーアップデート -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        メジャーアップデート <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="radio" name="is_major" value="1" {{ old('is_major', '0') === '1' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">はい</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_major" value="0" {{ old('is_major', '0') === '0' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">いいえ</span>
                        </label>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex items-center space-x-4">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition">
                        登録
                    </button>
                    <a href="{{ route('admin.portal.updates.index') }}" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md transition">
                        キャンセル
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
