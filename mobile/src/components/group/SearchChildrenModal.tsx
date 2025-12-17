/**
 * SearchChildrenModal - 未紐付け子検索モーダル
 * 
 * 機能:
 * - 親のメールアドレスで未紐付けの子アカウントを検索
 * - 検索結果一覧表示（FlatList）
 * - 各子アカウントに紐付けリクエスト送信
 * - レスポンシブデザイン対応
 * - テーマ対応（adult/child）
 * 
 * 使用例:
 * ```tsx
 * <SearchChildrenModal
 *   visible={showModal}
 *   onClose={() => setShowModal(false)}
 *   onSuccess={() => {
 *     setShowModal(false);
 *     loadGroupMembers();
 *   }}
 * />
 * ```
 */

import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Modal,
  FlatList,
  ActivityIndicator,
  Alert,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { searchUnlinkedChildren, sendLinkRequest } from '../../services/group.service';

/**
 * 子アカウント情報型
 */
interface ChildAccount {
  id: number;
  username: string;
  name: string | null;
  email: string;
  created_at: string;
  is_minor: boolean;
}

/**
 * SearchChildrenModal Props
 */
interface SearchChildrenModalProps {
  /** モーダル表示状態 */
  visible: boolean;
  /** 閉じるハンドラー */
  onClose: () => void;
  /** 成功時ハンドラー（リクエスト送信成功） */
  onSuccess?: () => void;
}

/**
 * SearchChildrenModal コンポーネント
 */
