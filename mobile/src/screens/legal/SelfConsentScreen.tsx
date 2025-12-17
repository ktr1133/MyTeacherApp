import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Linking,
  Dimensions,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { useThemedColors } from '../../hooks/useThemedColors';
import { getSpacing } from '../../utils/responsive';
import { getSelfConsentStatus, submitSelfConsent } from '../../services/legal.service';
import type { SelfConsentStatusResponse, SelfConsentRequest } from '../../types/legal.types';

const { width } = Dimensions.get('window');

/**
 * 本人同意画面（13歳到達時）
 * 
 * Phase 6D: 13歳到達時の本人再同意
 */
const SelfConsentScreen: React.FC = () => {
  const navigation = useNavigation();
  const colors = useThemedColors();

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [status, setStatus] = useState<SelfConsentStatusResponse | null>(null);
  const [privacyConsent, setPrivacyConsent] = useState(false);
  const [termsConsent, setTermsConsent] = useState(false);

  // スクリーン表示時に本人同意状態を取得
  useEffect(() => {
    fetchSelfConsentStatus();
  }, []);

  const fetchSelfConsentStatus = async () => {
    try {
      setLoading(true);
      const data = await getSelfConsentStatus();
      setStatus(data);
    } catch (error: any) {
      console.error('Failed to fetch self consent status:', error);
      Alert.alert('エラー', '本人同意状態の取得に失敗しました。');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitConsent = async () => {
    if (!privacyConsent || !termsConsent) {
      Alert.alert(
        '同意が必要です',
        'プライバシーポリシーと利用規約の両方に同意してください。'
      );
      return;
    }

    try {
      setSubmitting(true);

      const data: SelfConsentRequest = {
        privacy_policy_consent: privacyConsent,
        terms_consent: termsConsent,
      };

      await submitSelfConsent(data);

      Alert.alert(
        '本人同意が完了しました',
        'おめでとうございます！これからはあなた自身でサービスを利用できます。',
        [
          {
            text: 'OK',
            onPress: () => {
              // ダッシュボードに戻る
              navigation.reset({
                index: 0,
                routes: [{ name: 'Main' as never }],
              });
            },
          },
        ]
      );
    } catch (error: any) {
      console.error('Failed to submit self consent:', error);
      Alert.alert('エラー', error.message || '本人同意の送信に失敗しました。');
    } finally {
      setSubmitting(false);
    }
  };

  const openPrivacyPolicy = () => {
    const url = 'https://example.com/privacy-policy'; // TODO: 実際のURL
    Linking.openURL(url).catch(err => console.error('Failed to open URL:', err));
  };

  const openTermsOfService = () => {
    const url = 'https://example.com/terms'; // TODO: 実際のURL
    Linking.openURL(url).catch(err => console.error('Failed to open URL:', err));
  };

  if (loading) {
    return (
      <View style={[styles.container, { backgroundColor: colors.colors.background }]}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.accent.primary} />
          <Text style={[styles.loadingText, { color: colors.colors.text.primary }]}>
            {colors.theme === 'child' ? 'よみこみちゅう...' : '読み込み中...'}
          </Text>
        </View>
      </View>
    );
  }

  if (!status || !status.requires_self_consent) {
    return (
      <View style={[styles.container, { backgroundColor: colors.colors.background }]}>
        <View style={styles.centerContainer}>
          <Ionicons name="checkmark-circle" size={80} color={colors.colors.status.success} />
          <Text style={[styles.infoText, { color: colors.colors.text.primary }]}>
            本人同意は不要です
          </Text>
          <TouchableOpacity
            style={[styles.button, { backgroundColor: colors.accent.primary }]}
            onPress={() => navigation.goBack()}
          >
            <Text style={styles.buttonText}>戻る</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const isChildTheme = colors.theme === 'child';

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.colors.background }]}
      contentContainerStyle={styles.contentContainer}
    >
      {/* 通知メッセージ */}
      <View style={[styles.noticeBox, { backgroundColor: colors.colors.status.success + '20', borderLeftColor: colors.colors.status.success }]}>
        <View style={styles.noticeHeader}>
          <Ionicons name="happy" size={24} color={colors.colors.status.success} />
          <Text style={[styles.noticeTitle, { color: colors.colors.status.success }]}>
            {isChildTheme ? 'おめでとう！13さいになったよ 🎉' : 'おめでとうございます！13歳になりました 🎉'}
          </Text>
        </View>
        <Text style={[styles.noticeText, { color: colors.colors.text.primary }]}>
          {isChildTheme
            ? 'これからは、きみじしんでどういするひつようがあるよ。\nプライバシーポリシーとりようきやくをかくにんして、どういしてね。'
            : 'これからは、あなた自身で同意を行う必要があります。\nプライバシーポリシーと利用規約をご確認の上、同意してください。'}
        </Text>
      </View>

      {/* 年齢情報 */}
      {status.age && (
        <View style={[styles.infoCard, { backgroundColor: colors.colors.card }]}>
          <Text style={[styles.infoLabel, { color: colors.colors.text.secondary }]}>
            {isChildTheme ? 'いまのねんれい:' : 'あなたの年齢:'}
          </Text>
          <Text style={[styles.ageText, { color: colors.accent.primary }]}>
            {status.age}{isChildTheme ? 'さい' : '歳'}
          </Text>
        </View>
      )}

      {/* 説明セクション */}
      <View style={[styles.explanationBox, { backgroundColor: colors.colors.status.info + '20', borderLeftColor: colors.colors.status.info }]}>
        <Text style={[styles.explanationTitle, { color: colors.colors.text.primary }]}>
          {isChildTheme ? '📝 いままでのこと' : '📝 これまでの経緯'}
        </Text>
        <View style={styles.explanationContent}>
          <Text style={[styles.explanationText, { color: colors.colors.text.primary }]}>
            {isChildTheme
              ? '✅ いままでは、ほごしゃのひとがかわりにどういしてくれていたよ。'
              : '✅ これまでは、保護者の方が代わりに同意していました。'}
          </Text>
          <Text style={[styles.explanationText, { color: colors.colors.text.primary }]}>
            {isChildTheme
              ? '✅ 13さいになったから、これからはきみじしんでどういがひつようだよ。'
              : '✅ 13歳になったため、これからはあなた自身で同意する必要があります。'}
          </Text>
        </View>
      </View>

      {/* 同意チェックボックス */}
      <View style={styles.consentSection}>
        <Text style={[styles.sectionTitle, { color: colors.colors.text.primary }]}>
          {isChildTheme ? '✅ どういがひつようなこと' : '✅ 本人同意が必要な項目'}
        </Text>

        {/* プライバシーポリシー */}
        <TouchableOpacity
          style={styles.checkboxRow}
          onPress={() => setPrivacyConsent(!privacyConsent)}
          activeOpacity={0.7}
        >
          <View
            style={[
              styles.checkbox,
              {
                borderColor: colors.colors.border.default,
                backgroundColor: privacyConsent ? colors.colors.status.success : 'transparent',
              },
            ]}
          >
            {privacyConsent && <Ionicons name="checkmark" size={20} color="#FFFFFF" />}
          </View>
          <View style={styles.checkboxTextContainer}>
            <Text style={[styles.checkboxLabel, { color: colors.colors.text.primary }]}>
              <Text
                style={[styles.linkText, { color: colors.accent.primary }]}
                onPress={openPrivacyPolicy}
              >
                {isChildTheme ? 'プライバシーポリシー' : 'プライバシーポリシー'}
              </Text>
              {isChildTheme ? 'をよんで、わかりました' : 'を読み、内容を理解しました'}
              <Text style={[styles.required, { color: colors.colors.status.error }]}> *</Text>
            </Text>
            <Text style={[styles.checkboxDescription, { color: colors.colors.text.secondary }]}>
              {isChildTheme ? 'じぶんのじょうほうのあつかいについて' : '個人情報の取り扱いについての規約です'}
            </Text>
          </View>
        </TouchableOpacity>

        {/* 利用規約 */}
        <TouchableOpacity
          style={styles.checkboxRow}
          onPress={() => setTermsConsent(!termsConsent)}
          activeOpacity={0.7}
        >
          <View
            style={[
              styles.checkbox,
              {
                borderColor: colors.colors.border.default,
                backgroundColor: termsConsent ? colors.colors.status.success : 'transparent',
              },
            ]}
          >
            {termsConsent && <Ionicons name="checkmark" size={20} color="#FFFFFF" />}
          </View>
          <View style={styles.checkboxTextContainer}>
            <Text style={[styles.checkboxLabel, { color: colors.colors.text.primary }]}>
              <Text
                style={[styles.linkText, { color: colors.accent.primary }]}
                onPress={openTermsOfService}
              >
                {isChildTheme ? 'りようきやく' : '利用規約'}
              </Text>
              {isChildTheme ? 'をよんで、わかりました' : 'を読み、内容を理解しました'}
              <Text style={[styles.required, { color: colors.colors.status.error }]}> *</Text>
            </Text>
            <Text style={[styles.checkboxDescription, { color: colors.colors.text.secondary }]}>
              {isChildTheme ? 'サービスのつかいかたとルールについて' : 'サービスの使い方とルールについての規約です'}
            </Text>
          </View>
        </TouchableOpacity>
      </View>

      {/* 送信ボタン */}
      <TouchableOpacity
        style={[
          styles.submitButton,
          {
            backgroundColor: privacyConsent && termsConsent ? colors.colors.status.success : colors.colors.text.disabled,
          },
        ]}
        onPress={handleSubmitConsent}
        disabled={!privacyConsent || !termsConsent || submitting}
      >
        {submitting ? (
          <ActivityIndicator size="small" color="#FFFFFF" />
        ) : (
          <Text style={styles.submitButtonText}>
            {isChildTheme ? 'ほんにんとしてどういする' : '本人として同意する'}
          </Text>
        )}
      </TouchableOpacity>

      {/* 注意事項 */}
      <View style={[styles.warningBox, { backgroundColor: colors.colors.card }]}>
        <Text style={[styles.warningTitle, { color: colors.colors.text.primary }]}>
          {isChildTheme ? '⚠️ ちゅうい' : '⚠️ ご注意'}
        </Text>
        <View style={styles.warningList}>
          <Text style={[styles.warningText, { color: colors.colors.text.secondary }]}>
            {isChildTheme
              ? '• どういできないときは、サービスがつかえなくなるよ。'
              : '• 同意いただけない場合、サービスの継続利用ができません。'}
          </Text>
          <Text style={[styles.warningText, { color: colors.colors.text.secondary }]}>
            {isChildTheme
              ? '• わからないところがあったら、ほごしゃのひとにきいてね。'
              : '• わからない部分があれば、保護者の方に相談してください。'}
          </Text>
        </View>
      </View>

      {/* 保護者へのメッセージ */}
      <View style={[styles.parentBox, { backgroundColor: colors.colors.status.warning + '20', borderLeftColor: colors.colors.status.warning }]}>
        <Text style={[styles.parentTitle, { color: colors.colors.status.warning }]}>
          👨‍👩‍👧 保護者の方へ
        </Text>
        <Text style={[styles.parentText, { color: colors.colors.text.primary }]}>
          お子様が13歳になられましたので、本人同意が必要となりました。{'\n'}
          お子様と一緒に内容をご確認の上、ご本人に同意していただくようお願いいたします。
        </Text>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  contentContainer: {
    padding: getSpacing(2, width),
    paddingBottom: getSpacing(4, width),
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: getSpacing(2, width),
    fontSize: 16,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: getSpacing(3, width),
  },
  infoText: {
    fontSize: 18,
    marginTop: getSpacing(2, width),
    marginBottom: getSpacing(3, width),
  },
  button: {
    paddingVertical: getSpacing(1.5, width),
    paddingHorizontal: getSpacing(4, width),
    borderRadius: getSpacing(1, width),
  },
  buttonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  noticeBox: {
    padding: getSpacing(2, width),
    borderRadius: getSpacing(1, width),
    borderLeftWidth: 4,
    marginBottom: getSpacing(2, width),
  },
  noticeHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(1, width),
  },
  noticeTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginLeft: getSpacing(1, width),
  },
  noticeText: {
    fontSize: 14,
    lineHeight: 20,
  },
  infoCard: {
    padding: getSpacing(2, width),
    borderRadius: getSpacing(1, width),
    marginBottom: getSpacing(2, width),
  },
  infoLabel: {
    fontSize: 12,
    marginBottom: getSpacing(0.5, width),
  },
  ageText: {
    fontSize: 28,
    fontWeight: 'bold',
  },
  explanationBox: {
    padding: getSpacing(2, width),
    borderRadius: getSpacing(1, width),
    borderLeftWidth: 4,
    marginBottom: getSpacing(3, width),
  },
  explanationTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: getSpacing(1.5, width),
  },
  explanationContent: {
    gap: getSpacing(1, width),
  },
  explanationText: {
    fontSize: 14,
    lineHeight: 20,
  },
  consentSection: {
    marginBottom: getSpacing(3, width),
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: getSpacing(2, width),
  },
  checkboxRow: {
    flexDirection: 'row',
    marginBottom: getSpacing(2.5, width),
    alignItems: 'flex-start',
  },
  checkbox: {
    width: 24,
    height: 24,
    borderRadius: 6,
    borderWidth: 2,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: getSpacing(1.5, width),
    marginTop: 2,
  },
  checkboxTextContainer: {
    flex: 1,
  },
  checkboxLabel: {
    fontSize: 14,
    lineHeight: 20,
  },
  linkText: {
    fontWeight: '600',
    textDecorationLine: 'underline',
  },
  required: {
    fontWeight: 'bold',
  },
  checkboxDescription: {
    fontSize: 12,
    marginTop: getSpacing(0.5, width),
    lineHeight: 16,
  },
  submitButton: {
    paddingVertical: getSpacing(2, width),
    borderRadius: getSpacing(1, width),
    alignItems: 'center',
    marginBottom: getSpacing(2, width),
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  warningBox: {
    padding: getSpacing(2, width),
    borderRadius: getSpacing(1, width),
    marginBottom: getSpacing(2, width),
  },
  warningTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: getSpacing(1, width),
  },
  warningList: {
    gap: getSpacing(0.5, width),
  },
  warningText: {
    fontSize: 12,
    lineHeight: 18,
  },
  parentBox: {
    padding: getSpacing(2, width),
    borderRadius: getSpacing(1, width),
    borderLeftWidth: 4,
  },
  parentTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: getSpacing(1, width),
  },
  parentText: {
    fontSize: 13,
    lineHeight: 20,
  },
});

export default SelfConsentScreen;
