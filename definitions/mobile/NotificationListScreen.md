# 通知一覧画面 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-07 | GitHub Copilot | 初版作成: Phase 2.B-5 Step 2 完了後の実装内容を要件化 |

---

## 1. 概要

MyTeacherモバイルアプリの通知一覧画面（NotificationListScreen）の機能要件を定義します。本画面は通知の一覧表示、未読管理、検索機能、リアルタイムポーリングを提供します。

### 1.1 対応フェーズ

- **Phase 2.B-5 Step 2**: 通知機能基本実装（2025-12-07完了）
- **Phase 2.B-7.5**: Push通知実装（予定）

### 1.2 関連画面

- **Webアプリ**: 通知一覧（`resources/views/notifications/index.blade.php`）
- **モバイルアプリ**: NotificationListScreen、NotificationDetailScreen

---

## 2. 画面仕様

### 2.1 画面構成

```
┌─────────────────────────────────────┐
│ ヘッダー                             │
│ ┌─────────────┐  未読: 5件  [既読]  │
│ │ 通知         │                     │
│ └─────────────┘                     │
├─────────────────────────────────────┤
│ 検索バー                             │
│ ┌─────────────────────────────┐     │
│ │ 🔍 通知を検索...           [✕]│     │
│ └─────────────────────────────┘     │
├─────────────────────────────────────┤
│ 通知カード一覧（未読は背景色あり）     │
│ ┌─────────────────────────────┐     │
│ │ 🔴 [タイトル]        [優先度H]│     │
│ │ [メッセージ（2行まで）]       │     │
│ │ [カテゴリバッジ] 2025/12/07  │     │
│ └─────────────────────────────┘     │
│ ┌─────────────────────────────┐     │
│ │ ⚪ [タイトル（既読）]  [優先度M]│     │
│ │ [メッセージ（2行まで）]       │     │
│ │ [カテゴリバッジ] 2025/12/06  │     │
│ └─────────────────────────────┘     │
│                                     │
│ 【Pull-to-Refresh対応】              │
│ 【無限スクロール対応】                │
└─────────────────────────────────────┘
```

### 2.2 表示要素

#### 2.2.1 ヘッダー

| 要素 | 仕様 |
|------|------|
| タイトル | テーマ別: 「おしらせ」（子ども）/ 「通知」（大人） |
| 未読件数バッジ | 「未読: 5件」（未読がある場合のみ表示） |
| すべて既読ボタン | 「ぜんぶよんだ」（子ども）/ 「すべて既読」（大人） |

#### 2.2.2 検索バー

| 要素 | 仕様 |
|------|------|
| プレースホルダー | 「通知を検索」 |
| クリアボタン | 検索文字列入力時に [✕] ボタン表示、タップで検索クリア |
| デバウンス処理 | 300ms（入力停止後に検索実行） |
| 検索対象 | タイトル、メッセージ |
| 検索方式 | バックエンドAPI検索（`/api/notifications/search`） |

#### 2.2.3 通知カード

| 要素 | 仕様 | 表示条件 |
|------|------|---------|
| 未読インジケーター | 🔴（赤丸）/ ⚪（灰色丸） | 必須 |
| 背景色 | 未読: 薄い青色 / 既読: 白色 | 必須 |
| タイトル | 通知タイトル（1行、太字） | 必須 |
| 優先度バッジ | H（赤）/ M（黄）/ L（灰） | 必須 |
| メッセージ | メッセージ本文（2行まで表示） | 必須 |
| カテゴリバッジ | タスク / グループ / トークン / システム | 必須 |
| 作成日時 | 「2025/12/07 14:30」形式 | 必須 |

### 2.3 データ取得仕様

#### 2.3.1 API仕様

| 項目 | 仕様 |
|------|------|
| エンドポイント | `GET /api/notifications?page={page}` |
| パラメータ | `page`（ページ番号）、`per_page`（20件固定） |
| ページネーション | 20件/ページ |
| 認証方式 | Sanctum（トークン認証） |
| レスポンス | `{notifications: [], unread_count: 5, pagination: {}}` |

#### 2.3.2 取得データ項目