export const SearchChildrenModal: React.FC<SearchChildrenModalProps> = ({
  visible,
  onClose,
  onSuccess,
}) => {
  const { theme } = useTheme();
  const { colors, accent } = useThemedColors();
  const { width } = useResponsive();

  const [parentEmail, setParentEmail] = useState('');
  const [children, setChildren] = useState<ChildAccount[]>([]);
  const [searching, setSearching] = useState(false);
  const [sendingRequestFor, setSendingRequestFor] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  /**
   * 未紐付け子アカウント検索
   */
  const handleSearch = async () => {
    if (!parentEmail.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'メールアドレスを にゅうりょくしてね' : '親のメールアドレスを入力してください'
      );
      return;
    }

    setSearching(true);
    setError(null);
    try {
      const response = await searchUnlinkedChildren(parentEmail);
      
      if (response.success) {
        setChildren(response.data.children);
        
        if (response.data.children.length === 0) {
          Alert.alert(
            theme === 'child' ? 'けっか' : '検索結果',
            theme === 'child' 
              ? 'みつかりませんでした'
              : '該当する子アカウントが見つかりませんでした'
          );
        }
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '検索に失敗しました';
      setError(errorMessage);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        errorMessage
      );
      console.error('[SearchChildrenModal] Search error:', err);
    } finally {
      setSearching(false);
    }
  };

  /**
   * 紐付けリクエスト送信
   */
  const handleSendRequest = async (childId: number, childName: string) => {
    setSendingRequestFor(childId);
    try {
      const response = await sendLinkRequest(childId);
      
      if (response.success) {
        Alert.alert(
          theme === 'child' ? 'そうしんしたよ！' : 'リクエスト送信完了',
          theme === 'child'
            ? `${childName}さんに リクエストを おくったよ！`
            : `${childName}さんに紐付けリクエストを送信しました。`,
          [
            {
              text: 'OK',
              onPress: () => {
                // リストから削除（送信済み）
                setChildren((prev) => prev.filter((child) => child.id !== childId));
                
                // 成功コールバック実行
                if (onSuccess) {
                  onSuccess();
                }
              },
            },
          ]
        );
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '送信に失敗しました';
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        errorMessage
      );
      console.error('[SearchChildrenModal] Send request error:', err);
    } finally {
      setSendingRequestFor(null);
    }
  };

  /**
   * モーダルを閉じる
   */
  const handleClose = () => {
    setParentEmail('');
    setChildren([]);
    setError(null);
    onClose();
  };

  /**
   * 子アカウントカードレンダー
   */
  const renderChildItem = ({ item }: { item: ChildAccount }) => {
    const isSending = sendingRequestFor === item.id;
    const displayName = item.name || item.username;

    return (
      <View style={[styles.childCard, { backgroundColor: colors.surface }]}>
        <View style={styles.childInfo}>
          <Text style={[styles.childName, { color: colors.text.primary, fontSize: getFontSize(16, width, theme) }]}>
            {displayName}
          </Text>
          <Text style={[styles.childUsername, { color: colors.text.secondary, fontSize: getFontSize(14, width, theme) }]}>
            @{item.username}
          </Text>
          <Text style={[styles.childEmail, { color: colors.text.tertiary, fontSize: getFontSize(12, width, theme) }]}>
            {item.email}
          </Text>
          {item.is_minor && (
            <View style={[styles.minorBadge, { backgroundColor: accent.primary + '20' }]}>
              <Text style={[styles.minorBadgeText, { color: accent.primary as string, fontSize: getFontSize(11, width, theme) }]}>
                {theme === 'child' ? '13さいみまん' : '13歳未満'}
              </Text>
            </View>
          )}
        </View>

        <TouchableOpacity
          onPress={() => handleSendRequest(item.id, displayName)}
          disabled={isSending}
          style={styles.sendButton}
        >
          <LinearGradient
            colors={accent.gradient as any}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={[styles.sendButtonGradient, { borderRadius: getBorderRadius(8, width) }]}
          >
            {isSending ? (
              <ActivityIndicator size="small" color="#FFFFFF" />
            ) : (
              <Text style={[styles.sendButtonText, { fontSize: getFontSize(14, width, theme) }]}>
                {theme === 'child' ? 'おくる' : '送信'}
              </Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </View>
    );
  };

  const styles = createStyles(width, theme, colors, accent);

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={true}
      onRequestClose={handleClose}
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.modalOverlay}
      >
        <View style={styles.modalContainer}>
          {/* ヘッダー */}
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>
              {theme === 'child' ? 'こどもを さがす' : '未紐付け子検索'}
            </Text>
            <TouchableOpacity onPress={handleClose} style={styles.closeButton}>
              <Text style={styles.closeButtonText}>✕</Text>
            </TouchableOpacity>
          </View>

          {/* 検索フォーム */}
          <View style={styles.searchForm}>
            <Text style={styles.searchLabel}>
              {theme === 'child' ? 'おやの メールアドレス' : '親のメールアドレス'}
            </Text>
            <TextInput
              style={styles.searchInput}
              placeholder={theme === 'child' ? 'れい: parent@example.com' : '例: parent@example.com'}
              placeholderTextColor={colors.text.tertiary}
              value={parentEmail}
              onChangeText={setParentEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
            />

            <TouchableOpacity
              onPress={handleSearch}
              disabled={searching}
              style={styles.searchButton}
            >
              <LinearGradient
                colors={accent.gradient as any}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.searchButtonGradient}
              >
                {searching ? (
                  <ActivityIndicator size="small" color="#FFFFFF" />
                ) : (
                  <Text style={styles.searchButtonText}>
                    {theme === 'child' ? '🔍 さがす' : '🔍 検索'}
                  </Text>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </View>

          {/* エラー表示 */}
          {error && (
            <View style={styles.errorBox}>
              <Text style={styles.errorText}>{error}</Text>
            </View>
          )}

          {/* 検索結果 */}
          {children.length > 0 && (
            <>
              <Text style={styles.resultsHeader}>
                {theme === 'child' 
                  ? `${children.length}にんの こどもが みつかったよ！`
                  : `検索結果: ${children.length}件`}
              </Text>

              <FlatList
                data={children}
                renderItem={renderChildItem}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.resultsList}
                showsVerticalScrollIndicator={false}
              />
            </>
          )}

          {/* 検索前メッセージ */}
          {children.length === 0 && !searching && !error && (
            <View style={styles.emptyState}>
              <Text style={styles.emptyStateText}>
                {theme === 'child'
                  ? '👆 おやの メールアドレスを いれて、\nさがすボタンを おしてね！'
                  : '親のメールアドレスを入力して検索してください'}
              </Text>
            </View>
          )}
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
};

/**
 * スタイル生成関数
 */
const createStyles = (
  width: number,
  theme: 'adult' | 'child',
  colors: any,
  accent: { primary: string; gradient: string[] }
) => StyleSheet.create({
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContainer: {
    backgroundColor: colors.background,
    borderRadius: getBorderRadius(16, width),
    padding: getSpacing(20, width),
    width: width * 0.9,
    maxHeight: '80%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(20, width),
  },
  modalTitle: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
  },
  closeButton: {
    padding: getSpacing(8, width),
  },
  closeButtonText: {
    fontSize: getFontSize(24, width, theme),
    color: colors.text.secondary,
  },
  searchForm: {
    marginBottom: getSpacing(20, width),
  },
  searchLabel: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(8, width),
    fontWeight: '600',
  },
  searchInput: {
    backgroundColor: colors.surface,
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
    marginBottom: getSpacing(12, width),
  },
  searchButton: {
    marginTop: getSpacing(8, width),
  },
  searchButtonGradient: {
    paddingVertical: getSpacing(14, width),
    paddingHorizontal: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 48,
  },
  searchButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
  },
  errorBox: {
    backgroundColor: colors.status.error + '20',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.status.error,
    textAlign: 'center',
  },
  resultsHeader: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
    marginBottom: getSpacing(12, width),
  },
  resultsList: {
    paddingBottom: getSpacing(16, width),
  },
  childCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(12, width),
    marginBottom: getSpacing(12, width),
  },
  childInfo: {
    flex: 1,
  },
  childName: {
    fontWeight: '700',
    marginBottom: getSpacing(4, width),
  },
  childUsername: {
    marginBottom: getSpacing(2, width),
  },
  childEmail: {
    marginBottom: getSpacing(8, width),
  },
  minorBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(4, width),
  },
  minorBadgeText: {
    fontWeight: '600',
  },
  sendButton: {
    marginLeft: getSpacing(12, width),
  },
  sendButtonGradient: {
    paddingVertical: getSpacing(10, width),
    paddingHorizontal: getSpacing(16, width),
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 44,
  },
  sendButtonText: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: getSpacing(40, width),
  },
  emptyStateText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.tertiary,
    textAlign: 'center',
    lineHeight: getFontSize(22, width, theme),
  },
});
