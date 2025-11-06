{{-- resources/views/dashboard/partials/task-bento-layout.blade.php --}}
@props(['buckets', 'tags', 'prefix' => 'default'])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 bento-auto-rows">
    @foreach($buckets as $bucket)
        @php
            $count = $bucket['tasks']->count();
            // 件数に応じてサイズを変える
            if ($count >= 9) {
                $sizeClass = 'xl:col-span-2 xl:row-span-2 lg:col-span-2 lg:row-span-2';
            } elseif ($count >= 4) {
                $sizeClass = 'xl:col-span-2 xl:row-span-1 lg:col-span-2 lg:row-span-1';
            } else {
                $sizeClass = 'col-span-1 row-span-1';
            }
        @endphp

        <div class="group relative bg-white rounded-xl shadow hover:shadow-lg transition overflow-hidden border border-gray-100 p-5 cursor-pointer {{ $sizeClass }}"
             @click="$dispatch('open-tag-modal-{{ $prefix }}-{{ $bucket['id'] }}')">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#59B9C6]/10 text-[#59B9C6]">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 12a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2H5zM12 5a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V5zM12 14a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $bucket['name'] }}</h3>
                </div>
                <span class="ml-auto inline-flex items-center justify-center min-w-[2rem] h-6 px-2 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                    {{ $count }}
                </span>
            </div>

            {{-- サンプルとして先頭3件だけタイトルをチップ表示 --}}
            @php
                $preview = $bucket['tasks']->take(3);
            @endphp
            @if($preview->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($preview as $t)
                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full truncate max-w-[60%]">{{ $t->title }}</span>
                    @endforeach
                    @if($count > 3)
                        <span class="text-xs text-gray-400">ほか {{ $count - 3 }} 件</span>
                    @endif
                </div>
            @endif

            <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-[#59B9C6]/30 to-purple-400/30"></div>
        </div>

        {{-- タグのタスクリストモーダル --}}
        @include('dashboard.modal-tag-tasks', [
            'tagId' => $prefix . '-' . $bucket['id'],
            'tagName' => $bucket['name'],
            'tasksOfTag' => $bucket['tasks'],
            'allTags' => $tags,
        ])
    @endforeach
</div>