| 項目 | 型 | 説明 |
|------|-----|------|
| id | number | 通知ID |
| title | string | 通知タイトル |
| message | string | 通知メッセージ（本文） |
| category | 'task' \| 'group' \| 'token' \| 'system' | カテゴリ |
| priority | 1 \| 2 \| 3 | 優先度（1=高、2=中、3=低） |
| is_read | boolean | 既読フラグ |
| created_at | string | 作成日時（ISO 8601） |
| read_at | string \| null | 既読日時（ISO 8601） |

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "title": "タスクが承認されました",
        "message": "「宿題をする」が承認されました。",
        "category": "task",
        "priority": 2,
        "is_read": false,
        "created_at": "2025-12-07T14:30:00+09:00",
        "read_at": null
      }
    ],
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

---

## 3. 機能要件

### 3.1 通知一覧表示

#### 3.1.1 表示仕様

- **表示順序**: 作成日時降順（新しい通知が上）
- **未読優先**: なし（時系列順のみ）
- **ページネーション**: 20件/ページ、無限スクロール対応

#### 3.1.2 未読/既読の視覚的区別

| 状態 | 背景色 | インジケーター | フォント |
|------|--------|--------------|---------|
| 未読 | 薄い青色（#E3F2FD） | 🔴 赤丸 | 太字 |
| 既読 | 白色（#FFFFFF） | ⚪ 灰色丸 | 通常 |

### 3.2 未読件数表示・ポーリング

#### 3.2.1 未読件数取得

| 項目 | 仕様 |
|------|------|
| エンドポイント | `GET /api/notifications/unread-count` |
| 認証方式 | Sanctum（トークン認証） |
| レスポンス | `{count: 5}` |
| 表示位置 | ヘッダー右側「未読: 5件」 |

#### 3.2.2 リアルタイムポーリング

| 項目 | 仕様 |
|------|------|
| **ポーリング間隔** | 30秒 |
| **開始条件** | 画面表示時 + 認証済み |
| **停止条件** | 画面非表示時 / ログアウト時 / 401エラー時 |
| **挙動** | 未読件数が増加した場合は通知一覧を自動再取得 |

**ポーリング処理フロー**:
```
1. 30秒ごとに /api/notifications/unread-count を呼び出し
2. 新しい未読件数を取得
3. 未読件数が増加した場合:
   - 通知一覧を1ページ目から再取得
   - 未読件数バッジを更新
4. 未読件数が変わらない場合:
   - 未読件数バッジのみ更新
5. 401エラーの場合:
   - ポーリング停止
   - ログアウト処理（AuthContextで実施）
```

**実装場所**: `mobile/src/hooks/useNotifications.ts`

**注意事項**:
- バックグラウンドポーリング（アプリ非表示時）は実装しない
- Push通知はPhase 2.B-7.5で実装予定

### 3.3 通知詳細遷移・既読化

#### 3.3.1 遷移仕様

| 項目 | 仕様 |
|------|------|
| トリガー | 通知カードタップ |
| 遷移先 | NotificationDetailScreen |
| パラメータ | `{id: number}` |
| ナビゲーション | Stack Navigator |
| 既読化 | タップ時に既読化処理実行（非同期） |

#### 3.3.2 既読化API

| 項目 | 仕様 |
|------|------|
| エンドポイント | `POST /api/notifications/{id}/read` |
| タイミング | 通知カードタップ時（詳細画面遷移前） |
| エラーハンドリング | 失敗してもエラー表示しない（UX優先） |
| UI更新 | 既読化成功後、カードの背景色・インジケーターを即座に更新 |

### 3.4 すべて既読化

#### 3.4.1 機能仕様

| 項目 | 仕様 |
|------|------|
| エンドポイント | `POST /api/notifications/read-all` |
| トリガー | ヘッダーの「すべて既読」ボタンタップ |
| 確認ダイアログ | なし（即座に実行） |
| UI更新 | 成功後、全通知カードを既読状態に更新 |

**レスポンス例**:
```json
{
  "success": true,
  "message": "すべての通知を既読にしました。",
  "data": {
    "updated_count": 5
  }
}
```

### 3.5 通知検索機能

#### 3.5.1 検索仕様

| 項目 | 仕様 |
|------|------|
| エンドポイント | `GET /api/notifications/search?keywords[]={query}&operator=OR` |
| デバウンス処理 | 300ms（入力停止後に検索実行） |
| 検索対象 | タイトル、メッセージ |
| 検索演算子 | OR（デフォルト） |
| ページネーション | 検索結果はページネーションなし（全件表示） |

