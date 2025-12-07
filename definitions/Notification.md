# 通知機能 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-07 | GitHub Copilot | 初版作成: 通知機能（基本実装）の要件定義 |

---

## 1. 概要

MyTeacher AIタスク管理プラットフォームにおける通知機能は、ユーザーに対してシステムイベント（タスク完了、承認依頼、グループ招待等）を通知し、アプリケーション内で確認・管理できる機能です。Web版・モバイル版の両方で統一されたユーザー体験を提供します。

### 1.1 機能一覧

1. **通知一覧表示機能** - ユーザーの全通知を一覧表示（ページネーション対応）
2. **通知詳細表示機能** - 個別通知の詳細情報表示
3. **未読件数取得機能** - リアルタイムで未読通知数を監視
4. **既読化機能** - 個別通知の既読化・全通知一括既読化
5. **通知検索機能** - キーワード検索（AND/OR演算）
6. **通知ポーリング機能（モバイル）** - 30秒間隔での未読件数監視・自動更新

**注**: Push通知（Firebase Cloud Messaging）は Phase 2.B-7.5で実装予定

### 1.2 対応プラットフォーム

| プラットフォーム | 実装状況 | 認証方式 | 画面 |
|----------------|---------|---------|------|
| **Web** | ✅ 実装済み | セッション + CSRF | `resources/views/notifications/index.blade.php` |
| **モバイル** | ✅ 実装済み | Sanctum（トークン） | `NotificationListScreen.tsx`, `NotificationDetailScreen.tsx` |

---

## 2. 通知一覧表示機能

### 2.1 機能要件

**概要**: ユーザーに関連する全通知を一覧表示し、未読/既読の管理を提供する機能。

**アクセスルート**:
- **Web**: `GET /notifications` → `IndexNotificationAction`
- **モバイル**: `GET /api/notifications` → `IndexNotificationApiAction`

**クエリパラメータ**:

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|-----|------|-----------|------|
| `page` | integer | - | 1 | ページ番号 |
| `per_page` | integer | - | 20 | 1ページあたりの件数 |

**出力項目**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `id` | integer | 通知ID |
| `title` | string | 通知タイトル |
| `message` | text | 通知メッセージ |
| `category` | string | カテゴリ（task, group, token, system） |
| `priority` | integer | 優先度（1=高、2=中、3=低） |
| `is_read` | boolean | 既読フラグ |
| `created_at` | datetime | 作成日時 |
| `read_at` | datetime | 既読日時（null可） |

**処理フロー（Web版）**:
```
1. ユーザー認証確認
2. 通知一覧取得（NotificationManagementService::getUserNotifications）
   - ユーザーIDでフィルタリング
   - ページネーション（20件/ページ）
   - 作成日時降順でソート
3. 未読件数取得（NotificationManagementService::getUnreadCount）
4. Bladeテンプレートに渡してレンダリング
```

**処理フロー（モバイル版）**:
```
1. Sanctum認証確認（auth:sanctumミドルウェア）
2. 通知一覧取得（同上）
3. JSON形式でレスポンス
   {
     "success": true,
     "data": {
       "notifications": [...],
       "unread_count": 5,
       "pagination": {
         "current_page": 1,
         "per_page": 20,
         "total": 50,
         "last_page": 3
       }
     }
   }
```

**画面構成（Web版）**:
- ヘッダー: 通知件数バッジ（`resources/views/dashboard/partials/header.blade.php` L111-128）
- 一覧画面: `resources/views/notifications/index.blade.php`
  - 通知カード（未読/既読の視覚的区別）
  - すべて既読ボタン
  - ページネーション

**画面構成（モバイル版）**:
- 一覧画面: `NotificationListScreen.tsx`
  - 未読件数バッジ
  - 通知カード（タップで詳細画面遷移 + 既読化）
  - すべて既読ボタン
  - Pull-to-Refresh
  - 無限スクロール（次ページ自動ロード）
  - 検索バー（デバウンス処理300ms）

**エラーハンドリング**:
- 未認証（Web）: 302リダイレクト（ログインページへ）
- 未認証（モバイル）: `401 Unauthorized` + `{"message": "Unauthenticated."}`
- ページ番号不正: バリデーションエラー

### 2.2 通知カテゴリとイベント

**通知カテゴリ**:

| カテゴリ | 値 | 説明 | 例 |
|---------|-----|------|-----|
| タスク | `task` | タスク関連イベント | タスク完了、承認依頼、却下通知 |
| グループ | `group` | グループ関連イベント | 招待、メンバー追加、マスター譲渡 |
| トークン | `token` | トークン関連イベント | 残高不足警告、購入完了 |
| システム | `system` | システム通知 | メンテナンス予告、アップデート情報 |

**通知優先度**:

| 優先度 | 値 | 色 | 説明 |
|-------|-----|-----|------|
| 高 | `1` | 赤 | 即時対応が必要（承認依頼、残高不足等） |
| 中 | `2` | 黄 | 確認推奨（タスク完了、グループ招待等） |
| 低 | `3` | 灰 | 情報提供のみ（システム通知等） |

### 2.3 データモデル

**notification_templatesテーブル**:
```sql
CREATE TABLE notification_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL COMMENT '通知タイトル',
    message TEXT NOT NULL COMMENT '通知メッセージ',
    category ENUM('task', 'group', 'token', 'system') NOT NULL COMMENT 'カテゴリ',
    priority TINYINT NOT NULL DEFAULT 2 COMMENT '優先度（1=高、2=中、3=低）',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**user_notificationsテーブル**:
```sql
CREATE TABLE user_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL COMMENT 'ユーザーID',
    notification_template_id BIGINT NOT NULL COMMENT '通知テンプレートID',
    is_read BOOLEAN NOT NULL DEFAULT FALSE COMMENT '既読フラグ',
    read_at TIMESTAMP NULL COMMENT '既読日時',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_template_id) REFERENCES notification_templates(id) ON DELETE CASCADE,
    INDEX idx_user_notifications_user_id (user_id),
    INDEX idx_user_notifications_is_read (is_read),
    INDEX idx_user_notifications_created_at (created_at)
);
```

---

## 3. 通知詳細表示機能

### 3.1 機能要件

**概要**: 個別通知の詳細情報を表示し、未読の場合は自動的に既読化する機能。

**アクセスルート**:
- **Web**: `GET /notifications/{id}` → `ShowNotificationAction`
- **モバイル**: `GET /api/notifications/{id}` → `ShowNotificationApiAction`

**出力項目**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `id` | integer | 通知ID |
| `title` | string | 通知タイトル |
| `message` | text | 通知メッセージ（全文） |
| `category` | string | カテゴリ |
| `priority` | integer | 優先度 |
| `is_read` | boolean | 既読フラグ（自動的にtrueに更新） |
| `created_at` | datetime | 作成日時 |
| `read_at` | datetime | 既読日時（自動更新） |

**処理フロー**:
```
1. ユーザー認証確認
2. 通知取得（NotificationManagementService::getNotification）
   - ユーザーIDと通知IDで検証
3. 未読の場合は既読化（NotificationManagementService::markAsRead）
   - is_read: true
   - read_at: 現在時刻
4. レスポンス返却
   - Web: Bladeテンプレート
   - モバイル: JSON形式
```

**権限チェック**:
- 通知が存在しない: `404 Not Found`
- 他ユーザーの通知にアクセス: `404 Not Found`（権限エラーを隠蔽）

**エラーハンドリング**:
- 未認証: 302リダイレクト（Web）/ 401エラー（モバイル）
- 通知不存在: `404 Not Found` + `{"message": "通知が見つかりません。"}`

### 3.2 画面構成

**Web版**: `resources/views/notifications/show.blade.php`（未実装の場合は一覧画面に統合）

**モバイル版**: `NotificationDetailScreen.tsx`
- タイトル
- カテゴリバッジ
- 優先度インジケーター
- メッセージ本文
- 作成日時・既読日時
- テーマ対応UI（adult/child）

---

## 4. 未読件数取得機能

### 4.1 機能要件

**概要**: ユーザーの未読通知件数をリアルタイムで取得し、バッジ表示に利用する機能。

**アクセスルート**:
- **Web**: `GET /notifications/unread-count` → `GetUnreadCountAction`
- **モバイル**: `GET /api/notifications/unread-count` → `GetUnreadCountApiAction`

**認証方式の分離**:

| プラットフォーム | エンドポイント | 認証方式 | ミドルウェア | ルートファイル |
|----------------|--------------|---------|-------------|--------------|
| **モバイル** | `/api/notifications/unread-count` | Sanctum（トークン） | `auth:sanctum` | `routes/api.php` L192-199 |
| **Web** | `/notifications/unread-count` | セッション + CSRF | `web`, `auth` | `routes/web.php` L375-383 |

**エンドポイント分離の経緯**:
- 2025-12-07: Web用エンドポイントが`routes/web.php`に誤配置され、`web`ミドルウェア（セッション認証）が適用
- モバイルアプリで401エラー発生（トークン認証が通らない）
- **解決策**: エンドポイントを認証方式ごとに分離し、`routes/api.php`に経緯コメント付きで配置

**出力項目**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `count` | integer | 未読通知件数 |

**処理フロー**:
```
1. ユーザー認証確認
2. 未読件数取得（NotificationManagementService::getUnreadCount）
   - user_id: 認証ユーザーのID
   - is_read: false
