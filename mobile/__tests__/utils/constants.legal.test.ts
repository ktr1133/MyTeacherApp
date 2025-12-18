/**
 * constants.ts - WEB_APP_URL のテスト
 * 
 * 環境変数からWebアプリURLが正しく生成されることをテスト
 */

describe('WEB_APP_URL', () => {
  // 元の環境変数を保存
  const originalEnv = process.env;

  beforeEach(() => {
    jest.resetModules();
    process.env = { ...originalEnv };
  });

  afterAll(() => {
    process.env = originalEnv;
  });

  test('EXPO_PUBLIC_API_URLが設定されている場合、/apiを除去してWEB_APP_URLを生成', () => {
    process.env.EXPO_PUBLIC_API_URL = 'https://my-teacher-app.com/api';
    
    const { WEB_APP_URL } = require('../../src/utils/constants');
    
    expect(WEB_APP_URL).toBe('https://my-teacher-app.com');
  });

  test('EXPO_PUBLIC_API_URLが未設定の場合、デフォルトURLから/apiを除去', () => {
    delete process.env.EXPO_PUBLIC_API_URL;
    
    const { WEB_APP_URL } = require('../../src/utils/constants');
    
    // デフォルトURL（ngrok）から/apiが除去されていることを確認
    expect(WEB_APP_URL).toContain('ngrok-free.dev');
    expect(WEB_APP_URL).not.toContain('/api');
  });

  test('localhost URLの場合も正しく/apiを除去', () => {
    process.env.EXPO_PUBLIC_API_URL = 'http://localhost:8080/api';
    
    const { WEB_APP_URL } = require('../../src/utils/constants');
    
    expect(WEB_APP_URL).toBe('http://localhost:8080');
  });

  test('API_CONFIGとWEB_APP_URLが整合性を保つ', () => {
    process.env.EXPO_PUBLIC_API_URL = 'https://example.com/api';
    
    const { API_CONFIG, WEB_APP_URL } = require('../../src/utils/constants');
    
    expect(API_CONFIG.BASE_URL).toBe('https://example.com/api');
    expect(WEB_APP_URL).toBe('https://example.com');
    expect(WEB_APP_URL).toBe(API_CONFIG.BASE_URL.replace('/api', ''));
  });
});

describe('法的情報ページURL生成', () => {
  const originalEnv = process.env;

  beforeEach(() => {
    jest.resetModules();
    process.env = { ...originalEnv };
  });

  afterAll(() => {
    process.env = originalEnv;
  });

  test('プライバシーポリシーURLが正しく生成される', () => {
    process.env.EXPO_PUBLIC_API_URL = 'https://my-teacher-app.com/api';
    
    const { WEB_APP_URL } = require('../../src/utils/constants');
    const privacyPolicyUrl = `${WEB_APP_URL}/privacy-policy`;
    
    expect(privacyPolicyUrl).toBe('https://my-teacher-app.com/privacy-policy');
  });

  test('利用規約URLが正しく生成される', () => {
    process.env.EXPO_PUBLIC_API_URL = 'https://my-teacher-app.com/api';
    
    const { WEB_APP_URL } = require('../../src/utils/constants');
    const termsUrl = `${WEB_APP_URL}/terms-of-service`;
    
    expect(termsUrl).toBe('https://my-teacher-app.com/terms-of-service');
  });

  test('開発環境（localhost）でも正しくURL生成される', () => {
    process.env.EXPO_PUBLIC_API_URL = 'http://localhost:8080/api';
    
    const { WEB_APP_URL } = require('../../src/utils/constants');
    const privacyPolicyUrl = `${WEB_APP_URL}/privacy-policy`;
    
    expect(privacyPolicyUrl).toBe('http://localhost:8080/privacy-policy');
  });
});
