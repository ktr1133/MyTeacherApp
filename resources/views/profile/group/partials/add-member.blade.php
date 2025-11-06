<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h3 class="text-lg font-medium text-gray-900">{{ __('メンバー追加') }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ __('グループに新しいアカウントを作成します。') }}</p>
    </header>

    <form method="POST" action="{{ route('group.member.add') }}" class="mt-6 space-y-6">
        @csrf
        <div>
            <x-input-label for="username" :value="__('ユーザー名')" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" required maxlength="255" value="{{ old('username') }}" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('パスワード')" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="flex items-center gap-2">
            <input id="group_edit_flg" name="group_edit_flg" type="checkbox" value="1" class="rounded border-gray-300">
            <label for="group_edit_flg" class="text-sm text-gray-700">{{ __('編集権限を付与する') }}</label>
        </div>
        <div class="flex items-center gap-3">
            <x-primary-button>{{ __('追加する') }}</x-primary-button>
        </div>
    </form>
</div>