/**
 * タグ管理画面
 * 
 * Web版との整合性（mobile-rules.md総則4項準拠）:
 * - タグ名編集: カード内でインライン編集（編集フォーム表示/非表示切替、モーダルなし）
 * - 新規作成: モーダルで作成
 * - タグクリック: 詳細画面に遷移（タスク紐付け・解除管理）
 * - タスク存在時はタグ削除不可（Web版の制限）
 * - 色選択機能なし（Web版準拠、デフォルト色#3B82F6を使用）
 * 
 * @see /home/ktr/mtdev/resources/views/tags-list.blade.php (Web版)
 * @see /home/ktr/mtdev/docs/mobile/mobile-rules.md (モバイル開発規則)
 */
import { useEffect, useState, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
  ActivityIndicator,
  Alert,
  Modal,
  TextInput,
  Dimensions,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useTags } from '../../hooks/useTags';
import { useTheme } from '../../contexts/ThemeContext';
import { useAvatarContext } from '../../contexts/AvatarContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import AvatarWidget from '../../components/common/AvatarWidget';
import type { Tag } from '../../types/tag.types';

type RootStackParamList = {
  TagManagement: undefined;
  TagDetail: { tag: Tag };
};

type Props = NativeStackScreenProps<RootStackParamList, 'TagManagement'>;

/**
 * タグ管理画面コンポーネント
 */
