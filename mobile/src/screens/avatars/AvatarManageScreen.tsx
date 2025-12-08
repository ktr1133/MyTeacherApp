/**
 * AvatarManageScreen - アバター管理画面
 * 
 * Phase 2.B-7: アバター管理機能実装
 * 
 * 機能:
 * - アバター情報表示（画像プレビュー、生成ステータス、設定値）
 * - 表示ON/OFF切替
 * - 編集画面への遷移
 * - 画像再生成（確認ダイアログ付き）
 * - アバター削除（確認ダイアログ付き）
 * - 画像スワイプ切り替え（複数表情対応）
 * - テーマ対応UI（adult/child）
 * 
 * Web版: /resources/views/avatars/edit.blade.php
 */

import { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  Image,
  Dimensions,
  Switch,
  Modal,
  Pressable,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useAvatarManagement } from '../../hooks/useAvatarManagement';
import { AVATAR_OPTIONS, AVATAR_TOKEN_COST } from '../../utils/constants';
import { AvatarImage } from '../../types/avatar.types';

const { width } = Dimensions.get('window');

/**
 * AvatarManageScreen コンポーネント
 */
export const AvatarManageScreen: React.FC = () => {
  const navigation = useNavigation();
  const { theme } = useTheme();
  const {
    avatar,
    isLoading,
    // error, // 将来のエラー表示機能用
    fetchAvatar,
    deleteAvatar,
    regenerateImages,
    toggleVisibility,
  } = useAvatarManagement();

  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const scrollViewRef = useRef<ScrollView>(null);

  // 表情順にソートする関数
  const getEmotionOrder = (emotion: string | null): number => {
    const order: Record<string, number> = {
      'neutral': 0,
      'happy': 1,
      'sad': 2,
      'angry': 3,
      'surprised': 4,
    };
    return emotion ? (order[emotion] ?? 999) : 999;
  };

  // 画像を表情順にソート
  const sortedImages = avatar?.images
    ? [...avatar.images]
        .filter(img => img.image_url !== null)
        .sort((a, b) => getEmotionOrder(a.emotion) - getEmotionOrder(b.emotion))
    : [];

  // 表情名を表示用に変換
  const getEmotionLabel = (emotion: string | null): string => {
    if (!emotion) return '通常';
    const labels: Record<string, string> = {
      'neutral': '通常',
      'happy': '喜び',
      'sad': '悲しみ',
      'angry': '怒り',
      'surprised': '驚き',
    };
    return labels[emotion] || emotion;
  };

  // 初回アバター取得
  useEffect(() => {
    loadAvatar();
  }, []);

  // デバッグ: アバターデータをログ出力
  useEffect(() => {
    if (avatar) {
      console.log('🎭 [AvatarManageScreen] Avatar loaded:', {
        id: avatar.id,
        generation_status: avatar.generation_status,
        images_count: avatar.images?.length || 0,
        sortedImages_count: sortedImages.length,
      });
    }
  }, [avatar, sortedImages]);

  /**
   * アバター読み込み
   */
  const loadAvatar = async () => {
    try {
      const data = await fetchAvatar();
      if (!data) {
        // アバター未作成の場合、作成画面へ遷移
        Alert.alert(
          theme === 'child' ? 'アバターがないよ' : 'アバター未作成',
          theme === 'child'
            ? 'アバターをつくってね'
            : 'アバターが作成されていません。作成画面へ移動しますか？',
          [
            {
              text: theme === 'child' ? 'あとで' : 'キャンセル',
              style: 'cancel',
              onPress: () => navigation.goBack(),
            },
            {
              text: theme === 'child' ? 'つくる' : '作成する',
              onPress: () => navigation.navigate('AvatarCreate' as never),
            },
          ],
        );
      }
    } catch (err) {
      console.error('Failed to load avatar', err);
    }
  };

  /**
   * 編集画面へ遷移
   */
  const handleEdit = () => {
    if (avatar) {
      navigation.navigate('AvatarEdit' as never, { avatar } as any);
    }
  };

  /**
   * 画像再生成
   */
  const handleRegenerate = () => {
    Alert.alert(
      theme === 'child' ? 'えをつくりなおす' : '画像再生成',
      theme === 'child'
        ? `トークンを ${AVATAR_TOKEN_COST.REGENERATE.toLocaleString()} つかって、えをつくりなおすよ。いい？`
        : `${AVATAR_TOKEN_COST.REGENERATE.toLocaleString()}トークンを消費して画像を再生成します。よろしいですか？`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'つくりなおす' : '再生成',
          onPress: async () => {
            try {
              await regenerateImages();
              Alert.alert(
                theme === 'child' ? 'つくりはじめたよ' : '再生成開始',
                theme === 'child'
                  ? 'えをつくりなおしているよ。おわったらおしらせするね！'
                  : '画像の再生成を開始しました。完了したら通知でお知らせします。',
              );
            } catch (err) {
              console.error('Failed to regenerate images:', err);
              Alert.alert(
                theme === 'child' ? 'エラー' : 'エラー',
                theme === 'child'
                  ? 'えがつくれなかったよ。もういちどためしてね。'
                  : '画像の再生成に失敗しました。',
              );
            }
          },
        },
      ],
    );
  };

  /**
   * アバター削除
   */
  const handleDelete = () => {
    Alert.alert(
      theme === 'child' ? 'けす' : 'アバター削除',
      theme === 'child'
        ? 'アバターをけすと、もとにもどせないよ。けしてもいい？'
        : 'アバターを削除すると、元に戻せません。削除してもよろしいですか？',
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'けす' : '削除',
          style: 'destructive',
          onPress: async () => {
            try {
              await deleteAvatar();
              Alert.alert(
                theme === 'child' ? 'けしたよ' : '削除完了',
                theme === 'child' ? 'アバターをけしたよ' : 'アバターを削除しました。',
                [
                  {
                    text: 'OK',
                    onPress: () => navigation.goBack(),
                  },
                ],
              );
            } catch (err) {
              console.error('Failed to delete avatar:', err);
              Alert.alert(
                theme === 'child' ? 'エラー' : 'エラー',
                theme === 'child'
                  ? 'けせなかったよ。もういちどためしてね。'
                  : 'アバターの削除に失敗しました。',
              );
            }
          },
        },
      ],
    );
  };

  /**
   * 表示設定切替
   */
  const handleToggleVisibility = async (value: boolean) => {
    try {
      await toggleVisibility(value);
    } catch (err) {
      console.error('Failed to toggle visibility:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'せっていがかえられなかったよ'
          : '表示設定の切替に失敗しました。',
      );
    }
  };

  /**
   * 設定値ラベル取得
   */
  const getOptionLabel = (category: keyof typeof AVATAR_OPTIONS, value: string | null | undefined): string => {
    // hair_styleがnullの場合はデフォルトで「ミディアム」を表示
    if (category === 'hair_style' && !value) {
      value = 'middle';
    }
    const option = AVATAR_OPTIONS[category].find((opt: any) => opt.value === value);
    return option?.label || value || '';
  };

  const isChild = theme === 'child';

  // ローディング中
  if (isLoading && !avatar) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color="#8B5CF6" />
        <Text style={styles.loadingText}>
          {isChild ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  // アバター未作成
  if (!avatar) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <Text style={styles.emptyText}>
          {isChild ? 'アバターがないよ' : 'アバターが作成されていません'}
        </Text>
      </View>
    );
  }

  return (
    <ScrollView style={[styles.container, isChild && styles.childContainer]}>
      <View style={styles.content}>
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={[styles.title, isChild && styles.childTitle]}>
            {isChild ? 'アバターせってい' : 'アバター設定'}
          </Text>
        </View>

        {/* アバター画像プレビュー */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, isChild && styles.childSectionTitle]}>
            {isChild ? '🎨 アバターのえ' : '🎨 アバター画像'}
          </Text>

          {avatar.generation_status === 'completed' && avatar.images.length > 0 && sortedImages.length > 0 ? (
            <View>
              {/* メイン画像カルーセル */}
              <ScrollView
                ref={scrollViewRef}
                horizontal
                pagingEnabled
                showsHorizontalScrollIndicator={false}
                onMomentumScrollEnd={(event) => {
                  const index = Math.round(event.nativeEvent.contentOffset.x / width);
                  setSelectedImageIndex(index);
                }}
                style={styles.carousel}
              >
                {sortedImages.map((img, index) => (
                  <View key={img.id} style={styles.imageContainer}>
                    <TouchableOpacity 
                      style={styles.imageWrapper}
                      onPress={() => {
                        setSelectedImageIndex(index);
                        setIsModalVisible(true);
                      }}
                      activeOpacity={0.8}
                    >
                      {img.image_url ? (
                        <Image 
                          source={{ uri: img.image_url }} 
                          style={styles.image}
                          resizeMode="contain"
                        />
                      ) : (
                        <View style={styles.placeholderContainer}>
                          <Text style={styles.placeholderText}>画像なし</Text>
                        </View>
                      )}
                      {/* ラベルを画像の左上に配置 */}
                      <View style={styles.imageLabel}>
                        <Text style={styles.imageLabelText}>
                          {getEmotionLabel(img.emotion)}
                        </Text>
                      </View>
                      {/* タップヒント */}
                      <View style={styles.tapHint}>
                        <Text style={styles.tapHintText}>タップで拡大</Text>
                      </View>
                    </TouchableOpacity>
                  </View>
                ))}
              </ScrollView>

              {/* サムネイル一覧 */}
              <View style={styles.thumbnailWrapper}>
                <View style={styles.thumbnailContainer}>
                  {sortedImages.map((img, index) => (
                    <TouchableOpacity
                      key={img.id}
                      onPress={() => {
                        setSelectedImageIndex(index);
                        scrollViewRef.current?.scrollTo({ x: width * index, animated: true });
                      }}
                      style={[
                        styles.thumbnail,
                        selectedImageIndex === index && styles.thumbnailSelected,
                      ]}
                    >
                      {img.image_url ? (
                        <Image 
                          source={{ uri: img.image_url }} 
                          style={styles.thumbnailImage}
                          resizeMode="cover"
                        />
                      ) : (
                        <View style={[styles.thumbnailImage, styles.placeholderThumbnail]}>
                          <Text style={styles.placeholderThumbText}>...</Text>
                        </View>
                      )}
                    </TouchableOpacity>
                  ))}
                </View>
              </View>
            </View>
          ) : avatar.generation_status === 'processing' || avatar.generation_status === 'pending' ? (
            <View style={styles.statusContainer}>
              <ActivityIndicator size="large" color="#8B5CF6" />
              <Text style={styles.statusText}>
                {isChild ? 'えをつくっているよ...' : '画像生成中...'}
              </Text>
              <Text style={[styles.statusText, { fontSize: 14, marginTop: 8 }]}>
                {isChild 
                  ? 'せいせいには5ふんくらいかかるよ' 
                  : '生成には数分かかる場合があります'}
              </Text>
            </View>
          ) : avatar.images.length > 0 && avatar.images.every(img => img.image_url === null) ? (
            <View style={styles.statusContainer}>
              <ActivityIndicator size="large" color="#8B5CF6" />
              <Text style={styles.statusText}>
                {isChild ? 'えをつくっているよ...' : '画像生成処理中...'}
              </Text>
              <Text style={[styles.statusText, { fontSize: 14, marginTop: 8 }]}>
                {isChild 
                  ? 'もうすこしまってね' 
                  : '画像URLが生成されるまでお待ちください'}
              </Text>
            </View>
          ) : (
            <View style={styles.statusContainer}>
              <Text style={styles.statusTextError}>
                {isChild ? 'えがつくれなかったよ' : '生成に失敗しました'}
              </Text>
            </View>
          )}

          {/* 表示設定 */}
          <View style={styles.visibilityContainer}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'アバターをひょうじする' : 'アバター表示'}
            </Text>
            <Switch
              value={avatar.is_visible}
              onValueChange={handleToggleVisibility}
              trackColor={{ false: '#ccc', true: '#8B5CF6' }}
              thumbColor="#fff"
            />
          </View>
        </View>

        {/* 設定情報 */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, isChild && styles.childSectionTitle]}>
            {isChild ? '👤 みため' : '👤 外見の設定'}
          </Text>
          <View style={styles.infoGrid}>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'せいべつ' : '性別'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('sex', avatar.sex)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'かみがた' : '髪型'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('hair_style', avatar.hair_style || 'middle')}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'かみのいろ' : '髪の色'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('hair_color', avatar.hair_color)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'めのいろ' : '目の色'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('eye_color', avatar.eye_color)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'ふくそう' : '服装'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('clothing', avatar.clothing)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>アクセサリー</Text>
              <Text style={styles.infoValue}>{getOptionLabel('accessory', avatar.accessory || 'nothing')}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'たいけい' : '体型'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('body_type', avatar.body_type)}</Text>
            </View>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={[styles.sectionTitle, isChild && styles.childSectionTitle]}>
            {isChild ? '💬 せいかく' : '💬 性格の設定'}
          </Text>
          <View style={styles.infoGrid}>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'くちょう' : '口調'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('tone', avatar.tone)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'ねつい' : '熱意'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('enthusiasm', avatar.enthusiasm)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>{isChild ? 'ていねいさ' : '丁寧さ'}</Text>
              <Text style={styles.infoValue}>{getOptionLabel('formality', avatar.formality)}</Text>
            </View>
            <View style={styles.infoItem}>
              <Text style={styles.infoLabel}>ユーモア</Text>
              <Text style={styles.infoValue}>{getOptionLabel('humor', avatar.humor)}</Text>
            </View>
          </View>
        </View>

        {/* アクションボタン */}
        <View style={styles.buttonContainer}>
          <TouchableOpacity
            style={[styles.button, styles.buttonPrimary, isChild && styles.childButton]}
            onPress={handleEdit}
          >
            <Text style={[styles.buttonText, isChild && styles.childButtonText]}>
              {isChild ? 'へんしゅう' : '編集する'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.buttonSecondary, isChild && styles.childButton]}
            onPress={handleRegenerate}
            disabled={avatar.generation_status !== 'completed'}
          >
            <Text style={[styles.buttonText, isChild && styles.childButtonText]}>
              {isChild ? 'えをつくりなおす' : '画像を再生成'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.buttonDanger]}
            onPress={handleDelete}
          >
            <Text style={styles.buttonText}>
              {isChild ? 'けす' : '削除する'}
            </Text>
          </TouchableOpacity>
        </View>

        <View style={styles.footer} />
      </View>

      {/* 画像拡大モーダル */}
      <Modal
        visible={isModalVisible}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setIsModalVisible(false)}
      >
        <Pressable 
          style={styles.modalOverlay}
          onPress={() => setIsModalVisible(false)}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {sortedImages[selectedImageIndex] && getEmotionLabel(sortedImages[selectedImageIndex].emotion)}
              </Text>
              <TouchableOpacity 
                onPress={() => setIsModalVisible(false)}
                style={styles.closeButton}
              >
                <Text style={styles.closeButtonText}>✕</Text>
              </TouchableOpacity>
            </View>
            
            {sortedImages[selectedImageIndex]?.image_url && (
              <View style={styles.modalImageWrapper}>
                <Image 
                  source={{ uri: sortedImages[selectedImageIndex].image_url }} 
                  style={styles.modalImage}
                  resizeMode="contain"
                />
              </View>
            )}

            {/* 前後ボタン */}
            {sortedImages.length > 1 && (
              <View style={styles.navigationButtons}>
                <TouchableOpacity
                  onPress={() => {
                    const newIndex = selectedImageIndex > 0 ? selectedImageIndex - 1 : sortedImages.length - 1;
                    setSelectedImageIndex(newIndex);
                    scrollViewRef.current?.scrollTo({ x: width * newIndex, animated: true });
                  }}
                  style={styles.navButton}
                >
                  <Text style={styles.navButtonText}>← 前へ</Text>
                </TouchableOpacity>
                <Text style={styles.pageIndicator}>
                  {selectedImageIndex + 1} / {sortedImages.length}
                </Text>
                <TouchableOpacity
                  onPress={() => {
                    const newIndex = selectedImageIndex < sortedImages.length - 1 ? selectedImageIndex + 1 : 0;
                    setSelectedImageIndex(newIndex);
                    scrollViewRef.current?.scrollTo({ x: width * newIndex, animated: true });
                  }}
                  style={styles.navButton}
                >
                  <Text style={styles.navButtonText}>次へ →</Text>
                </TouchableOpacity>
              </View>
            )}
          </View>
        </Pressable>
      </Modal>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  childContainer: {
    backgroundColor: '#FFF8DC',
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    padding: 16,
  },
  header: {
    marginBottom: 24,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#333',
  },
  childTitle: {
    fontSize: 26,
    color: '#FF6B35',
  },
  section: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 16,
  },
  childSectionTitle: {
    fontSize: 20,
    color: '#FF6B35',
  },
  carousel: {
    width,
    height: width,
  },
  imageContainer: {
    width,
    height: width,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
  },
  imageWrapper: {
    position: 'relative',
    width: width - 32,
    height: width - 32,
    justifyContent: 'center',
    alignItems: 'center',
  },
  image: {
    width: '100%',
    height: '100%',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
  },
  placeholderContainer: {
    width: '100%',
    height: '100%',
    backgroundColor: '#E5E7EB',
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  placeholderText: {
    fontSize: 16,
    color: '#9CA3AF',
  },
  imageLabel: {
    position: 'absolute',
    top: 8,
    left: 8,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
  },
  imageLabelText: {
    fontSize: 14,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  thumbnailWrapper: {
    marginTop: 16,
    paddingHorizontal: 16,
    overflow: 'hidden',
  },
  thumbnailContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 10,
  },
  thumbnail: {
    width: 64,
    height: 64,
    borderRadius: 8,
    borderWidth: 2,
    borderColor: 'transparent',
  },
  thumbnailSelected: {
    borderColor: '#8B5CF6',
  },
  thumbnailImage: {
    width: '100%',
    height: '100%',
    borderRadius: 6,
  },
  placeholderThumbnail: {
    backgroundColor: '#E5E7EB',
    justifyContent: 'center',
    alignItems: 'center',
  },
  placeholderThumbText: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  statusContainer: {
    paddingVertical: 32,
    alignItems: 'center',
  },
  statusText: {
    marginTop: 12,
    fontSize: 16,
    color: '#666',
  },
  statusTextError: {
    fontSize: 16,
    color: '#DC2626',
  },
  visibilityContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  childLabel: {
    fontSize: 16,
    color: '#FF8C42',
  },
  infoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  infoItem: {
    width: '50%',
    marginBottom: 12,
  },
  infoLabel: {
    fontSize: 12,
    color: '#666',
    marginBottom: 4,
  },
  infoValue: {
    fontSize: 14,
    color: '#333',
    fontWeight: '600',
  },
  buttonContainer: {
    marginTop: 8,
  },
  button: {
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 12,
  },
  buttonPrimary: {
    backgroundColor: '#8B5CF6',
  },
  buttonSecondary: {
    backgroundColor: '#6B7280',
  },
  buttonDanger: {
    backgroundColor: '#DC2626',
  },
  childButton: {
    backgroundColor: '#FF6B35',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  childButtonText: {
    fontSize: 18,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#666',
  },
  emptyText: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
  },
  footer: {
    height: 32,
  },
  tapHint: {
    position: 'absolute',
    bottom: 8,
    right: 8,
    backgroundColor: 'rgba(139, 92, 246, 0.9)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  tapHintText: {
    fontSize: 12,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.9)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    width: '100%',
    height: '100%',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalHeader: {
    position: 'absolute',
    top: 60,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    zIndex: 10,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  closeButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  closeButtonText: {
    fontSize: 24,
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
  modalImageWrapper: {
    width: '100%',
    height: '70%',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalImage: {
    width: '90%',
    height: '100%',
  },
  navigationButtons: {
    position: 'absolute',
    bottom: 60,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    width: '90%',
    paddingHorizontal: 20,
  },
  navButton: {
    backgroundColor: 'rgba(139, 92, 246, 0.9)',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
  },
  navButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  pageIndicator: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
});

export default AvatarManageScreen;
