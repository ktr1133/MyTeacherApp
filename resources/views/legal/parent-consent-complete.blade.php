<x-guest-layout>
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl w-full space-y-8">
        @if(session('child_user'))
        <!-- 完了メッセージ -->
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-6">
                <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                同意が完了しました
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                お子様のMyTeacherアカウント利用が可能になりました
            </p>
        </div>

        <!-- お子様の情報 -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 space-y-6">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    お子様のアカウント情報
                </h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ユーザー名</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">{{ session('child_user')->username }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">メールアドレス</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ session('child_user')->email }}</dd>
                    </div>
                </dl>
            </div>

            <!-- ログイン可能通知 -->
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0 hidden sm:block">
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="sm:ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-300">
                            お子様がログインできるようになりました
                        </h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-400">
                            <p>お子様に以下をお伝えください：</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>MyTeacherにログインできるようになりました</li>
                                <li>ユーザー名: <span class="font-semibold">{{ session('child_user')->username }}</span></li>
                                <li>ログインには登録時に設定したパスワードが必要です</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 保護者アカウント作成案内 -->
            @if($invitationToken = request()->route('token'))
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0 hidden sm:block">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="sm:ml-3 flex-1">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            保護者の方もアカウント作成をお勧めします
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                            <p class="mb-3">保護者アカウントを作成すると、以下の機能が利用できます：</p>
                            <ul class="list-disc list-inside space-y-1 mb-4">
                                <li>お子様の学習進捗を確認</li>
                                <li>タスクの承認・管理</li>
                                <li>お子様とのコミュニケーション</li>
                            </ul>
                            <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-blue-300 dark:border-blue-700">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">下記の専用リンクから登録すると、自動的にお子様と紐付けられます：</p>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                                    <input 
                                        type="text" 
                                        id="invitation-link" 
                                        readonly 
                                        value="{{ url(route('register', ['parent_invite' => $invitationToken])) }}" 
                                        class="flex-1 px-3 py-2 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white font-mono"
                                    >
                                    <button 
                                        onclick="copyInvitationLink()" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200 flex items-center space-x-1"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">コピー</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    ⚠️ このリンクは30日間有効です
                                </p>
                            </div>
                            <div class="mt-4">
                                <a 
                                    href="{{ route('register', ['parent_invite' => $invitationToken]) }}" 
                                    class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg"
                                >
                                    保護者アカウントを作成する
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- モバイルアプリ案内 -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="h-5 w-5 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                    モバイルアプリのご案内
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    MyTeacherはモバイルアプリでもご利用いただけます。スマートフォンやタブレットから簡単にアクセスできます。
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a 
                        href="#" 
                        class="flex items-center justify-center px-4 py-3 bg-black hover:bg-gray-800 text-white rounded-lg transition-colors duration-200"
                    >
                        <svg class="h-6 w-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                        </svg>
                        <div>
                            <div class="text-xs">Download on the</div>
                            <div class="text-sm font-semibold -mt-1">App Store</div>
                        </div>
                    </a>
                    <a 
                        href="#" 
                        class="flex items-center justify-center px-4 py-3 bg-black hover:bg-gray-800 text-white rounded-lg transition-colors duration-200"
                    >
                        <svg class="h-6 w-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                        </svg>
                        <div>
                            <div class="text-xs">GET IT ON</div>
                            <div class="text-sm font-semibold -mt-1">Google Play</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                <a 
                    href="{{ route('login') }}" 
                    class="w-full flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    お子様のログイン画面へ
                </a>
                <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                    問題が発生した場合は、<a href="mailto:support@myteacher.example" class="text-blue-600 dark:text-blue-400 hover:underline">サポート</a>までお問い合わせください。
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function copyInvitationLink() {
    const input = document.getElementById('invitation-link');
    input.select();
    input.setSelectionRange(0, 99999); // モバイル対応
    
    navigator.clipboard.writeText(input.value).then(() => {
        // コピー成功メッセージ
        const button = event.target.closest('button');
        const originalHTML = button.innerHTML;
        button.innerHTML = `
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span>コピーしました</span>
        `;
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        button.classList.add('bg-green-600', 'hover:bg-green-700');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    }).catch(err => {
        console.error('コピーに失敗しました:', err);
        alert('リンクのコピーに失敗しました。手動でコピーしてください。');
    });
}
</script>
@else
<!-- セッションがない場合のメッセージ -->
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-6">
        <svg class="h-10 w-10 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
    </div>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        セッションが切れています
    </h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        同意完了画面を表示できません。
    </p>
    <div class="mt-6">
        <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
            ログイン画面へ
        </a>
    </div>
</div>
@endif
    </div>
</div>
</x-guest-layout>

