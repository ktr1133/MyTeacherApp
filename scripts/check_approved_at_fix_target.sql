-- データメンテナンス対象確認SQL
-- 承認不要グループタスクで completed_at はあるが approved_at がない件数

-- 対象件数確認
SELECT 
    COUNT(*) as target_count,
    '対象: 承認不要・完了済み・グループタスク・approved_atがnull' as description
FROM tasks
WHERE group_task_id IS NOT NULL
  AND requires_approval = false
  AND is_completed = true
  AND approved_at IS NULL
  AND completed_at IS NOT NULL;

-- 対象データのサンプル（最大10件）
SELECT 
    id,
    user_id,
    group_task_id,
    title,
    requires_approval,
    is_completed,
    completed_at,
    approved_at,
    approved_by_user_id,
    created_at
FROM tasks
WHERE group_task_id IS NOT NULL
  AND requires_approval = false
  AND is_completed = true
  AND approved_at IS NULL
  AND completed_at IS NOT NULL
ORDER BY completed_at DESC
LIMIT 10;

-- ユーザーごとの対象件数
SELECT 
    user_id,
    COUNT(*) as task_count
FROM tasks
WHERE group_task_id IS NOT NULL
  AND requires_approval = false
  AND is_completed = true
  AND approved_at IS NULL
  AND completed_at IS NOT NULL
GROUP BY user_id
ORDER BY task_count DESC;
