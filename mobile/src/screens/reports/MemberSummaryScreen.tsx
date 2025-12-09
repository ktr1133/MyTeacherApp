/**
 * メンバー別概況レポート画面
 * 
 * 責務:
 * - AIコメント表示
 * - タスク分類円グラフ表示
 * - 報酬推移折れ線グラフ表示
 * - トークン消費量表示
 * - 戻るボタンでの確認ダイアログ
 * 
 * Web版: resources/views/reports/monthly/show.blade.php の
 * {{-- メンバー別概況レポート結果表示モーダル --}} に相当
 */

import { useLayoutEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  Alert,
  TouchableOpacity,
  useColorScheme,
  Dimensions,
} from 'react-native';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { PieChart, LineChart } from 'react-native-chart-kit';
import { Ionicons } from '@expo/vector-icons';
import { MemberSummaryData } from '../../types/performance.types';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';

type RootStackParamList = {
  MemberSummary: { data: MemberSummaryData };
};

type MemberSummaryScreenRouteProp = RouteProp<RootStackParamList, 'MemberSummary'>;
type MemberSummaryScreenNavigationProp = NativeStackNavigationProp<
  RootStackParamList,
  'MemberSummary'
>;

export default function MemberSummaryScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const navigation = useNavigation<MemberSummaryScreenNavigationProp>();
  const route = useRoute<MemberSummaryScreenRouteProp>();
  const { width } = useResponsive();
  const styles = useMemo(() => createStyles(width), [width]);
  const { data } = route.params;

  const screenWidth = Dimensions.get('window').width;

  /**
   * 戻るボタンのカスタマイズ（確認ダイアログ付き）
   */
  useLayoutEffect(() => {
    navigation.setOptions({
      headerLeft: () => (
        <TouchableOpacity
          onPress={handleBackPress}
          style={styles.headerButton}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons
            name="arrow-back"
            size={24}
            color={isDark ? '#e5e7eb' : '#374151'}
          />
        </TouchableOpacity>
      ),
      title: `${data.user_name}さんの概況レポート`,
    });
  }, [navigation, isDark, data.user_name]);

  /**
   * 戻るボタン押下時の確認ダイアログ
   */
  const handleBackPress = () => {
    Alert.alert(
      'レポートを閉じますか？',
      'このレポートはトークンを消費して生成されています。\n戻ると生成結果が破棄されます。\n\n本当に戻ってもよろしいですか？',
      [
        {
          text: 'キャンセル',
          style: 'cancel',
        },
        {
          text: '戻る',
          style: 'destructive',
          onPress: () => navigation.goBack(),
        },
      ]
    );
  };

  /**
   * 円グラフデータ整形
   */
  const getPieChartData = () => {
    const colors = [
      'rgba(59, 130, 246, 0.9)',   // blue
      'rgba(168, 85, 247, 0.9)',   // purple
      'rgba(236, 72, 153, 0.9)',   // pink
      'rgba(16, 185, 129, 0.9)',   // green
      'rgba(251, 146, 60, 0.9)',   // orange
      'rgba(250, 204, 21, 0.9)',   // yellow
    ];

    return data.task_classification.labels.map((label, index) => ({
      name: label,
      population: data.task_classification.data[index],
      color: colors[index % colors.length],
      legendFontColor: isDark ? '#e5e7eb' : '#374151',
      legendFontSize: 12,
    }));
  };

  /**
   * 折れ線グラフデータ整形
   */
  const getLineChartData = () => {
    return {
      labels: data.reward_trend.labels,
      datasets: [
        {
          data: data.reward_trend.data,
          color: (opacity = 1) => `rgba(251, 146, 60, ${opacity})`,
          strokeWidth: 3,
        },
      ],
    };
  };

  /**
   * グラフ共通設定
   */
  const chartConfig = {
    backgroundColor: isDark ? '#1f2937' : '#ffffff',
    backgroundGradientFrom: isDark ? '#1f2937' : '#ffffff',
    backgroundGradientTo: isDark ? '#1f2937' : '#ffffff',
    decimalPlaces: 0,
    color: (opacity = 1) => (isDark ? `rgba(229, 231, 235, ${opacity})` : `rgba(55, 65, 81, ${opacity})`),
    labelColor: (opacity = 1) => (isDark ? `rgba(156, 163, 175, ${opacity})` : `rgba(107, 114, 128, ${opacity})`),
    style: {
      borderRadius: 16,
    },
    propsForDots: {
      r: '6',
      strokeWidth: '3',
      stroke: '#ffffff',
    },
  };

  return (
    <ScrollView
      style={[styles.container, isDark && styles.containerDark]}
      contentContainerStyle={styles.contentContainer}
    >
      {/* AIコメントセクション */}
      <View style={[styles.section, isDark && styles.sectionDark]}>
        <View style={styles.sectionHeader}>
          <Ionicons
            name="sparkles"
            size={20}
            color={isDark ? '#fbbf24' : '#f59e0b'}
          />
          <Text style={[styles.sectionTitle, isDark && styles.sectionTitleDark]}>
            AIによる概況分析
          </Text>
        </View>
        <Text style={[styles.commentText, isDark && styles.commentTextDark]}>
          {data.comment}
        </Text>
      </View>

      {/* タスク分類円グラフセクション */}
      <View style={[styles.section, isDark && styles.sectionDark]}>
        <Text style={[styles.sectionTitle, isDark && styles.sectionTitleDark]}>
          タスク分類
        </Text>
        <View style={styles.chartContainer}>
          <PieChart
            data={getPieChartData()}
            width={screenWidth - 64}
            height={220}
            chartConfig={chartConfig}
            accessor="population"
            backgroundColor="transparent"
            paddingLeft="15"
            center={[10, 0]}
            hasLegend={true}
          />
        </View>
      </View>

      {/* 報酬推移折れ線グラフセクション */}
      <View style={[styles.section, isDark && styles.sectionDark]}>
        <Text style={[styles.sectionTitle, isDark && styles.sectionTitleDark]}>
          報酬の推移
        </Text>
        <View style={styles.chartContainer}>
          <LineChart
            data={getLineChartData()}
            width={screenWidth - 64}
            height={220}
            chartConfig={chartConfig}
            bezier
            style={styles.lineChart}
            withInnerLines={true}
            withOuterLines={true}
            withVerticalLines={false}
            withHorizontalLines={true}
            withDots={true}
            withShadow={false}
            formatYLabel={(value) => `${parseInt(value).toLocaleString()}円`}
          />
        </View>
      </View>

      {/* トークン消費量セクション */}
      <View style={[styles.section, isDark && styles.sectionDark]}>
        <View style={styles.tokensContainer}>
          <Ionicons
            name="information-circle"
            size={20}
            color={isDark ? '#60a5fa' : '#3b82f6'}
          />
          <Text style={[styles.tokensText, isDark && styles.tokensTextDark]}>
            このレポート生成に{' '}
            <Text style={styles.tokensValue}>
              {data.tokens_used.toLocaleString()}トークン
            </Text>
            {' '}を消費しました
          </Text>
        </View>
      </View>

      {/* PDF生成ボタン（将来実装） */}
      <View style={[styles.section, isDark && styles.sectionDark]}>
        <TouchableOpacity
          style={[styles.pdfButton, styles.pdfButtonDisabled]}
          disabled={true}
        >
          <Ionicons name="download-outline" size={20} color="#9ca3af" />
          <Text style={styles.pdfButtonTextDisabled}>
            PDFダウンロード（準備中）
          </Text>
        </TouchableOpacity>
        {/* TODO: PDF生成機能実装
         * - React Native Blob Util等でPDFダウンロード
         * - バックエンドAPI: POST /reports/monthly/member-summary/pdf
         * - リクエストボディ: { user_id, year_month, comment, chart_image }
         */}
      </View>

      {/* 生成日時 */}
      <View style={styles.footer}>
        <Text style={[styles.footerText, isDark && styles.footerTextDark]}>
          生成日時: {new Date(data.generated_at).toLocaleString('ja-JP')}
        </Text>
      </View>
    </ScrollView>
  );
}

