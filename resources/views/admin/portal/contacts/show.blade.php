<x-app-layout>
    @push('styles')
        @vite(['resources/css/admin/common.css'])
    @endpush

    <div x-data="adminPage()" class="flex min-h-screen admin-gradient-bg relative overflow-hidden">
        
        <div class="absolute inset-0 pointer-events-none z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl admin-floating-decoration"></div>
        </div>

        <x-layouts.sidebar />

        <div class="flex-1 flex flex-col overflow-hidden relative z-10">
            <header class="admin-header shrink-0 shadow-sm">
                <div class="px-4 lg:px-6 h-14 lg:h-16 flex items-center gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-base lg:text-lg font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                お問い合わせ詳細
                            </h1>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-auto px-4 lg:px-6 py-4 lg:py-6">
                
                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 rounded-lg text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- お問い合わせ内容 --}}
                    <div class="lg:col-span-2 admin-card p-6">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">受信日時</label>
                                <p class="text-gray-900 dark:text-gray-100"><x-user-local-time :datetime="$contact->created_at" format="Y-m-d H:i:s" /></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">氏名</label>
                                <p class="text-gray-900 dark:text-gray-100">
                                    {{ $contact->name }}
                                    @if ($contact->user)
                                        <span class="ml-2 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">会員ID: {{ $contact->user_id }}</span>
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メールアドレス</label>
                                <p class="text-gray-900 dark:text-gray-100">{{ $contact->email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">対象アプリ</label>
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm rounded">{{ $contact->app_name }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">件名</label>
                                <p class="text-gray-900 dark:text-gray-100">{{ $contact->subject }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">本文</label>
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ $contact->message }}</div>
                            </div>

                            @if($contact->admin_note)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">管理者メモ</label>
                                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ $contact->admin_note }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ステータス更新 --}}
                    <div class="admin-card p-6">
                        <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">ステータス管理</h2>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">現在のステータス</label>
                            @if ($contact->status === 'pending')
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded">未対応</span>
                            @elseif ($contact->status === 'in_progress')
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded">対応中</span>
                            @else
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded">解決済み</span>
                            @endif
                        </div>

                        <form action="{{ route('admin.portal.contacts.update-status', $contact) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ステータス変更</label>
                                <select name="status" id="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                    <option value="pending" {{ $contact->status === 'pending' ? 'selected' : '' }}>未対応</option>
                                    <option value="in_progress" {{ $contact->status === 'in_progress' ? 'selected' : '' }}>対応中</option>
                                    <option value="resolved" {{ $contact->status === 'resolved' ? 'selected' : '' }}>解決済み</option>
                                </select>
                            </div>

                            <div>
                                <label for="admin_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">管理者メモ</label>
                                <textarea name="admin_note" id="admin_note" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">{{ old('admin_note', $contact->admin_note) }}</textarea>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-lg transition">
                                更新
                            </button>
                        </form>

                        <div class="mt-4">
                            <a href="{{ route('admin.portal.contacts.index') }}" class="block text-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                                一覧に戻る
                            </a>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</x-app-layout>
