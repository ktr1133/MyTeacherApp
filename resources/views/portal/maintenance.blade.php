{{-- filepath: /home/ktr/mtdev/laravel/resources/views/portal/maintenance.blade.php --}}
@extends('layouts.portal')

@section('title', 'メンテナンス情報')
@section('meta_description', 'MyTeacherのメンテナンス予定と実施状況')

@section('content')
<!-- Page Header -->
<section class="px-4 sm:px-6 lg:px-8 py-12 bg-white dark:bg-gray-800/50">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl font-bold gradient-text mb-4">メンテナンス情報</h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            定期メンテナンスや緊急メンテナンスの予定と実施状況をお知らせします
        </p>
    </div>
</section>

<!-- Filter Section -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <form method="GET" action="{{ route('portal.maintenance') }}" class="flex flex-wrap gap-3">
            <!-- Status Filter -->
            <select 
                name="status" 
                class="px-4 py-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition"
                onchange="this.form.submit()"
            >
                <option value="">すべてのステータス</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>予定</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>実施中</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>完了</option>
            </select>

            <!-- App Filter -->
            <select 
                name="app" 
                class="px-4 py-2 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-[#59B9C6] dark:focus:border-[#59B9C6] focus:ring-0 text-gray-900 dark:text-white transition"
                onchange="this.form.submit()"
            >
                <option value="">すべてのアプリ</option>
                @foreach(config('const.app_names') as $key => $label)
                    <option value="{{ $key }}" {{ request('app') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <!-- Reset Button -->
            @if(request()->hasAny(['status', 'app']))
                <a href="{{ route('portal.maintenance') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    リセット
                </a>
            @endif
        </form>
    </div>
</section>

<!-- Maintenance List -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        @if($maintenances->isEmpty())
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg text-gray-600 dark:text-gray-400">該当するメンテナンス情報はありません</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($maintenances as $maintenance)
                <div class="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 p-6 hover:border-[#59B9C6] dark:hover:border-[#59B9C6] transition">
                    <div class="flex items-start gap-4 mb-4">
                        <!-- Status Badge -->
                        @php
                            $statusConfig = [
                                'scheduled' => [
                                    'label' => '予定',
                                    'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
                                ],
                                'in_progress' => [
                                    'label' => '実施中',
                                    'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                                ],
                                'completed' => [
                                    'label' => '完了',
                                    'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                                ]
                            ];
                            $status = $statusConfig[$maintenance->status] ?? $statusConfig['scheduled'];
                        @endphp
                        
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full {{ $status['class'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $status['icon'] }}"/>
                            </svg>
                            <span class="text-sm font-semibold">{{ $status['label'] }}</span>
                        </div>

                        <!-- Creator Badge -->
                        @if($maintenance->creator)
                            <div class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
                                作成者: {{ $maintenance->creator->name }}
                            </div>
                        @endif
                    </div>

                    <!-- Title & Description -->
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">{{ $maintenance->title }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4 whitespace-pre-line">{{ $maintenance->description }}</p>

                    <!-- Meta Info Grid -->
                    <div class="grid sm:grid-cols-2 gap-4 text-sm">
                        <!-- Scheduled At -->
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <span class="font-semibold">予定日時:</span>
                                <br>
                                {{ $maintenance->scheduled_at->format('Y年m月d日 H:i') }}
                            </div>
                        </div>

                        <!-- Started At -->
                        @if($maintenance->started_at)
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <span class="font-semibold">開始:</span>
                                <br>
                                {{ $maintenance->started_at->format('Y年m月d日 H:i') }}
                            </div>
                        </div>
                        @endif

                        <!-- Completed At -->
                        @if($maintenance->completed_at)
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <span class="font-semibold">完了:</span>
                                <br>
                                {{ $maintenance->completed_at->format('Y年m月d日 H:i') }}
                            </div>
                        </div>
                        @endif

                        <!-- Affected Apps -->
                        @if($maintenance->affected_apps)
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <span class="font-semibold">対象アプリ:</span>
                                <br>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($maintenance->affected_apps as $app)
                                        <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs">
                                            {{ config('const.app_names')[$app] ?? $app }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Impact Notice (if in progress) -->
                    @if($maintenance->status === 'in_progress')
                    <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-600 dark:border-yellow-400 p-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="font-semibold text-yellow-800 dark:text-yellow-300">現在メンテナンス実施中です</span>
                        </div>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            対象アプリは一時的にご利用いただけない場合があります
                        </p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- Info Box -->
<section class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-semibold mb-2">メンテナンス情報について</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>定期メンテナンスは原則として深夜時間帯に実施します</li>
                        <li>緊急メンテナンスの場合は事前予告なく実施する場合があります</li>
                        <li>メンテナンス中は一部機能がご利用いただけない場合があります</li>
                        <li>最新情報はこのページで随時更新いたします</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
