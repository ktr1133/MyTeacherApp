# プライバシーポリシー・利用規約実装完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-16 | GitHub Copilot | 初版作成: Phase 1-4完了報告 |

## 概要

**MyTeacher**システムに**プライバシーポリシー・利用規約システム**を実装しました。この作業により、以下の目標を達成しました：

- ✅ **App Store/Google Play審査対応**: Web版プライバシーポリシー・利用規約ページの公開URL提供
- ✅ **COPPA対応基盤**: usersテーブル拡張（birthdate, is_minor等）、未成年者判定メソッド実装
- ✅ **GDPR対応**: 90日経過ユーザー削除バッチの自動実行設定
- ✅ **ユーザー体験向上**: モバイルアプリ内からWebViewで法的情報へアクセス可能
- ✅ **ダークモード対応**: Web/モバイル両方でダークモード完全対応

## 計画との対応

**参照ドキュメント**: `/home/ktr/mtdev/docs/plans/privacy-policy-and-terms-implementation-plan.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| **Phase 1: Web版法的ページ** | ✅ 完了 | 計画通り実施 | なし |
| Phase 1-1: プライバシーポリシー本文 | ✅ 完了 | `/home/ktr/mtdev/docs/legal/privacy-policy.md` 作成 | 13セクション、COPPA/GDPR対応 |
| Phase 1-2: 利用規約本文 | ✅ 完了 | `/home/ktr/mtdev/docs/legal/terms-of-service.md` 作成 | 13条文、完全な法的枠組み |
| Phase 1-3: Web版プライバシーポリシー画面 | ✅ 完了 | `resources/views/legal/privacy-policy.blade.php` 作成 | Tailwind CSS、レスポンシブ、印刷対応 |
| Phase 1-4: Web版利用規約画面 | ✅ 完了 | `resources/views/legal/terms-of-service.blade.php` 作成 | デザイン統一、ダークモード対応 |
| Phase 1-5: ルート追加 | ✅ 完了 | `/privacy-policy`, `/terms-of-service` を公開ルートとして追加 | 認証不要で全ユーザーアクセス可能 |
| **Phase 2: モバイルアプリWebView対応** | ✅ 完了 | 計画通り実施 | なし |
| Phase 2-1: react-native-webview インストール | ✅ 完了 | `npm install react-native-webview` + `npx expo prebuild` 実行 | ネイティブコード生成完了 |
| Phase 2-2: モバイルプライバシーポリシー画面 | ✅ 完了 | `/home/ktr/mtdev/mobile/src/screens/legal/PrivacyPolicyScreen.tsx` 作成 | カスタムヘッダー、エラーハンドリング |
| Phase 2-3: モバイル利用規約画面 | ✅ 完了 | `/home/ktr/mtdev/mobile/src/screens/legal/TermsOfServiceScreen.tsx` 作成 | 子供テーマ対応タイトル表示 |
| Phase 2-4: AppNavigator.tsx更新 | ✅ 完了 | DrawerNavigatorに法的情報画面のルート追加 | `headerShown: false` でカスタムヘッダー使用 |
| Phase 2-5: ProfileScreen.tsx更新 | ✅ 完了 | 法的情報セクション追加（プライバシーポリシー・利用規約リンク） | テーマ別タイトル表示（おやくそく/法的情報） |
| **Phase 3: データベース拡張** | ✅ 完了 | 計画通り実施 | なし |
| Phase 3-1: usersテーブル拡張 | ✅ 完了 | マイグレーション作成・実行完了 | birthdate, is_minor, parent_email等6カラム追加 |
| Phase 3-2: User.phpモデル更新 | ✅ 完了 | $fillable, $casts更新 + メソッド追加 | calculateIsMinor(), needsParentConsent(), isParentConsentExpired() |
| **Phase 4: データ削除バッチ** | ✅ 完了 | 計画通り実施 | なし |
| Phase 4-1: 削除コマンド作成 | ✅ 完了 | `DeleteInactiveUsersCommand` 作成 | Dry runモード、S3削除、トランザクション対応 |
| Phase 4-2: スケジューラー登録 | ✅ 完了 | `routes/console.php` に毎日午前1時実行設定 | 二重実行防止、ログ出力設定 |
| **Phase 5: 保護者同意プロセス** | ⚠️ 未実施 | 今後の実装タスクとして残す | 複雑性が高く、13歳未満ユーザー対応時に実装 |

## 実施内容詳細

### Phase 1: Web版法的ページ作成

#### 1. プライバシーポリシー・利用規約本文作成（Markdown形式）

- **ファイル**: 
  - `/home/ktr/mtdev/docs/legal/privacy-policy.md` （約650行）
  - `/home/ktr/mtdev/docs/legal/terms-of-service.md` （約450行）

- **主要内容**:
  - 事業者情報（個人事業主、東京都所在、famicoapp@gmail.com）
  - データ収集項目（アカウント、タスク、AI利用、決済、デバイス情報）
  - AI利用開示（OpenAI GPT-4o、Replicate Stable Diffusion、米国データ転送警告）
  - 第三者サービス一覧表（OpenAI、Replicate、Stripe、AWS、Firebase）
  - データ保持期間（90日削除ポリシー）
  - COPPA対応（13歳未満保護者同意プロセス）
  - GDPR対応（アクセス権、訂正権、削除権、データポータビリティ）
  - 利用規約13条文（定義、登録、禁止行為、有料サービス、知的財産権、免責事項等）

#### 2. Web版画面作成（Blade + Tailwind CSS）

- **ファイル**:
  - `/home/ktr/mtdev/resources/views/legal/privacy-policy.blade.php` （約650行）
  - `/home/ktr/mtdev/resources/views/legal/terms-of-service.blade.php` （約450行）

- **デザインシステム**:
  - グラデーションヘッダー（#59B9C6 → purple-600）
  - スティッキーナビゲーション（トップへ戻る、相互リンク）
  - 目次（アンカーリンク付き13セクション/条文）
  - アラートボックス（警告：黄色、情報：青色、確認：緑色、注意：赤色）
  - レスポンシブテーブル（overflow-x-auto）
  - ダークモード対応（`dark:bg-gray-900`, `dark:text-white` 等）
  - 印刷スタイル（`@media print` でヘッダー/フッター非表示、白背景強制）

- **公開URL**:
  - `https://my-teacher-app.com/privacy-policy`
  - `https://my-teacher-app.com/terms-of-service`
  - 認証不要、全ユーザーアクセス可能

