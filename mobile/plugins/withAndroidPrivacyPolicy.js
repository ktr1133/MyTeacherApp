/**
 * Expo Config Plugin: プライバシーポリシーURLをAndroidManifestに追加
 * 
 * このプラグインは、Android端末の「アプリ情報」画面にプライバシーポリシーへのリンクを表示するため、
 * AndroidManifest.xmlに必要なメタデータを自動的に追加します。
 * 
 * @see https://developer.android.com/reference/android/support/v4/app/AppLaunchChecker
 */
const { withAndroidManifest } = require('@expo/config-plugins');

// 環境変数からAPIのURLを取得し、プライバシーポリシーのURLを構築
// EXPO_PUBLIC_API_URL が設定されている場合は、/api を除いたベースURLを使用
const getPrivacyPolicyUrl = () => {
  const apiUrl = process.env.EXPO_PUBLIC_API_URL;
  
  if (apiUrl) {
    // /api を除いたベースURLを取得
    const baseUrl = apiUrl.replace(/\/api\/?$/, '');
    return `${baseUrl}/privacy-policy`;
  }
  
  // 環境変数が設定されていない場合は本番URLを使用
  return 'https://my-teacher-app.com/privacy-policy';
};

const PRIVACY_POLICY_URL = getPrivacyPolicyUrl();

module.exports = function withAndroidPrivacyPolicy(config) {
  return withAndroidManifest(config, (config) => {
    const { manifest } = config.modResults;

    // <application>タグを取得
    if (!manifest.application) {
      manifest.application = [{}];
    }

    const application = manifest.application[0];

    // 既存のmeta-dataを取得（存在しない場合は初期化）
    if (!application['meta-data']) {
      application['meta-data'] = [];
    }

    // プライバシーポリシーのmeta-dataが既に存在するか確認
    const existingMetaData = application['meta-data'].find(
      (item) => item.$?.['android:name'] === 'android.support.PRIVACY_POLICY_URL'
    );

    if (!existingMetaData) {
      // meta-dataを追加
      application['meta-data'].push({
        $: {
          'android:name': 'android.support.PRIVACY_POLICY_URL',
          'android:value': PRIVACY_POLICY_URL,
        },
      });

      console.log(`✅ プライバシーポリシーURLを追加しました: ${PRIVACY_POLICY_URL}`);
    } else {
      // 既存のURLを更新
      existingMetaData.$['android:value'] = PRIVACY_POLICY_URL;
      console.log(`✅ プライバシーポリシーURLを更新しました: ${PRIVACY_POLICY_URL}`);
    }

    return config;
  });
};
