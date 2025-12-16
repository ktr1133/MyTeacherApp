/**
 * PDF生成・共有サービス
 * 
 * 責務:
 * - メンバー別概況PDFのダウンロード
 * - ファイルシステムへの一時保存
 * - ネイティブ共有ダイアログの表示
 * 
 * mobile-rules.md: Service層メソッド命名規則
 * - CRUD動詞使用: download, share, cache
 * - 具体的なメソッド名: downloadAndShareMemberSummaryPdf
 */

import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import api from './api';

/**
 * PDFダウンロードパラメータ
 */
export interface DownloadPdfParams {
  user_id: number;
  group_id: number;
  year_month: string;
  comment?: string;  // AIコメント（オプショナル、渡さない場合はサーバーで再生成）
  chart_image?: string;  // 円グラフ画像Base64（オプショナル）
}

/**
 * PDFダウンロード結果
 */
export interface DownloadPdfResult {
  success: boolean;
  fileUri?: string;
}

/**
 * メンバーサマリーPDFをダウンロード・共有
 * 
 * フロー:
 * 1. バックエンドAPIからPDFバイナリをダウンロード
 * 2. FileSystem.cacheDirectoryに一時保存
 * 3. expo-sharingでネイティブ共有ダイアログ表示
 * 
 * エラーハンドリング:
 * - 402: トークン不足（メンバーサマリー生成時に消費済みのため発生しない想定）
 * - 403: 権限不足
 * - 500: サーバーエラー
 * - タイムアウト: 60秒
 * - ネットワークエラー
 * 
 * @param params ダウンロードパラメータ
 * @returns ダウンロード成功/失敗
 * @throws {Error} ネットワークエラー、権限エラー、サーバーエラー
 */
export const downloadAndShareMemberSummaryPdf = async (
  params: DownloadPdfParams
): Promise<DownloadPdfResult> => {
  try {
    // 1. PDFバイナリをダウンロード
    const response = await api.post(
      '/reports/monthly/member-summary/pdf',
      params,
      {
        responseType: 'blob',
        timeout: 60000, // 60秒（PDF生成時間を考慮）
      }
    );
    
    // レスポンスデータが空またはサイズが異常に小さい場合はエラー
    if (!response.data || (response.data.size && response.data.size < 1000)) {
      throw new Error('PDFデータが空またはサイズが不正です');
    }

    // 2. 一時ファイルとして保存
    const fileName = `member-summary-${params.year_month}-${params.user_id}.pdf`;
    const fileUri = `${FileSystem.cacheDirectory}${fileName}`;
    
    // Blobをbase64に変換
    const reader = new FileReader();
    const base64Promise = new Promise<string>((resolve, reject) => {
      reader.onloadend = () => {
        const base64data = reader.result as string;
        // data:application/pdf;base64, を除去
        const base64Content = base64data.includes(',') 
          ? base64data.split(',')[1] 
          : base64data;
        resolve(base64Content);
      };
      reader.onerror = (error) => {
        console.error('[pdf.service] FileReader error:', error);
        reject(new Error('PDFファイルの読み込みに失敗しました'));
      };
    });
    reader.readAsDataURL(response.data);
    const base64data = await base64Promise;

    // ファイルに書き込み（Base64をバイナリとして保存）
    await FileSystem.writeAsStringAsync(fileUri, base64data, {
      encoding: FileSystem.EncodingType.Base64,
    });

    // 3. 共有ダイアログを表示
    const canShare = await Sharing.isAvailableAsync();
    if (!canShare) {
      throw new Error('この端末では共有機能がサポートされていません');
    }

    await Sharing.shareAsync(fileUri, {
      mimeType: 'application/pdf',
      dialogTitle: 'メンバー別概況レポート',
      UTI: 'com.adobe.pdf', // iOS用
    });

    return { success: true, fileUri };
  } catch (error: any) {
    console.error('[pdf.service] PDF download error:', error);
    
    // エラー種別に応じたメッセージ
    if (error.response?.status === 402) {
      throw new Error('トークン残高が不足しています');
    } else if (error.response?.status === 403) {
      throw new Error('レポートをダウンロードする権限がありません');
    } else if (error.response?.status === 500) {
      const serverMessage = error.response?.data?.message || '';
      throw new Error(
        `PDF生成に失敗しました。${serverMessage ? serverMessage : 'しばらくしてから再試行してください'}`
      );
    } else if (error.code === 'ECONNABORTED') {
      throw new Error('タイムアウトしました。ネットワーク接続を確認してください');
    } else if (!error.response) {
      throw new Error('ネットワークエラーが発生しました');
    } else if (error.message) {
      // FileReaderエラー、共有エラー等
      throw error;
    }
    
    throw new Error('PDFのダウンロードに失敗しました');
  }
};

/**
 * キャッシュディレクトリのPDFファイルを削除（クリーンアップ用）
 * 
 * 注: expo-sharingの共有後は自動的にクリーンアップされるため、
 * 通常は手動削除不要。エラー時のクリーンアップ用に提供。
 * 
 * @param fileUri 削除するファイルのURI
 */
export const deletePdfFile = async (fileUri: string): Promise<void> => {
  try {
    const fileInfo = await FileSystem.getInfoAsync(fileUri);
    if (fileInfo.exists) {
      await FileSystem.deleteAsync(fileUri);
    }
  } catch (error) {
    // エラーが発生しても処理は継続（クリーンアップ失敗は致命的ではない）
  }
};
