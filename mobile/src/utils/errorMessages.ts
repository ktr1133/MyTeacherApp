/**
 * エラーメッセージ変換ユーティリティ
 * 
 * Service層で投げるエラーコードをテーマに応じた日本語メッセージに変換
 * Web版の Blade内での条件分岐（@if ($theme === 'child')）に相当
 */

import { ThemeType } from '../types/user.types';

/**
 * エラーメッセージマップ
 * 
 * 各エラーコードに対して大人向け・子供向けのメッセージを定義
 */
const ERROR_MESSAGES: Record<string, { adult: string; child: string }> = {
  // 認証エラー
  AUTH_REQUIRED: {
    adult: 'ログインが必要です',
    child: 'ログインしてね',
  },
  SESSION_EXPIRED: {
    adult: 'セッションが切れました。再度ログインしてください',
    child: 'じかんがたったよ。もういちどログインしてね',
  },

  // ネットワークエラー
  NETWORK_ERROR: {
    adult: 'ネットワークエラーが発生しました',
    child: 'インターネットにつながらないよ',
  },
  REQUEST_TIMEOUT: {
    adult: '通信がタイムアウトしました',
    child: 'じかんがかかりすぎちゃった',
  },

  // ユーザー情報関連エラー
  USER_FETCH_FAILED: {
    adult: 'ユーザー情報の取得に失敗しました',
    child: 'じぶんのじょうほうがとれなかったよ',
  },

  // プロフィール関連エラー
  PROFILE_FETCH_FAILED: {
    adult: 'プロフィール情報の取得に失敗しました',
    child: 'じぶんのじょうほうがとれなかったよ',
  },
  PROFILE_UPDATE_FAILED: {
    adult: 'プロフィールの更新に失敗しました',
    child: 'じぶんのじょうほうをかえられなかったよ',
  },
  PROFILE_DELETE_FAILED: {
    adult: 'プロフィールの削除に失敗しました',
    child: 'アカウントをけせなかったよ',
  },

  // タイムゾーン関連エラー
  TIMEZONE_FETCH_FAILED: {
    adult: 'タイムゾーン情報の取得に失敗しました',
    child: 'じかんのせっていがとれなかったよ',
  },
  TIMEZONE_UPDATE_FAILED: {
    adult: 'タイムゾーンの更新に失敗しました',
    child: 'じかんのせっていをかえられなかったよ',
  },

  // パスワード関連エラー
  PASSWORD_UPDATE_FAILED: {
    adult: 'パスワードの更新に失敗しました',
    child: 'パスワードをかえられなかったよ',
  },
  CURRENT_PASSWORD_INCORRECT: {
    adult: '現在のパスワードが正しくありません',
    child: 'いまのパスワードがちがうよ',
  },
  PASSWORD_TOO_SHORT: {
    adult: 'パスワードは8文字以上で入力してください',
    child: 'パスワードは8もじいじょうにしてね',
  },
  PASSWORD_CONFIRMATION_MISMATCH: {
    adult: 'パスワードが確認用と一致しません',
    child: 'パスワードがあっていないよ',
  },

  // タスク操作エラー
  TASK_NOT_FOUND: {
    adult: 'タスクが見つかりません',
    child: 'やることがみつからないよ',
  },
  TASK_ALREADY_COMPLETED: {
    adult: 'タスクは既に完了しています',
    child: 'もうおわっているよ',
  },
  TASK_CREATE_FAILED: {
    adult: 'タスクの作成に失敗しました',
    child: 'やることをつくれなかったよ',
  },
  TASK_UPDATE_FAILED: {
    adult: 'タスクの更新に失敗しました',
    child: 'へんこうできなかったよ',
  },
  TASK_DELETE_FAILED: {
    adult: 'タスクの削除に失敗しました',
    child: 'けせなかったよ',
  },
  TASK_FETCH_FAILED: {
    adult: 'タスクの取得に失敗しました',
    child: 'やることがみつからなかったよ',
  },

  // 承認操作エラー
  APPROVAL_NOT_ALLOWED: {
    adult: '承認権限がありません',
    child: 'しょうにんできないよ',
  },
  ALREADY_APPROVED: {
    adult: '既に承認済みです',
    child: 'もうOKをだしているよ',
  },
  ALREADY_REJECTED: {
    adult: '既に却下済みです',
    child: 'もうだめっていったよ',
  },

  // 画像操作エラー
  IMAGE_UPLOAD_FAILED: {
    adult: '画像のアップロードに失敗しました',
    child: 'しゃしんをおくれなかったよ',
  },
  IMAGE_DELETE_FAILED: {
    adult: '画像の削除に失敗しました',
    child: 'しゃしんをけせなかったよ',
  },
  IMAGE_TOO_LARGE: {
    adult: '画像サイズが大きすぎます',
    child: 'しゃしんがおおきすぎるよ',
  },
  INVALID_IMAGE_FORMAT: {
    adult: '画像形式が不正です',
    child: 'しゃしんのかたちがちがうよ',
  },

  // 通知関連エラー
  NOTIFICATION_FETCH_FAILED: {
    adult: '通知の取得に失敗しました',
    child: 'おしらせがとれなかったよ',
  },
  NOTIFICATION_UPDATE_FAILED: {
    adult: '通知の更新に失敗しました',
    child: 'おしらせをこうしんできなかったよ',
  },
  NOTIFICATION_SEARCH_FAILED: {
    adult: '通知の検索に失敗しました',
    child: 'おしらせをさがせなかったよ',
  },

  // トークン関連エラー
  TOKEN_BALANCE_FETCH_FAILED: {
    adult: 'トークン残高の取得に失敗しました',
    child: 'トークンのかずがとれなかったよ',
  },
  TOKEN_HISTORY_FETCH_FAILED: {
    adult: 'トークン履歴の取得に失敗しました',
    child: 'トークンのりれきがとれなかったよ',
  },
  TOKEN_PACKAGES_FETCH_FAILED: {
    adult: 'トークンパッケージの取得に失敗しました',
    child: 'トークンパッケージがとれなかったよ',
  },
  TOKEN_PURCHASE_REQUEST_FAILED: {
    adult: '購入リクエストの作成に失敗しました',
    child: 'かうおねがいができなかったよ',
  },
  TOKEN_APPROVAL_FAILED: {
    adult: '購入リクエストの承認に失敗しました',
    child: 'OKをだせなかったよ',
  },
  TOKEN_REJECTION_FAILED: {
    adult: '購入リクエストの却下に失敗しました',
    child: 'だめっていえなかったよ',
  },
  INSUFFICIENT_TOKENS: {
    adult: 'トークンが不足しています',
    child: 'トークンがたりないよ',
  },

  // サーバーエラー
  SERVER_ERROR: {
    adult: 'サーバーエラーが発生しました',
    child: 'サーバーがこまっているよ',
  },
  MAINTENANCE_MODE: {
    adult: 'メンテナンス中です',
    child: 'いまおやすみちゅうだよ',
  },

  // バリデーションエラー
  VALIDATION_ERROR: {
    adult: '入力内容に誤りがあります',
    child: 'かいたないようがまちがっているよ',
  },
  TITLE_REQUIRED: {
    adult: 'タイトルは必須です',
    child: 'タイトルをかいてね',
  },
  TITLE_TOO_LONG: {
    adult: 'タイトルが長すぎます',
    child: 'タイトルがながすぎるよ',
  },
  DESCRIPTION_TOO_LONG: {
    adult: '説明が長すぎます',
    child: 'せつめいがながすぎるよ',
  },

  // AIタスク分解関連エラー
  TASK_PROPOSE_FAILED: {
    adult: 'AIタスク分解に失敗しました',
    child: 'AIがやることをわけられなかったよ',
  },
  TASK_ADOPT_FAILED: {
    adult: '提案の採用に失敗しました',
    child: 'やることをつくれなかったよ',
  },
  TOKEN_INSUFFICIENT: {
    adult: 'トークンが不足しています。トークンを購入してください',
    child: 'トークンがたりないよ。かってね',
  },
  SPAN_REQUIRED: {
    adult: '期間は必須です',
    child: 'きかんをえらんでね',
  },
  PROPOSAL_ID_INVALID: {
    adult: '提案IDが無効です',
    child: 'ていあんIDがおかしいよ',
  },
  TASKS_REQUIRED: {
    adult: '作成するタスクを選択してください',
    child: 'つくるやることをえらんでね',
  },

  // 汎用エラー
  UNKNOWN_ERROR: {
    adult: '予期しないエラーが発生しました',
    child: 'なにかおかしくなっちゃった',
  },
};

