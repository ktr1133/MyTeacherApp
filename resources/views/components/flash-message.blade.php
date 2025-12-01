{{--
    フラッシュメッセージコンポーネント
    
    セッションに保存された success, error, warning, info メッセージを表示します。
    Alpine.js を使用して自動的にフェードイン/アウトします。
    
    使用方法:
    <x-flash-message />
    
    メッセージの設定:
    return redirect()->route('dashboard')->with('success', 'タスクを更新しました');
    return redirect()->route('dashboard')->with('error', 'エラーが発生しました');
    return redirect()->route('dashboard')->with('warning', '注意が必要です');
    return redirect()->route('dashboard')->with('info', 'お知らせです');
--}}

@php
    $flashMessage = null;
    $flashType = 'success';
    
    if (session()->has('success')) {
        $flashMessage = session('success');
        $flashType = 'success';
    } elseif (session()->has('error')) {
        $flashMessage = session('error');
        $flashType = 'error';
    } elseif (session()->has('warning')) {
        $flashMessage = session('warning');
        $flashType = 'warning';
    } elseif (session()->has('info')) {
        $flashMessage = session('info');
        $flashType = 'info';
    } elseif (isset($errors) && $errors->any()) {
        $flashMessage = $errors->first();
        $flashType = 'error';
    }
@endphp

@if ($flashMessage)
    <div
        x-data="{
            show: false,
            message: {{ Js::from($flashMessage) }},
            type: {{ Js::from($flashType) }},
            
            init() {
                this.showMessage();
            },
            
            showMessage() {
                this.show = true;
                
                // 5秒後に自動的に閉じる
                setTimeout(() => {
                    this.closeMessage();
                }, 5000);
            },
            
            closeMessage() {
                this.show = false;
            },
            
            get bgColor() {
                const colors = {
                    success: 'bg-green-50',
                    error: 'bg-red-50',
                    warning: 'bg-yellow-50',
                    info: 'bg-blue-50'
                };
                return colors[this.type] || colors.success;
            },
            
            get borderColor() {
                const colors = {
                    success: 'border-green-500',
                    error: 'border-red-500',
                    warning: 'border-yellow-500',
                    info: 'border-blue-500'
                };
                return colors[this.type] || colors.success;
            },
            
            get textColor() {
                const colors = {
                    success: 'text-green-800',
                    error: 'text-red-800',
                    warning: 'text-yellow-800',
                    info: 'text-blue-800'
                };
                return colors[this.type] || colors.success;
            },
            
            get iconPath() {
                const paths = {
                    success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                    warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                };
                return paths[this.type] || paths.success;
            }
        }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        class="fixed top-4 right-4 z-50 max-w-sm w-full"
        style="display: none;"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >
        <div
            :class="[bgColor, borderColor, textColor]"
            class="flex items-start p-4 rounded-lg shadow-lg border-l-4"
        >
            <!-- アイコン -->
            <div class="flex-shrink-0">
                <svg
                    class="h-5 w-5"
                    :class="textColor"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                        stroke-width="2" 
                        :d="iconPath"
                    />
                </svg>
            </div>
            
            <!-- メッセージ -->
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium" x-text="message"></p>
            </div>
            
            <!-- 閉じるボタン -->
            <div class="ml-4 flex-shrink-0 flex">
                <button
                    @click="closeMessage()"
                    :class="textColor"
                    class="inline-flex rounded-md hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="{
                        'focus:ring-green-500': type === 'success',
                        'focus:ring-red-500': type === 'error',
                        'focus:ring-yellow-500': type === 'warning',
                        'focus:ring-blue-500': type === 'info'
                    }"
                >
                    <span class="sr-only">閉じる</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endif