**クエリパラメータ例**:
```
GET /api/notifications/search?keywords[]=タスク&operator=OR
```

#### 3.5.2 検索結果表示

- **0件時**: 「検索結果がありません」
- **件数表示**: なし（検索結果数は表示しない）
- **クリア**: 検索バーの [✕] ボタンで通常一覧に戻る

### 3.6 Pull-to-Refresh

| 項目 | 仕様 |
|------|------|
| トリガー | 画面を下にスワイプ |
| 処理 | 通知一覧を1ページ目から再取得 |
| ローディング | RefreshControl標準UI |

### 3.7 無限スクロール

| 項目 | 仕様 |
|------|------|
| トリガー | リストの最下部に到達時 |
| 処理 | 次ページの通知を追加取得 |
| 終了条件 | `pagination.current_page >= pagination.last_page` |
| ローディング | リスト下部にActivityIndicator表示 |

---

## 4. UI/UXガイドライン

### 4.1 テーマ対応

#### 4.1.1 子どもテーマ

| 要素 | 仕様 |
|------|------|
| タイトル | 「おしらせ」 |
| 検索プレースホルダー | 「さがす」 |
| すべて既読ボタン | 「ぜんぶよんだ」 |
| 未読件数 | 「よんでないおしらせ: 5こ」 |
| カテゴリ | タスク → 「やること」、グループ → 「なかま」 |
| 空状態 | 「おしらせがないよ！」 |

#### 4.1.2 大人テーマ

| 要素 | 仕様 |
|------|------|
| タイトル | 「通知」 |
| 検索プレースホルダー | 「通知を検索」 |
| すべて既読ボタン | 「すべて既読」 |
| 未読件数 | 「未読: 5件」 |
| カテゴリ | タスク / グループ / トークン / システム |
| 空状態 | 「通知がありません」 |

### 4.2 カテゴリ別バッジデザイン

| カテゴリ | 背景色 | テキスト | アイコン |
|---------|--------|---------|---------|
| タスク | 青色（#2196F3） | 白 | 📋 |
| グループ | 緑色（#4CAF50） | 白 | 👥 |
| トークン | オレンジ色（#FF9800） | 白 | 💰 |
| システム | 灰色（#9E9E9E） | 白 | ⚙️ |

### 4.3 優先度別バッジデザイン

| 優先度 | 値 | 背景色 | テキスト | 表示 |
|-------|-----|--------|---------|------|
| 高 | 1 | 赤色（#F44336） | 白 | H |
| 中 | 2 | 黄色（#FFC107） | 黒 | M |
| 低 | 3 | 灰色（#9E9E9E） | 白 | L |

### 4.4 レスポンシブ対応

- **リスト表示**: FlatList使用、スクロール最適化
- **タップ領域**: 最小44x44pt（iOS Human Interface Guidelines準拠）
- **カードの高さ**: 動的（メッセージの長さに応じて調整）

---

## 5. データベーススキーマ対応

### 5.1 使用テーブル

#### 5.1.1 notification_templatesテーブル

| カラム名 | 型 | 説明 | 使用箇所 |
|---------|-----|------|---------|
| id | integer | 通知テンプレートID | リレーション |
| title | string | 通知タイトル | 通知カード |
| message | text | 通知メッセージ | 通知カード |
| category | enum | カテゴリ | バッジ表示 |
| priority | integer | 優先度 | バッジ表示 |

#### 5.1.2 user_notificationsテーブル

| カラム名 | 型 | 説明 | 使用箇所 |
|---------|-----|------|---------|
| id | integer | 通知ID | 一覧表示、詳細遷移 |
| user_id | integer | ユーザーID | フィルター条件 |
| notification_template_id | integer | テンプレートID | リレーション |
| is_read | boolean | 既読フラグ | 未読判定、背景色 |
| read_at | timestamp | 既読日時 | 表示用 |
| created_at | timestamp | 作成日時 | ソート、表示 |

**リレーション**: `user_notifications.notification_template_id` → `notification_templates.id`

---

## 6. エラーハンドリング

### 6.1 エラーパターン