/**
 * エラーコードをテーマに応じたメッセージに変換
 * 
 * Web版の Blade内での以下のパターンに相当:
 * ```blade
 * @if ($theme === 'child')
 *   やることをつくれなかったよ
 * @else
 *   タスクの作成に失敗しました
 * @endif
 * ```
 * 
 * @param errorCode エラーコード（例: 'TASK_CREATE_FAILED'）
 * @param theme テーマ ('adult' | 'child')
 * @returns テーマに応じた日本語メッセージ
 * 
 * @example
 * ```tsx
 * const { theme } = useTheme();
 * 
 * try {
 *   await taskService.createTask(data);
 * } catch (error: any) {
 *   const message = getErrorMessage(error.message, theme);
 *   Alert.alert('エラー', message);
 * }
 * ```
 */
export const getErrorMessage = (errorCode: string, theme: ThemeType): string => {
  const messages = ERROR_MESSAGES[errorCode];
  
  if (!messages) {
    // 未定義のエラーコードは汎用メッセージを返す
    return theme === 'child' 
      ? 'なにかおかしくなっちゃった' 
      : '予期しないエラーが発生しました';
  }

  return theme === 'child' ? messages.child : messages.adult;
};

/**
 * エラーコード一覧を取得（開発用）
 * 
 * @returns 定義済みエラーコードの配列
 */
export const getAvailableErrorCodes = (): string[] => {
  return Object.keys(ERROR_MESSAGES);
};
