/**
 * AvatarCreateScreen - アバター作成画面
 * 
 * Phase 2.B-7: アバター管理機能実装
 * 
 * 機能:
 * - アバター外見設定（性別、髪型、髪色、目の色、服装、アクセサリー、体型）
 * - アバター性格設定（口調、熱意、丁寧さ、ユーモア）
 * - 描画モデル選択（トークン消費量動的表示）
 * - バックグラウンド画像生成（生成完了後に通知）
 * - テーマ対応UI（adult/child）
 * 
 * Web版: /resources/views/avatars/create.blade.php
 */

import React, { useState, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  Modal,
  FlatList,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useAvatarManagement } from '../../hooks/useAvatarManagement';
import { AVATAR_OPTIONS } from '../../utils/constants';
import { MaterialIcons } from '@expo/vector-icons';
import {
  AvatarSex,
  AvatarHairStyle,
  AvatarHairColor,
  AvatarEyeColor,
  AvatarClothing,
  AvatarAccessory,
  AvatarBodyType,
  AvatarTone,
  AvatarEnthusiasm,
  AvatarFormality,
  AvatarHumor,
  AvatarDrawModelVersion,
} from '../../types/avatar.types';

/**
 * AvatarCreateScreen コンポーネント
 */
export const AvatarCreateScreen: React.FC = () => {
  const navigation = useNavigation();
  const { width } = useResponsive();
  const { theme } = useTheme();
  const { colors, accent } = useThemedColors();
  const { createAvatar, isLoading, error } = useAvatarManagement();

  // 外見設定
  const [sex, setSex] = useState<AvatarSex>('female');
  const [hairStyle, setHairStyle] = useState<AvatarHairStyle>('short');
  const [hairColor, setHairColor] = useState<AvatarHairColor>('black');
  const [eyeColor, setEyeColor] = useState<AvatarEyeColor>('black');
  const [clothing, setClothing] = useState<AvatarClothing>('suit');
  const [accessory, setAccessory] = useState<AvatarAccessory>('nothing');
  const [bodyType, setBodyType] = useState<AvatarBodyType>('average');

  // 性格設定
  const [tone, setTone] = useState<AvatarTone>('gentle');
  const [enthusiasm, setEnthusiasm] = useState<AvatarEnthusiasm>('normal');
  const [formality, setFormality] = useState<AvatarFormality>('polite');
  const [humor, setHumor] = useState<AvatarHumor>('normal');

  // 描画設定
  const [drawModelVersion, setDrawModelVersion] = useState<AvatarDrawModelVersion>('anything-v4.0');
  const [isTransparent] = useState(true); // 固定: 背景透過ON
  const [isChibi] = useState(false); // 固定: デフォルメOFF

  // モーダル選択state
  const [showModal, setShowModal] = useState(false);
  const [modalType, setModalType] = useState<string>('');
  const [modalOptions, setModalOptions] = useState<Array<{ value: string; label: string; emoji?: string }>>([]);
  const [modalTitle, setModalTitle] = useState('');

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, theme, colors, accent), [width, theme, colors, accent]);

  // 推定トークン消費量を取得
  const getEstimatedTokenUsage = (): number => {
    const model = AVATAR_OPTIONS.draw_model_version.find(m => m.value === drawModelVersion);
    return model?.estimatedTokenUsage || 5000;
  };

  /**
   * モーダルを開く
   */
  const openModal = (
    type: string,
    title: string,
    options: Array<{ value: string; label: string; emoji?: string }>
  ) => {
    setModalType(type);
    setModalTitle(title);
    setModalOptions(options);
    setShowModal(true);
  };

  /**
   * モーダルで選択を確定
   */
  const handleModalSelect = (value: string) => {
    switch (modalType) {
      case 'sex':
        setSex(value as AvatarSex);
        break;
      case 'hairStyle':
        setHairStyle(value as AvatarHairStyle);
        break;
      case 'hairColor':
        setHairColor(value as AvatarHairColor);
        break;
      case 'eyeColor':
        setEyeColor(value as AvatarEyeColor);
        break;
      case 'clothing':
        setClothing(value as AvatarClothing);
        break;
      case 'accessory':
        setAccessory(value as AvatarAccessory);
        break;
      case 'bodyType':
        setBodyType(value as AvatarBodyType);
        break;
      case 'tone':
        setTone(value as AvatarTone);
        break;
      case 'enthusiasm':
        setEnthusiasm(value as AvatarEnthusiasm);
        break;
      case 'formality':
        setFormality(value as AvatarFormality);
        break;
      case 'humor':
        setHumor(value as AvatarHumor);
        break;
      case 'drawModel':
        setDrawModelVersion(value as AvatarDrawModelVersion);
        break;
    }
    setShowModal(false);
  };

  /**
   * 現在の選択値を取得
   */
  const getCurrentValue = (type: string): string => {
    const values: { [key: string]: string } = {
      sex,
      hairStyle,
      hairColor,
      eyeColor,
      clothing,
      accessory,
      bodyType,
      tone,
      enthusiasm,
      formality,
      humor,
      drawModel: drawModelVersion,
    };
    return values[type] || '';
  };

  /**
   * 現在の選択値のラベルを取得
   */
  const getCurrentLabel = (type: string): string => {
    const currentValue = getCurrentValue(type);
    const optionsMap: { [key: string]: any[] } = {
      sex: AVATAR_OPTIONS.sex,
      hairStyle: AVATAR_OPTIONS.hair_style,
      hairColor: AVATAR_OPTIONS.hair_color,
      eyeColor: AVATAR_OPTIONS.eye_color,
      clothing: AVATAR_OPTIONS.clothing,
      accessory: AVATAR_OPTIONS.accessory,
      bodyType: AVATAR_OPTIONS.body_type,
      tone: AVATAR_OPTIONS.tone,
      enthusiasm: AVATAR_OPTIONS.enthusiasm,
      formality: AVATAR_OPTIONS.formality,
      humor: AVATAR_OPTIONS.humor,
      drawModel: AVATAR_OPTIONS.draw_model_version,
    };
    const option = optionsMap[type]?.find((o: any) => o.value === currentValue);
    return option ? (option.emoji ? `${option.emoji} ${option.label}` : option.label) : '';
  };

  /**
   * アバター作成処理
   */
  const handleCreate = async () => {
    try {
      const estimatedUsage = getEstimatedTokenUsage();
      
      // 確認ダイアログ
      Alert.alert(
        theme === 'child' ? 'アバターをつくる' : 'アバター作成',
        theme === 'child'
          ? `トークンを ${estimatedUsage.toLocaleString()} つかうよ。つくってもいい？`
          : `${estimatedUsage.toLocaleString()}トークンを消費してアバターを作成します。よろしいですか？`,
        [
          {
            text: theme === 'child' ? 'やめる' : 'キャンセル',
            style: 'cancel',
          },
          {
            text: theme === 'child' ? 'つくる' : '作成',
            onPress: async () => {
              try {
                await createAvatar({
                  sex,
                  hair_style: hairStyle,
                  hair_color: hairColor,
                  eye_color: eyeColor,
                  clothing,
                  accessory,
                  body_type: bodyType,
                  tone,
                  enthusiasm,
                  formality,
                  humor,
                  draw_model_version: drawModelVersion,
                  is_transparent: isTransparent,
                  is_chibi: isChibi,
                });

                Alert.alert(
                  theme === 'child' ? 'つくりはじめたよ' : '作成開始',
                  theme === 'child'
                    ? 'アバターのえをつくっているよ。すうふんかかるから、おわったらおしらせするね！'
                    : 'アバター画像の生成を開始しました。数分かかりますので、完了したら通知でお知らせします。',
                  [
                    {
                      text: 'OK',
                      onPress: () => navigation.goBack(),
                    },
                  ],
                );
              } catch (err) {
                console.error('Failed to create avatar:', err);
                Alert.alert(
                  theme === 'child' ? 'エラー' : 'エラー',
                  theme === 'child'
                    ? 'アバターがつくれなかったよ。もういちどためしてね。'
                    : 'アバターの作成に失敗しました。',
                );
              }
            },
          },
        ],
      );
    } catch (err) {
      console.error('Failed to create avatar:', err);
    }
  };

  const isChild = theme === 'child';

  return (
    <ScrollView style={[styles.container, isChild && styles.childContainer]}>
      <View style={styles.content}>
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={[styles.title, isChild && styles.childTitle]}>
            {isChild ? 'アバターをつくろう' : 'アバター作成'}
          </Text>
          <Text style={[styles.subtitle, isChild && styles.childSubtitle]}>
            {isChild
              ? 'せんせいのみためとせいかくをえらんでね'
              : '教師アバターの外見と性格を選択してください'}
          </Text>
        </View>

        {/* 外見設定 */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, isChild && styles.childSectionTitle]}>
            {isChild ? '👤 みため' : '👤 外見の設定'}
          </Text>

          {/* 性別 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'せいべつ' : '性別'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('sex', isChild ? 'せいべつ' : '性別', AVATAR_OPTIONS.sex)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('sex')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 髪型 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'かみがた' : '髪型'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('hairStyle', isChild ? 'かみがた' : '髪型', AVATAR_OPTIONS.hair_style)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('hairStyle')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 髪の色 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'かみのいろ' : '髪の色'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('hairColor', isChild ? 'かみのいろ' : '髪の色', AVATAR_OPTIONS.hair_color)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('hairColor')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 目の色 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'めのいろ' : '目の色'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('eyeColor', isChild ? 'めのいろ' : '目の色', AVATAR_OPTIONS.eye_color)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('eyeColor')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 服装 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ふくそう' : '服装'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('clothing', isChild ? 'ふくそう' : '服装', AVATAR_OPTIONS.clothing)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('clothing')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* アクセサリー */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'アクセサリー' : 'アクセサリー'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('accessory', isChild ? 'アクセサリー' : 'アクセサリー', AVATAR_OPTIONS.accessory)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('accessory')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 体型 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'たいけい' : '体型'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('bodyType', isChild ? 'たいけい' : '体型', AVATAR_OPTIONS.body_type)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('bodyType')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>
        </View>

        {/* 性格設定 */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, isChild && styles.childSectionTitle]}>
            {isChild ? '💬 せいかく' : '💬 性格の設定'}
          </Text>

          {/* 口調 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'くちょう' : '口調'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('tone', isChild ? 'くちょう' : '口調', AVATAR_OPTIONS.tone)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('tone')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 熱意 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ねつい' : '熱意'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('enthusiasm', isChild ? 'ねつい' : '熱意', AVATAR_OPTIONS.enthusiasm)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('enthusiasm')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* 丁寧さ */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ていねいさ' : '丁寧さ'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('formality', isChild ? 'ていねいさ' : '丁寧さ', AVATAR_OPTIONS.formality)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('formality')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* ユーモア */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ユーモア' : 'ユーモア'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('humor', isChild ? 'ユーモア' : 'ユーモア', AVATAR_OPTIONS.humor)}
            >
              <Text style={styles.selectionButtonText}>{getCurrentLabel('humor')}</Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>
        </View>

        {/* 描画モデル設定 */}
        <View style={styles.section}>
          <Text style={[styles.sectionTitle, isChild && styles.childSectionTitle]}>
            {isChild ? '🎨 えのスタイル' : '🎨 描画モデルの選択'}
          </Text>

          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'モデル' : 'イラストスタイル'}
            </Text>
            <TouchableOpacity
              style={styles.selectionButton}
              onPress={() => openModal('drawModel', isChild ? 'モデル' : 'イラストスタイル', 
                AVATAR_OPTIONS.draw_model_version.map(opt => ({
                  ...opt,
                  label: `${opt.label} - ${opt.description}`
                }))
              )}
            >
              <Text style={styles.selectionButtonText} numberOfLines={1}>
                {getCurrentLabel('drawModel')}
              </Text>
              <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
            </TouchableOpacity>
          </View>

          {/* トークン消費警告 */}
          <View style={[styles.warning, isChild && styles.childWarning]}>
            <Text style={[styles.warningText, isChild && styles.childWarningText]}>
              ⚠️{' '}
              {isChild
                ? `トークンを ${getEstimatedTokenUsage().toLocaleString()} つかうよ`
                : `アバター作成には ${getEstimatedTokenUsage().toLocaleString()} トークンが必要です。`}
            </Text>
          </View>
        </View>

        {/* エラーメッセージ */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* 作成ボタン */}
        <TouchableOpacity
          style={[
            styles.buttonWrapper,
            isLoading && styles.buttonDisabled,
          ]}
          onPress={handleCreate}
          disabled={isLoading}
          activeOpacity={0.8}
        >
          <LinearGradient
            colors={['#EC4899', '#9333EA']} // pink-500 → purple-600
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={[styles.button, isChild && styles.childButton]}
          >
            {isLoading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={[styles.buttonText, isChild && styles.childButtonText]}>
                {isChild ? 'アバターをつくる' : 'アバターを作成する'}
              </Text>
            )}
          </LinearGradient>
        </TouchableOpacity>

        <View style={styles.footer} />
      </View>

      {/* 選択モーダル */}
      <Modal
        visible={showModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{modalTitle}</Text>
              <TouchableOpacity onPress={() => setShowModal(false)}>
                <Text style={styles.modalClose}>✕</Text>
              </TouchableOpacity>
            </View>
            <FlatList
              data={modalOptions}
              keyExtractor={(item) => item.value}
              renderItem={({ item }) => (
                <TouchableOpacity
                  style={[
                    styles.modalOption,
                    item.value === getCurrentValue(modalType) && styles.modalOptionSelected,
                  ]}
                  onPress={() => handleModalSelect(item.value)}
                >
                  <Text
                    style={[
                      styles.modalOptionText,
                      item.value === getCurrentValue(modalType) && styles.modalOptionTextSelected,
                    ]}
                    numberOfLines={2}
                  >
                    {item.emoji ? `${item.emoji} ${item.label}` : item.label}
                  </Text>
                  {item.value === getCurrentValue(modalType) && (
                    <MaterialIcons name="check" size={24} color="#3b82f6" />
                  )}
                </TouchableOpacity>
              )}
            />
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
};