| エラー種別 | 原因 | 対応 |
|-----------|------|------|
| ネットワークエラー | 通信失敗 | アラート表示、リトライ可能 |
| 401エラー | 認証切れ | ポーリング停止 → ログアウト → ログイン画面遷移 |
| 404エラー | 通知不存在 | 「通知が見つかりません」アラート表示 |
| 500エラー | サーバーエラー | アラート表示、技術サポート誘導 |
| 通知0件 | データなし | 空状態表示 |

### 6.2 エラーメッセージ

| テーマ | メッセージ |
|-------|-----------|
| 子ども | 「おしらせがよめなかったよ！もういちどためしてね」 |
| 大人 | 「通知の取得に失敗しました。再度お試しください。」 |

### 6.3 401エラー特別処理

**重要**: 401エラーは認証トークンの無効化を示すため、特別な処理が必要です。

**処理フロー**:
```
1. ポーリング中に401エラーを検知
2. ポーリングを即座に停止
3. エラーログを記録（デバッグ用）
4. AuthContextの認証状態を無効化
5. ログアウト処理実行
6. ログイン画面に遷移
```

**実装場所**: `mobile/src/hooks/useNotifications.ts` のポーリング処理

---

## 7. パフォーマンス要件

### 7.1 応答時間目標

| 項目 | 目標 | 最大 |
|------|------|------|
| 一覧取得 | < 200ms | < 500ms |
| 未読件数取得 | < 50ms | < 100ms |
| 既読化処理 | < 100ms | < 300ms |
| 検索処理 | < 200ms | < 500ms |

### 7.2 最適化施策

1. **FlatList最適化**:
   - `windowSize`: 10（デフォルト21から削減）
   - `removeClippedSubviews`: true
   - `maxToRenderPerBatch`: 10

2. **ポーリング最適化**:
   - バックグラウンド時はポーリング停止
   - 401エラー時は即座に停止（無限リトライ防止）

3. **キャッシュ戦略**:
   - 未読件数: ローカルステート保持（30秒間隔で更新）
   - 通知一覧: キャッシュなし（リアルタイム性重視）

---

## 8. セキュリティ要件

### 8.1 認証・認可

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **認証** | Sanctum（トークン認証） | `Authorization: Bearer {token}` ヘッダー |
| **権限チェック** | 通知所有者のみアクセス可能 | バックエンドで`user_id`検証 |
| **トークン保護** | AsyncStorageに暗号化保存 | `@react-native-async-storage/async-storage` |

### 8.2 データ保護

| 項目 | 要件 | 実装方法 |
|------|------|---------|
| **個人情報保護** | 他ユーザーの通知にアクセス不可 | バックエンド404エラー |
| **通信暗号化** | HTTPS必須 | API base URL: `https://` |

---

## 9. テスト仕様

### 9.1 単体テスト

**テストファイル**: `mobile/__tests__/screens/NotificationListScreen.test.tsx`

**テストケース**:
1. ✅ 通知一覧が正しく表示される
2. ✅ 未読件数バッジが表示される
3. ✅ 未読通知は背景色が変わる
4. ✅ すべて既読ボタンが機能する
5. ✅ 検索機能が動作する（デバウンス確認）
6. ✅ 通知タップで詳細画面に遷移する
7. ✅ Pull-to-Refreshが機能する
8. ✅ 無限スクロールが機能する

### 9.2 統合テスト

**テストファイル**: `mobile/__tests__/hooks/useNotifications.test.ts`

**テストケース**:
1. ✅ 通知一覧を取得できる
2. ✅ 未読件数を取得できる
3. ✅ ポーリングが30秒間隔で実行される
4. ✅ 未読件数増加時に通知一覧が再取得される
5. ✅ 401エラー時にポーリングが停止する
6. ✅ 画面非表示時にポーリングが停止する
7. ✅ すべて既読化が機能する
8. ✅ 検索機能が動作する

---

## 10. 実装詳細

### 10.1 ファイル構成

| ファイル | パス | 説明 |
|---------|------|------|
| 画面 | `mobile/src/screens/notifications/NotificationListScreen.tsx` | 通知一覧画面 |
| Hook | `mobile/src/hooks/useNotifications.ts` | 通知状態管理・ポーリング |
| Service | `mobile/src/services/notification.service.ts` | 通知API通信 |
| 型定義 | `mobile/src/types/notification.types.ts` | 通知型定義 |
| テスト | `mobile/__tests__/screens/NotificationListScreen.test.tsx` | 画面テスト |
| テスト | `mobile/__tests__/hooks/useNotifications.test.ts` | Hookテスト |

