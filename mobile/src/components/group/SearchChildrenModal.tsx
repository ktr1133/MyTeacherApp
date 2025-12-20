/**
 * SearchChildrenModal - 未紐付け子検索モーダル
 * 
 * 機能:
 * - 親のメールアドレスで未紐付けの子アカウントを検索
 * - 検索結果一覧表示（FlatList）
 * - 各子アカウントに「×」ボタンで除外
 * - 選択した子アカウントを一括紐づけ（同意なし）
 * - レスポンシブデザイン対応
 * - テーマ対応（adult/child）
 * 
 * Phase 6更新: 紐づけリクエスト送信 → 一括紐づけに変更
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

import React, { useState, useEffect, useRef } from 'react';
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
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { searchUnlinkedChildren, linkChildren } from '../../services/group.service';

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
  const { user } = useAuth();
  const { theme } = useTheme();
  const { colors, accent } = useThemedColors();
  const { width } = useResponsive();

  const [parentEmail, setParentEmail] = useState(user?.email || '');
  const [children, setChildren] = useState<ChildAccount[]>([]);
  const [selectedChildren, setSelectedChildren] = useState<Set<number>>(new Set());
  const [searching, setSearching] = useState(false);
  const [linking, setLinking] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // マウント状態管理とクリーンアップ
  useEffect(() => {
    isMountedRef.current = true;
    
    return () => {
      // アンマウント時にフラグをfalseに設定
      isMountedRef.current = false;
    };
  }, []);

  // マウント状態を追跡（アンマウント後の状態更新を防止）
  const isMountedRef = useRef(true);

  // モーダルが開かれた時とユーザー情報更新時にメールアドレスを同期
  useEffect(() => {
    console.log('[SearchChildrenModal] visible:', visible);
    console.log('[SearchChildrenModal] user:', JSON.stringify(user, null, 2));
    console.log('[SearchChildrenModal] user.email:', user?.email);
    
    if (visible && user?.email) {
      console.log('[SearchChildrenModal] Setting parentEmail to:', user.email);
      setParentEmail(user.email);
    }
  }, [visible, user?.email]);

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
        
        // 全ての子アカウントを初期選択状態にする
        const allChildrenIds = new Set(response.data.children.map(child => child.id));
        setSelectedChildren(allChildrenIds);
        
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
   * 子アカウントを除外
   */
  const handleRemoveChild = (childId: number) => {
    setSelectedChildren(prev => {
      const newSet = new Set(prev);
      newSet.delete(childId);
      return newSet;
    });
  };

  /**
   * 選択した子アカウントを一括紐づけ
   */
  const handleLinkChildren = async () => {
    if (selectedChildren.size === 0) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'ひもづける こどもを えらんでね'
          : '紐づけする子アカウントを選択してください'
      );
      return;
    }

    setLinking(true);
    try {
      const response = await linkChildren(Array.from(selectedChildren));
      
      if (response.success) {
        let message = response.message;
        
        // スキップされたアカウントがある場合は詳細を表示
        if (response.data.skipped_children.length > 0) {
          message += '\n\n紐づけできなかったアカウント：\n';
          response.data.skipped_children.forEach(skipped => {
            message += `• ${skipped.username || 'ID: ' + skipped.user_id}: ${skipped.reason}\n`;
          });
        }
        
        // Alert表示前にローディング状態を解除
        if (isMountedRef.current) {
          setLinking(false);
        }
        
        // Alertを表示し、OKボタン押下後にコールバック実行
        Alert.alert(
          theme === 'child' ? 'できたよ！' : '紐づけ完了',
          message,
          [
            {
              text: 'OK',
              onPress: () => {
                // マウント状態チェック後に状態クリア
                if (isMountedRef.current) {
                  setChildren([]);
                  setSelectedChildren(new Set());
                  setError(null);
                }
                
                // onSuccessコールバックを実行（親側でモーダルクローズとデータ再取得を制御）
                if (onSuccess) {
                  onSuccess();
                }
              },
            },
          ]
        );
      }
    } catch (err) {
      if (isMountedRef.current) {
        setLinking(false);
      }
      const errorMessage = err instanceof Error ? err.message : '紐づけに失敗しました';
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        errorMessage
      );
      console.error('[SearchChildrenModal] Link children error:', err);
    }
  };

  /**
   * モーダルを閉じる
   */
  const handleClose = () => {
    // 検索結果と選択状態をクリア（親メールアドレスは保持）
    setChildren([]);
    setSelectedChildren(new Set());
    setError(null);
    onClose();
  };

  /**
   * 子アカウントカードレンダー
   */
  const renderChildItem = ({ item }: { item: ChildAccount }) => {
    const isSelected = selectedChildren.has(item.id);
    const displayName = item.name || item.username;

    return (
      <View style={[styles.childCard, { backgroundColor: colors.surface, opacity: isSelected ? 1 : 0.5 }]}>
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
          onPress={() => handleRemoveChild(item.id)}
          style={[
            styles.removeButton,
            { 
              borderColor: isSelected ? (colors.status?.error || '#EF4444') : (colors.text.tertiary || '#9CA3AF'),
              opacity: isSelected ? 1 : 0.5
            }
          ]}
        >
          <Text style={[
            styles.removeButtonText,
            { color: isSelected ? (colors.status?.error || '#EF4444') : (colors.text.tertiary || '#9CA3AF') }
          ]}>
            ✕
          </Text>
        </TouchableOpacity>
      </View>
    );
  };

  const styles = createStyles(width, theme, colors);

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
              <View style={styles.resultsHeaderContainer}>
                <Text style={styles.resultsHeader}>
                  {theme === 'child' 
                    ? `${children.length}にんの こどもが みつかったよ！`
                    : `検索結果: ${children.length}件`}
                </Text>
                <Text style={styles.resultsSubHeader}>
                  {theme === 'child'
                    ? '「×」で はずせるよ'
                    : '「×」ボタンで除外できます'}
                </Text>
              </View>

              <FlatList
                data={children}
                renderItem={renderChildItem}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.resultsList}
                showsVerticalScrollIndicator={false}
              />

              {/* 紐づけボタン */}
              <TouchableOpacity
                onPress={handleLinkChildren}
                disabled={linking || selectedChildren.size === 0}
                style={styles.linkButton}
              >
                <LinearGradient
                  colors={accent.gradient as any}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={[
                    styles.linkButtonGradient,
                    { opacity: selectedChildren.size === 0 ? 0.5 : 1 }
                  ]}
                >
                  {linking ? (
                    <ActivityIndicator size="small" color="#FFFFFF" />
                  ) : (
                    <Text style={[styles.linkButtonText, { fontSize: getFontSize(16, width, theme) }]}>
                      {theme === 'child'
                        ? `${selectedChildren.size}にんを ひもづける`
                        : `選択した${selectedChildren.size}人を紐づける`}
                    </Text>
                  )}
                </LinearGradient>
              </TouchableOpacity>
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
  colors: any
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
  resultsHeaderContainer: {
    marginBottom: getSpacing(12, width),
  },
  resultsHeader: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
    marginBottom: getSpacing(4, width),
  },
  resultsSubHeader: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.tertiary,
  },
  resultsList: {
    paddingBottom: getSpacing(16, width),
    maxHeight: '50%',
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
  removeButton: {
    marginLeft: getSpacing(12, width),
    width: 36,
    height: 36,
    borderRadius: getBorderRadius(8, width),
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  removeButtonText: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: '700',
  },
  linkButton: {
    marginTop: getSpacing(16, width),
  },
  linkButtonGradient: {
    paddingVertical: getSpacing(14, width),
    paddingHorizontal: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 48,
  },
  linkButtonText: {
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