const createStyles = (
  width: number,
  theme: any,
  colors: ReturnType<typeof useThemedColors>['colors'],
  accent: ReturnType<typeof useThemedColors>['accent']
) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  childContainer: {
    backgroundColor: '#FFF8DC',
  },
  content: {
    padding: getSpacing(16, width),
  },
  header: {
    marginBottom: getSpacing(24, width),
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
  },
  childTitle: {
    fontSize: getFontSize(26, width, theme),
    color: '#FF6B35',
  },
  subtitle: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
  childSubtitle: {
    fontSize: getFontSize(16, width, theme),
    color: '#FF8C42',
  },
  section: {
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(3, width),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(16, width),
  },
  childSectionTitle: {
    fontSize: getFontSize(20, width, theme),
    color: '#FF6B35',
  },
  formGroup: {
    marginBottom: getSpacing(16, width),
  },
  label: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
  },
  childLabel: {
    fontSize: getFontSize(16, width, theme),
    color: '#FF8C42',
  },
  selectionButton: {
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.card,
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    minHeight: 50,
  },
  selectionButtonText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
    flex: 1,
  },
  pickerWrapper: {
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.card,
    minHeight: 50,
    justifyContent: 'center',
  },
  childPickerWrapper: {
    borderColor: '#FFD93D',
    borderWidth: 2,
  },
  picker: {
    height: 50,
    width: '100%',
    color: colors.text.primary,
  },
  warning: {
    backgroundColor: '#FFF3CD',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginTop: getSpacing(8, width),
    borderWidth: 1,
    borderColor: '#FFC107',
  },
  childWarning: {
    backgroundColor: '#FFE5B4',
    borderColor: '#FFD93D',
  },
  warningText: {
    fontSize: getFontSize(14, width, theme),
    color: '#856404',
    textAlign: 'center',
  },
  childWarningText: {
    fontSize: getFontSize(16, width, theme),
    color: '#FF6B35',
    fontWeight: 'bold',
  },
  errorContainer: {
    backgroundColor: '#F8D7DA',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginBottom: getSpacing(16, width),
    borderWidth: 1,
    borderColor: '#F5C6CB',
  },
  errorText: {
    color: '#721C24',
    fontSize: getFontSize(14, width, theme),
    textAlign: 'center',
  },
  buttonWrapper: {
    borderRadius: getBorderRadius(12, width),
    overflow: 'hidden',
    marginBottom: getSpacing(16, width),
  },
  button: {
    padding: getSpacing(16, width),
    alignItems: 'center',
  },
  childButton: {
    // Child theme uses same gradient
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonText: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
  childButtonText: {
    fontSize: getFontSize(18, width, theme),
  },
  footer: {
    height: getSpacing(32, width),
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: colors.card,
    borderTopLeftRadius: getBorderRadius(20, width),
    borderTopRightRadius: getBorderRadius(20, width),
    maxHeight: '70%',
    paddingBottom: getSpacing(20, width),
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(20, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  modalTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  modalClose: {
    fontSize: getFontSize(24, width, theme),
    color: colors.text.disabled,
    fontWeight: 'bold',
  },
  modalOption: {
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(20, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  modalOptionSelected: {
    backgroundColor: `${accent.primary}15`,
  },
  modalOptionText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
    flex: 1,
    marginRight: getSpacing(8, width),
  },
  modalOptionTextSelected: {
    color: accent.primary,
    fontWeight: '600',
  },
});

export default AvatarCreateScreen;
