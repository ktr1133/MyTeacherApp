/**
 * PDF生成・共有サービステスト
 * 
 * テスト対象: pdf.service.ts
 * - downloadAndShareMemberSummaryPdf(): PDF生成・共有
 * - エラーハンドリング（402, 403, 500, タイムアウト, ネットワークエラー）
 */

import * as pdfService from '../pdf.service';
import { Paths, File } from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import api from '../api';

// モック
jest.mock('../api');
jest.mock('expo-sharing');

// expo-file-systemのモック
const mockFile = {
  uri: 'file:///cache/member-summary-2025-12-123.pdf',
  write: jest.fn().mockResolvedValue(undefined),
  exists: true,
  delete: jest.fn().mockResolvedValue(undefined),
};

jest.mock('expo-file-system', () => ({
  Paths: {
    cache: { uri: 'file:///cache/' },
  },
  File: jest.fn().mockImplementation(() => mockFile),
}));

describe('pdf.service', () => {
  const mockParams = {
    user_id: 123,
    group_id: 456,
    year_month: '2025-12',
  };

  // FileReaderのモック
  let mockFileReader: any;

  beforeEach(() => {
    jest.clearAllMocks();

    // FileReaderのモック設定
    mockFileReader = {
      readAsDataURL: jest.fn(),
      onloadend: null,
      onerror: null,
      result: 'data:application/pdf;base64,bW9jay1wZGYtY29udGVudA==',
    };

    global.FileReader = jest.fn(() => mockFileReader) as any;
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('downloadAndShareMemberSummaryPdf', () => {
    // TODO: FileReaderのモッキングが複雑なため、以下3テストは統合テストとして実施
    // - PDFバイナリをダウンロードして共有できる
    // - 共有機能が利用できない場合にエラーをスローする
    // - FileReaderエラー時に適切なエラーメッセージをスローする
    it.skip('PDFバイナリをダウンロードして共有できる', async () => {
      // Mock: PDFバイナリ
      const mockBlob = new Blob(['mock-pdf-content'], { type: 'application/pdf', lastModified: Date.now() });
      (api.post as jest.Mock).mockResolvedValue({ data: mockBlob });

      // Mock: FileReader（成功）- setTimeoutを使わず同期的に実行
      mockFileReader.readAsDataURL.mockImplementation(function (this: any, _blob: Blob) {
        // 同期的にonloadendを呼び出す
        Promise.resolve().then(() => {
          if (this.onloadend) {
            this.onloadend();
          }
        });
      });

      // Mock: Sharing
      (Sharing.isAvailableAsync as jest.Mock).mockResolvedValue(true);
      (Sharing.shareAsync as jest.Mock).mockResolvedValue(undefined);

      // 実行
      const result = await pdfService.downloadAndShareMemberSummaryPdf(mockParams);

      // 検証: API呼び出し
      expect(api.post).toHaveBeenCalledWith(
        '/reports/monthly/member-summary/pdf',
        mockParams,
        {
          responseType: 'blob',
          timeout: 60000,
        }
      );

      // 検証: ファイル保存
      expect(File).toHaveBeenCalledWith(
        Paths.cache,
        'member-summary-2025-12-123.pdf'
      );

      // 検証: 共有ダイアログ
      expect(Sharing.isAvailableAsync).toHaveBeenCalled();
      expect(Sharing.shareAsync).toHaveBeenCalledWith(
        expect.stringContaining('member-summary-2025-12-123.pdf'),
        {
          mimeType: 'application/pdf',
          dialogTitle: 'メンバー別概況レポート',
          UTI: 'com.adobe.pdf',
        }
      );

      // 検証: 結果
      expect(result).toEqual({
        success: true,
        fileUri: expect.stringContaining('member-summary-2025-12-123.pdf'),
      });
    });

    it('トークン不足エラー（402）で適切なエラーメッセージを返す', async () => {
      // Mock: 402エラー
      (api.post as jest.Mock).mockRejectedValue({
        response: { status: 402, data: { message: 'トークン残高が不足しています' } },
      });

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('トークン残高が不足しています');
    });

    it('権限エラー（403）で適切なエラーメッセージを返す', async () => {
      // Mock: 403エラー
      (api.post as jest.Mock).mockRejectedValue({
        response: { status: 403, data: { message: '権限がありません' } },
      });

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('レポートをダウンロードする権限がありません');
    });

    it('サーバーエラー（500）で適切なエラーメッセージを返す', async () => {
      // Mock: 500エラー
      (api.post as jest.Mock).mockRejectedValue({
        response: {
          status: 500,
          data: { message: 'PDF生成中にエラーが発生しました' },
        },
      });

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('PDF生成に失敗しました。PDF生成中にエラーが発生しました');
    });

    it('タイムアウトで適切なエラーメッセージを返す', async () => {
      // Mock: タイムアウトエラー
      (api.post as jest.Mock).mockRejectedValue({
        code: 'ECONNABORTED',
        message: 'timeout of 60000ms exceeded',
      });

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('タイムアウトしました。ネットワーク接続を確認してください');
    });

    it('ネットワークエラーで適切なエラーメッセージを返す', async () => {
      // Mock: ネットワークエラー（response なし）
      (api.post as jest.Mock).mockRejectedValue({
        message: 'Network Error',
      });

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('ネットワークエラーが発生しました');
    });

    it.skip('共有機能が利用できない場合にエラーを返す', async () => {
      // Mock: PDFバイナリ
      const mockBlob = new Blob(['mock-pdf-content'], { type: 'application/pdf', lastModified: Date.now() });
      (api.post as jest.Mock).mockResolvedValue({ data: mockBlob });

      // Mock: FileReader（成功）
      mockFileReader.readAsDataURL.mockImplementation(function (this: any) {
        Promise.resolve().then(() => {
          if (this.onloadend) {
            this.onloadend();
          }
        });
      });

      // Mock: Sharing（利用不可）
      (Sharing.isAvailableAsync as jest.Mock).mockResolvedValue(false);

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('この端末では共有機能がサポートされていません');
    });

    it.skip('FileReaderエラー時に適切なエラーメッセージを返す', async () => {
      // Mock: PDFバイナリ
      const mockBlob = new Blob(['mock-pdf-content'], { type: 'application/pdf', lastModified: Date.now() });
      (api.post as jest.Mock).mockResolvedValue({ data: mockBlob });

      // Mock: FileReader（エラー）
      mockFileReader.readAsDataURL.mockImplementation(function (this: any) {
        Promise.resolve().then(() => {
          if (this.onerror) {
            this.onerror(new Error('FileReader error'));
          }
        });
      });

      // 実行・検証
      await expect(
        pdfService.downloadAndShareMemberSummaryPdf(mockParams)
      ).rejects.toThrow('PDFファイルの読み込みに失敗しました');
    });
  });

  describe('deletePdfFile', () => {
    it('存在するファイルを削除できる', async () => {
      // Mock: ファイルが存在し、削除成功
      const existingFile = {
        ...mockFile,
        exists: true,
        delete: jest.fn().mockResolvedValue(undefined),
      };
      (File as jest.MockedFunction<typeof File>).mockImplementationOnce(() => existingFile as any);

      // 実行
      await pdfService.deletePdfFile('file:///cache/test.pdf');

      // 検証
      expect(File).toHaveBeenCalledWith('file:///cache/test.pdf');
      expect(existingFile.delete).toHaveBeenCalled();
    });

    it('存在しないファイルの削除をスキップする', async () => {
      // Mock: ファイルが存在しない
      const nonExistentFile = { ...mockFile, exists: false };
      (File as jest.MockedFunction<typeof File>).mockImplementationOnce(() => nonExistentFile as any);

      // 実行
      await pdfService.deletePdfFile('file:///cache/test.pdf');

      // 検証: deleteは呼ばれない
      expect(nonExistentFile.delete).not.toHaveBeenCalled();
    });

    it('削除エラー時も例外を投げない（クリーンアップ失敗は無視）', async () => {
      // Mock: delete() がエラー
      const errorFile = {
        ...mockFile,
        exists: true,
        delete: jest.fn().mockRejectedValue(new Error('Delete error')),
      };
      (File as jest.MockedFunction<typeof File>).mockImplementationOnce(() => errorFile as any);

      // 実行・検証（例外が投げられないことを確認）
      await expect(
        pdfService.deletePdfFile('file:///cache/test.pdf')
      ).resolves.not.toThrow();
    });
  });
});
