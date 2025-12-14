/**
 * AvatarEditScreen - アバター編集画面
 * 
 * Phase 2.B-7: アバター管理機能実装
 * 
 * 機能:
 * - AvatarCreateScreenと同じUI
 * - 初期値に既存アバター情報を設定
 * - 更新処理（PUT /api/avatar）
 * - テーマ対応UI（adult/child）
 * 
 * Web版: /resources/views/avatars/edit.blade.php
 * 
 * 注意: AvatarCreateScreenとコードを共通化し、コンポーネント化すべきだが、
 *      Phase 2.B-7範囲では個別実装とする（リファクタリングは次フェーズで検討）
 */

import React, { useState, useEffect, useMemo } from 'react';
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
  Switch,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { MaterialIcons } from '@expo/vector-icons';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useAvatarManagement } from '../../hooks/useAvatarManagement';
import { AVATAR_OPTIONS } from '../../utils/constants';
import {
  Avatar,
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

type RootStackParamList = {
  AvatarEdit: { avatar: Avatar };
};

type AvatarEditScreenRouteProp = RouteProp<RootStackParamList, 'AvatarEdit'>;

/**
 * AvatarEditScreen コンポーネント
 */
export const AvatarEditScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<AvatarEditScreenRouteProp>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const { colors } = useThemedColors();
  const { updateAvatar, isLoading, error } = useAvatarManagement();

  const avatar = route.params?.avatar;

  // 外見設定（初期値: 既存アバターデータ）
  const [sex, setSex] = useState<AvatarSex>(avatar?.sex || 'female');
  const [hairStyle, setHairStyle] = useState<AvatarHairStyle>(avatar?.hair_style || 'middle');
  const [hairColor, setHairColor] = useState<AvatarHairColor>(avatar?.hair_color || 'black');
  const [eyeColor, setEyeColor] = useState<AvatarEyeColor>(avatar?.eye_color || 'black');
  const [clothing, setClothing] = useState<AvatarClothing>(avatar?.clothing || 'suit');
  const [accessory, setAccessory] = useState<AvatarAccessory>(avatar?.accessory || 'nothing');
  const [bodyType, setBodyType] = useState<AvatarBodyType>(avatar?.body_type || 'average');

  // 性格設定
  const [tone, setTone] = useState<AvatarTone>(avatar?.tone || 'gentle');
  const [enthusiasm, setEnthusiasm] = useState<AvatarEnthusiasm>(avatar?.enthusiasm || 'normal');
  const [formality, setFormality] = useState<AvatarFormality>(avatar?.formality || 'polite');
  const [humor, setHumor] = useState<AvatarHumor>(avatar?.humor || 'normal');

  // 描画設定
  const [drawModelVersion, setDrawModelVersion] = useState<AvatarDrawModelVersion>(
    avatar?.draw_model_version || 'anything-v4.0'
  );
  const [isTransparent, setIsTransparent] = useState(avatar?.is_transparent ?? true);
  const [isChibi, setIsChibi] = useState(avatar?.is_chibi ?? false);

  // モーダル表示状態
  const [showModal, setShowModal] = useState(false);
  const [modalTitle, setModalTitle] = useState('');
  const [modalOptions, setModalOptions] = useState<Array<{value: string, label: string, emoji?: string}>>([]);
  const [modalOnSelect, setModalOnSelect] = useState<(value: string) => void>(() => () => {});
  const [showModelInfo, setShowModelInfo] = useState(false);

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, theme, colors), [width, theme, colors]);

  useEffect(() => {
    if (!avatar) {
      // パラメータが渡されていない場合、管理画面に戻る
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'アバターがみつからないよ'
          : 'アバター情報が見つかりません',
        [
          {
            text: 'OK',
            onPress: () => navigation.goBack(),
          },
        ]
      );
    }
  }, [avatar, navigation, theme]);

  /**
   * 選択モーダルを表示
   */
  const openSelectModal = (
    title: string,
    options: Array<{value: string, label: string, emoji?: string}>,
    onSelect: (value: string) => void
  ) => {
    setModalTitle(title);
    setModalOptions(options);
    setModalOnSelect(() => onSelect);
    setShowModal(true);
  };

  /**
   * 選択モーダルで項目を選択
   */
  const handleSelectOption = (value: string) => {
    modalOnSelect(value);
    setShowModal(false);
  };

  /**
   * アバター更新処理
   */
  const handleUpdate = async () => {
    try {
      await updateAvatar({
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
        theme === 'child' ? 'こうしんしたよ' : '更新完了',
        theme === 'child'
          ? 'アバターのせっていをこうしんしたよ'
          : 'アバター設定を更新しました。',
        [
          {
            text: 'OK',
            onPress: () => navigation.navigate('AvatarManage' as never),
          },
        ],
      );
    } catch (err) {
      console.error('Failed to update avatar:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'こうしんできなかったよ。もういちどためしてね。'
          : 'アバターの更新に失敗しました。',
      );
    }
  };

  const isChild = theme === 'child';

  if (!avatar) {
    return null;
  }

  return (
    <ScrollView style={[styles.container, isChild && styles.childContainer]}>
      <View style={styles.content}>
        {/* 外見設定 */}
        <View style={styles.section}>
          <LinearGradient
            colors={['#3B82F6', '#6366F1']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.sectionHeader}
          >
            <MaterialIcons name="person-outline" size={20} color="#FFFFFF" />
            <Text style={styles.sectionHeaderText}>
              {isChild ? 'みため' : '外見の設定'}
            </Text>
          </LinearGradient>

          {/* 性別 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'せいべつ' : '性別'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'せいべつ' : '性別',
                [...AVATAR_OPTIONS.sex],
                (value) => setSex(value as AvatarSex)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.sex.find(opt => opt.value === sex)?.emoji} {AVATAR_OPTIONS.sex.find(opt => opt.value === sex)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 髪型 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'かみがた' : '髪型'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'かみがた' : '髪型',
                [...AVATAR_OPTIONS.hair_style],
                (value) => setHairStyle(value as AvatarHairStyle)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.hair_style.find(opt => opt.value === hairStyle)?.label || 'ミディアム'}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 髪の色 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'かみのいろ' : '髪の色'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'かみのいろ' : '髪の色',
                [...AVATAR_OPTIONS.hair_color],
                (value) => setHairColor(value as AvatarHairColor)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.hair_color.find(opt => opt.value === hairColor)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 目の色 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'めのいろ' : '目の色'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'めのいろ' : '目の色',
                [...AVATAR_OPTIONS.eye_color],
                (value) => setEyeColor(value as AvatarEyeColor)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.eye_color.find(opt => opt.value === eyeColor)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 服装 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ふくそう' : '服装'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'ふくそう' : '服装',
                [...AVATAR_OPTIONS.clothing],
                (value) => setClothing(value as AvatarClothing)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.clothing.find(opt => opt.value === clothing)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* アクセサリー */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              アクセサリー
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                'アクセサリー',
                [...AVATAR_OPTIONS.accessory],
                (value) => setAccessory(value as AvatarAccessory)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.accessory.find(opt => opt.value === accessory)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 体型 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'たいけい' : '体型'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'たいけい' : '体型',
                [...AVATAR_OPTIONS.body_type],
                (value) => setBodyType(value as AvatarBodyType)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.body_type.find(opt => opt.value === bodyType)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* 性格設定 */}
        <View style={styles.section}>
          <LinearGradient
            colors={['#10B981', '#14B8A6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.sectionHeader}
          >
            <MaterialIcons name="chat-bubble-outline" size={20} color="#FFFFFF" />
            <Text style={styles.sectionHeaderText}>
              {isChild ? 'せいかく' : '性格の設定'}
            </Text>
          </LinearGradient>

          {/* 口調 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'くちょう' : '口調'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'くちょう' : '口調',
                [...AVATAR_OPTIONS.tone],
                (value) => setTone(value as AvatarTone)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.tone.find(opt => opt.value === tone)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 熱意 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ねつい' : '熱意'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'ねつい' : '熱意',
                [...AVATAR_OPTIONS.enthusiasm],
                (value) => setEnthusiasm(value as AvatarEnthusiasm)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.enthusiasm.find(opt => opt.value === enthusiasm)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 丁寧さ */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ていねいさ' : '丁寧さ'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'ていねいさ' : '丁寧さ',
                [...AVATAR_OPTIONS.formality],
                (value) => setFormality(value as AvatarFormality)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.formality.find(opt => opt.value === formality)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* ユーモア */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              ユーモア
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                'ユーモア',
                [...AVATAR_OPTIONS.humor],
                (value) => setHumor(value as AvatarHumor)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.humor.find(opt => opt.value === humor)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* 描画モデル設定 */}
        <View style={styles.section}>
          <LinearGradient
            colors={['#8B5CF6', '#EC4899']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.sectionHeader}
          >
            <MaterialIcons name="palette" size={20} color="#FFFFFF" />
            <Text style={styles.sectionHeaderText}>
              {isChild ? 'えのスタイル' : '描画モデルの選択'}
            </Text>
          </LinearGradient>

          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'モデル' : 'イラストスタイル'}
            </Text>
            <TouchableOpacity
              style={[styles.selectButton, isChild && styles.childSelectButton]}
              onPress={() => openSelectModal(
                isChild ? 'モデル' : 'イラストスタイル',
                AVATAR_OPTIONS.draw_model_version.map(opt => ({
                  value: opt.value,
                  label: `${opt.label} - ${opt.description}`,
                  emoji: ''
                })),
                (value) => setDrawModelVersion(value as AvatarDrawModelVersion)
              )}
              disabled={isLoading}
            >
              <Text style={styles.selectButtonText}>
                {AVATAR_OPTIONS.draw_model_version.find(opt => opt.value === drawModelVersion)?.label}
              </Text>
              <Text style={styles.selectButtonArrow}>▼</Text>
            </TouchableOpacity>
          </View>

          {/* 背景透過 */}
          <View style={styles.checkboxGroup}>
            <TouchableOpacity
              style={styles.checkboxRow}
              onPress={() => !isLoading && setIsTransparent(!isTransparent)}
              activeOpacity={0.7}
              disabled={isLoading}
            >
              <View style={[styles.checkbox, isTransparent && styles.checkboxChecked]}>
                {isTransparent && <Text style={styles.checkboxIcon}>✓</Text>}
              </View>
              <Text style={[styles.checkboxLabel, isChild && styles.childLabel]}>
                {isChild ? 'はいけいをすけすけに' : '背景を透過する'}
              </Text>
            </TouchableOpacity>
            <Text style={[styles.helpText, isChild && styles.childHelpText]}>
              {isChild
                ? 'オンにすると、えのうしろがとうめいになるよ。\nほかのアプリでつかうときにべんりだよ！'
                : 'ONにすると、アバター画像の背景が透明になります。\n他のアプリケーションで使用する際に便利です。'}
            </Text>
          </View>

          {/* ちびキャラ */}
          <View style={styles.checkboxGroup}>
            <TouchableOpacity
              style={styles.checkboxRow}
              onPress={() => !isLoading && setIsChibi(!isChibi)}
              activeOpacity={0.7}
              disabled={isLoading}
            >
              <View style={[styles.checkbox, isChibi && styles.checkboxChecked]}>
                {isChibi && <Text style={styles.checkboxIcon}>✓</Text>}
              </View>
              <Text style={[styles.checkboxLabel, isChild && styles.childLabel]}>
                {isChild ? 'ちびキャラにする' : 'ちびキャラにする'}
              </Text>
            </TouchableOpacity>
            <Text style={[styles.helpText, isChild && styles.childHelpText]}>
              {isChild
                ? 'オンにすると、かわいいちびキャラになるよ。\nつうじょうのえよりもちいさくてかわいいよ！'
                : 'ONにすると、デフォルメされた可愛いちびキャラスタイルになります。\n通常のアバターよりも小さく、可愛らしい印象になります。'}
            </Text>
          </View>

          {/* 描画モデル情報カード（折りたたみ式） */}
          <View style={styles.infoCard}>
            <TouchableOpacity
              style={styles.infoCardHeader}
              onPress={() => setShowModelInfo(!showModelInfo)}
              activeOpacity={0.7}
            >
              <View style={styles.infoCardHeaderContent}>
                <View style={styles.infoCardIcon}>
                  <Text style={styles.infoCardIconText}>ℹ️</Text>
                </View>
                <Text style={[styles.infoCardTitle, isChild && styles.childLabel]}>
                  {isChild ? '画風について' : '描画モデルについて'}
                </Text>
              </View>
              <MaterialIcons
                name={showModelInfo ? 'keyboard-arrow-up' : 'keyboard-arrow-down'}
                size={24}
                color={colors.text.secondary}
              />
            </TouchableOpacity>
            {showModelInfo && (
              <View style={styles.infoCardContent}>
                <Text style={[styles.infoCardText, isChild && styles.childHelpText]}>
                  {isChild
                    ? '画風によって、絵のタッチが変わるよ。\n使うコインの数も変わるよ。\n好きなタイプを選んでね！'
                    : '描画モデルによって、アバターのイラストタッチが変わります。\nモデルによって消費するトークンは異なります。\nお好みのスタイルをお選びください。今後、新しいモデルが追加される予定です。'}
                </Text>
              </View>
            )}
          </View>
        </View>

        {/* トークン消費警告 */}
        <View style={styles.section}>
          <View style={[styles.warning, isChild && styles.childWarning]}>
            <Text style={[styles.warningText, isChild && styles.childWarningText]}>
              ℹ️{' '}
              {isChild
                ? '※ へんしゅうしても、えはつくりなおされないよ。えをかえたいときは「えをつくりなおす」ボタンをおしてね。'
                : '※ 設定を更新しても画像は再生成されません。画像を変更したい場合は「画像を再生成」ボタンを押してください。'}
            </Text>
          </View>
        </View>

        {/* エラーメッセージ */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* 更新ボタン */}
        <TouchableOpacity
          style={[
            styles.buttonWrapper,
            isLoading && styles.buttonDisabled,
          ]}
          onPress={handleUpdate}
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
                {isChild ? 'こうしんする' : '更新する'}
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
          <View style={[styles.modalContent, isChild && styles.childModalContent]}>
            <View style={styles.modalHeader}>
              <Text style={[styles.modalTitle, isChild && styles.childModalTitle]}>
                {modalTitle}
              </Text>
              <TouchableOpacity onPress={() => setShowModal(false)}>
                <Text style={styles.modalClose}>✕</Text>
              </TouchableOpacity>
            </View>
            <FlatList
              data={modalOptions}
              keyExtractor={(item) => item.value}
              renderItem={({ item }) => (
                <TouchableOpacity
                  style={styles.modalOption}
                  onPress={() => handleSelectOption(item.value)}
                >
                  <Text style={styles.modalOptionText}>
                    {item.emoji && `${item.emoji} `}{item.label}
                  </Text>
                </TouchableOpacity>
              )}
            />
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
};

// スタイル定義（AvatarCreateScreenと同一）
const createStyles = (
  width: number,
  theme: any,
  colors: ReturnType<typeof useThemedColors>['colors']
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
    overflow: 'hidden',
    marginBottom: getSpacing(16, width),
    ...getShadow(3),
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    gap: getSpacing(8, width),
  },
  sectionHeaderText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(16, width),
    paddingHorizontal: getSpacing(16, width),
  },
  childSectionTitle: {
    fontSize: getFontSize(20, width, theme),
    color: '#FF6B35',
  },
  formGroup: {
    marginBottom: getSpacing(16, width),
    paddingHorizontal: getSpacing(16, width),
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
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
  pickerItem: {
    height: 120,
    fontSize: getFontSize(16, width, theme),
  },
  warning: {
    backgroundColor: '#E0F2FE',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginTop: getSpacing(8, width),
    marginHorizontal: getSpacing(16, width),
    borderWidth: 1,
    borderColor: '#38BDF8',
  },
  childWarning: {
    backgroundColor: '#FFE5B4',
    borderColor: '#FFD93D',
  },
  warningText: {
    fontSize: getFontSize(14, width, theme),
    color: '#075985',
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
    marginHorizontal: getSpacing(16, width),
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
    marginHorizontal: getSpacing(16, width),
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
  selectButton: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#fff',
    padding: getSpacing(15, width),
    minHeight: 50,
  },
  childSelectButton: {
    borderColor: '#FFD93D',
    borderWidth: 2,
  },
  selectButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: '#1F2937',
  },
  selectButtonArrow: {
    fontSize: getFontSize(12, width, theme),
    color: '#9CA3AF',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderTopLeftRadius: getBorderRadius(20, width),
    borderTopRightRadius: getBorderRadius(20, width),
    maxHeight: '70%',
    paddingBottom: getSpacing(20, width),
  },
  childModalContent: {
    borderTopLeftRadius: getBorderRadius(24, width),
    borderTopRightRadius: getBorderRadius(24, width),
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: getSpacing(20, width),
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  modalTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#1F2937',
  },
  childModalTitle: {
    fontSize: getFontSize(20, width, theme),
    color: '#FF6B35',
  },
  modalClose: {
    fontSize: getFontSize(24, width, theme),
    color: '#9CA3AF',
  },
  modalOption: {
    padding: getSpacing(16, width),
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  modalOptionText: {
    fontSize: getFontSize(16, width, theme),
    color: '#1F2937',
  },
  // チェックボックススタイル
  checkboxGroup: {
    marginTop: getSpacing(16, width),
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  checkbox: {
    width: 24,
    height: 24,
    borderWidth: 2,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(4, width),
    backgroundColor: colors.background,
    marginRight: getSpacing(12, width),
    justifyContent: 'center',
    alignItems: 'center',
  },
  checkboxChecked: {
    backgroundColor: '#8B5CF6',
    borderColor: '#8B5CF6',
  },
  checkboxIcon: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
  checkboxLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    flex: 1,
  },
  helpText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    marginLeft: getSpacing(36, width),
    lineHeight: getFontSize(18, width, theme),
  },
  childHelpText: {
    fontSize: getFontSize(14, width, theme),
    color: '#FF8C42',
  },
  // 情報カードスタイル
  infoCard: {
    marginTop: getSpacing(16, width),
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.card,
    overflow: 'hidden',
  },
  infoCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: getSpacing(12, width),
  },
  infoCardHeaderContent: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  infoCardIcon: {
    width: 32,
    height: 32,
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#8B5CF615',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: getSpacing(12, width),
  },
  infoCardIconText: {
    fontSize: getFontSize(18, width, theme),
  },
  infoCardTitle: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  infoCardContent: {
    padding: getSpacing(12, width),
    paddingTop: 0,
    borderTopWidth: 1,
    borderTopColor: colors.border.light,
  },
  infoCardText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    lineHeight: getFontSize(18, width, theme),
  },
});

export default AvatarEditScreen;
