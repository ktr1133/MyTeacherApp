<x-app-layout>
    @push('styles')
        @vite(['resources/css/admin/common.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/admin/common.js'])
    @endpush

    <div x-data="adminPage()" 
         x-effect="document.body.style.overflow = showSidebar ? 'hidden' : ''"
         class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl admin-floating-decoration"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl admin-floating-decoration" style="animation-delay: -10s;"></div>
        </div>

        <x-layouts.sidebar />

        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
            
            <header class="admin-header shrink-0 shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0 transition"
                            data-sidebar-toggle="mobile">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                                    メンテナンス情報作成
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-auto px-4 lg:px-6 py-4 lg:py-6">
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="admin-card p-6 max-w-4xl">
                    <form action="{{ route('admin.portal.maintenances.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">タイトル <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-orange-500 focus:border-orange-500">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">詳細説明 <span class="text-red-500">*</span></label>
                            <textarea name="description" id="description" rows="5" required
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-orange-500 focus:border-orange-500">{{ old('description') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メンテナンス予定日時 <span class="text-red-500">*</span></label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <div>
                                <label for="estimated_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">予定時間（分） <span class="text-red-500">*</span></label>
                                <input type="number" name="estimated_duration" id="estimated_duration" value="{{ old('estimated_duration', 60) }}" min="1" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">対象アプリ <span class="text-red-500">*</span></label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="affected_apps[]" value="myteacher" {{ in_array('myteacher', old('affected_apps', [])) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-700 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">MyTeacher</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="affected_apps[]" value="app2" {{ in_array('app2', old('affected_apps', [])) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-700 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">App2</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="affected_apps[]" value="app3" {{ in_array('app3', old('affected_apps', [])) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-700 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">App3</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ステータス</label>
                            <select name="status" id="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-orange-500 focus:border-orange-500">
                                <option value="scheduled" {{ old('status', 'scheduled') === 'scheduled' ? 'selected' : '' }}>予定</option>
                                <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>実施中</option>
                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>完了</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3 pt-4">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white rounded-lg transition font-medium">
                                作成
                            </button>
                            <a href="{{ route('admin.portal.maintenances.index') }}" class="px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                                キャンセル
                            </a>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>
</x-app-layout>