export default function TagManagementScreen({ navigation }: Props) {
  const { theme } = useTheme();
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const {
    tags,
    isLoading,
    error,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
    clearError,
    refreshTags,
  } = useTags();
  const {
    isVisible: avatarVisible,
    currentData: avatarData,
    hideAvatar,
  } = useAvatarContext();

  const [refreshing, setRefreshing] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [newTagName, setNewTagName] = useState('');
  const [editingTagId, setEditingTagId] = useState<number | null>(null);
  const [editingTagName, setEditingTagName] = useState('');
  const DEFAULT_TAG_COLOR = accent.primary; // テーマのアクセントカラーを使用

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, theme, colors, accent), [width, theme, colors, accent]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
    console.log('[TagManagementScreen] Mounting, loading tags...');
    fetchTags();
  }, [fetchTags]);

  /**
   * Pull-to-Refresh処理
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await refreshTags();
    setRefreshing(false);
  }, [refreshTags]);

  /**
   * 新規作成モーダルを開く
   */
  const openCreateModal = () => {
    setNewTagName('');
    setModalVisible(true);
  };

  /**
   * モーダルを閉じる
   */
  const closeModal = () => {
    setModalVisible(false);
    setNewTagName('');
  };

  /**
   * インライン編集を開始（Web版準拠）
   */
  const startEditingTag = (tag: Tag) => {
    setEditingTagId(tag.id);
    setEditingTagName(tag.name);
  };

  /**
   * インライン編集をキャンセル
   */
  const cancelEditingTag = () => {
    setEditingTagId(null);
    setEditingTagName('');
  };

  /**
   * タグ新規作成処理
   */
  const handleCreateTag = async () => {
    if (!newTagName.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'タグのなまえをいれてね！' : 'タグ名を入力してください。'
      );
      return;
    }

    // 重複チェック
    const isDuplicate = tags.some(
      (tag) => tag.name.toLowerCase() === newTagName.trim().toLowerCase()
    );
    if (isDuplicate) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'そのなまえは もう あるよ！' : 'このタグ名は既に存在します。'
      );
      return;
    }

    const result = await createTag({
      name: newTagName.trim(),
      color: DEFAULT_TAG_COLOR,
    });

    if (result) {
      closeModal();
      Alert.alert(
        theme === 'child' ? 'やったね！' : '成功',
        theme === 'child'
          ? 'あたらしいタグをつくったよ！'
          : 'タグを作成しました。'
      );
    }
  };

  /**
   * タグ名更新処理（インライン編集）
   */
  const handleUpdateTag = async (tag: Tag) => {
    if (!editingTagName.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'タグのなまえをいれてね！' : 'タグ名を入力してください。'
      );
      return;
    }

    // 重複チェック（自分自身を除く）
    const isDuplicate = tags.some(
      (t) => t.name.toLowerCase() === editingTagName.trim().toLowerCase() && t.id !== tag.id
    );
    if (isDuplicate) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'そのなまえは もう あるよ！' : 'このタグ名は既に存在します。'
      );
      return;
    }

    const result = await updateTag(tag.id, {
      name: editingTagName.trim(),
      color: tag.color,
    });

    if (result) {
      cancelEditingTag();
      Alert.alert(
        theme === 'child' ? 'やったね！' : '成功',
        theme === 'child' ? 'タグをへんこうしたよ！' : 'タグを更新しました。'
      );
    }
  };

  /**
   * タグ削除確認（Web版準拠: タスク存在時は削除不可）
   */
  const confirmDeleteTag = (tag: Tag) => {
    // タスク存在チェック（Web版の制限）
    if (tag.tasks_count > 0) {
      Alert.alert(
        theme === 'child' ? 'けせないよ！' : '削除できません',
        theme === 'child'
          ? `「${tag.name}」には ${tag.tasks_count}こ の タスクが あるから けせないよ！\nさきに タスクの ひもづけを はずしてね！`
          : `このタグには${tag.tasks_count}件のタスクが紐づいています。タスクの紐付けを解除してから削除してください。`,
        [{ text: 'OK' }]
      );
      return;
    }

    Alert.alert(
      theme === 'child' ? 'ほんとうに けす？' : '確認',
      theme === 'child'
        ? `「${tag.name}」を けしても いいかな？`
        : `タグ「${tag.name}」を削除してもよろしいですか？`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'けす！' : '削除',
          style: 'destructive',
          onPress: async () => {
            const success = await deleteTag(tag.id);
            if (success) {
              Alert.alert(
                theme === 'child' ? 'けしたよ！' : '削除完了',
                theme === 'child'
                  ? 'タグを けしたよ！'
                  : 'タグを削除しました。'
              );
            }
          },
        },
      ]
    );
  };

  /**
   * タグクリック処理（詳細画面に遷移）
   */
  const handleTagPress = (tag: Tag) => {
    navigation.navigate('TagDetail', { tag });
  };

  /**
   * タグカードレンダリング（Web版準拠: インライン編集）
   */
  const renderTagItem = ({ item }: { item: Tag }) => {
    const canDelete = item.tasks_count === 0;
    const isEditing = editingTagId === item.id;

    return (
      <View style={[styles.tagCard, { borderLeftColor: item.color, borderLeftWidth: 4 }]}>
        {/* タグ情報（クリックで詳細画面遷移） */}
        {!isEditing ? (
          <TouchableOpacity
            style={styles.tagInfo}
            onPress={() => handleTagPress(item)}
            activeOpacity={0.7}
          >
            <Text style={styles.tagName}>{item.name}</Text>
            <View style={[styles.taskCountBadge, { backgroundColor: item.color }]}>
              <Text style={styles.taskCountText}>{item.tasks_count}</Text>
            </View>
          </TouchableOpacity>
        ) : (
          /* インライン編集フォーム（Web版準拠） */
          <View style={styles.editForm}>
            <TextInput
              style={styles.editInput}
              value={editingTagName}
              onChangeText={setEditingTagName}
              autoFocus
              maxLength={255}
            />
          </View>
        )}

        <View style={styles.tagActions}>
          {!isEditing ? (
            /* 編集ボタン */
            <>
              <LinearGradient
                colors={[accent.primary, accent.primary] as const}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.actionButton, styles.editButton]}
              >
                <TouchableOpacity
                  style={styles.actionButtonTouchable}
                  onPress={() => startEditingTag(item)}
                >
                  <Text style={styles.buttonText}>
                    {theme === 'child' ? 'へんこう' : '編集'}
                  </Text>
                </TouchableOpacity>
              </LinearGradient>

              {/* 削除ボタン */}
              <TouchableOpacity
                style={[
                  styles.actionButton,
                  styles.deleteButton,
                  !canDelete && styles.deleteButtonDisabled,
                ]}
                onPress={() => confirmDeleteTag(item)}
                disabled={!canDelete}
              >
                <Text style={[styles.buttonText, !canDelete && styles.buttonTextDisabled]}>
                  {theme === 'child' ? 'けす' : '削除'}
                </Text>
              </TouchableOpacity>
            </>
          ) : (
            /* 編集モード: 保存・キャンセルボタン */
            <>
              <LinearGradient
                colors={[colors.status.success, colors.status.success] as const}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.actionButton, styles.saveButton]}
              >
                <TouchableOpacity
                  style={styles.actionButtonTouchable}
                  onPress={() => handleUpdateTag(item)}
                >
                  <Text style={styles.buttonText}>
                    {theme === 'child' ? 'ほぞん' : '保存'}
                  </Text>
                </TouchableOpacity>
              </LinearGradient>
              <TouchableOpacity
                style={[styles.actionButton, styles.cancelButton]}
                onPress={cancelEditingTag}
              >
                <Text style={[styles.buttonText, { color: colors.text.secondary }]}>
                  {theme === 'child' ? 'やめる' : 'キャンセル'}
                </Text>
              </TouchableOpacity>
            </>
          )}
        </View>
      </View>
    );
  };



  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>
          {theme === 'child' ? 'タグ かんり' : 'タグ管理'}
        </Text>
        <LinearGradient
          colors={[accent.primary, accent.primary] as const}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={styles.createButton}
        >
          <TouchableOpacity
            style={styles.createButtonTouchable}
            onPress={openCreateModal}
          >
            <Text style={styles.createButtonText}>＋ {theme === 'child' ? 'あたらしいタグ' : '新規作成'}</Text>
          </TouchableOpacity>
        </LinearGradient>
      </View>

      {/* エラー表示 */}
      {error && (
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={clearError}>
            <Text style={styles.errorDismiss}>✕</Text>
          </TouchableOpacity>
        </View>
      )}

      {/* ローディング */}
      {isLoading && !refreshing && (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={accent.primary} />
        </View>
      )}

      {/* タグ一覧 */}
      <FlatList
        data={tags}
        keyExtractor={(item) => item.id.toString()}
        renderItem={renderTagItem}
        contentContainerStyle={styles.listContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        ListEmptyComponent={
          !isLoading ? (
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyText}>
                {theme === 'child'
                  ? 'まだ タグが ないよ！\nあたらしい タグを つくってね！'
                  : 'タグがありません。\n新規作成ボタンからタグを作成してください。'}
              </Text>
            </View>
          ) : null
        }
      />

      {/* タグ新規作成モーダル */}
      <Modal
        animationType="slide"
        transparent={true}
        visible={modalVisible}
        onRequestClose={closeModal}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>
              {theme === 'child' ? 'あたらしい タグ' : '新しいタグ'}
            </Text>

            {/* タグ名入力 */}
            <TextInput
              style={styles.input}
              placeholder={theme === 'child' ? 'タグのなまえ' : 'タグ名'}
              value={newTagName}
              onChangeText={setNewTagName}
              maxLength={255}
            />

            {/* 注意メッセージ: 色選択なし */}
            <Text style={styles.colorNote}>
              {theme === 'child'
                ? '※ いろは じどうで きまるよ！'
                : '※ 色は自動で設定されます'}
            </Text>

            {/* ボタン */}
            <View style={styles.modalButtons}>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalCancelButton]}
                onPress={closeModal}
              >
                <Text style={styles.cancelButtonText}>
                  {theme === 'child' ? 'やめる' : 'キャンセル'}
                </Text>
              </TouchableOpacity>
              <LinearGradient
                colors={[accent.primary, accent.primary] as const}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.modalButton, styles.modalSaveButton]}
              >
                <TouchableOpacity
                  style={styles.modalButtonTouchable}
                  onPress={handleCreateTag}
                >
                  <Text style={styles.saveButtonText}>
                    {theme === 'child' ? 'つくる' : '作成'}
                  </Text>
                </TouchableOpacity>
              </LinearGradient>
            </View>
          </View>
        </View>
      </Modal>

      {/* アバター表示 */}
      {avatarVisible && avatarData && (
        <AvatarWidget
          visible={avatarVisible}
          data={avatarData}
          onClose={hideAvatar}
        />
      )}
    </View>
  );
}