### 10.2 主要コンポーネント

#### 10.2.1 NotificationListScreen

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
  const { theme } = useTheme();

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

  return (
    <SafeAreaView>
      {/* ヘッダー */}
      <View style={styles.header}>
        <Text style={styles.title}>
          {theme === 'child' ? 'おしらせ' : '通知'}
        </Text>
        {unreadCount > 0 && (
          <Text style={styles.badge}>
            {theme === 'child' 
              ? `よんでないおしらせ: ${unreadCount}こ`
              : `未読: ${unreadCount}件`}
          </Text>
        )}
        <TouchableOpacity onPress={markAllAsRead}>
          <Text style={styles.readAllButton}>
            {theme === 'child' ? 'ぜんぶよんだ' : 'すべて既読'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* 検索バー */}
      <View style={styles.searchBar}>
        <TextInput
          value={searchQuery}
          onChangeText={setSearchQuery}
          placeholder={theme === 'child' ? 'さがす' : '通知を検索'}
        />
        {searchQuery && (
          <TouchableOpacity onPress={() => setSearchQuery('')}>
            <Text>✕</Text>
          </TouchableOpacity>
        )}
      </View>

      {/* 通知一覧 */}
      <FlatList
        data={notifications}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <NotificationCard
            notification={item}
            onPress={() => handleNotificationPress(item)}
          />
        )}
        refreshControl={
          <RefreshControl refreshing={loading} onRefresh={refresh} />
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
        ListEmptyComponent={
          <EmptyState
            message={theme === 'child' 
              ? 'おしらせがないよ！'
              : '通知がありません'}
          />
        }
      />
    </SafeAreaView>
  );
}
```

#### 10.2.2 useNotifications Hook

```typescript
export function useNotifications(enablePolling: boolean = false) {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const [hasMore, setHasMore] = useState(true);

  const { isAuthenticated } = useAuth();
  const pollingIntervalRef = useRef<NodeJS.Timeout | null>(null);

  // ポーリング処理
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
          console.error('[Polling] 401エラー - ポーリング停止');
          stopPolling();
        }
      }
    }, 30000); // 30秒間隔
  }, [unreadCount, isAuthenticated]);

  // ライフサイクル管理
  useEffect(() => {
    if (enablePolling && isAuthenticated) {
      startPolling();
    }

    return () => {
      stopPolling();
    };
  }, [enablePolling, isAuthenticated, startPolling]);

  return {
    notifications,
    unreadCount,
    loading,
    hasMore,
    fetchNotifications,
    markAllAsRead,
    searchNotifications,
    loadMore,
    refresh,
  };
}
```

---

## 11. 今後の拡張予定

### 11.1 Phase 2.B-7.5: Push通知実装

**実装内容**:
- Firebase Cloud Messaging（FCM）統合
- デバイストークン管理
- バックグラウンド通知受信
- 通知タップ時のディープリンク

### 11.2 将来的な機能拡張

- 通知カテゴリ別フィルタリング
- 通知の優先度ソート
- 通知の削除機能
- 通知設定画面（カテゴリ別ON/OFF）

---

## 12. 参考資料

### 12.1 関連ドキュメント

| ドキュメント | パス |
|------------|------|
| 通知機能要件定義 | `definitions/Notification.md` |
| モバイル開発規則 | `docs/mobile/mobile-rules.md` |
| 完了レポート | `docs/reports/mobile/2025-12-07-phase2-b5-step2-notification-completion-report.md` |
| 実装計画 | `docs/plans/phase2-mobile-app-implementation-plan.md` |

### 12.2 Web版対応画面

| Web画面 | パス |
|--------|------|
| 通知一覧 | `resources/views/notifications/index.blade.php` |
| 通知ボタン | `resources/views/dashboard/partials/header.blade.php` L111-128 |
| ポーリング処理 | `resources/js/common/notification-polling.js` L148 |

---

**作成日**: 2025-12-07  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**バージョン**: 1.0
