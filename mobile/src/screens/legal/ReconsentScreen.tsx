/**
 * ReconsentScreen - プライバシーポリシー・利用規約 再同意画面
 * 
 * 機能:
 * - 最新版への再同意が必要な旨を通知
 * - バージョン情報の表示（現在 → 最新）
 * - プライバシーポリシー・利用規約への同意チェックボックス
 * - 各ドキュメントへのリンク（WebView画面に遷移）
 * - 同意送信処理（API呼び出し）
 * - ダークモード対応（adult/child両テーマ）
 * - レスポンシブデザイン
 * 
 * Phase 6C: 再同意プロセス実装
 */

import React, { useState, useMemo, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
  ScrollView,
  Alert,
  Platform,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useResponsive, getSpacing, getFontSize, getBorderRadius } from '../../utils/responsive';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useChildTheme } from '../../hooks/useChildTheme';
import { Ionicons } from '@expo/vector-icons';
import LegalService from '../../services/legal.service';
import type { ConsentStatusResponse } from '../../types/legal.types';

/**
 * ReconsentScreen コンポーネント
 */
export const ReconsentScreen: React.FC = () => {
  const navigation = useNavigation<any>();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, colors, accent.primary, themeType), [width, colors, accent.primary, themeType]);

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [consentStatus, setConsentStatus] = useState<ConsentStatusResponse | null>(null);
  const [privacyConsent, setPrivacyConsent] = useState(false);
  const [termsConsent, setTermsConsent] = useState(false);

  /**
   * 同意状態を取得
   */
  useEffect(() => {
    const fetchConsentStatus = async () => {
      try {
        const status = await LegalService.getConsentStatus();
        setConsentStatus(status);
      } catch (error: any) {
        const errorMessage = isChildTheme
          ? 'じょうほうの とりこみに しっぱいしました'
          : '情報の取得に失敗しました';
        Alert.alert('エラー', errorMessage);
      } finally {
        setLoading(false);
      }
    };

    fetchConsentStatus();
  }, [isChildTheme]);

  /**
   * 同意送信ハンドラー
   */
  const handleSubmit = async () => {
    // バリデーション
    if (!privacyConsent || !termsConsent) {
      const message = isChildTheme
        ? 'すべてに チェックを いれてね！'
        : 'プライバシーポリシーと利用規約の両方に同意してください。';
      Alert.alert('確認', message);
      return;
    }

    setSubmitting(true);

    try {
      await LegalService.submitReconsent({
        privacy_policy_consent: privacyConsent,
        terms_consent: termsConsent,
      });

      const successMessage = isChildTheme
        ? 'どういが かんりょうしました！'
        : '同意が完了しました。';

      Alert.alert('完了', successMessage, [
        {
          text: 'OK',
          onPress: () => {
            // ダッシュボードに戻る
            navigation.reset({
              index: 0,
              routes: [{ name: 'Main' }],
            });
          },
        },
      ]);
    } catch (error: any) {
      const errorMessage = isChildTheme
        ? 'どういの きろくに しっぱいしました'
        : '同意の記録に失敗しました。もう一度お試しください。';
      Alert.alert('エラー', errorMessage);
    } finally {
      setSubmitting(false);
    }
  };

  /**
   * プライバシーポリシーを開く
   */
  const handleOpenPrivacyPolicy = () => {
    navigation.navigate('PrivacyPolicy');
  };

  /**
   * 利用規約を開く
   */
  const handleOpenTerms = () => {
    navigation.navigate('TermsOfService');
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={accent.primary} />
        <Text style={styles.loadingText}>
          {isChildTheme ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar
        barStyle={themeType === 'child' ? 'light-content' : colors.background === '#000000' ? 'light-content' : 'dark-content'}
        backgroundColor={colors.background}
      />

      {/* ヘッダー */}
      <View style={styles.header}>
        <View style={styles.headerLeft} />
        <Text style={styles.headerTitle}>
          {isChildTheme ? 'おやくそくの かくにん' : '規約の更新確認'}
        </Text>
        <View style={styles.headerRight} />
      </View>

      <ScrollView style={styles.content} contentContainerStyle={styles.contentContainer}>
        {/* 通知バナー */}
        <View style={styles.noticeBanner}>
          <Ionicons name="information-circle" size={24} color={accent.primary} />
          <Text style={styles.noticeText}>
            {isChildTheme
              ? 'おやくそくが あたらしく なりました。\nもういちど かくにんしてね！'
              : 'プライバシーポリシーまたは利用規約が更新されました。\n最新版への同意が必要です。'}
          </Text>
        </View>

        {/* バージョン情報 */}
        {consentStatus && (
          <View style={styles.versionSection}>
            <Text style={styles.sectionTitle}>
              {isChildTheme ? 'バージョンじょうほう' : 'バージョン情報'}
            </Text>

            {/* プライバシーポリシー */}
            <View style={styles.versionCard}>
              <Text style={styles.versionLabel}>
                {isChildTheme ? 'プライバシー' : 'プライバシーポリシー'}
              </Text>
              <View style={styles.versionRow}>
                <Text style={styles.versionCurrent}>
                  {isChildTheme ? 'いま: ' : '現在: '}
                  {consentStatus.privacy_policy.current_version || '未同意'}
                </Text>
                <Ionicons name="arrow-forward" size={16} color={colors.text.secondary} />
                <Text style={styles.versionRequired}>
                  {isChildTheme ? 'さいしん: ' : '最新: '}
                  {consentStatus.privacy_policy.required_version}
                </Text>
              </View>
            </View>

            {/* 利用規約 */}
            <View style={styles.versionCard}>
              <Text style={styles.versionLabel}>
                {isChildTheme ? 'りようきやく' : '利用規約'}
              </Text>
              <View style={styles.versionRow}>
                <Text style={styles.versionCurrent}>
                  {isChildTheme ? 'いま: ' : '現在: '}
                  {consentStatus.terms.current_version || '未同意'}
                </Text>
                <Ionicons name="arrow-forward" size={16} color={colors.text.secondary} />
                <Text style={styles.versionRequired}>
                  {isChildTheme ? 'さいしん: ' : '最新: '}
                  {consentStatus.terms.required_version}
                </Text>
              </View>
            </View>
          </View>
        )}

        {/* 同意チェックボックス */}
        <View style={styles.consentSection}>
          <Text style={styles.sectionTitle}>
            {isChildTheme ? '✅ どうい' : '✅ 同意が必要な項目'}
          </Text>

          {/* プライバシーポリシー */}
          <TouchableOpacity
            style={styles.checkboxContainer}
            onPress={() => setPrivacyConsent(!privacyConsent)}
          >
            <View style={[styles.checkbox, privacyConsent && styles.checkboxChecked]}>
              {privacyConsent && <Ionicons name="checkmark" size={18} color="#FFFFFF" />}
            </View>
            <View style={styles.checkboxTextContainer}>
              <Text style={styles.checkboxText}>
                <Text style={styles.link} onPress={handleOpenPrivacyPolicy}>
                  {isChildTheme ? 'プライバシー' : `プライバシーポリシー（v${consentStatus?.privacy_policy.required_version}）`}
                </Text>
                <Text> に同意します</Text>
                <Text style={styles.required}> *</Text>
              </Text>
            </View>
          </TouchableOpacity>

          {/* 利用規約 */}
          <TouchableOpacity
            style={styles.checkboxContainer}
            onPress={() => setTermsConsent(!termsConsent)}
          >
            <View style={[styles.checkbox, termsConsent && styles.checkboxChecked]}>
              {termsConsent && <Ionicons name="checkmark" size={18} color="#FFFFFF" />}
            </View>
            <View style={styles.checkboxTextContainer}>
              <Text style={styles.checkboxText}>
                <Text style={styles.link} onPress={handleOpenTerms}>
                  {isChildTheme ? 'りようきやく' : `利用規約（v${consentStatus?.terms.required_version}）`}
                </Text>
                <Text> に同意します</Text>
                <Text style={styles.required}> *</Text>
              </Text>
            </View>
          </TouchableOpacity>
        </View>

        {/* 注意事項 */}
        <View style={styles.warningSection}>
          <Text style={styles.warningTitle}>
            {isChildTheme ? '⚠️ ちゅうい' : '⚠️ ご注意'}
          </Text>
          <Text style={styles.warningText}>
            {isChildTheme
              ? '• どうい しないと つかえなく なります\n• リンクを おして ないようを みてね'
              : '• 同意いただけない場合、サービスの継続利用ができません\n• リンクをタップして内容をご確認ください\n• 同意後は通常通りサービスをご利用いただけます'}
          </Text>
        </View>

        {/* 送信ボタン */}
        <TouchableOpacity
          style={[
            styles.submitButton,
            (!privacyConsent || !termsConsent || submitting) && styles.submitButtonDisabled,
          ]}
          onPress={handleSubmit}
          disabled={!privacyConsent || !termsConsent || submitting}
        >
          {submitting ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.submitButtonText}>
              {isChildTheme ? 'どういして つづける' : '同意して続ける'}
            </Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
};

/**
 * スタイル作成関数
 */
const createStyles = (width: number, colors: any, accent: string, themeType: 'adult' | 'child') => {
  return StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    loadingContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: colors.background,
    },
    loadingText: {
      marginTop: getSpacing(2, width),
      fontSize: 14,
      color: colors.text.secondary,
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: getSpacing(2, width),
      paddingVertical: getSpacing(2, width),
      backgroundColor: themeType === 'child' ? accent : colors.card,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
      ...Platform.select({
        ios: {
          shadowColor: '#000',
          shadowOffset: { width: 0, height: 2 },
          shadowOpacity: 0.1,
          shadowRadius: 4,
        },
        android: {
          elevation: 4,
        },
      }),
    },
    headerLeft: {
      width: 40,
    },
    headerTitle: {
      fontSize: themeType === 'child' ? 20 : 18,
      fontWeight: '700',
      color: themeType === 'child' ? '#FFFFFF' : colors.text.primary,
      textAlign: 'center',
      flex: 1,
    },
    headerRight: {
      width: 40,
    },
    content: {
      flex: 1,
    },
    contentContainer: {
      padding: getSpacing(2, width),
    },
    noticeBanner: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      backgroundColor: themeType === 'child' ? `${accent}20` : colors.card,
      borderLeftWidth: 4,
      borderLeftColor: accent,
      padding: getSpacing(2, width),
      borderRadius: getSpacing(1, width),
      marginBottom: getSpacing(3, width),
    },
    noticeText: {
      flex: 1,
      marginLeft: getSpacing(2, width),
      fontSize: themeType === 'child' ? 15 : 14,
      color: colors.text.primary,
      lineHeight: 20,
    },
    versionSection: {
      marginBottom: getSpacing(3, width),
    },
    sectionTitle: {
      fontSize: themeType === 'child' ? 18 : 16,
      fontWeight: '700',
      color: colors.text.primary,
      marginBottom: getSpacing(2, width),
    },
    versionCard: {
      backgroundColor: colors.card,
      padding: getSpacing(2, width),
      borderRadius: getSpacing(1, width),
      marginBottom: getSpacing(2, width),
      borderWidth: 1,
      borderColor: colors.border,
    },
    versionLabel: {
      fontSize: themeType === 'child' ? 15 : 14,
      fontWeight: '600',
      color: colors.text.primary,
      marginBottom: getSpacing(1, width),
    },
    versionRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(1, width),
    },
    versionCurrent: {
      fontSize: getFontSize(12, width),
      color: colors.text.secondary,
    },
    versionRequired: {
      fontSize: getFontSize(12, width),
      fontWeight: '600',
      color: accent,
    },
    consentSection: {
      marginBottom: getSpacing(3, width),
      paddingTop: getSpacing(2, width),
      paddingBottom: getSpacing(2, width),
      borderTopWidth: 1,
      borderBottomWidth: 1,
      borderColor: colors.border,
    },
    checkboxContainer: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      marginBottom: getSpacing(2, width),
    },
    checkbox: {
      width: 24,
      height: 24,
      borderWidth: 2,
      borderColor: colors.border,
      borderRadius: getBorderRadius(4, width),
      backgroundColor: colors.background,
      justifyContent: 'center',
      alignItems: 'center',
      marginRight: getSpacing(2, width),
      marginTop: 2,
    },
    checkboxChecked: {
      backgroundColor: accent,
      borderColor: accent,
    },
    checkboxTextContainer: {
      flex: 1,
    },
    checkboxText: {
      fontSize: getFontSize(themeType === 'child' ? 15 : 14, width),
      color: colors.text.primary,
      lineHeight: 20,
    },
    link: {
      color: accent,
      fontWeight: '600',
      textDecorationLine: 'underline',
    },
    required: {
      color: '#EF4444',
    },
    warningSection: {
      backgroundColor: colors.card,
      padding: getSpacing(2, width),
      borderRadius: getSpacing(1, width),
      marginBottom: getSpacing(3, width),
      borderWidth: 1,
      borderColor: colors.border,
    },
    warningTitle: {
      fontSize: getFontSize(14, width),
      fontWeight: '600',
      color: colors.text.primary,
      marginBottom: getSpacing(1, width),
    },
    warningText: {
      fontSize: getFontSize(12, width),
      color: colors.text.secondary,
      lineHeight: 18,
    },
    submitButton: {
      backgroundColor: accent,
      paddingVertical: getSpacing(2, width),
      paddingHorizontal: getSpacing(3, width),
      borderRadius: getSpacing(1.5, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
      marginBottom: getSpacing(4, width),
      ...Platform.select({
        ios: {
          shadowColor: accent,
          shadowOffset: { width: 0, height: 4 },
          shadowOpacity: 0.3,
          shadowRadius: 8,
        },
        android: {
          elevation: 6,
        },
      }),
    },
    submitButtonDisabled: {
      backgroundColor: colors.text.secondary,
      opacity: 0.5,
    },
    submitButtonText: {
      fontSize: getFontSize(themeType === 'child' ? 18 : 16, width),
      fontWeight: '700',
      color: '#FFFFFF',
    },
  });
};

export default ReconsentScreen;
