<x-app-layout>
    @push('styles')
        @vite(['resources/css/tokens/purchase.css'])
    @endpush

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- ヘッダー --}}
            <div class="mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    トークン購入の承認待ち
                </h1>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                    子どもたちからのトークン購入リクエストを確認・承認できます。
                </p>
            </div>

            {{-- 承認待ちリクエスト一覧 --}}
            <div class="pending-approvals-list">
                @forelse($pendingRequests as $request)
                    <div class="approval-card">
                        {{-- 子ども情報 --}}
                        <div class="child-info">
                            <div class="avatar">
                                {{ substr($request->user->username, 0, 2) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $request->user->username }}さん
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                    <x-user-local-time :datetime="$request->created_at" format="Y-m-d H:i" />
                                </p>
                            </div>
                        </div>

                        {{-- パッケージ情報 --}}
                        <div class="package-info">
                            <div class="icon-wrapper">
                                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $request->package->name }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($request->package->tokens) }}トークン
                                    <span class="mx-2">•</span>
                                    ¥{{ number_format($request->package->price) }}
                                </p>
                            </div>
                        </div>

                        {{-- アクションボタン --}}
                        <div class="action-buttons">
                            <form action="{{ route('tokens.requests.approve', $request) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-approve">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>承認する</span>
                                </button>
                            </form>

                            <button 
                                type="button" 
                                class="btn-reject"
                                onclick="openRejectModal({{ $request->id }})">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>却下する</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            承認待ちのリクエストはありません
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            子どもから購入リクエストがあると、ここに表示されます。
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 却下モーダル --}}
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3 class="modal-title">購入を却下</h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="form-group">
                    <label for="reason">却下理由（任意）</label>
                    <textarea 
                        id="reason" 
                        name="reason" 
                        rows="3" 
                        class="form-control"
                        placeholder="例：今月はもう十分使いました"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeRejectModal()">
                        キャンセル
                    </button>
                    <button type="submit" class="btn-danger">
                        却下する
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openRejectModal(requestId) {
                const modal = document.getElementById('rejectModal');
                const form = document.getElementById('rejectForm');
                form.action = `/tokens/requests/${requestId}/reject`;
                modal.style.display = 'flex';
            }

            function closeRejectModal() {
                const modal = document.getElementById('rejectModal');
                modal.style.display = 'none';
            }
        </script>
    @endpush
</x-app-layout>