3. レスポンス返却
   - Web: JSON形式 {"count": 5}
   - モバイル: JSON形式 {"count": 5}
```

**レスポンス例**:
```json
{
  "count": 5
}
```

**エラーハンドリング**:
- 未認証（モバイル）: `401 Unauthorized` + ポーリング停止（後述）
- 未認証（Web）: `401 Unauthorized`（JavaScript側で処理）

### 4.2 ポーリング処理（モバイル専用）

**概要**: 30秒間隔で未読件数を監視し、新着通知がある場合は通知一覧を自動更新する機能。

**実装場所**: `mobile/src/hooks/useNotifications.ts`

**ポーリング仕様**:

| 項目 | 値 | 説明 |
|------|-----|------|
| **間隔** | 30秒 | `setInterval(callback, 30000)` |
| **条件** | 認証済み + ポーリング有効化 | `isAuthenticated && enablePolling` |
| **停止条件** | 401エラー、ログアウト | 無限リトライ防止 |
| **挙動** | 未読件数増加時に通知一覧再取得 | `newUnreadCount > unreadCount` |

**処理フロー**:
```typescript
const startPolling = useCallback(() => {
  pollingIntervalRef.current = setInterval(async () => {
    // 1. 認証状態チェック
    if (!isAuthenticated) {
      stopPolling();
      return;
    }

    try {
      // 2. 未読件数取得（バックグラウンド処理）
      const response = await notificationService.getUnreadCount();
      const newUnreadCount = response.count;

      // 3. 未読件数が増えた場合は通知一覧を再取得
      if (newUnreadCount > unreadCount) {
        await fetchNotifications(1); // 1ページ目を取得
      } else {
        setUnreadCount(newUnreadCount); // 件数のみ更新
      }
    } catch (err: any) {
      // 4. 401エラーの場合はポーリング停止（トークン無効化対策）
      if (err?.response?.status === 401) {
        console.error('[Polling] 認証エラー（401）- ポーリング停止');
        stopPolling();
      }
      // その他のエラーは無視（ネットワークエラー等）
    }
  }, 30000); // 30秒間隔
}, [unreadCount, fetchNotifications, isAuthenticated, stopPolling]);
```

**認証エラー対策**:
- 401エラー時にポーリング停止（無限リトライ防止）
- トークン無効化検知後はログアウト画面に遷移（`AuthContext`で処理）
- エラーログを記録（デバッグ用）

**ライフサイクル管理**:
```typescript
useEffect(() => {
  if (enablePolling && isAuthenticated) {
    startPolling();
  }
  
  return () => {
    stopPolling(); // コンポーネントアンマウント時に停止
  };
}, [enablePolling, isAuthenticated, startPolling, stopPolling]);
```

**Web版ポーリング**:
- 実装場所: `resources/js/common/notification-polling.js` L148
- エンドポイント: `/notifications/unread-count`（`/api`プレフィックスなし）
- 間隔: 30秒
- 認証方式: セッション認証

---

## 5. 既読化機能

### 5.1 個別既読化

**概要**: 特定の通知を既読状態に変更する機能。

**アクセスルート**:
- **Web**: `POST /notifications/{id}/read` → `MarkNotificationAsReadAction`
- **モバイル**: `POST /api/notifications/{id}/read` → `MarkNotificationAsReadApiAction`

**処理フロー**:
```
1. ユーザー認証確認
2. 通知の所有権確認（NotificationManagementService::verifyOwnership）
3. 既読化処理（NotificationManagementService::markAsRead）
   - is_read: true
   - read_at: 現在時刻
