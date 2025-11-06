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
                                @if (!($member->group && $member->group->master_user_id === $member->id))
                                    <form method="POST" action="{{ route('group.member.permission', $member) }}" class="inline-flex">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="group_edit_flg" value="{{ $member->group_edit_flg ? 0 : 1 }}">
                                        <x-secondary-button>{{ $member->group_edit_flg ? __('編集権限を外す') : __('編集権限を付与') }}</x-secondary-button>
                                    </form>
                                @endif

                                @if (Auth::user()->group && Auth::user()->group->master_user_id === Auth::id() && $member->id !== Auth::id())
                                    <form method="POST" action="{{ route('group.master.transfer', $member) }}" class="inline-flex">
                                        @csrf
                                        <x-secondary-button>{{ __('マスター譲渡') }}</x-secondary-button>
                                    </form>
                                @endif

                                @if ($member->id !== (Auth::user()->group->master_user_id ?? 0))
                                    <form method="POST" action="{{ route('group.member.remove', $member) }}" class="inline-flex" onsubmit="return confirm('{{ __('このメンバーをグループから外しますか？') }}')">
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