### Phase 2: モバイルアプリWebView対応

#### 1. 依存関係インストール

```bash
cd /home/ktr/mtdev/mobile
npm install react-native-webview
npx expo prebuild --clean
```

- ネイティブコード（android/, ios/）生成完了
- WebView機能有効化

#### 2. 法的情報画面作成（React Native + TypeScript）

- **ファイル**:
  - `/home/ktr/mtdev/mobile/src/screens/legal/PrivacyPolicyScreen.tsx`
  - `/home/ktr/mtdev/mobile/src/screens/legal/TermsOfServiceScreen.tsx`

- **実装機能**:
  - WebViewによるWeb版ページ表示
  - カスタムヘッダー（戻るボタン、テーマ別タイトル）
  - ローディングインジケーター（ActivityIndicator）
  - エラーハンドリング（再読み込みボタン付き）
  - ダークモード同期（`injectedJavaScript`でWeb側にclass追加）
  - レスポンシブデザイン（`useResponsive`, `getFontSize`, `getSpacing`）
  - テーマ別タイトル表示（adult: "プライバシーポリシー"/"利用規約", child: "プライバシーについて"/"おやくそく"）

#### 3. ナビゲーション統合

- **DrawerNavigator.tsx更新**:
  - PrivacyPolicy, TermsOfService画面をStackに追加
  - `headerShown: false` でカスタムヘッダー使用

- **ProfileScreen.tsx更新**:
  - 法的情報セクション追加（はじめに・使い方ボタンと削除ボタンの間）
  - プライバシーポリシー・利用規約リンク（chevron-rightアイコン付き）
  - テーマ別セクションタイトル（adult: "法的情報", child: "おやくそく"）

### Phase 3: データベース拡張（COPPA対応基盤）

#### 1. マイグレーション実行

- **ファイル**: `database/migrations/2025_12_16_115636_add_minor_consent_columns_to_users_table.php`

