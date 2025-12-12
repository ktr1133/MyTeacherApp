/**
 * Getting Started Page - Navigation Scroll Tracker
 * 
 * PC: サイドバーナビゲーションのスクロール連動ハイライト
 * Mobile: アコーディオン型ナビゲーション（sticky固定）
 */

document.addEventListener('DOMContentLoaded', () => {
    // ナビゲーション要素
    const navLinks = document.querySelectorAll('[data-nav-link]');
    const sections = document.querySelectorAll('[data-section]');
    const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');

    if (!navLinks.length || !sections.length) {
        return; // ナビゲーション要素がない場合は終了
    }

    // モバイルナビゲーションのトグル
    if (mobileToggle && mobileNav) {
        mobileToggle.addEventListener('click', () => {
            const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
            mobileToggle.setAttribute('aria-expanded', !isExpanded);
            mobileNav.classList.toggle('hidden');
            
            // アイコン回転
            const icon = mobileToggle.querySelector('svg');
            if (icon) {
                icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
    }

    // スクロール位置に応じてアクティブセクションを検出
    const updateActiveNav = () => {
        let currentSection = '';
        const scrollPosition = window.scrollY + 150; // ヘッダー高さ考慮

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                currentSection = section.getAttribute('id');
            }
        });

        // すべてのナビゲーションリンクを非アクティブ化
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            const targetId = href ? href.substring(1) : '';
            
            if (targetId === currentSection) {
                // アクティブ化
                link.classList.add('nav-active');
                link.classList.remove('nav-inactive');
            } else {
                // 非アクティブ化
                link.classList.remove('nav-active');
                link.classList.add('nav-inactive');
            }
        });
    };

    // スムーズスクロール
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                const headerOffset = 100; // ヘッダー高さ + 余白
                const elementPosition = targetSection.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // モバイルナビゲーションを閉じる
                if (mobileNav && !mobileNav.classList.contains('hidden')) {
                    mobileNav.classList.add('hidden');
                    if (mobileToggle) {
                        mobileToggle.setAttribute('aria-expanded', 'false');
                        const icon = mobileToggle.querySelector('svg');
                        if (icon) {
                            icon.style.transform = 'rotate(0deg)';
                        }
                    }
                }
            }
        });
    });

    // スクロールイベント（パフォーマンス最適化: throttle）
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateActiveNav();
                ticking = false;
            });
            ticking = true;
        }
    });

    // 初期実行
    updateActiveNav();
});