4. キャッシュクリア（未読件数）
5. 成功メッセージ返却
```

**レスポンス**:
```json
{
  "success": true,
  "message": "通知を既読にしました。"
}
```

**エラーハンドリング**:
- 通知不存在: `404 Not Found` + `{"message": "通知が見つかりません。"}`
- 権限なし: `403 Forbidden`（内部的には404として扱う）

### 5.2 全既読化

**概要**: ユーザーの全未読通知を一括で既読状態に変更する機能。

**アクセスルート**:
- **Web**: `POST /notifications/read-all` → `MarkAllNotificationsAsReadAction`
- **モバイル**: `POST /api/notifications/read-all` → `MarkAllNotificationsAsReadApiAction`

**処理フロー**:
```
1. ユーザー認証確認
2. 全未読通知を既読化（NotificationManagementService::markAllAsRead）
   - WHERE user_id = {認証ユーザー} AND is_read = false
   - UPDATE is_read = true, read_at = NOW()
3. キャッシュクリア（未読件数、通知一覧）
4. 成功メッセージ返却
```

**レスポンス**:
```json
{
  "success": true,
  "message": "すべての通知を既読にしました。",
  "data": {
    "updated_count": 5
  }
}
```

**UI配置**:
- **Web**: 通知一覧画面のヘッダー
- **モバイル**: `NotificationListScreen`のヘッダー（すべて既読ボタン）

---

## 6. 通知検索機能

### 6.1 機能要件

**概要**: 通知タイトル・メッセージをキーワード検索する機能（AND/OR演算対応）。

**アクセスルート**:
- **Web**: `GET /notifications/search` → `SearchNotificationsAction`
- **モバイル**: `GET /api/notifications/search` → `SearchNotificationsApiAction`

**クエリパラメータ**:

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|-----|------|-----------|------|
| `keywords` | array | ✓ | - | 検索キーワード配列 |
| `operator` | string | - | `AND` | 演算子（`AND`/`OR`） |
| `page` | integer | - | 1 | ページ番号 |
| `per_page` | integer | - | 20 | 1ページあたりの件数 |

**処理フロー**:
```
1. ユーザー認証確認
2. バリデーション
   - keywords: 必須、配列
   - operator: AND または OR
3. 検索実行（NotificationManagementService::searchNotifications）
   - ユーザーIDでフィルタリング
   - キーワードでタイトル・メッセージを検索
   - AND/OR演算適用
4. ページネーション適用（20件/ページ）
5. レスポンス返却
```

**検索ロジック**:

**AND検索**（デフォルト）:
```sql
WHERE user_id = {認証ユーザー}
  AND (
    (title LIKE '%keyword1%' OR message LIKE '%keyword1%')
    AND (title LIKE '%keyword2%' OR message LIKE '%keyword2%')
    AND (title LIKE '%keyword3%' OR message LIKE '%keyword3%')
  )
```

**OR検索**:
```sql
WHERE user_id = {認証ユーザー}
  AND (
    title LIKE '%keyword1%' OR message LIKE '%keyword1%'
    OR title LIKE '%keyword2%' OR message LIKE '%keyword2%'
    OR title LIKE '%keyword3%' OR message LIKE '%keyword3%'
  )
```

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "notifications": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 3,
      "last_page": 1
    }
  }
}
```

**エラーハンドリング**:
- キーワード未指定: `400 Bad Request` + `{"message": "検索キーワードを指定してください。"}`
- 演算子不正: `400 Bad Request` + `{"message": "演算子はANDまたはORを指定してください。"}`

**UI実装**:
- **モバイル**: `NotificationListScreen`の検索バー
  - デバウンス処理: 300ms
  - クリアボタン付き
  - 検索中はローディング表示

---

## 7. モバイルアプリ実装詳細

### 7.1 画面一覧

| 画面名 | ファイル | 説明 |
|-------|---------|------|
| 通知一覧 | `NotificationListScreen.tsx` | 通知一覧、検索、全既読化 |
| 通知詳細 | `NotificationDetailScreen.tsx` | 通知詳細、自動既読化 |

### 7.2 コンポーネント構成

