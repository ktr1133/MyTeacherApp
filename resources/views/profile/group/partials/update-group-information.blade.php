<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h3 class="text-lg font-medium text-gray-900">{{ __('グループ情報') }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ __('グループ名の作成・変更ができます。') }}</p>
    </header>

    <form method="POST" action="{{ route('group.update') }}" class="mt-6 space-y-6">
        @csrf
        <div>
            <x-input-label for="group_name" :value="__('グループ名')" />
            <x-text-input id="group_name" name="group_name" type="text" class="mt-1 block w-full" required maxlength="255" value="{{ old('group_name', $group?->name) }}" />
            <x-input-error :messages="$errors->get('group_name')" class="mt-2" />
        </div>
        <div class="flex items-center gap-3">
            <x-primary-button>{{ $group ? __('更新する') : __('作成する') }}</x-primary-button>
        </div>
    </form>
</div>