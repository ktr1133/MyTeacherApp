<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h3 class="text-lg font-medium text-gray-900">{{ __('メンバー一覧') }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ __('権限の変更やマスター譲渡ができます。') }}</p>
    </header>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="px-3 py-2">{{ __('ID') }}</th>
                    <th class="px-3 py-2">{{ __('ユーザー名') }}</th>
                    <th class="px-3 py-2">{{ __('権限') }}</th>
                    <th class="px-3 py-2">{{ __('操作') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupMembers as $member)
                    <tr class="border-t">
                        <td class="px-3 py-2">{{ $member->id }}</td>
                        <td class="px-3 py-2">{{ $member->username }}</td>
                        <td class="px-3 py-2">
                            @if ($member->group && $member->group->master_user_id === $member->id)
                                <span class="inline-flex items-center rounded bg-yellow-100 text-yellow-800 px-2 py-0.5 text-xs">{{ __('マスター') }}</span>
                            @elseif ($member->group_edit_flg)
                                <span class="inline-flex items-center rounded bg-green-100 text-green-800 px-2 py-0.5 text-xs">{{ __('編集権限あり') }}</span>
                            @else
                                <span class="inline-flex items-center rounded bg-gray-100 text-gray-700 px-2 py-0.5 text-xs">{{ __('一般') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
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
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-md border border-gray-300 hover:bg-gray-200 hover:border-gray-400 transition-all">
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
                    <tr class="border-t">
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">{{ __('メンバーがいません。') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>