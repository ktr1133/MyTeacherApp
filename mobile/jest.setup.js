/**
 * Jest セットアップファイル
 * 
 * テスト実行前にグローバルな設定とモックを行う
 * jest-expoプリセットが多くのモックを自動処理するため、最小限の設定のみ記述
 */

// AsyncStorage のモック
jest.mock('@react-native-async-storage/async-storage', () =>
  require('@react-native-async-storage/async-storage/jest/async-storage-mock')
);

// Expo Vector Icons のモック
jest.mock('@expo/vector-icons', () => {
  const React = require('react');
  return {
    MaterialIcons: 'MaterialIcons',
    FontAwesome: 'FontAwesome',
    Ionicons: 'Ionicons',
    MaterialCommunityIcons: 'MaterialCommunityIcons',
  };
});

// React Navigation のモック
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: () => ({
    navigate: jest.fn(),
    goBack: jest.fn(),
    replace: jest.fn(),
    reset: jest.fn(),
  }),
  useRoute: () => ({
    params: {},
  }),
  useFocusEffect: jest.fn(),
}));

// console の出力を抑制（テスト実行時のノイズ削減）
global.console = {
  ...console,
  // log: jest.fn(), // デバッグ時はコメントアウト解除
  warn: jest.fn(),
  error: jest.fn(),
};

// タイムアウト設定（非同期テスト用）
jest.setTimeout(10000);
