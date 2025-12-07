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
import { useEffect, useState, useCallback } from 'react';
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
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useTags } from '../../hooks/useTags';
import { useTheme } from '../../contexts/ThemeContext';
import { useAvatarContext } from '../../contexts/AvatarContext';
import AvatarWidget from '../../components/common/AvatarWidget';
import type { Tag } from '../../types/tag.types';

const { width } = Dimensions.get('window');

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
  const DEFAULT_TAG_COLOR = '#3B82F6'; // Web版準拠: 色選択機能なし、デフォルト色固定

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
              <TouchableOpacity
                style={[styles.actionButton, styles.editButton]}
                onPress={() => startEditingTag(item)}
              >
                <Text style={styles.buttonText}>
                  {theme === 'child' ? 'へんこう' : '編集'}
                </Text>
              </TouchableOpacity>
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
              <TouchableOpacity
                style={[styles.actionButton, styles.saveButton]}
                onPress={() => handleUpdateTag(item)}
              >
                <Text style={styles.buttonText}>
                  {theme === 'child' ? 'ほぞん' : '保存'}
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.actionButton, styles.cancelButton]}
                onPress={cancelEditingTag}
              >
                <Text style={[styles.buttonText, { color: '#6B7280' }]}>
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
        <TouchableOpacity
          style={styles.createButton}
          onPress={openCreateModal}
        >
          <Text style={styles.createButtonText}>＋ {theme === 'child' ? 'あたらしいタグ' : '新規作成'}</Text>
        </TouchableOpacity>
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
          <ActivityIndicator size="large" color="#3B82F6" />
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
              <TouchableOpacity
                style={[styles.modalButton, styles.modalSaveButton]}
                onPress={handleCreateTag}
              >
                <Text style={styles.saveButtonText}>
                  {theme === 'child' ? 'つくる' : '作成'}
                </Text>
              </TouchableOpacity>
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

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#111827',
  },
  createButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  createButtonText: {
    color: '#FFFFFF',
    fontWeight: '600',
    fontSize: 14,
  },
  errorContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 8,
  },
  errorText: {
    flex: 1,
    color: '#991B1B',
    fontSize: 14,
  },
  errorDismiss: {
    color: '#991B1B',
    fontSize: 18,
    fontWeight: 'bold',
    marginLeft: 8,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  listContent: {
    paddingHorizontal: 16,
    paddingVertical: 16,
  },
  tagCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  tagInfo: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  editForm: {
    flex: 1,
    marginBottom: 12,
  },
  editInput: {
    borderWidth: 1,
    borderColor: '#3B82F6',
    borderRadius: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    fontSize: 16,
    backgroundColor: '#F9FAFB',
  },
  tagName: {
    fontSize: 18,
    fontWeight: '600',
    color: '#111827',
    flex: 1,
  },
  taskCountBadge: {
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 4,
  },
  taskCountText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: 14,
  },
  tagActions: {
    flexDirection: 'row',
    gap: 8,
  },
  actionButton: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 6,
    alignItems: 'center',
  },
  editButton: {
    backgroundColor: '#3B82F6',
  },
  saveButton: {
    backgroundColor: '#10B981',
  },
  cancelButton: {
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#D1D5DB',
  },
  deleteButton: {
    backgroundColor: '#EF4444',
  },
  deleteButtonDisabled: {
    backgroundColor: '#D1D5DB',
    opacity: 0.6,
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: '600',
    fontSize: 14,
  },
  buttonTextDisabled: {
    color: '#9CA3AF',
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
  },
  emptyText: {
    fontSize: 16,
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 24,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 24,
    width: width - 48,
    maxWidth: 400,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#111827',
    marginBottom: 20,
    textAlign: 'center',
  },
  input: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 16,
    marginBottom: 12,
    backgroundColor: '#F9FAFB',
  },
  colorNote: {
    fontSize: 12,
    color: '#6B7280',
    marginBottom: 20,
    textAlign: 'center',
  },
  modalButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  modalButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  modalCancelButton: {
    backgroundColor: '#F3F4F6',
  },
  cancelButtonText: {
    color: '#111827',
    fontWeight: '600',
    fontSize: 16,
  },
  modalSaveButton: {
    backgroundColor: '#3B82F6',
  },
  saveButtonText: {
    color: '#FFFFFF',
    fontWeight: '600',
    fontSize: 16,
  },
});
