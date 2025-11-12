# 教師アバター機能 要件定義・設計書（最終版）

1. 要件定義（確定版）
1.1 機能要件
1.1.1 アバター作成機能
初回作成フロー

新規登録完了後、/avatars/create に自動リダイレクト
外見5項目、性格3項目を選択（プルダウン）
「アバターを作成」ボタンまたは「スキップ」ボタンを表示
スキップした場合はダッシュボードへ遷移（アバター未作成状態）
作成ボタンクリック → GenerateAvatarImagesJob をディスパッチ（トークン消費）
非同期でOpenAI DALL-E 3 APIを2回呼び出し + コメントテンプレート生成
全身画像（1024x1024px、透過PNG）
バストアップ画像（512x512px、透過PNG）
性格設定を基にした各イベント用コメントテンプレート（18種類）
生成した画像をS3にアップロード（avatars/{user_id}/full_body.png, bust.png）
DBに画像パス・コメントテンプレートを保存
完了後、ダッシュボードへリダイレクト
編集機能

サイドバーに「教師アバター設定」リンク追加
外見・性格の再選択可能
「画像を再生成」ボタンで再度Job実行（トークン消費）
コメントテンプレートも再生成
トークン消費

アバター作成: 100,000トークン
画像再生成: 50,000トークン
1.1.2 アバター表示機能
表示位置: イベント発生時のみ画面右下に非同期で表示（ドラッグ移動可能）

表示サイズ: デスクトップ: 250x300px程度、スマホ: 180x220px程度

非表示機能: ユーザーが手動で非表示にできる（設定を保存）

イベント発生時の動作

画面右下にフェードイン（背景透明）
アバター画像（全身またはバストアップ）を表示
吹き出しでコメント表示（白背景、角丸、影付き）
CSSアニメーション実行（感情に応じた動き）
コメント文字数に応じた表示時間（50文字まで5秒、以降10文字ごと+1秒、最大10秒）
自動でフェードアウト
イベント種別と使用画像

タスク関連（バストアップ）
タスク作成: 応援アニメーション
タスク完了: 喜びアニメーション
タスク分解: 秘書アニメーション
タスク分解再実行: 質問アニメーション
グループタスク作成: 秘書アニメーション
認証関連（バストアップ）
ログイン: 挨拶アニメーション
ログアウト: 見送りアニメーション
ログイン空白期間（3日以上）: 心配アニメーション
トークン関連（バストアップ）
トークン購入: 感謝アニメーション
実績・タグ関連（バストアップ）
実績表示: 称賛アニメーション
タグ作成: 承認アニメーション
タグ削除: 注意アニメーション
グループ関連（バストアップ）
グループ作成: 祝福アニメーション
グループ編集: 秘書アニメーション
グループ削除: 確認アニメーション

1.1.3 コメント管理機能
コメントテンプレート生成

アバター作成時、OpenAI APIで性格設定を踏まえた18種類のコメントテンプレートを自動生成
各イベント×性格の組み合わせでバリエーション作成
コメント例（性格: 優しい、熱意: 高い、丁寧さ: 丁寧）

1.1.4 アニメーション機能

2. データベース設計
2.1 テーブル定義

3. アーキテクチャ設計
3.1 ディレクトリ構成
app/
├── Http/
│   └── Actions/
│       └── Avatar/
│           ├── CreateTeacherAvatarAction.php
│           ├── StoreTeacherAvatarAction.php (Job実行)
│           ├── EditTeacherAvatarAction.php
│           ├── UpdateTeacherAvatarAction.php
│           ├── RegenerateAvatarImageAction.php
│           ├── GetAvatarCommentAction.php (API)
│           └── ToggleAvatarVisibilityAction.php
├── Services/
│   └── Avatar/
│       ├── TeacherAvatarService.php
│       ├── TeacherAvatarServiceInterface.php
│       ├── AvatarCommentGeneratorService.php (性格反映コメント生成)
│       └── AvatarEventHandlerService.php (イベント検知・表示制御)
├── Repositories/
│   └── Avatar/
│       ├── TeacherAvatarRepository.php
│       └── TeacherAvatarRepositoryInterface.php
├── Responders/
│   └── Avatar/
│       └── TeacherAvatarResponder.php
├── Models/
│   ├── TeacherAvatar.php
│   ├── AvatarImage.php
│   └── AvatarComment.php
├── Jobs/
│   └── GenerateAvatarImagesJob.php (DALL-E 3呼び出し + S3保存 + コメント生成)
└── Events/
    ├── TaskCreatedEvent.php
    ├── TaskCompletedEvent.php
    ├── UserLoggedInEvent.php
    ├── UserLoggedOutEvent.php
    ├── TokenPurchasedEvent.php
    ├── PerformanceViewedEvent.php
    ├── TagCreatedEvent.php
    ├── TagDeletedEvent.php
    ├── GroupTaskCreatedEvent.php
    ├── GroupCreatedEvent.php
    ├── GroupEditedEvent.php
    ├── GroupDeletedEvent.php
    └── TaskBreakdownExecutedEvent.php

resources/
├── views/
│   └── avatars/
│       ├── create.blade.php
│       ├── edit.blade.php
│       └── components/
│           └── avatar-widget.blade.php (イベント時表示)
├── css/
│   └── avatar/
│       └── avatar.css
└── js/
    └── avatar/
        ├── avatar-controller.js (Alpine.js)
        └── avatar-event-dispatcher.js (イベント発火)

4. 画面設計
4.1 アバター初期設定画面
4.2 アバターウィジェット（イベント時表示）

5. CSS設計
/home/ktr/mtdev/laravel/resources/css/avatar/avatar.css

6. JavaScript設計
/home/ktr/mtdev/laravel/resources/js/avatar/avatar-controller.js

7. OpenAI API 連携（既存サービス活用）
GenerateAvatarImagesJobクラス