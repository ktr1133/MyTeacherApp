<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('FAQ編集') }}
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
            <form method="POST" action="{{ route('admin.portal.faqs.update', $faq) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- アプリ名 -->
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        アプリ名 <span class="text-red-500">*</span>
                    </label>
                    <select name="app_name" id="app_name" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">選択してください</option>
                        <option value="MyTeacher" {{ old('app_name', $faq->app_name) === 'MyTeacher' ? 'selected' : '' }}>MyTeacher</option>
                        <option value="KeepItSimple" {{ old('app_name', $faq->app_name) === 'KeepItSimple' ? 'selected' : '' }}>KeepItSimple</option>
                    </select>
                </div>

                <!-- カテゴリ -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        カテゴリ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="category" id="category" value="{{ old('category', $faq->category) }}" required maxlength="50" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">例: 基本操作、機能について、トラブルシューティング など</p>
                </div>

                <!-- 質問 -->
                <div>
                    <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        質問 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="question" id="question" value="{{ old('question', $faq->question) }}" required maxlength="255" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- 回答 -->
                <div>
                    <label for="answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        回答 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer" id="answer" rows="8" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('answer', $faq->answer) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">HTMLタグが使用できます。</p>
                </div>

                <!-- 表示順 -->
                <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        表示順 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $faq->display_order) }}" required min="0" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">数値が小さいほど上位に表示されます。</p>
                </div>

                <!-- 公開状態 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        公開状態 <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="radio" name="is_published" value="1" {{ old('is_published', $faq->is_published) == '1' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">公開</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_published" value="0" {{ old('is_published', $faq->is_published) == '0' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">非公開</span>
                        </label>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex items-center space-x-4">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition">
                        更新
                    </button>
                    <a href="{{ route('admin.portal.faqs.index') }}" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md transition">
                        キャンセル
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
