@props(['buckets', 'tags', 'prefix' => 'default'])

<div class="bento-grid grid grid-cols-1 sm:grid-cols-2 md:landscape:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6 bento-auto-rows">
    @foreach($buckets as $bucket)
        @php
            $count = $bucket['tasks']->count();
            
            // ========================================
            // XL (1280px以上) - 4カラムグリッド
            // ========================================
            if ($count >= 12) {
                $xlClass = 'xl:col-span-2 xl:row-span-2';  // 超大 (2x2)
                $xlPreview = 6;
            } elseif ($count >= 9) {
                $xlClass = 'xl:col-span-2 xl:row-span-2';  // 大 (2x2)
                $xlPreview = 6;
            } elseif ($count >= 5) {
                $xlClass = 'xl:col-span-2 xl:row-span-1';  // 中 (2x1)
                $xlPreview = 4;
            } else {
                $xlClass = 'xl:col-span-1 xl:row-span-1';  // 小 (1x1)
                $xlPreview = 3;
            }

            // ========================================
            // LG (1024px以上) - 3カラムグリッド
            // ========================================
            if ($count >= 12) {
                $lgClass = 'lg:col-span-2 lg:row-span-2';  // 超大 (2x2)
                $lgPreview = 6;
            } elseif ($count >= 9) {
                $lgClass = 'lg:col-span-2 lg:row-span-2';  // 大 (2x2)
                $lgPreview = 6;
            } elseif ($count >= 5) {
                $lgClass = 'lg:col-span-2 lg:row-span-1';  // 中 (2x1)
                $lgPreview = 4;
            } else {
                $lgClass = 'lg:col-span-1 lg:row-span-1';  // 小 (1x1)
                $lgPreview = 3;
            }

            // ========================================
            // MD Landscape (横向きスマホ/タブレット) - 3カラムグリッド
            // ========================================
            if ($count >= 12) {
                $mdLandscapeClass = 'md:landscape:col-span-2 md:landscape:row-span-2';  // 超大 (2x2)
                $mdLandscapePreview = 6;
            } elseif ($count >= 9) {
                $mdLandscapeClass = 'md:landscape:col-span-2 md:landscape:row-span-1';  // 大 (2x1)
                $mdLandscapePreview = 4;
            } elseif ($count >= 5) {
                $mdLandscapeClass = 'md:landscape:col-span-1 md:landscape:row-span-2';  // 中 (1x2)
                $mdLandscapePreview = 4;
            } else {
                $mdLandscapeClass = 'md:landscape:col-span-1 md:landscape:row-span-1';  // 小 (1x1)
                $mdLandscapePreview = 3;
            }

            // ========================================
            // SM (640px以上) - 2カラムグリッド
            // ========================================
            if ($count >= 12) {
                $smClass = 'sm:col-span-2 sm:row-span-2';  // 超大 (2x2)
                $smPreview = 6;
            } elseif ($count >= 9) {
                $smClass = 'sm:col-span-2 sm:row-span-2';  // 大 (2x2)
                $smPreview = 6;
            } elseif ($count >= 5) {
                $smClass = 'sm:col-span-1 sm:row-span-2';  // 中 (1x2)
                $smPreview = 4;
            } else {
                $smClass = 'sm:col-span-1 sm:row-span-1';  // 小 (1x1)
                $smPreview = 3;
            }

            // ========================================
            // モバイル (640px未満) - 1カラム
            // ========================================
            if ($count >= 12) {
                $mobileClass = 'col-span-1 row-span-2';  // 超大 (1x2)
                $mobilePreview = 6;
            } elseif ($count >= 9) {
                $mobileClass = 'col-span-1 row-span-2';  // 大 (1x2)
                $mobilePreview = 6;
            } elseif ($count >= 5) {
                $mobileClass = 'col-span-1 row-span-1';  // 中 (1x1)
                $mobilePreview = 4;
            } else {
                $mobileClass = 'col-span-1 row-span-1';  // 小 (1x1)
                $mobilePreview = 3;
            }

            // 最終的なクラスを結合
            $sizeClass = "{$mobileClass} {$smClass} {$mdLandscapeClass} {$lgClass} {$xlClass}";
            
            // プレビュー数はXLサイズ基準（最大の画面サイズを想定）
            $preview = $bucket['tasks']->take($xlPreview);
            
            // グループタスクが含まれているか判定
            $hasGroupTask = $bucket['tasks']->contains(fn($task) => !is_null($task->group_task_id));
        @endphp

        <div class="bento-card group relative rounded-2xl shadow-lg hover:shadow-2xl p-4 lg:p-6 cursor-pointer transition-all duration-300 {{ $sizeClass }}"
             data-tag-modal-id="{{ $prefix }}-{{ $bucket['id'] }}"
             onclick="window.TagTasksModalController?.open('{{ $prefix }}-{{ $bucket['id'] }}');">
            
            {{-- ヘッダー --}}
            <div class="flex items-start justify-between mb-3 lg:mb-4">
                <div class="flex items-center gap-2 lg:gap-3 min-w-0 flex-1">
                    <span class="inline-flex items-center justify-center w-8 h-8 lg:w-10 lg:h-10 rounded-lg lg:rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 text-white shadow-lg flex-shrink-0">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 12a2 2 0 00-2 2v3a2 2 0 002 2h3a2 2 0 002-2v-3a2 2 0 00-2-2H5zM12 5a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V5zM12 14a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z"/>
                        </svg>
                    </span>
                    <h3 class="text-base lg:text-lg font-bold text-gray-900 dark:text-white truncate">{{ $bucket['name'] }}</h3>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                    <span class="tag-badge-gradient inline-flex items-center justify-center min-w-[2rem] lg:min-w-[2.5rem] h-6 lg:h-7 px-2 lg:px-3 rounded-full text-xs font-bold shadow-md">
                        {{ $count }}
                    </span>
                    @if($count > 0 && $prefix === 'todo')
                        <button type="button"
                                class="bulk-complete-btn inline-flex items-center justify-center h-6 lg:h-7 px-2 lg:px-3 rounded-lg text-xs font-semibold text-white transition shadow-md {{ $hasGroupTask ? 'bg-gray-400 cursor-not-allowed opacity-60' : 'bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 hover:shadow-lg' }}"
                                data-bucket-tasks="{{ $bucket['tasks']->pluck('id')->implode(',') }}"
                                data-bucket-name="{{ $bucket['name'] }}"
                                data-is-completed="true"
                                data-has-group-task="{{ $hasGroupTask ? 'true' : 'false' }}"
                                onclick="event.stopPropagation();"
                                title="{{ $hasGroupTask ? 'グループタスクが含まれているため一括操作できません' : 'このタグのタスクをすべて完了' }}">
                            全完
                        </button>
                    @elseif($count > 0 && $prefix === 'completed')
                        <button type="button"
                                class="bulk-complete-btn inline-flex items-center justify-center h-6 lg:h-7 px-2 lg:px-3 rounded-lg text-xs font-semibold text-white transition shadow-md {{ $hasGroupTask ? 'bg-gray-400 cursor-not-allowed opacity-60' : 'bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 hover:shadow-lg' }}"
                                data-bucket-tasks="{{ $bucket['tasks']->pluck('id')->implode(',') }}"
                                data-bucket-name="{{ $bucket['name'] }}"
                                data-is-completed="false"
                                data-has-group-task="{{ $hasGroupTask ? 'true' : 'false' }}"
                                onclick="event.stopPropagation();"
                                title="{{ $hasGroupTask ? 'グループタスクが含まれているため一括操作できません' : 'このタグのタスクをすべて未完了に戻す' }}">
                            全戻
                        </button>
                    @endif
                </div>
            </div>

            {{-- タスクプレビュー --}}
            @if($preview->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 lg:gap-2">
                    @foreach($preview as $t)
                        <span class="text-xs bg-white/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 px-2 lg:px-3 py-1 lg:py-1.5 rounded-full truncate max-w-[60%] backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50">
                            {{ $t->title }}
                        </span>
                    @endforeach
                    @if($count > $xlPreview)
                        <span class="text-xs text-gray-400 dark:text-gray-500 px-2 lg:px-3 py-1 lg:py-1.5">他 {{ $count - $xlPreview }} 件</span>
                    @endif
                </div>
            @else
                <div class="text-center py-4 text-sm text-gray-400 dark:text-gray-500">
                    タスクがありません
                </div>
            @endif

            {{-- 下部グラデーションバー --}}
            <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-[#59B9C6] to-purple-600 rounded-b-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        @include('dashboard.modal-tag-tasks', [
            'tagId' => $prefix . '-' . $bucket['id'],
            'tagName' => $bucket['name'],
            'tasksOfTag' => $bucket['tasks'],
            'allTags' => $tags,
            'isChildTheme' => $isChildTheme,
        ])
    @endforeach
</div>