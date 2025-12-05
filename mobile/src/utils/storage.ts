/**
 * AsyncStorageユーティリティ
 * ローカルストレージへのデータ保存・取得を簡易化
 */
import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * データを保存
 */
export const setItem = async (key: string, value: string): Promise<void> => {
  try {
    await AsyncStorage.setItem(key, value);
  } catch (error) {
    console.error(`Failed to save ${key}:`, error);
    throw error;
  }
};

/**
 * データを取得
 */
export const getItem = async (key: string): Promise<string | null> => {
  try {
    return await AsyncStorage.getItem(key);
  } catch (error) {
    console.error(`Failed to get ${key}:`, error);
    return null;
  }
};

/**
 * データを削除
 */
export const removeItem = async (key: string): Promise<void> => {
  try {
    await AsyncStorage.removeItem(key);
  } catch (error) {
    console.error(`Failed to remove ${key}:`, error);
    throw error;
  }
};

/**
 * すべてのデータをクリア
 */
export const clear = async (): Promise<void> => {
  try {
    await AsyncStorage.clear();
  } catch (error) {
    console.error('Failed to clear storage:', error);
    throw error;
  }
};