- **追加カラム**:
  | カラム名 | 型 | 説明 |
  |---------|---|------|
  | `birthdate` | DATE | 生年月日（年齢判定用） |
  | `is_minor` | BOOLEAN | 未成年フラグ（13歳未満の場合true） |
  | `parent_email` | STRING | 保護者のメールアドレス |
  | `parent_consent_token` | STRING(64) | 保護者同意確認用トークン |
  | `parent_consented_at` | TIMESTAMP | 保護者同意日時 |
  | `parent_consent_expires_at` | TIMESTAMP | 保護者同意有効期限（7日間） |

- **インデックス**:
  - `parent_consent_token` （検索高速化）
  - `parent_consented_at` （有効期限判定高速化）

#### 2. Userモデル更新

- **$fillable追加**: 6カラム追加
- **$casts追加**: 
  - `birthdate` → `date`
  - `is_minor` → `boolean`
  - `parent_consented_at`, `parent_consent_expires_at` → `datetime`

- **メソッド追加**:
  ```php
  // 13歳未満かどうかを判定
  public function calculateIsMinor(): bool
  
  // 保護者同意が必要かどうかを判定
  public function needsParentConsent(): bool
  
  // 保護者同意の有効期限が切れているかを判定
  public function isParentConsentExpired(): bool
  ```

### Phase 4: データ削除バッチ（GDPR対応）

#### 1. 削除コマンド実装

- **ファイル**: `/home/ktr/mtdev/app/Console/Commands/DeleteInactiveUsersCommand.php`

- **機能**:
  - 論理削除（`deleted_at`）から90日経過したユーザーを検索
  - トランザクション内で関連データ削除:
    - tasks（カスケード削除でtask_images, task_completions等も削除）
    - token_transactions
    - notifications
    - token_balances
    - teacher_avatars
  - S3オブジェクト削除:
    - `avatars/{user_id}/` ディレクトリ
    - `task_approvals/{user_id}/` ディレクトリ
  - ユーザーレコード物理削除（`forceDelete()`）
  - 詳細ログ出力（成功/エラー）

- **オプション**:
  - `--dry-run`: Dry runモード（削除なし、情報表示のみ）
  - `--days=90`: 削除対象日数（デフォルト90日）

- **実行例**:
  ```bash
  # Dry run（テスト実行）
  php artisan batch:delete-inactive-users --dry-run
  
  # 本番実行
  php artisan batch:delete-inactive-users
  
  # 30日経過で削除（テスト用）
  php artisan batch:delete-inactive-users --days=30
  ```

#### 2. スケジューラー登録

- **ファイル**: `/home/ktr/mtdev/routes/console.php`

- **設定**:
  ```php
  Schedule::command('batch:delete-inactive-users')
      ->dailyAt('01:00')               // 毎日午前1時（JST）
      ->timezone('Asia/Tokyo')
      ->withoutOverlapping()           // 二重実行防止
      ->onOneServer()                  // 複数サーバー環境での重複実行防止
      ->appendOutputTo(storage_path('logs/delete-inactive-users.log'))
      ->onSuccess(function () {
          Log::info('90日経過ユーザー削除成功');
      })
      ->onFailure(function () {
          Log::error('90日経過ユーザー削除失敗');
      });
  ```

- **Cron設定**（既存のスケジューラー設定を使用）:
  ```bash
  * * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1
  ```

## 成果と効果

### 定量的効果

- **開発規模**:
  - 新規ファイル: 8ファイル（Markdown 2、Blade 2、TypeScript 2、PHP 2）
  - 総行数: 約3,500行
  - マイグレーション: 1ファイル（カラム6個追加、インデックス2個）
  - スケジューラー: 1タスク追加

- **機能追加**:
  - Web公開URL: 2ページ（プライバシーポリシー、利用規約）
  - モバイル画面: 2画面（WebView表示）
  - バッチコマンド: 1コマンド（90日削除バッチ）
  - Userモデルメソッド: 3メソッド（未成年者判定系）

### 定性的効果

- **法令遵守**:
  - COPPA（米国児童オンラインプライバシー保護法）対応基盤構築
  - GDPR（EU一般データ保護規則）対応（90日削除ポリシー実装）

- **App Store/Google Play審査対応**:
  - プライバシーポリシー公開URL提供（審査必須項目）
  - 利用規約公開URL提供（審査必須項目）

