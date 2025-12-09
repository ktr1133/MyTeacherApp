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
} from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { Picker } from '@react-native-picker/picker';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useAvatarManagement } from '../../hooks/useAvatarManagement';
import { AVATAR_OPTIONS } from '../../utils/constants';
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

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);

  // 推定トークン消費量を取得
  const getEstimatedTokenUsage = (): number => {
    const model = AVATAR_OPTIONS.draw_model_version.find(m => m.value === drawModelVersion);
    return model?.estimatedTokenUsage || 5000;
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
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={sex}
                onValueChange={(value) => setSex(value as AvatarSex)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.sex.map((option) => (
                  <Picker.Item
                    key={option.value}
                    label={`${option.emoji} ${option.label}`}
                    value={option.value}
                  />
                ))}
              </Picker>
            </View>
          </View>

          {/* 髪型 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'かみがた' : '髪型'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={hairStyle}
                onValueChange={(value) => setHairStyle(value as AvatarHairStyle)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.hair_style.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* 髪の色 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'かみのいろ' : '髪の色'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={hairColor}
                onValueChange={(value) => setHairColor(value as AvatarHairColor)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.hair_color.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* 目の色 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'めのいろ' : '目の色'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={eyeColor}
                onValueChange={(value) => setEyeColor(value as AvatarEyeColor)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.eye_color.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* 服装 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ふくそう' : '服装'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={clothing}
                onValueChange={(value) => setClothing(value as AvatarClothing)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.clothing.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* アクセサリー */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'アクセサリー' : 'アクセサリー'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={accessory}
                onValueChange={(value) => setAccessory(value as AvatarAccessory)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.accessory.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* 体型 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'たいけい' : '体型'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={bodyType}
                onValueChange={(value) => setBodyType(value as AvatarBodyType)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.body_type.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
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
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={tone}
                onValueChange={(value) => setTone(value as AvatarTone)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.tone.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* 熱意 */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ねつい' : '熱意'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={enthusiasm}
                onValueChange={(value) => setEnthusiasm(value as AvatarEnthusiasm)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.enthusiasm.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* 丁寧さ */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ていねいさ' : '丁寧さ'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={formality}
                onValueChange={(value) => setFormality(value as AvatarFormality)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.formality.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
          </View>

          {/* ユーモア */}
          <View style={styles.formGroup}>
            <Text style={[styles.label, isChild && styles.childLabel]}>
              {isChild ? 'ユーモア' : 'ユーモア'}
            </Text>
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={humor}
                onValueChange={(value) => setHumor(value as AvatarHumor)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.humor.map((option) => (
                  <Picker.Item key={option.value} label={option.label} value={option.value} />
                ))}
              </Picker>
            </View>
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
            <View style={[styles.pickerWrapper, isChild && styles.childPickerWrapper]}>
              <Picker
                selectedValue={drawModelVersion}
                onValueChange={(value) => setDrawModelVersion(value as AvatarDrawModelVersion)}
                style={styles.picker}
              >
                {AVATAR_OPTIONS.draw_model_version.map((option) => (
                  <Picker.Item
                    key={option.value}
                    label={`${option.label} - ${option.description}`}
                    value={option.value}
                  />
                ))}
              </Picker>
            </View>
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
            styles.button,
            isChild && styles.childButton,
            isLoading && styles.buttonDisabled,
          ]}
          onPress={handleCreate}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={[styles.buttonText, isChild && styles.childButtonText]}>
              {isChild ? 'アバターをつくる' : 'アバターを作成する'}
            </Text>
          )}
        </TouchableOpacity>

        <View style={styles.footer} />
      </View>
    </ScrollView>
  );
};

const createStyles = (width: number, theme: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
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
    color: '#333',
    marginBottom: getSpacing(8, width),
  },
  childTitle: {
    fontSize: getFontSize(26, width, theme),
    color: '#FF6B35',
  },
  subtitle: {
    fontSize: getFontSize(14, width, theme),
    color: '#666',
  },
  childSubtitle: {
    fontSize: getFontSize(16, width, theme),
    color: '#FF8C42',
  },
  section: {
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(3, width),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#333',
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
    color: '#333',
    marginBottom: getSpacing(8, width),
  },
  childLabel: {
    fontSize: getFontSize(16, width, theme),
    color: '#FF8C42',
  },
  pickerWrapper: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#fff',
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
    color: '#1F2937',
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
  button: {
    backgroundColor: '#8B5CF6',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    alignItems: 'center',
    marginBottom: getSpacing(16, width),
  },
  childButton: {
    backgroundColor: '#FF6B35',
  },
  buttonDisabled: {
    backgroundColor: '#ccc',
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
});

export default AvatarCreateScreen;
