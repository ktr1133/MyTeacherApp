/**
 * サブスクリプション購入WebView画面
 * 
 * Stripe CheckoutをWebViewで表示し、購入処理を実行
 * 
 * @module screens/subscriptions/SubscriptionWebViewScreen
 */

import React, { useState, useRef } from 'react';
import {
  View,
  StyleSheet,
  ActivityIndicator,
  SafeAreaView,
  Alert,
} from 'react-native';
import { WebView } from 'react-native-webview';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

type RouteParams = {
  SubscriptionWebView: {
    url: string;
    title?: string;
  };
};

/**
 * サブスクリプション購入WebView画面コンポーネント
 * 
 * 機能:
 * - Stripe Checkout URLをWebViewで表示
 * - 購入完了/キャンセルの検出
 * - ローディング表示
 * - エラーハンドリング
 * 
 * @returns {JSX.Element} WebView画面
 */
const SubscriptionWebViewScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const route = useRoute<RouteProp<RouteParams, 'SubscriptionWebView'>>();
  const { url } = route.params;

  const [isLoading, setIsLoading] = useState(true);
  const webViewRef = useRef<WebView>(null);

  /**
   * URL変更時のハンドラー
   * 購入完了またはキャンセルを検出
   */
  const handleNavigationStateChange = (navState: any) => {
    const { url: currentUrl } = navState;

    // Stripe Checkoutの成功URL（successパラメータ含む）
    if (currentUrl.includes('/subscription/success') || currentUrl.includes('success=true')) {
      Alert.alert(
        '購入完了',
        'サブスクリプションの購入が完了しました。',
        [
          {
            text: 'OK',
            onPress: () => {
              // サブスクリプション管理画面に戻る
              navigation.navigate('SubscriptionManage');
            },
          },
        ]
      );
    }

    // Stripe CheckoutのキャンセルURL
    if (currentUrl.includes('/subscription/cancel') || currentUrl.includes('canceled=true')) {
      Alert.alert(
        'キャンセル',
        'サブスクリプションの購入をキャンセルしました。',
        [
          {
            text: 'OK',
            onPress: () => {
              navigation.goBack();
            },
          },
        ]
      );
    }
  };

  /**
   * WebViewエラーハンドラー
   */
  const handleError = (syntheticEvent: any) => {
    const { nativeEvent } = syntheticEvent;
    console.error('[SubscriptionWebView] WebView error:', nativeEvent);
    
    Alert.alert(
      'エラー',
      'ページの読み込みに失敗しました。',
      [
        {
          text: 'OK',
          onPress: () => navigation.goBack(),
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <WebView
        ref={webViewRef}
        source={{ uri: url }}
        onLoadStart={() => setIsLoading(true)}
        onLoadEnd={() => setIsLoading(false)}
        onNavigationStateChange={handleNavigationStateChange}
        onError={handleError}
        style={styles.webView}
        startInLoadingState={true}
        renderLoading={() => (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#4F46E5" />
          </View>
        )}
        // iOS設定
        allowsBackForwardNavigationGestures={true}
        // Android設定
        domStorageEnabled={true}
        javaScriptEnabled={true}
        // セキュリティ設定
        mixedContentMode="always"
      />
      
      {isLoading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color="#4F46E5" />
        </View>
      )}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  webView: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
  },
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
  },
});

export default SubscriptionWebViewScreen;