**NotificationListScreen.tsx**:
```typescript
export default function NotificationListScreen({ navigation }: any) {
  const {
    notifications,
    unreadCount,
    loading,
    error,
    hasMore,
    fetchNotifications,
    markAllAsRead,
    searchNotifications,
    loadMore,
    refresh,
  } = useNotifications(true); // ポーリング有効化

  const [searchQuery, setSearchQuery] = useState('');

  // 検索処理（デバウンス300ms）
  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchQuery.trim()) {
        searchNotifications([searchQuery.trim()], 'OR');
      } else {
        fetchNotifications(1);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  // 通知タップ時の処理
  const handleNotificationPress = (notification: any) => {
    navigation.navigate('NotificationDetail', { id: notification.id });
  };

  // すべて既読ボタン
  const handleMarkAllAsRead = async () => {
    await markAllAsRead();
  };

  return (
    <View>
      {/* 検索バー */}
      <TextInput
        value={searchQuery}
        onChangeText={setSearchQuery}
        placeholder="通知を検索"
      />

      {/* 未読件数バッジ */}
      {unreadCount > 0 && (
        <Text>未読: {unreadCount}件</Text>
      )}

      {/* すべて既読ボタン */}
      <Button title="すべて既読" onPress={handleMarkAllAsRead} />

      {/* 通知一覧 */}
      <FlatList
        data={notifications}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <TouchableOpacity onPress={() => handleNotificationPress(item)}>
            <NotificationCard notification={item} />
          </TouchableOpacity>
        )}
        refreshControl={
          <RefreshControl refreshing={loading} onRefresh={refresh} />
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
      />
    </View>
  );
}
```

**NotificationDetailScreen.tsx**:
```typescript
export default function NotificationDetailScreen({ route, navigation }: any) {
  const { id } = route.params;
  const [notification, setNotification] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadNotification();
  }, [id]);

  const loadNotification = async () => {
    try {
      setLoading(true);
      const response = await notificationService.getNotificationDetail(id);
      setNotification(response.data.notification);

      // 未読の場合は既読化（自動）
      if (!response.data.notification.is_read) {
        await notificationService.markAsRead(id);
      }
    } catch (err) {
      console.error('通知取得エラー:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <Loading />;

  return (
    <ScrollView>
      <Text style={styles.title}>{notification.title}</Text>
      <Badge category={notification.category} />
      <PriorityIndicator priority={notification.priority} />
      <Text style={styles.message}>{notification.message}</Text>
      <Text style={styles.date}>
        {new Date(notification.created_at).toLocaleString('ja-JP')}
      </Text>
    </ScrollView>
  );
}
```

### 7.3 カスタムフック

**useNotifications.ts**:
```typescript
export function useNotifications(enablePolling: boolean = false) {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);

  const { isAuthenticated } = useAuth();
  const pollingIntervalRef = useRef<NodeJS.Timeout | null>(null);

  // 通知一覧取得
  const fetchNotifications = useCallback(async (page: number = 1) => {
    setLoading(true);
    try {
      const response = await notificationService.getNotifications(page);
      setNotifications(page === 1 ? response.data.notifications : [
        ...notifications,
        ...response.data.notifications,
      ]);
      setUnreadCount(response.data.unread_count);
      setHasMore(response.data.pagination.current_page < response.data.pagination.last_page);
      setCurrentPage(page);
    } catch (err) {
      setError('通知の取得に失敗しました');
    } finally {
      setLoading(false);
    }
  }, [notifications]);

  // 未読件数ポーリング（30秒間隔）
  const startPolling = useCallback(() => {
    pollingIntervalRef.current = setInterval(async () => {
      if (!isAuthenticated) {
        stopPolling();
        return;
      }

      try {
        const response = await notificationService.getUnreadCount();
        const newUnreadCount = response.count;

        if (newUnreadCount > unreadCount) {
          await fetchNotifications(1);
        } else {
          setUnreadCount(newUnreadCount);
        }
      } catch (err: any) {
        if (err?.response?.status === 401) {
          stopPolling();
        }
      }
    }, 30000);
  }, [unreadCount, fetchNotifications, isAuthenticated]);

  const stopPolling = useCallback(() => {
    if (pollingIntervalRef.current) {
      clearInterval(pollingIntervalRef.current);
      pollingIntervalRef.current = null;
    }
  }, []);

  // 全既読化
  const markAllAsRead = useCallback(async () => {
    try {
      await notificationService.markAllAsRead();
      setUnreadCount(0);
      await fetchNotifications(1);
    } catch (err) {
      setError('既読化に失敗しました');
    }
  }, [fetchNotifications]);

  // 検索
  const searchNotifications = useCallback(async (
    keywords: string[],
    operator: 'AND' | 'OR' = 'AND'
  ) => {
    setLoading(true);
    try {
      const response = await notificationService.searchNotifications(keywords, operator);
      setNotifications(response.data.notifications);
      setHasMore(false); // 検索結果はページネーションなし
    } catch (err) {
      setError('検索に失敗しました');
    } finally {
      setLoading(false);
    }
  }, []);

  // ポーリング開始/停止
  useEffect(() => {
    if (enablePolling && isAuthenticated) {
      startPolling();
    }

    return () => {
      stopPolling();
    };
  }, [enablePolling, isAuthenticated, startPolling, stopPolling]);

  return {
    notifications,
    unreadCount,
    loading,
    error,
    hasMore,
    fetchNotifications,
    markAllAsRead,
    searchNotifications,
    loadMore: () => fetchNotifications(currentPage + 1),
    refresh: () => fetchNotifications(1),
  };
}
```

