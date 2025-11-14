{{-- filepath: /home/ktr/mtdev/laravel/resources/views/profile/group/partials/update-group-information.blade.php --}}

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('グループ情報') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("グループ名を更新できます。") }}
        </p>
    </header>

    <form id="group-update-form" method="post" action="{{ route('group.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- グループ名 -->
        <div>
            <x-input-label for="group_name" :value="__('グループ名')" />
            <x-text-input 
                id="group_name" 
                name="name" 
                type="text" 
                class="mt-1 block w-full" 
                :value="old('name', $group->name ?? '')" 
                required 
                autofocus 
                autocomplete="organization"
                data-current-group-id="{{ $group->id ?? '' }}"
            />
            
            {{-- スピナー（JSで制御） --}}
            <div id="group_name-spinner" class="validation-spinner" style="display: none;">
                <svg class="spinner" viewBox="0 0 50 50">
                    <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                </svg>
                <span class="spinner-text">確認中...</span>
            </div>
            
            {{-- バリデーションメッセージ（JSで制御） --}}
            <div id="group_name-validation" class="validation-message" style="display: none;"></div>
            
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button 
                id="group-update-button"
                disabled
                class="opacity-50 cursor-not-allowed">
                {{ __('保存') }}
            </x-primary-button>

            @if (session('status') === 'group-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('保存しました。') }}</p>
            @endif
        </div>
    </form>
</section>