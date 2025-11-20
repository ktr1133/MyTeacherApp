@push('scripts')
    {{-- ログインイベント発火 --}}
    <script>
        let avatarEventFired = false;
        let dispatchAttempts = 0;
        
        // DOMContentLoadedを直接使用
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('avatar_event'))
                if (avatarEventFired) {
                    console.warn('[Dashboard] Avatar event already fired, skipping');
                    return;
                }
                
                const waitForAlpineAvatar = setInterval(() => {
                    dispatchAttempts++;
                    
                    if (window.Alpine && typeof window.dispatchAvatarEvent === 'function') {
                        clearInterval(waitForAlpineAvatar);
                        avatarEventFired = true;

                        setTimeout(() => {
                            window.dispatchAvatarEvent('{{ session('avatar_event') }}');
                        }, 500);
                    }
                    
                    // 5秒（100回）でタイムアウト
                    if (dispatchAttempts > 100) {
                        clearInterval(waitForAlpineAvatar);
                        console.error('[Dashboard] Alpine initialization timeout', {
                            attempts: dispatchAttempts,
                        });
                    }
                }, 50);
            @endif
        });
    </script>
@endpush