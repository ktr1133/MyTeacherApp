-- ========================================
-- Stripe Price ID 本番環境登録SQL
-- 
-- 実行日: 2025-12-03
-- 目的: Stripe商品ID・価格IDをtoken_packagesテーブルに登録
-- ========================================

BEGIN;

-- 現在の状態確認
SELECT 
    id,
    name,
    token_amount,
    price,
    stripe_price_id,
    stripe_product_id,
    is_active
FROM token_packages
ORDER BY token_amount;

-- 0.5Mトークン (500,000トークン、¥400)
UPDATE token_packages 
SET 
    stripe_price_id = 'price_1Sa6aWCPYj0shj9pkCoLolxi',
    stripe_product_id = 'prod_TXAwz7CtPonuuJ',
    updated_at = CURRENT_TIMESTAMP
WHERE token_amount = 500000
  AND is_active = true;

-- 2.5Mトークン (2,500,000トークン、¥1,800)
UPDATE token_packages 
SET 
    stripe_price_id = 'price_1Sa6bjCPYj0shj9pXFf5t0NG',
    stripe_product_id = 'prod_TXAxIyVqTxCA0V',
    updated_at = CURRENT_TIMESTAMP
WHERE token_amount = 2500000
  AND is_active = true;

-- 5Mトークン (5,000,000トークン、¥3,400)
UPDATE token_packages 
SET 
    stripe_price_id = 'price_1Sa6dRCPYj0shj9pXf5UfRMv',
    stripe_product_id = 'prod_TXAzmWv0fPhXeo',
    updated_at = CURRENT_TIMESTAMP
WHERE token_amount = 5000000
  AND is_active = true;

-- 更新結果確認
SELECT 
    id,
    name,
    token_amount,
    price,
    stripe_price_id,
    stripe_product_id,
    is_active,
    updated_at
FROM token_packages
WHERE stripe_price_id IS NOT NULL
ORDER BY token_amount;

-- 期待結果:
-- 3行が更新され、すべてのパッケージにstripe_price_idが設定されている

-- 問題なければコミット
COMMIT;

-- 問題があればロールバック
-- ROLLBACK;
