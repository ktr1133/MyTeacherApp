/**
 * テスト用の最小限Drawerナビゲーター
 * エラー原因の切り分け用
 */

import React from 'react';
import { createDrawerNavigator } from '@react-navigation/drawer';
import { View, Text, StyleSheet } from 'react-native';

const Drawer = createDrawerNavigator();

// テスト用のダミー画面
function TestScreen() {
  return (
    <View style={styles.container}>
      <Text style={styles.text}>Test Screen</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  text: {
    fontSize: 18,
    fontWeight: 'bold',
  },
});

/**
 * 最小限のDrawerナビゲーター（デバッグ用）
 */
export default function DrawerNavigatorTest() {
  return (
    <Drawer.Navigator initialRouteName="Test">
      <Drawer.Screen
        name="Test"
        component={TestScreen}
        options={{ title: 'テスト画面' }}
      />
    </Drawer.Navigator>
  );
}
