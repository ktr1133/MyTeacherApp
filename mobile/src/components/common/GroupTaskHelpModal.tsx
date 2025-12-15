/**
 * グループタスクヘルプモーダル
 * 
 * グループタスクの使い方と設定項目の説明を表示
 */
import {
  Modal,
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useThemedColors } from '../../hooks/useThemedColors';

interface GroupTaskHelpModalProps {
  visible: boolean;
  onClose: () => void;
  theme: 'adult' | 'child';
}

/**
 * グループタスクヘルプモーダルコンポーネント
 */
export default function GroupTaskHelpModal({
  visible,
  onClose,
  theme,
}: GroupTaskHelpModalProps) {
  const { colors } = useThemedColors();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onClose}
    >
      <View style={styles.overlay}>
        <View style={[
          styles.modalContainer, 
          { 
            backgroundColor: colors.card,
            height: '95%', // maxHeight → height に変更して強制的に高さを確保
          }
        ]}>
          {/* ヘッダー */}
          <LinearGradient
            colors={['#9333ea', '#ec4899']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.header}
          >
            <View style={styles.headerContent}>
              <View style={styles.iconContainer}>
                <Ionicons name="people" size={24} color="#FFFFFF" />
              </View>
              <View style={styles.headerTextContainer}>
                <Text style={styles.headerTitle}>
                  {theme === 'child' ? 'みんなのやることって？' : 'グループタスクとは？'}
                </Text>
                <Text style={styles.headerSubtitle}>
                  {theme === 'child' ? 'つかいかたのせつめい' : '使い方の説明'}
                </Text>
              </View>
            </View>
            <TouchableOpacity onPress={onClose} style={styles.closeButton}>
              <Ionicons name="close" size={28} color="#FFFFFF" />
            </TouchableOpacity>
          </LinearGradient>

          {/* コンテンツ */}
          <ScrollView 
            style={[
              styles.content, 
              { 
                backgroundColor: colors.background,
                flex: 1, // 明示的にflex: 1を指定
              }
            ]} 
            contentContainerStyle={[
              styles.contentContainer,
              { flexGrow: 1 } // 明示的にflexGrow: 1を指定
            ]}
            testID="help-modal-content"
          >
            {/* 概要 */}
            <View style={styles.section}>
              <Text 
                style={[
                  styles.sectionTitle, 
                  { color: colors.text.primary }
                ]} 
                testID="section-title-overview"
              >
                {theme === 'child' ? '📋 なにができるの？' : '📋 概要'}
              </Text>
              <Text 
                style={[
                  styles.description, 
                  { color: colors.text.secondary }
                ]} 
                testID="description-overview"
              >
                {theme === 'child'
                  ? 'グループのみんなにおなじやることをいちどにつくれるよ。みんながおなじことをやるときにべんり！'
                  : 'グループメンバー全員に同じタスクを一度に作成できます。家族で分担する家事や、みんなで取り組む活動に便利です。'}
              </Text>
            </View>

            {/* 設定項目の説明 */}
            <View style={styles.section}>
              <Text style={[styles.sectionTitle, { color: colors.text.primary }]}>
                {theme === 'child' ? '⚙️ せっていのせつめい' : '⚙️ 設定項目の説明'}
              </Text>

              {/* 報酬 */}
              <View style={styles.settingCard}>
                <LinearGradient
                  colors={['#dbeafe', '#bfdbfe']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={styles.settingCardGradient}
                >
                  <View style={styles.settingHeader}>
                    <Ionicons name="cash-outline" size={20} color="#3b82f6" />
                    <Text style={styles.settingTitle}>
                      {theme === 'child' ? 'ごほうび' : '報酬'}
                    </Text>
                  </View>
                  <Text style={styles.settingDescription}>
                    {theme === 'child'
                      ? 'やることをおわらせたらもらえるポイントだよ'
                      : 'タスク完了時にもらえるトークンの量を設定できます'}
                  </Text>
                </LinearGradient>
              </View>

              {/* 承認必須 */}
              <View style={styles.settingCard}>
                <LinearGradient
                  colors={['#fef3c7', '#fed7aa']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={styles.settingCardGradient}
                >
                  <View style={styles.settingHeader}>
                    <Ionicons name="checkmark-circle-outline" size={20} color="#f59e0b" />
                    <Text style={styles.settingTitle}>
                      {theme === 'child' ? 'かくにんがひつよう' : '承認が必要'}
                    </Text>
                    <View style={styles.recommendBadge}>
                      <Text style={styles.recommendText}>
                        {theme === 'child' ? 'おすすめ' : '推奨'}
                      </Text>
                    </View>
                  </View>
                  <Text style={styles.settingDescription}>
                    {theme === 'child'
                      ? 'できたらおとなにみてもらってから、おわったことにするよ。チェックをはずすと、すぐにおわったことになるよ。'
                      : 'タスク完了時に親の承認が必要になります。チェックを外すと即座に完了扱いになります。'}
                  </Text>
                </LinearGradient>
              </View>

              {/* 画像必須 */}
              <View style={styles.settingCard}>
                <LinearGradient
                  colors={['#fae8ff', '#fce7f3']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={styles.settingCardGradient}
                >
                  <View style={styles.settingHeader}>
                    <Ionicons name="camera-outline" size={20} color="#9333ea" />
                    <Text style={styles.settingTitle}>
                      {theme === 'child' ? 'しゃしんがひつよう' : '画像が必要'}
                    </Text>
                  </View>
                  <Text style={styles.settingDescription}>
                    {theme === 'child'
                      ? 'できたら、やったことがわかるしゃしんをとってもらうよ'
                      : 'タスク完了時に証拠画像のアップロードが必要になります'}
                  </Text>
                </LinearGradient>
              </View>
            </View>

            {/* 使い方のヒント */}
            <View style={styles.section}>
              <Text style={[styles.sectionTitle, { color: colors.text.primary }]}>
                {theme === 'child' ? '💡 つかいかた' : '💡 使い方のヒント'}
              </Text>
              <View style={styles.tipsList}>
                <View style={styles.tipItem}>
                  <Text style={styles.tipBullet}>•</Text>
                  <Text style={[styles.tipText, { color: colors.text.secondary }]}>
                    {theme === 'child'
                      ? 'みんなでおなじそうじをするときにつかおう'
                      : '家族で分担する掃除や片付けに使いましょう'}
                  </Text>
                </View>
                <View style={styles.tipItem}>
                  <Text style={styles.tipBullet}>•</Text>
                  <Text style={[styles.tipText, { color: colors.text.secondary }]}>
                    {theme === 'child'
                      ? 'ちゃんとできたかみるために、かくにんをオンにしておこう'
                      : 'しっかり確認するため、承認設定をONにしておくのがおすすめです'}
                  </Text>
                </View>
                <View style={styles.tipItem}>
                  <Text style={styles.tipBullet}>•</Text>
                  <Text style={[styles.tipText, { color: colors.text.secondary }]}>
                    {theme === 'child'
                      ? 'まえにつくったやることから、かんたんにえらべるよ'
                      : '過去のグループタスクをテンプレートとして再利用できます'}
                  </Text>
                </View>
              </View>
            </View>
          </ScrollView>

          {/* フッター */}
          <View style={[styles.footer, { borderTopColor: colors.border.default }]}>
            <TouchableOpacity onPress={onClose} style={styles.closeFooterButton}>
              <LinearGradient
                colors={['#9333ea', '#ec4899']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.closeFooterButtonGradient}
              >
                <Text style={styles.closeFooterButtonText}>
                  {theme === 'child' ? 'わかった！' : '閉じる'}
                </Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  modalContainer: {
    width: '100%',
    maxWidth: 500,
    maxHeight: '95%', // 90% → 95%に拡大
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
    flexDirection: 'column',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    flex: 1,
  },
  iconContainer: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTextContainer: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 12,
    color: 'rgba(255, 255, 255, 0.9)',
    marginTop: 2,
  },
  closeButton: {
    padding: 4,
  },
  content: {
    flex: 1,
    minHeight: 400, // 200 → 400に増加
  },
  contentContainer: {
    padding: 20,
    paddingBottom: 30,
    flexGrow: 1, // コンテンツがスクロール可能な高さを確保
  },
  section: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 12,
    // color は動的に設定
  },
  description: {
    fontSize: 14,
    lineHeight: 22,
    // color は動的に設定
  },
  settingCard: {
    marginBottom: 12,
    borderRadius: 12,
    overflow: 'hidden',
  },
  settingCardGradient: {
    padding: 16,
    borderRadius: 12,
  },
  settingHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 8,
  },
  settingTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1F2937',
  },
  recommendBadge: {
    backgroundColor: '#f59e0b',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    marginLeft: 4,
  },
  recommendText: {
    fontSize: 10,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  settingDescription: {
    fontSize: 12,
    color: '#6B7280',
    lineHeight: 18,
  },
  tipsList: {
    gap: 12,
  },
  tipItem: {
    flexDirection: 'row',
    gap: 8,
    paddingLeft: 8,
  },
  tipBullet: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#9333ea',
  },
  tipText: {
    flex: 1,
    fontSize: 14,
    lineHeight: 22,
    // color は動的に設定
  },
  footer: {
    borderTopWidth: 1,
    padding: 16,
  },
  closeFooterButton: {
    borderRadius: 12,
    overflow: 'hidden',
  },
  closeFooterButtonGradient: {
    paddingVertical: 14,
    alignItems: 'center',
  },
  closeFooterButtonText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
});