const createStyles = (width: number) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  containerDark: {
    backgroundColor: '#111827',
  },
  contentContainer: {
    padding: getSpacing(16, width),
    paddingBottom: getSpacing(32, width),
  },
  headerButton: {
    padding: getSpacing(8, width),
  },
  section: {
    backgroundColor: '#ffffff',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(2, width),
  },
  sectionDark: {
    backgroundColor: '#1f2937',
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  sectionTitle: {
    fontSize: getFontSize(16, width, {}),
    fontWeight: '600',
    color: '#111827',
    marginLeft: getSpacing(8, width),
  },
  sectionTitleDark: {
    color: '#f3f4f6',
  },
  commentText: {
    fontSize: getFontSize(14, width, {}),
    lineHeight: getFontSize(22, width, {}),
    color: '#374151',
  },
  commentTextDark: {
    color: '#d1d5db',
  },
  chartContainer: {
    marginTop: getSpacing(12, width),
    alignItems: 'center',
  },
  lineChart: {
    borderRadius: getBorderRadius(12, width),
  },
  tokensContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: getSpacing(12, width),
    backgroundColor: '#eff6ff',
    borderRadius: getBorderRadius(8, width),
  },
  tokensText: {
    fontSize: getFontSize(14, width, {}),
    color: '#374151',
    marginLeft: getSpacing(8, width),
    flex: 1,
  },
  tokensTextDark: {
    color: '#d1d5db',
  },
  tokensValue: {
    fontWeight: '700',
    color: '#3b82f6',
  },
  pdfButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#3b82f6',
    padding: getSpacing(14, width),
    borderRadius: getBorderRadius(8, width),
  },
  pdfButtonDisabled: {
    backgroundColor: '#e5e7eb',
  },
  pdfButtonText: {
    color: '#ffffff',
    fontSize: getFontSize(16, width, {}),
    fontWeight: '600',
    marginLeft: getSpacing(8, width),
  },
  pdfButtonTextDisabled: {
    color: '#9ca3af',
    fontSize: getFontSize(16, width, {}),
    fontWeight: '600',
    marginLeft: getSpacing(8, width),
  },
  footer: {
    alignItems: 'center',
    marginTop: getSpacing(8, width),
  },
  footerText: {
    fontSize: getFontSize(12, width, {}),
    color: '#6b7280',
  },
  footerTextDark: {
    color: '#9ca3af',
  },
});
