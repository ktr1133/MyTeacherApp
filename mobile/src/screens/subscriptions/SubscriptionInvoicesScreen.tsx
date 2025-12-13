/**
 * 請求履歴画面
 * 
 * サブスクリプションの請求履歴を表示
 * 
 * @module screens/subscriptions/SubscriptionInvoicesScreen
 */

import React, { useEffect, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  SafeAreaView,
  Linking,
} from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useSubscription } from '../../hooks/useSubscription';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import type { Invoice } from '../../types/subscription.types';

/**
 * 請求履歴画面コンポーネント
 * 
 * 機能:
 * - 請求履歴一覧表示（リスト形式）
 * - 日付、金額、ステータス表示
 * - Invoice PDFリンク
 * - Pull-to-Refresh機能
 * 
 * @returns {JSX.Element} 請求履歴画面
 */
const SubscriptionInvoicesScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const { theme } = useTheme();
  const {
    invoices,
    loadInvoices,
    isLoading,
  } = useSubscription();
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, colors, accent), [width, colors, accent]);

  // 画面フォーカス時にデータ更新
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      loadInvoices();
    });
    return unsubscribe;
  }, [navigation, loadInvoices]);

  // 初回ロード
  useEffect(() => {
    loadInvoices();
  }, []);

  // テーマに応じたラベル
  const labels = theme === 'child' ? {
    title: 'りょうきんりれき',
    noInvoices: 'りょうきんりれきがないよ',
    date: 'ひづけ',
    amount: 'きんがく',
    status: 'じょうたい',
    viewPdf: 'PDFをみる',
  } : {
    title: '請求履歴',
    noInvoices: '請求履歴がありません',
    date: '日付',
    amount: '金額',
    status: 'ステータス',
    viewPdf: 'PDFを表示',
  };

  /**
   * ステータスの日本語変換
   */
  const getStatusLabel = (status: string): string => {
    const statusMap: Record<string, string> = {
      'draft': '下書き',
      'open': '未払い',
      'paid': '支払済み',
      'uncollectible': '回収不能',
      'void': '無効',
    };
    return statusMap[status] || status;
  };

  /**
   * ステータスの色
   */
  const getStatusColor = (status: string): string => {
    const colorMap: Record<string, string> = {
      'draft': '#999999',
      'open': '#FF9800',
      'paid': '#4CAF50',
      'uncollectible': '#F44336',
      'void': '#999999',
    };
    return colorMap[status] || '#999999';
  };

  /**
   * 金額フォーマット（円）
   */
  const formatAmount = (amount: number): string => {
    return `¥${amount.toLocaleString('ja-JP')}`;
  };

  /**
   * 日付フォーマット
   */
  const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });
  };

  /**
   * Invoice PDF表示
   */
  const handleViewPdf = async (pdfUrl: string | null) => {
    if (!pdfUrl) {
      return;
    }

    try {
      const supported = await Linking.canOpenURL(pdfUrl);
      if (supported) {
        await Linking.openURL(pdfUrl);
      }
    } catch (err) {
      console.error('[SubscriptionInvoicesScreen] handleViewPdf error:', err);
    }
  };

  /**
   * 請求書カード描画
   */
  const renderInvoiceCard = (invoice: Invoice) => {
    return (
      <View key={invoice.id} style={styles.invoiceCard}>
        <View style={styles.invoiceHeader}>
          <Text style={styles.invoiceDate}>
            {formatDate(invoice.date)}
          </Text>
          <View
            style={[
              styles.statusBadge,
              { backgroundColor: getStatusColor(invoice.status) + '20' },
            ]}
          >
            <Text
              style={[
                styles.statusText,
                { color: getStatusColor(invoice.status) },
              ]}
            >
              {getStatusLabel(invoice.status)}
            </Text>
          </View>
        </View>

        <View style={styles.invoiceBody}>
          <View style={styles.amountRow}>
            <Text style={styles.amountLabel}>{labels.amount}</Text>
            <Text style={styles.amountValue}>
              {formatAmount(invoice.total)}
            </Text>
          </View>

          {invoice.invoice_pdf && (
            <TouchableOpacity
              style={styles.pdfButton}
              onPress={() => handleViewPdf(invoice.invoice_pdf)}
            >
              <Text style={styles.pdfButtonText}>
                📄 {labels.viewPdf}
              </Text>
            </TouchableOpacity>
          )}
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        refreshControl={
          <RefreshControl
            refreshing={isLoading}
            onRefresh={loadInvoices}
          />
        }
      >
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>{labels.title}</Text>
        </View>

        {/* 請求履歴一覧 */}
        {invoices.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>{labels.noInvoices}</Text>
          </View>
        ) : (
          <View style={styles.invoicesList}>
            {invoices.map(renderInvoiceCard)}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const createStyles = (
  width: number,
  colors: ReturnType<typeof useThemedColors>['colors'],
  accent: ReturnType<typeof useThemedColors>['accent']
) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  scrollView: {
    flex: 1,
  },
  header: {
    backgroundColor: accent.primary,
    paddingVertical: getSpacing(20, width),
    paddingHorizontal: getSpacing(16, width),
  },
  headerTitle: {
    fontSize: getFontSize(24, width, {}),
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  emptyContainer: {
    padding: getSpacing(40, width),
    alignItems: 'center',
  },
  emptyText: {
    fontSize: getFontSize(16, width, {}),
    color: colors.text.secondary,
  },
  invoicesList: {
    padding: getSpacing(16, width),
  },
  invoiceCard: {
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(3, width),
  },
  invoiceHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: getSpacing(16, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  invoiceDate: {
    fontSize: getFontSize(16, width, {}),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  statusBadge: {
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(12, width),
  },
  statusText: {
    fontSize: getFontSize(12, width, {}),
    fontWeight: 'bold',
  },
  invoiceBody: {
    padding: getSpacing(16, width),
  },
  amountRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  amountLabel: {
    fontSize: getFontSize(14, width, {}),
    color: colors.text.secondary,
  },
  amountValue: {
    fontSize: getFontSize(20, width, {}),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  pdfButton: {
    backgroundColor: accent.primary,
    paddingVertical: getSpacing(10, width),
    paddingHorizontal: getSpacing(16, width),
    borderRadius: getBorderRadius(6, width),
    alignItems: 'center',
  },
  pdfButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(14, width, {}),
    fontWeight: 'bold',
  },
});

export default SubscriptionInvoicesScreen;