const createStyles = (width: number, theme: any, colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(16, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  createButton: {
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
    ...getShadow(4),
  },
  createButtonTouchable: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    alignItems: 'center',
  },
  createButtonText: {
    color: colors.background,
    fontWeight: '600',
    fontSize: getFontSize(14, width, theme),
  },
  errorContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.status.error + '20',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
  },
  errorText: {
    flex: 1,
    color: colors.status.error,
    fontSize: getFontSize(14, width, theme),
  },
  errorDismiss: {
    color: colors.status.error,
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    marginLeft: getSpacing(8, width),
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  listContent: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(16, width),
  },
  tagCard: {
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    ...getShadow(2),
  },
  tagInfo: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: getSpacing(12, width),
  },
  editForm: {
    flex: 1,
    marginBottom: getSpacing(12, width),
  },
  editInput: {
    borderWidth: 1,
    borderColor: accent.primary,
    borderRadius: getBorderRadius(6, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
    fontSize: getFontSize(16, width, theme),
    backgroundColor: colors.background,
    color: colors.text.primary,
  },
  tagName: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    flex: 1,
  },
  taskCountBadge: {
    borderRadius: getBorderRadius(12, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(4, width),
  },
  taskCountText: {
    color: colors.background,
    fontWeight: 'bold',
    fontSize: getFontSize(14, width, theme),
  },
  tagActions: {
    flexDirection: 'row',
    gap: getSpacing(8, width),
  },
  actionButton: {
    flex: 1,
    borderRadius: getBorderRadius(6, width),
    overflow: 'hidden',
    ...getShadow(2),
  },
  actionButtonTouchable: {
    width: '100%',
    paddingVertical: getSpacing(8, width),
    alignItems: 'center',
  },
  editButton: {
    // LinearGradient適用のためbackgroundColor削除
  },
  saveButton: {
    // LinearGradient適用のためbackgroundColor削除
  },
  cancelButton: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  deleteButton: {
    backgroundColor: colors.status.error,
  },
  deleteButtonDisabled: {
    backgroundColor: colors.border,
    opacity: 0.6,
  },
  buttonText: {
    color: colors.background,
    fontWeight: '600',
    fontSize: getFontSize(14, width, theme),
  },
  buttonTextDisabled: {
    color: colors.text.disabled,
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(60, width),
  },
  emptyText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
    textAlign: 'center',
    lineHeight: getFontSize(24, width, theme),
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(16, width),
    padding: getSpacing(24, width),
    width: width - getSpacing(48, width),
    maxWidth: 400,
  },
  modalTitle: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(20, width),
    textAlign: 'center',
  },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    marginBottom: getSpacing(12, width),
    backgroundColor: colors.background,
    color: colors.text.primary,
  },
  colorNote: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(20, width),
    textAlign: 'center',
  },
  modalButtons: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
  },
  modalButton: {
    flex: 1,
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  modalButtonTouchable: {
    width: '100%',
    paddingVertical: getSpacing(12, width),
    alignItems: 'center',
  },
  modalCancelButton: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  cancelButtonText: {
    color: colors.text.primary,
    fontWeight: '600',
    fontSize: getFontSize(16, width, theme),
  },
  modalSaveButton: {
    // LinearGradient適用のためbackgroundColor削除
  },
  saveButtonText: {
    color: colors.background,
    fontWeight: '600',
    fontSize: getFontSize(16, width, theme),
  },
});
