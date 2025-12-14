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
export const SubscriptionWebViewScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const route = useRoute<RouteProp<RouteParams, 'SubscriptionWebView'>>();
  const { url } = route.params;

  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState(false);
  const webViewRef = useRef<WebView>(null);

  console.log('[SubscriptionWebView] Initializing with URL:', url);

  /**
   * URL変更時のハンドラー
   * 購入完了またはキャンセルを検出
   */
  const handleNavigationStateChange = (navState: any) => {
    const { url: currentUrl } = navState;

    // モバイルAPI経由の成功URL（/api/subscriptions/success）
    if (currentUrl.includes('/api/subscriptions/success')) {
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
      return;
    }

    // モバイルAPI経由のキャンセルURL（/api/subscriptions/cancel）
    if (currentUrl.includes('/api/subscriptions/cancel')) {
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
      return;
    }

    // Web版の成功URL（後方互換）
    if (currentUrl.includes('/subscription/success') || currentUrl.includes('success=true')) {
      Alert.alert(
        '購入完了',
        'サブスクリプションの購入が完了しました。',
        [
          {
            text: 'OK',
            onPress: () => {
              navigation.navigate('SubscriptionManage');
            },
          },
        ]
      );
      return;
    }

    // Web版のキャンセルURL（後方互換）
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
    console.error('[SubscriptionWebView] WebView error:', {
      code: nativeEvent.code,
      description: nativeEvent.description,
      domain: nativeEvent.domain,
      url: nativeEvent.url,
    });
    
    setLoadError(true);
    setIsLoading(false);
    
    // ネットワークエラーの場合は再試行オプションを提供
    const isNetworkError = nativeEvent.code === -1004 || nativeEvent.code === -1009;
    const isSSLError = nativeEvent.code === -1200 || nativeEvent.code === -1202;
    
    let errorMessage = 'ページの読み込みに失敗しました。';
    if (isNetworkError) {
      errorMessage = 'ネットワーク接続に失敗しました。インターネット接続を確認してください。';
    } else if (isSSLError) {
      errorMessage = 'セキュリティ設定により接続できませんでした。アプリを再起動してください。';
    }
    
    Alert.alert(
      'エラー',
      errorMessage,
      isNetworkError || isSSLError
        ? [
            {
              text: '再試行',
              onPress: () => {
                setLoadError(false);
                setIsLoading(true);
                webViewRef.current?.reload();
              },
            },
            {
              text: 'キャンセル',
              style: 'cancel',
              onPress: () => navigation.goBack(),
            },
          ]
        : [
            {
              text: 'OK',
              onPress: () => navigation.goBack(),
            },
          ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {!loadError && (
        <WebView
          ref={webViewRef}
          source={{ uri: url }}
          onLoadStart={() => {
            console.log('[SubscriptionWebView] Load started');
            setIsLoading(true);
          }}
          onLoadEnd={() => {
            console.log('[SubscriptionWebView] Load ended');
            setIsLoading(false);
          }}
          onNavigationStateChange={handleNavigationStateChange}
          onError={handleError}
          onHttpError={(syntheticEvent) => {
            const { nativeEvent } = syntheticEvent;
            console.error('[SubscriptionWebView] HTTP error:', {
              statusCode: nativeEvent.statusCode,
              url: nativeEvent.url,
            });
          }}
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
          // タイムアウト設定
          cacheEnabled={false}
          // ネットワーク設定
          allowsInlineMediaPlayback={true}
          mediaPlaybackRequiresUserAction={false}
          // SSL証明書エラーを無視（開発環境のみ - 本番では削除推奨）
          onShouldStartLoadWithRequest={(request) => {
            console.log('[SubscriptionWebView] Loading URL:', request.url);
            return true;
          }}
        />
      )}
      
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
