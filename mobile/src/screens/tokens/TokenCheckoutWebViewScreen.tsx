/**
 * トークン購入WebView画面
 * 
 * Stripe CheckoutをWebViewで表示し、トークン購入処理を実行
 * 
 * @module screens/tokens/TokenCheckoutWebViewScreen
 */

import React, { useState, useRef, useMemo } from 'react';
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
import { API_CONFIG } from '../../utils/constants';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useResponsive, getSpacing, getFontSize, getBorderRadius } from '../../utils/responsive';

type RouteParams = {
  TokenCheckoutWebView: {
    url: string;
    title?: string;
  };
};

/**
 * トークン購入WebView画面コンポーネント
 * 
 * 機能:
 * - Stripe Checkout URLをWebViewで表示
 * - 購入完了/キャンセルの検出
 * - ローディング表示
 * - エラーハンドリング
 * 
 * @returns {JSX.Element} WebView画面
 */
export const TokenCheckoutWebViewScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const route = useRoute<RouteProp<RouteParams, 'TokenCheckoutWebView'>>();
  const { url } = route.params;
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, colors), [width, colors]);

  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState(false);
  const webViewRef = useRef<WebView>(null);

  /**
   * URL変更時のハンドラー
   * 購入完了またはキャンセルを検出
   */
  const handleNavigationStateChange = (navState: any) => {
    const { url: currentUrl } = navState;

    // モバイルAPI経由の成功URL（/api/tokens/success）
    if (currentUrl.includes('/api/tokens/success')) {
      Alert.alert(
        '購入完了',
        'トークンの購入が完了しました。',
        [
          {
            text: 'OK',
            onPress: () => {
              // トークン購入画面に戻る
              navigation.navigate('TokenPackageList');
            },
          },
        ]
      );
      return;
    }

    // モバイルAPI経由のキャンセルURL（/api/tokens/cancel）
    if (currentUrl.includes('/api/tokens/cancel')) {
      Alert.alert(
        'キャンセル',
        'トークンの購入をキャンセルしました。',
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
    if (currentUrl.includes('/tokens/success') || currentUrl.includes('success=true')) {
      Alert.alert(
        '購入完了',
        'トークンの購入が完了しました。',
        [
          {
            text: 'OK',
            onPress: () => {
              navigation.navigate('TokenPackageList');
            },
          },
        ]
      );
      return;
    }

    // Web版のキャンセルURL（後方互換）
    if (currentUrl.includes('/tokens/cancel') || currentUrl.includes('canceled=true')) {
      Alert.alert(
        'キャンセル',
        'トークンの購入をキャンセルしました。',
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
    console.error('[TokenCheckoutWebView] WebView error:', {
      code: nativeEvent.code,
      description: nativeEvent.description,
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
            setIsLoading(true);
          }}
          onLoadEnd={() => {
            setIsLoading(false);
          }}
          onNavigationStateChange={handleNavigationStateChange}
          onError={handleError}
          onHttpError={(syntheticEvent) => {
            const { nativeEvent } = syntheticEvent;
            console.error('[TokenCheckoutWebView] HTTP error:', {
              statusCode: nativeEvent.statusCode,
              url: nativeEvent.url,
            });
            
            // HTTPエラーもユーザーに通知
            if (nativeEvent.statusCode >= 400) {
              Alert.alert(
                'エラー',
                `サーバーエラーが発生しました（${nativeEvent.statusCode}）`,
                [
                  {
                    text: 'OK',
                    onPress: () => navigation.goBack(),
                  },
                ]
              );
            }
          }}
          style={styles.webView}
          startInLoadingState={true}
          renderLoading={() => (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" color={accent.primary} />
            </View>
          )}
          // iOS設定
          allowsBackForwardNavigationGestures={true}
          allowsLinkPreview={false} // Stripe Checkoutでリンクプレビューを無効化
          sharedCookiesEnabled={true} // Cookie共有を有効化（Stripe Checkoutに必須）
          // Android設定
          domStorageEnabled={true}
          javaScriptEnabled={true}
          thirdPartyCookiesEnabled={true}
          // ネットワーク設定
          cacheEnabled={false} // キャッシュを無効化（常に最新のCheckoutセッションを読み込む）
          incognito={false}
          // メディア設定
          allowsInlineMediaPlayback={true}
          mediaPlaybackRequiresUserAction={false}
          // セキュリティ設定
          mixedContentMode="compatibility" // 互換性モード（"always"から変更）
          // URL読み込み制御
          onShouldStartLoadWithRequest={(request) => {
            // ブロックされるURLパターンをチェック
            if (request.url.includes('about:blank')) {
              return false;
            }
            
            // バックエンドURL（開発環境: ngrok、本番環境: 通常のHTTPS）を取得
            const backendHost = API_CONFIG.BASE_URL.replace('/api', '').replace('https://', '').replace('http://', '');
            const isNgrok = backendHost.includes('ngrok');
            const isLocalhost = request.url.includes('localhost') || request.url.includes('127.0.0.1');
            
            // バックエンドへのリダイレクトを検出（成功/キャンセル）
            // localhost も開発環境として扱う（モバイルから接続不可）
            if (request.url.includes(backendHost) || isLocalhost) {
              
              // 成功URLの場合
              if (request.url.includes('/api/tokens/success') || request.url.includes('/tokens/success')) {
                // 開発環境（ngrok/localhost）の場合: WebView接続をスキップしてネイティブ処理
                // 本番環境: 通常通りWebViewで読み込み（onNavigationStateChangeで処理）
                if (isNgrok || isLocalhost) {
                  Alert.alert(
                    '購入完了',
                    'トークンの購入が完了しました。',
                    [
                      {
                        text: 'OK',
                        onPress: () => {
                          navigation.navigate('TokenPackageList');
                        },
                      },
                    ]
                  );
                  return false; // ngrok/localhostへのWebView接続をブロック
                }
                
                // 本番環境: WebViewで読み込み許可（onNavigationStateChangeで処理）
                return true;
              }
              
              // キャンセルURLの場合
              if (request.url.includes('/api/tokens/cancel') || request.url.includes('/tokens/cancel')) {
                // 開発環境（ngrok/localhost）の場合: WebView接続をスキップしてネイティブ処理
                // 本番環境: 通常通りWebViewで読み込み（onNavigationStateChangeで処理）
                if (isNgrok || isLocalhost) {
                  Alert.alert(
                    'キャンセル',
                    'トークンの購入をキャンセルしました。',
                    [
                      {
                        text: 'OK',
                        onPress: () => {
                          navigation.goBack();
                        },
                      },
                    ]
                  );
                  return false; // ngrok/localhostへのWebView接続をブロック
                }
                
                // 本番環境: WebViewで読み込み許可（onNavigationStateChangeで処理）
                return true;
              }
            }
            
            return true; // その他のURLは許可
          }}
          onMessage={(event) => {
            console.log('[TokenCheckoutWebView] Message from WebView:', event.nativeEvent.data);
          }}
          onContentProcessDidTerminate={() => {
            Alert.alert(
              'エラー',
              'WebViewプロセスが終了しました。再読み込みしてください。',
              [
                {
                  text: '再読み込み',
                  onPress: () => webViewRef.current?.reload(),
                },
                {
                  text: 'キャンセル',
                  style: 'cancel',
                  onPress: () => navigation.goBack(),
                },
              ]
            );
          }}
        />
      )}
      
      {isLoading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color={accent.primary} />
        </View>
      )}
    </SafeAreaView>
  );
};

const createStyles = (width: number, colors: any) => ({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  webView: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center' as const,
    alignItems: 'center' as const,
    backgroundColor: colors.background,
  },
  loadingOverlay: {
    position: 'absolute' as const,
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: 'center' as const,
    alignItems: 'center' as const,
    backgroundColor: colors.background + 'E6', // 90% opacity
  },
});
