<x-app-layout>
    @push('styles')
        @vite(['resources/css/dashboard.css'])
    @endpush

    @push('scripts')
        @vite(['resources/js/dashboard/dashboard.js'])
    @endpush

    <div class="flex min-h-[100dvh] dashboard-gradient-bg relative overflow-hidden">

        {{-- 背景装飾 --}}
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div class="dashboard-floating-decoration absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl"></div>
            <div class="dashboard-floating-decoration absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl" style="animation-delay: 5s;"></div>
        </div>

        {{-- サイドバー --}}
        <x-layouts.sidebar />

        {{-- メインコンテンツ --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- ヘッダー --}}
            <header class="bg-white/80 backdrop-blur-md border-b border-gray-200">
                <div class="px-4 lg:px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                🛡️ 管理者ダッシュボード
                            </h1>
                            <p class="text-sm text-gray-600 mt-1">
                                ようこそ、{{ Auth::user()->name }} さん
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-gray-600">
                                管理者権限でログイン中
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            {{-- メインコンテンツ --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar">
                <div class="max-w-[1920px] mx-auto px-4 lg:px-6 py-4 lg:py-6">
                    
                    {{-- セキュリティステータス --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                        {{-- アカウントセキュリティ --}}
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    🔒 セキュリティステータス
                                </h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">アカウントロック機能</span>
                                    <span class="text-green-600 font-medium">✅ 有効</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">ログイン試行記録</span>
                                    <span class="text-green-600 font-medium">✅ 有効</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">二要素認証</span>
                                    <span class="{{ Auth::user()->two_factor_enabled ? 'text-green-600' : 'text-yellow-600' }} font-medium">
                                        {{ Auth::user()->two_factor_enabled ? '✅ 有効' : '⚠️ 無効' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">IP制限</span>
                                    <span class="text-green-600 font-medium">{{ config('admin.ip_restriction_enabled') ? '✅ 有効' : '⚠️ 無効' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- アカウント情報 --}}
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    📊 アカウント情報
                                </h3>
                            </div>
                            <div class="space-y-3">
                                <div class="text-sm">
                                    <span class="text-gray-600">メール:</span>
                                    <div class="text-gray-900 font-medium mt-1">{{ Auth::user()->email }}</div>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">ユーザー名:</span>
                                    <div class="text-gray-900 font-medium mt-1">{{ Auth::user()->username }}</div>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">最終ログイン:</span>
                                    <div class="text-gray-900 font-medium mt-1">
                                        {{ Auth::user()->last_login_at?->format('Y-m-d H:i') ?? '未記録' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ログイン試行状況 --}}
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    🔍 ログイン試行状況
                                </h3>
                            </div>
                            <div class="space-y-3">
                                <div class="text-sm">
                                    <span class="text-gray-600">失敗試行回数:</span>
                                    <div class="text-gray-900 font-medium mt-1">{{ Auth::user()->failed_login_attempts }} 回</div>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-600">アカウント状態:</span>
                                    <div class="font-medium mt-1 {{ Auth::user()->is_locked ? 'text-red-600' : 'text-green-600' }}">
                                        {{ Auth::user()->is_locked ? '🔒 ロック中' : '✅ 正常' }}
                                    </div>
                                </div>
                                @if(Auth::user()->last_failed_login_at)
                                <div class="text-sm">
                                    <span class="text-gray-600">最終失敗日時:</span>
                                    <div class="text-gray-900 font-medium mt-1">
                                        {{ Auth::user()->last_failed_login_at->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 管理メニュー --}}
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">📋 管理メニュー</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <a href="#" class="group block p-6 bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-xl transition-all duration-200 border border-purple-200">
                                <div class="text-2xl mb-2">👥</div>
                                <div class="text-purple-900 font-semibold text-lg mb-1">ユーザー管理</div>
                                <div class="text-sm text-purple-700">ユーザーの確認・編集</div>
                            </a>
                            
                            <a href="#" class="group block p-6 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl transition-all duration-200 border border-blue-200">
                                <div class="text-2xl mb-2">⚙️</div>
                                <div class="text-blue-900 font-semibold text-lg mb-1">システム設定</div>
                                <div class="text-sm text-blue-700">システム全体の設定</div>
                            </a>
                            
                            <a href="#" class="group block p-6 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl transition-all duration-200 border border-green-200">
                                <div class="text-2xl mb-2">📊</div>
                                <div class="text-green-900 font-semibold text-lg mb-1">ログ確認</div>
                                <div class="text-sm text-green-700">システムログの閲覧</div>
                            </a>
                            
                            <a href="#" class="group block p-6 bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 rounded-xl transition-all duration-200 border border-orange-200">
                                <div class="text-2xl mb-2">🔔</div>
                                <div class="text-orange-900 font-semibold text-lg mb-1">通知管理</div>
                                <div class="text-sm text-orange-700">お知らせの作成・編集</div>
                            </a>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</x-app-layout>
