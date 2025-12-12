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

// React Native Worklets のモック（Reanimatedの依存）
jest.mock('react-native-worklets', () => ({
  useSharedValue: jest.fn(),
  useWorklet: jest.fn(),
  runOnJS: jest.fn(),
  runOnUI: jest.fn(),
}));

// React Native Reanimated のモック
jest.mock('react-native-reanimated', () => ({
  default: {
    View: require('react-native').View,
    Text: require('react-native').Text,
    ScrollView: require('react-native').ScrollView,
    Image: require('react-native').Image,
    createAnimatedComponent: (component) => component,
    call: jest.fn(),
  },
  useSharedValue: jest.fn(),
  useAnimatedStyle: jest.fn(() => ({})),
  withTiming: jest.fn((value) => value),
  withSpring: jest.fn((value) => value),
  withDelay: jest.fn((_, value) => value),
  Easing: {
    linear: jest.fn(),
    ease: jest.fn(),
    quad: jest.fn(),
    cubic: jest.fn(),
  },
}));

// React Native Gesture Handler のモック
jest.mock('react-native-gesture-handler', () => {
  const View = require('react-native/Libraries/Components/View/View');
  return {
    Swipeable: View,
    DrawerLayout: View,
    State: {},
    ScrollView: View,
    Slider: View,
    Switch: View,
    TextInput: View,
    ToolbarAndroid: View,
    ViewPagerAndroid: View,
    DrawerLayoutAndroid: View,
    WebView: View,
    NativeViewGestureHandler: View,
    TapGestureHandler: View,
    FlingGestureHandler: View,
    ForceTouchGestureHandler: View,
    LongPressGestureHandler: View,
    PanGestureHandler: View,
    PinchGestureHandler: View,
    RotationGestureHandler: View,
    /* Buttons */
    RawButton: View,
    BaseButton: View,
    RectButton: View,
    BorderlessButton: View,
    /* Other */
    FlatList: View,
    gestureHandlerRootHOC: jest.fn(),
    Directions: {},
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
  // useFocusEffectは渡されたコールバックを即座に実行
  useFocusEffect: (callback) => {
    callback();
  },
}));

// React Native Safe Area Context のモック
jest.mock('react-native-safe-area-context', () => {
  const React = require('react');
  
  // SafeAreaInsetsContextを作成（@react-navigation/elementsが使用）
  const SafeAreaInsetsContext = React.createContext({
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
  });
  
  // SafeAreaFrameContextを作成
  const SafeAreaFrameContext = React.createContext({
    x: 0,
    y: 0,
    width: 375,
    height: 812,
  });
  
  return {
    SafeAreaProvider: ({ children }) => children,
    SafeAreaView: require('react-native').View,
    SafeAreaInsetsContext: SafeAreaInsetsContext,
    SafeAreaFrameContext: SafeAreaFrameContext,
    useSafeAreaInsets: () => ({ top: 0, right: 0, bottom: 0, left: 0 }),
    useSafeAreaFrame: () => ({ x: 0, y: 0, width: 375, height: 812 }),
    initialWindowMetrics: {
      insets: { top: 0, right: 0, bottom: 0, left: 0 },
      frame: { x: 0, y: 0, width: 375, height: 812 },
    },
  };
});

// Navigation Reference のモック
jest.mock('./src/utils/navigationRef', () => {
  const mockNavigationRef = {
    isReady: jest.fn(() => true),
    navigate: jest.fn(),
    reset: jest.fn(),
    goBack: jest.fn(),
    dispatch: jest.fn(),
    setParams: jest.fn(),
    addListener: jest.fn(() => jest.fn()),
    removeListener: jest.fn(),
    current: null,
  };

  return {
    navigationRef: mockNavigationRef,
    navigate: jest.fn(),
    resetTo: jest.fn(),
  };
});

// console の出力を抑制(テスト実行時のノイズ削減)
global.console = {
  ...console,
  // log: jest.fn(), // デバッグ時はコメントアウト解除
  warn: jest.fn(),
  error: jest.fn(),
};

// Firebase Messaging のモック（Phase 2.B-7.5: Push通知機能）
jest.mock('@react-native-firebase/messaging', () => {
  const mockMessaging = jest.fn(() => ({
    requestPermission: jest.fn(),
    getToken: jest.fn(),
    onMessage: jest.fn(() => jest.fn()),
    onNotificationOpenedApp: jest.fn(() => jest.fn()),
    getInitialNotification: jest.fn(),
    setBackgroundMessageHandler: jest.fn(),
    deleteToken: jest.fn(),
  }));

  // AuthorizationStatus 定数
  mockMessaging.AuthorizationStatus = {
    NOT_DETERMINED: -1,
    DENIED: 0,
    AUTHORIZED: 1,
    PROVISIONAL: 2,
  };

  return {
    __esModule: true,
    default: mockMessaging,
    FirebaseMessagingTypes: {
      AuthorizationStatus: mockMessaging.AuthorizationStatus,
    },
  };
});

// Alert のモック強化（Jest環境のtear down後のエラー防止）
const { Alert } = require('react-native');
const originalAlert = Alert.alert;
Alert.alert = jest.fn((...args) => {
  // テスト実行中のみ動作、tear down後は何もしない
  if (global.window && global.document) {
    return originalAlert(...args);
  }
  return undefined;
});

// タイムアウト設定（非同期テスト用）
jest.setTimeout(10000);