- **ユーザー体験向上**:
  - モバイルアプリ内から法的情報へ直接アクセス可能
  - ダークモード対応により目への負担軽減
  - テーマ別表示（おやくそく/法的情報）で子供ユーザーにも配慮

- **保守性向上**:
  - Markdownファイルで法的文書のバージョン管理可能
  - Bladeテンプレートで容易な内容更新
  - バッチコマンドのDry runモードでテスト実行可能

## 未完了項目・次のステップ

### Phase 5: 保護者同意プロセス（未実施）

**理由**: 複雑性が高く、13歳未満ユーザーの実際の登録ニーズを見てから実装判断が適切

**必要な実装内容**:
1. **Notificationクラス作成**:
   - `ParentConsentRequestNotification` （保護者への同意依頼メール）
   - メールテンプレート（日本語/英語）
   - 同意確認URLの生成

2. **Actionクラス作成**:
   - `SendParentConsentAction` （保護者同意依頼送信）
   - `VerifyParentConsentAction` （保護者同意確認）
   - トークン検証、有効期限チェック

3. **登録画面修正**:
   - Web版RegisterScreen: 生年月日入力フィールド追加
   - モバイル版RegisterScreen: 生年月日ピッカー追加
   - 年齢判定ロジック（13歳未満の場合、保護者メール入力へ遷移）

4. **保護者メール入力画面作成**:
   - Web版: `resources/views/auth/parent-email.blade.php`
   - モバイル版: `mobile/src/screens/auth/ParentEmailScreen.tsx`
   - バリデーション（メールアドレス形式、重複チェック）

5. **保護者同意確認画面作成**:
   - Web版: `resources/views/auth/parent-consent.blade.php`
   - トークン検証、有効期限表示
   - 同意ボタン、拒否ボタン

6. **有効期限切れバッチ作成**:
   - `ExpireParentConsentCommand` （7日経過で同意トークン無効化）
   - スケジューラー登録（毎日午前2時実行）

**実装優先度**: 中 - 13歳未満ユーザーの登録が発生した場合に優先的に実装

### 今後の推奨事項

1. **App Store/Google Play申請前の確認事項**:
   - [ ] プライバシーポリシーURL: `https://my-teacher-app.com/privacy-policy` がアクセス可能か確認
   - [ ] 利用規約URL: `https://my-teacher-app.com/terms-of-service` がアクセス可能か確認
   - [ ] App Storeコネクト: プライバシーポリシーURLを登録
   - [ ] Google Play Console: プライバシーポリシーURLを登録
   - [ ] モバイルアプリ内リンク動作確認（WebView正常表示）

2. **法的文書の定期レビュー**:
   - 頻度: 6ヶ月ごと
   - 確認項目: 外部サービス変更、法改正、個人情報保護委員会ガイドライン更新
   - 更新手順: Markdownファイル編集 → Webページ自動反映

3. **バッチ動作監視**:
   - ログ確認: `storage/logs/delete-inactive-users.log`
   - アラート設定: 削除エラー発生時に管理者通知
   - 定期監査: 削除実行履歴のレビュー（月次）

4. **13歳未満ユーザー対応準備**:
   - ユーザー登録フロー分析（年齢分布確認）
   - 13歳未満登録数が月間10件以上になった場合、Phase 5実装を検討
   - 保護者同意プロセスのUX設計（メール通知、リマインド機能等）

5. **多言語対応検討**:
   - 優先言語: 英語（App Store/Google Playの国際展開時）
   - 対象ファイル: Markdownファイル、Bladeテンプレート
   - 実装方法: Laravel多言語化機能（`lang/` ディレクトリ）

## まとめ

プライバシーポリシー・利用規約システムの実装により、**App Store/Google Play審査対応**（最優先）、**COPPA/GDPR法令遵守**（高優先）の基盤を構築しました。Phase 1-4（Web法的ページ、モバイルWebView、データベース拡張、削除バッチ）を完全に実装し、Phase 5（保護者同意プロセス）は今後の実装タスクとして残しています。

すべての実装はダークモード対応、レスポンシブデザイン、テーマ別表示（adult/child）に準拠しており、ユーザー体験を損なうことなく法的要件を満たしています。今後はApp Store/Google Playへの申請準備を進めてください。
