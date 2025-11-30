{{-- サブスクリプション プラン変更確認モーダル --}}
@props(['currentPlan', 'plans'])

<div id="plan-change-modal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
    {{-- オーバーレイ --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" data-modal-close></div>
    
    {{-- ダイアログコンテナ --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            {{-- ダイアログカード --}}
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all w-full max-w-md">
                
                {{-- ヘッダー --}}
                <div class="bg-gradient-to-r from-[#59B9C6]/10 to-purple-600/10 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            プラン変更の確認
                        </h3>
                    </div>
                </div>
                
                {{-- フォーム --}}
                <form action="{{ route('subscriptions.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan" id="change-plan-input" value="">
                    
                    {{-- メッセージ --}}
                    <div class="px-6 py-6">
                        <p class="text-base text-gray-700 dark:text-gray-300 mb-4">
                            現在のプラン「<strong>{{ $plans[$currentPlan]['name'] }}</strong>」から<br>
                            「<strong id="new-plan-name"></strong>」に変更しますか？
                        </p>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <strong>注意:</strong> プラン変更は即座に適用されます。料金は日割り計算で調整されます。
                            </p>
                        </div>
                    </div>
                    
                    {{-- ボタン --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex gap-3 justify-end">
                        <button type="button" 
                                data-modal-close
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 transition shadow-sm">
                            キャンセル
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4AA5B2] hover:to-purple-700 transition shadow-lg">
                            このプランに変更
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * プラン変更モーダル制御
 */
(function() {
    'use strict';
    
    const modal = document.getElementById('plan-change-modal');
    const changePlanInput = document.getElementById('change-plan-input');
    const newPlanName = document.getElementById('new-plan-name');
    
    if (!modal || !changePlanInput || !newPlanName) {
        console.warn('[PlanChangeModal] Modal elements not found');
        return;
    }
    
    /**
     * モーダルを開く
     */
    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // フェードインアニメーション
        const overlay = modal.querySelector('[data-modal-close]');
        const card = modal.querySelector('.relative');
        
        requestAnimationFrame(() => {
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 300ms';
            }
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                card.style.transition = 'opacity 300ms, transform 300ms';
            }
            
            requestAnimationFrame(() => {
                if (overlay) overlay.style.opacity = '1';
                if (card) {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }
            });
        });
    }
    
    /**
     * モーダルを閉じる
     */
    function closeModal() {
        const overlay = modal.querySelector('[data-modal-close]');
        const card = modal.querySelector('.relative');
        
        if (overlay) overlay.style.opacity = '0';
        if (card) {
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
    
    // data-plan-change属性を持つボタンでモーダルを開く
    document.querySelectorAll('[data-plan-change]').forEach(btn => {
        btn.addEventListener('click', function() {
            const plan = this.getAttribute('data-plan-change');
            const planName = this.getAttribute('data-plan-name');
            
            if (changePlanInput) {
                changePlanInput.value = plan;
            }
            if (newPlanName) {
                newPlanName.textContent = planName;
            }
            
            openModal();
        });
    });
    
    // data-modal-close属性でモーダルを閉じる
    modal.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });
    
    // Escapeキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
    
    console.log('[PlanChangeModal] Initialized');
})();
</script>
@endpush