### 7.4 サービス層

**notification.service.ts**:
```typescript
import api from './api';

export const notificationService = {
  // 通知一覧取得
  getNotifications: async (page: number = 1, perPage: number = 20) => {
    const response = await api.get('/notifications', {
      params: { page, per_page: perPage },
    });
    return response.data;
  },

  // 通知詳細取得
  getNotificationDetail: async (id: number) => {
    const response = await api.get(`/notifications/${id}`);
    return response.data;
  },

  // 未読件数取得
  getUnreadCount: async () => {
    const response = await api.get('/notifications/unread-count');
    return response.data;
  },

  // 個別既読化
  markAsRead: async (id: number) => {
    const response = await api.post(`/notifications/${id}/read`);
    return response.data;
  },

  // 全既読化
  markAllAsRead: async () => {
    const response = await api.post('/notifications/read-all');
    return response.data;
  },

  // 通知検索
  searchNotifications: async (keywords: string[], operator: 'AND' | 'OR' = 'AND') => {
    const response = await api.get('/notifications/search', {
      params: { keywords, operator },
    });
    return response.data;
  },
};
```

### 7.5 型定義

**notification.types.ts**:
```typescript
export interface Notification {
  id: number;
  title: string;
  message: string;
  category: 'task' | 'group' | 'token' | 'system';
  priority: 1 | 2 | 3;
  is_read: boolean;
  created_at: string;
  read_at: string | null;
}

export interface NotificationListResponse {
  success: boolean;
  data: {
    notifications: Notification[];
    unread_count: number;
    pagination: {
      current_page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
}

export interface NotificationDetailResponse {
  success: boolean;
  data: {
    notification: Notification;
  };
}

export interface UnreadCountResponse {
  count: number;
}
```

---

## 8. テスト仕様

### 8.1 単体テスト（Laravel）

**テストファイル**: `tests/Feature/Api/Notification/NotificationApiTest.php`

**テストケース**:
1. ✅ 通知一覧を取得できること
2. ✅ 通知詳細を取得できること（自動既読化確認）
3. ✅ 他ユーザーの通知は取得できないこと（404）
4. ✅ 通知を既読化できること
5. ✅ 全通知を既読化できること
6. ✅ 未読通知件数を取得できること
7. ✅ AND検索で通知を検索できること
8. ✅ OR検索で通知を検索できること
9. ✅ 検索キーワードが空の場合は400エラー
10. ✅ 演算子が不正な場合は400エラー

**実行コマンド**:
```bash
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Api/Notification/NotificationApiTest.php
```

### 8.2 統合テスト（モバイル）

**テストファイル**:
- `mobile/__tests__/hooks/useNotifications.test.ts`
- `mobile/__tests__/screens/NotificationListScreen.test.tsx`
- `mobile/__tests__/screens/NotificationDetailScreen.test.tsx`

**テストケース**:
1. ✅ 通知一覧を取得できること
2. ✅ 通知詳細画面で自動既読化されること
3. ✅ すべて既読ボタンが機能すること
4. ✅ 検索機能が動作すること（デバウンス確認）
5. ✅ Pull-to-Refreshが機能すること
6. ✅ 無限スクロールが機能すること
7. ✅ ポーリング処理が30秒間隔で実行されること
8. ✅ 401エラー時にポーリングが停止すること

**実行コマンド**:
```bash
cd /home/ktr/mtdev/mobile
npm test -- __tests__/hooks/useNotifications.test.ts
npm test -- __tests__/screens/NotificationListScreen.test.tsx
```

---

