/**
 * 請求履歴画面
 * 
 * サブスクリプションの請求履歴を表示
 * 
 * @module screens/subscriptions/SubscriptionInvoicesScreen
 */

import React, { useEffect } from 'react';
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
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useSubscription } from '../../hooks/useSubscription';
import { useTheme } from '../../contexts/ThemeContext';
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

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F5F5',
  },
  scrollView: {
    flex: 1,
  },
  header: {
    backgroundColor: '#4A90E2',
    paddingVertical: 20,
    paddingHorizontal: 16,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  emptyContainer: {
    padding: 40,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 16,
    color: '#999999',
  },
  invoicesList: {
    padding: 16,
  },
  invoiceCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  invoiceHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#EEEEEE',
  },
  invoiceDate: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333333',
  },
  statusBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 12,
    fontWeight: 'bold',
  },
  invoiceBody: {
    padding: 16,
  },
  amountRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  amountLabel: {
    fontSize: 14,
    color: '#666666',
  },
  amountValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333333',
  },
  pdfButton: {
    backgroundColor: '#4A90E2',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 6,
    alignItems: 'center',
  },
  pdfButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
  },
});

export default SubscriptionInvoicesScreen;
