export default {
  expo: {
    name: "MyTeacher",
    slug: "mobile",
    version: "1.0.0",
    orientation: "default",
    icon: "./assets/icon.png",
    userInterfaceStyle: "automatic",
    newArchEnabled: true,
    extra: {
      eas: {
        projectId: "293f5236-a789-455d-b73a-e990044fc7f0"
      },
      privacyPolicyUrl: "https://my-teacher-app.com/privacy-policy",
      termsOfServiceUrl: "https://my-teacher-app.com/terms-of-service"
    },
    splash: {
      image: "./assets/splash-icon.png",
      resizeMode: "contain",
      backgroundColor: "#ffffff"
    },
    ios: {
      supportsTablet: true,
      bundleIdentifier: "com.myteacherfamco.app",
      googleServicesFile: process.env.GOOGLE_SERVICES_IOS ?? "./GoogleService-Info.plist",
      buildNumber: "1",
      infoPlist: {
        ITSAppUsesNonExemptEncryption: false,
        UIBackgroundModes: ["remote-notification"],
        // App Transport Security (ATS)設定
        // 本番環境: 個別ドメイン設定推奨
        // 開発環境: 一時的に全許可（Stripe Checkout接続問題対応）
        NSAppTransportSecurity: process.env.EAS_BUILD_PROFILE === "production" 
          ? {
              NSExceptionDomains: {
                "my-teacher-app.com": {
                  NSExceptionRequiresForwardSecrecy: false,
                  NSIncludesSubdomains: true
                },
                "stripe.com": {
                  NSExceptionRequiresForwardSecrecy: false,
                  NSIncludesSubdomains: true
                }
              }
            }
          : {
              NSAllowsArbitraryLoads: true, // 開発環境のみ
              NSExceptionDomains: {
                "localhost": {
                  NSExceptionAllowsInsecureHTTPLoads: true,
                  NSExceptionRequiresForwardSecrecy: false,
                }
              }
            }
      },
      entitlements: {
        "aps-environment": process.env.EAS_BUILD_PROFILE === "production" ? "production" : "development"
      }
    },
    android: {
      adaptiveIcon: {
        foregroundImage: "./assets/adaptive-icon.png",
        backgroundColor: "#ffffff"
      },
      edgeToEdgeEnabled: true,
      predictiveBackGestureEnabled: false,
      package: "com.myteacherfamco.app",
      versionCode: 1,
      googleServicesFile: "./google-services.json",
      permissions: [],
      intentFilters: [
        {
          action: "VIEW",
          data: {
            scheme: "https",
            host: "my-teacher-app.com"
          },
          category: ["BROWSABLE", "DEFAULT"]
        }
      ]
    },
    web: {
      favicon: "./assets/favicon.png"
    },
    plugins: [
      "@react-native-firebase/app",
      "@react-native-firebase/messaging",
      [
        "expo-build-properties",
        {
          ios: {
            useFrameworks: "static",
            forceStaticLinking: [
              "RNFBApp",
              "RNFBMessaging"
            ]
          }
        }
      ],
      "./plugins/withAndroidPrivacyPolicy"
    ]
  }
};