## 9. パフォーマンス要件

### 9.1 応答時間

| エンドポイント | 目標 | 最大 | 備考 |
|--------------|------|------|------|
| 通知一覧取得 | < 200ms | < 500ms | ページネーション必須 |
| 通知詳細取得 | < 100ms | < 300ms | 単一レコード取得 |
| 未読件数取得 | < 50ms | < 100ms | ポーリングで頻繁に呼ばれる |
| 既読化処理 | < 100ms | < 300ms | キャッシュクリア含む |

### 9.2 最適化施策

1. **インデックス設計**:
   - `user_notifications.user_id`: ユーザー絞り込み
   - `user_notifications.is_read`: 未読件数集計
   - `user_notifications.created_at`: ソート処理

2. **キャッシュ戦略**:
   - 未読件数: Redis（TTL: 60秒）
   - 通知一覧: キャッシュなし（リアルタイム性重視）

3. **ページネーション**:
   - 1ページあたり20件（固定）
   - オフセットベースのページネーション

4. **N+1問題対策**:
   - `with(['template'])` でEager Loading

---

## 10. セキュリティ要件

### 10.1 認証・認可

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **認証** | 全エンドポイントで必須 | `auth:sanctum`（モバイル）、`auth`（Web） |
| **権限チェック** | 通知所有者のみアクセス可能 | Service層で`user_id`検証 |
| **CSRF保護** | Web版で必須 | `web`ミドルウェア |

### 10.2 データ保護

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **個人情報保護** | 他ユーザーの通知にアクセス不可 | 404エラー（権限エラーを隠蔽） |
| **SQLインジェクション対策** | パラメータバインディング必須 | Eloquent ORM使用 |
| **XSS対策** | ユーザー入力のエスケープ | Blade `{{ }}`, React自動エスケープ |

---

## 11. 今後の拡張予定

### 11.1 Phase 2.B-7.5: Push通知実装（予定）

**実装内容**:
- Firebase Cloud Messaging（FCM）統合
- デバイストークン管理
- プッシュ通知送信（バックグラウンド配信）
- 通知タップ時のディープリンク

**関連ドキュメント**: `docs/plans/phase2-mobile-app-implementation-plan.md` Phase 2.B-7.5

### 11.2 将来的な機能拡張

- 通知カテゴリ別フィルタリング
- 通知の優先度ソート
- 通知の削除機能
- 通知テンプレートのカスタマイズ
- 通知設定画面（カテゴリ別ON/OFF）

---

## 12. 参考資料

### 12.1 関連ドキュメント

| ドキュメント | パス | 説明 |
|------------|------|------|
| モバイル開発規則 | `docs/mobile/mobile-rules.md` | モバイルアプリ開発の総合規則 |
| OpenAPI仕様 | `docs/api/openapi.yaml` | 通知API仕様定義 |
| 実装計画 | `docs/plans/phase2-mobile-app-implementation-plan.md` | Phase 2.B-5実装計画 |
| 完了レポート | `docs/reports/mobile/2025-12-07-phase2-b5-step2-notification-completion-report.md` | 実装完了報告書 |

### 12.2 関連ファイル

**Laravel（バックエンド）**:
- `routes/api.php` L192-199: モバイル用エンドポイント
- `routes/web.php` L375-383: Web用エンドポイント
- `app/Http/Actions/Notification/`: 通知関連Action
- `app/Services/Notification/`: 通知管理Service
- `app/Repositories/Notification/`: 通知Repository
- `tests/Feature/Api/Notification/NotificationApiTest.php`: APIテスト

**モバイル（React Native）**:
- `mobile/src/screens/notifications/NotificationListScreen.tsx`: 通知一覧画面
- `mobile/src/screens/notifications/NotificationDetailScreen.tsx`: 通知詳細画面
- `mobile/src/hooks/useNotifications.ts`: 通知Hook
- `mobile/src/services/notification.service.ts`: 通知Service
- `mobile/src/types/notification.types.ts`: 通知型定義
- `mobile/__tests__/hooks/useNotifications.test.ts`: Hookテスト

**Web（Laravel Blade）**:
- `resources/views/notifications/index.blade.php`: 通知一覧画面
- `resources/views/dashboard/partials/header.blade.php` L111-128: 通知ボタン
- `resources/js/common/notification-polling.js` L148: ポーリング処理

---

**作成日**: 2025-12-07  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**バージョン**: 1.0
