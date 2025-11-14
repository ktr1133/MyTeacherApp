@props(['buckets', 'tags', 'prefix' => 'default'])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 bento-auto-rows">
    @foreach($buckets as $bucket)
        @php
            $count = $bucket['tasks']->count();
            if ($count >= 9) {
                $sizeClass = 'xl:col-span-2 xl:row-span-2 lg:col-span-2 lg:row-span-2';
                $preview = $bucket['tasks']->take(6);
            } elseif ($count >= 4) {
                $sizeClass = 'xl:col-span-2 xl:row-span-1 lg:col-span-2 lg:row-span-1';
                $preview = $bucket['tasks']->take(3);
            } else {
                $sizeClass = 'col-span-1 row-span-1';
                $preview = $bucket['tasks'];
            }
        @endphp

        <div class="bento-card group relative rounded-2xl shadow-lg hover:shadow-2xl p-6 cursor-pointer {{ $sizeClass }}"
             @click="$dispatch('open-tag-modal-{{ $prefix }}-{{ $bucket['id'] }}')">
            
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 text-white shadow-lg">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 12a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2H5zM12 5a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V5zM12 14a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"/>
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $bucket['name'] }}</h3>
                </div>
                <span class="tag-badge-gradient inline-flex items-center justify-center min-w-[2.5rem] h-7 px-3 rounded-full text-xs font-bold shadow-md">
                    {{ $count }}
                </span>
            </div>
            @if($preview->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($preview as $t)
                        <span class="text-xs bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-full truncate max-w-[60%] backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50">
                            {{ $t->title }}
                        </span>
                    @endforeach
                    @if($count > 3)
                        <span class="text-xs text-gray-400 dark:text-gray-500 px-3 py-1.5">他 {{ $count - 3 }} 件</span>
                    @endif
                </div>
            @endif

            <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-[#59B9C6] to-purple-600 rounded-b-2xl"></div>
        </div>

        @include('dashboard.modal-tag-tasks', [
            'tagId' => $prefix . '-' . $bucket['id'],
            'tagName' => $bucket['name'],
            'tasksOfTag' => $bucket['tasks'],
            'allTags' => $tags,
        ])
    @endforeach
</div>