<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('アカウントの削除') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('アカウントを削除すると、すべてのデータが完全に削除されます。削除する前に、保持したいデータやファイルをダウンロードしてください。') }}
        </p>

        @php
            $user = auth()->user();
            $isGroupMaster = $user->group && $user->group->master_user_id === $user->id;
            $groupMembersCount = $isGroupMaster ? $user->group->users()->count() : 0;
            $hasSubscription = $isGroupMaster && $user->group?->subscription('default')?->active();
        @endphp
    </header>

    <button
        type="button"
        id="delete-account-btn"
        data-is-group-master="{{ $isGroupMaster ? '1' : '0' }}"
        data-members-count="{{ $groupMembersCount }}"
        data-has-subscription="{{ $hasSubscription ? '1' : '0' }}"
        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
    >{{ __('アカウントを削除') }}</button>

    {{-- グループマスター削除確認用の隠しフォーム --}}
    @if($isGroupMaster)
        <form id="delete-group-form" method="post" action="{{ route('profile.destroy') }}" style="display: none;">
            @csrf
            @method('delete')
            <input type="hidden" name="delete_group" value="1">
            <input type="hidden" name="password" id="group-delete-password">
        </form>
    @endif

    {{-- 通常のユーザー削除用フォーム --}}
    <form id="delete-user-form" method="post" action="{{ route('profile.destroy') }}" style="display: none;">
        @csrf
        @method('delete')
        <input type="hidden" name="password" id="user-delete-password">
    </form>

    {{-- グループマスター専用削除確認ダイアログ --}}
    @if($isGroupMaster)
        <x-delete-group-master-dialog />
    @endif
    
    {{-- パスワード入力ダイアログ --}}
    <x-password-prompt-dialog />
</section>

@push('scripts')
<script>
(function() {
    'use strict';
    
    const deleteBtn = document.getElementById('delete-account-btn');
    
    if (!deleteBtn) return;
    
    const isGroupMaster = deleteBtn.dataset.isGroupMaster === '1';
    const membersCount = parseInt(deleteBtn.dataset.membersCount || '0', 10);
    const hasSubscription = deleteBtn.dataset.hasSubscription === '1';
    
    deleteBtn.addEventListener('click', function() {
        if (isGroupMaster) {
            // グループマスターの場合は専用ダイアログを表示
            if (typeof window.showDeleteGroupMasterDialog === 'function') {
                window.showDeleteGroupMasterDialog(membersCount, hasSubscription, 'delete-group-form');
            } else {
                console.error('Delete group master dialog not available');
                // フォールバック: 従来のconfirm
                let message = `⚠️ グループの管理権限を保持しています\n\n`;
                message += `アカウントを削除すると：\n`;
                message += `• 全メンバー（${membersCount}名）が削除されます\n`;
                if (hasSubscription) {
                    message += `• サブスクリプションが即時解約されます\n`;
                }
                message += `• グループのすべてのデータが削除されます\n\n`;
                message += `本当に削除しますか？`;
                
                if (confirm(message)) {
                    const password = prompt('削除を確定するには、パスワードを入力してください：');
                    if (password) {
                        document.getElementById('group-delete-password').value = password;
                        document.getElementById('delete-group-form').submit();
                    }
                }
            }
        } else {
            // 通常ユーザーの場合
            const message = 'アカウントを削除すると、すべてのデータが完全に削除されます。\n\n本当に削除しますか？';
            
            if (typeof window.showConfirmDialog === 'function') {
                window.showConfirmDialog(
                    message,
                    () => {
                        // パスワード入力モーダルを表示
                        if (typeof window.showPasswordPromptDialog === 'function') {
                            window.showPasswordPromptDialog(
                                'アカウント削除を確定するには、パスワードを入力してください。',
                                (password) => {
                                    document.getElementById('user-delete-password').value = password;
                                    document.getElementById('delete-user-form').submit();
                                }
                            );
                        } else {
                            // フォールバック
                            const password = prompt('削除を確定するには、パスワードを入力してください：');
                            if (password) {
                                document.getElementById('user-delete-password').value = password;
                                document.getElementById('delete-user-form').submit();
                            }
                        }
                    }
                );
            } else {
                if (confirm(message)) {
                    const password = prompt('削除を確定するには、パスワードを入力してください：');
                    if (password) {
                        document.getElementById('user-delete-password').value = password;
                        document.getElementById('delete-user-form').submit();
                    }
                }
            }
        }
    });

})();
</script>
@endpush