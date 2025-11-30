{{-- メンバー一覧 --}}
@push('styles')
    @vite(['resources/css/profile/group-edit.css'])
@endpush

{{-- デスクトップ表示: テーブル --}}
<div class="member-table-desktop">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">{{ __('表示名') }}</th>
                    <th class="px-3 py-2">{{ __('ユーザー名') }}</th>
                    <th class="px-3 py-2">{{ __('権限') }}</th>
                    <th class="px-3 py-2">{{ __('操作') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupMembers as $member)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $member->name ?: '未設定' }}</td>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $member->username }}</td>
                        <td class="px-3 py-2">
                            @if ($member->group && $member->group->master_user_id === $member->id)
                                <span class="inline-flex items-center rounded bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-0.5 text-xs">{{ __('マスター') }}</span>
                            @elseif ($member->group_edit_flg)
                                <span class="inline-flex items-center rounded bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-0.5 text-xs">{{ __('編集権限あり') }}</span>
                            @else
                                <span class="inline-flex items-center rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-0.5 text-xs">{{ __('一般') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- 編集権限 --}}
                                @if (!($member->group && $member->group->master_user_id === $member->id))
                                    <form method="POST" action="{{ route('group.member.permission', $member) }}" class="inline-flex" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('{{ $member->group_edit_flg ? __('編集権限を外しますか？') : __('編集権限を付与しますか？') }}', () => { event.target.submit(); }); } else { if (confirm('{{ $member->group_edit_flg ? __('編集権限を外しますか？') : __('編集権限を付与しますか？') }}')) { event.target.submit(); } }">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="group_edit_flg" value="{{ $member->group_edit_flg ? 0 : 1 }}">
                                        <x-secondary-button type="submit">{{ $member->group_edit_flg ? __('編集権限を外す') : __('編集権限を付与') }}</x-secondary-button>
                                    </form>
                                @endif
                                {{-- マスター譲渡 --}}
                                @if (Auth::user()->group && Auth::user()->group->master_user_id === Auth::id() && $member->id !== Auth::id())
                                    <form method="POST" action="{{ route('group.master.transfer', $member) }}" class="inline-flex" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('{{ __('マスター権限を譲渡しますか？この操作は取り消せません。') }}', () => { event.target.submit(); }); } else { if (confirm('{{ __('マスター権限を譲渡しますか？この操作は取り消せません。') }}')) { event.target.submit(); } }">
                                        @csrf
                                        <x-secondary-button type="submit">{{ __('マスター譲渡') }}</x-secondary-button>
                                    </form>
                                @endif
                                {{-- 子ども用画面設定 --}}
                                @if ($member->isChild())
                                    <form method="POST" action="{{ route('group.member.theme', $member) }}" class="inline-flex">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="theme" value="{{ $member->theme === 'child' ? '0' : '1' }}">
                                        
                                        @if ($member->theme === 'child')
                                            {{-- 子ども用テーマが有効な場合 --}}
                                            <button type="submit" 
                                                    title="{{ __('大人用テーマに切り替える') }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gradient-to-r from-amber-400 to-orange-400 text-white text-xs font-bold rounded-md shadow-sm hover:from-amber-500 hover:to-orange-500 transition-all hover:scale-105">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                                </svg>
                                                <span>{{ __('子ども') }}</span>
                                            </button>
                                        @else
                                            {{-- 大人用テーマの場合 --}}
                                            <button type="submit" 
                                                    title="{{ __('子ども用テーマに切り替える') }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                                </svg>
                                                <span>{{ __('大人') }}</span>
                                            </button>
                                        @endif
                                    </form>
                                @endif
                                {{-- メンバー削除 --}}
                                @if ($member->id !== (Auth::user()->group->master_user_id ?? 0))
                                    <form method="POST" action="{{ route('group.member.remove', $member) }}" class="inline-flex" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('{{ __('このメンバーをグループから外しますか？') }}', () => { event.target.submit(); }); } else { if (confirm('{{ __('このメンバーをグループから外しますか？') }}')) { event.target.submit(); } }">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button>{{ __('外す') }}</x-danger-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if ($groupMembers->isEmpty())
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">{{ __('メンバーがいません。') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- モバイル表示: カード --}}
<div class="member-cards-mobile">
    @if ($groupMembers->isEmpty())
        <div class="member-empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p>{{ __('メンバーがいません。') }}</p>
        </div>
    @else
        @foreach ($groupMembers as $member)
            <div class="member-card">
                {{-- ヘッダー: 表示名 + ユーザー名 + 権限バッジ --}}
                <div class="member-card-header">
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="member-card-username">{{ $member->name ?: $member->username }}</span>
                        @if ($member->name)
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ '@' . $member->username }}</span>
                        @endif
                    </div>
                    <div class="member-card-badge">
                        @if ($member->group && $member->group->master_user_id === $member->id)
                            <span class="inline-flex items-center rounded bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-0.5 text-xs font-bold">{{ __('マスター') }}</span>
                        @elseif ($member->group_edit_flg)
                            <span class="inline-flex items-center rounded bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-0.5 text-xs font-bold">{{ __('編集権限') }}</span>
                        @else
                            <span class="inline-flex items-center rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-0.5 text-xs font-medium">{{ __('一般') }}</span>
                        @endif
                    </div>
                </div>

                {{-- 主要アクション --}}
                <div class="member-card-primary-actions">
                    {{-- 子ども用画面設定（主要アクション） --}}
                    @if ($member->isChild())
                        <form method="POST" action="{{ route('group.member.theme', $member) }}" style="flex: 1;">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="theme" value="{{ $member->theme === 'child' ? '0' : '1' }}">
                            
                            @if ($member->theme === 'child')
                                {{-- 子ども用テーマが有効な場合 --}}
                                <button type="submit" 
                                        title="{{ __('大人用テーマに切り替える') }}"
                                        class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 bg-gradient-to-r from-amber-400 to-orange-400 text-white text-xs font-bold rounded-lg shadow-sm hover:from-amber-500 hover:to-orange-500 transition-all">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                    </svg>
                                    <span>{{ __('子ども用テーマ') }}</span>
                                </button>
                            @else
                                {{-- 大人用テーマの場合 --}}
                                <button type="submit" 
                                        title="{{ __('子ども用テーマに切り替える') }}"
                                        class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-all">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                    </svg>
                                    <span>{{ __('大人用テーマ') }}</span>
                                </button>
                            @endif
                        </form>
                    @endif

                    {{-- メンバー削除（主要アクション） --}}
                    @if ($member->id !== (Auth::user()->group->master_user_id ?? 0))
                        <form method="POST" action="{{ route('group.member.remove', $member) }}" style="flex: 1;" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('{{ __('このメンバーをグループから外しますか？') }}', () => { event.target.submit(); }); } else { if (confirm('{{ __('このメンバーをグループから外しますか？') }}')) { event.target.submit(); } }">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                {{ __('外す') }}
                            </button>
                        </form>
                    @endif
                </div>

                {{-- 追加アクション（折りたたみ） --}}
                @php
                    $hasSecondaryActions = false;
                    // 編集権限付与がある場合
                    if (!($member->group && $member->group->master_user_id === $member->id)) {
                        $hasSecondaryActions = true;
                    }
                    // マスター譲渡がある場合
                    if (Auth::user()->group && Auth::user()->group->master_user_id === Auth::id() && $member->id !== Auth::id()) {
                        $hasSecondaryActions = true;
                    }
                @endphp

                @if ($hasSecondaryActions)
                    <button type="button" class="member-card-more-button" onclick="this.classList.toggle('active'); this.nextElementSibling.classList.toggle('show');">
                        <span>その他の操作</span>
                        <svg class="member-card-more-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div class="member-card-secondary-actions">
                        {{-- 編集権限（追加アクション） --}}
                        @if (!($member->group && $member->group->master_user_id === $member->id))
                            <form method="POST" action="{{ route('group.member.permission', $member) }}" style="flex: 1;" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('{{ $member->group_edit_flg ? __('編集権限を外しますか？') : __('編集権限を付与しますか？') }}', () => { event.target.submit(); }); } else { if (confirm('{{ $member->group_edit_flg ? __('編集権限を外しますか？') : __('編集権限を付与しますか？') }}')) { event.target.submit(); } }">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="group_edit_flg" value="{{ $member->group_edit_flg ? 0 : 1 }}">
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium rounded-lg border transition {{ $member->group_edit_flg ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600' : 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-900/50' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if ($member->group_edit_flg)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        @endif
                                    </svg>
                                    {{ $member->group_edit_flg ? __('権限を外す') : __('権限を付与') }}
                                </button>
                            </form>
                        @endif

                        {{-- マスター譲渡 --}}
                        @if (Auth::user()->group && Auth::user()->group->master_user_id === Auth::id() && $member->id !== Auth::id())
                            <form method="POST" action="{{ route('group.master.transfer', $member) }}" style="flex: 1;" onsubmit="event.preventDefault(); if (window.showConfirmDialog) { window.showConfirmDialog('{{ __('マスター権限を譲渡しますか？この操作は取り消せません。') }}', () => { event.target.submit(); }); } else { if (confirm('{{ __('マスター権限を譲渡しますか？この操作は取り消せません。') }}')) { event.target.submit(); } }">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    {{ __('マスター譲渡') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</div>