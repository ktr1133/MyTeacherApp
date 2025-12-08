/**
 * グラフコンポーネント
 * 
 * react-native-chart-kitを使用したグラフ表示
 */

import React from 'react';
import { View, Text, StyleSheet, Dimensions, ScrollView } from 'react-native';
import { BarChart, LineChart } from 'react-native-chart-kit';
import { ChartData } from '../../types/performance.types';

interface PerformanceChartProps {
  data: ChartData;
  taskType: 'normal' | 'group';
  period: 'week' | 'month' | 'year';
}

export const PerformanceChart: React.FC<PerformanceChartProps> = ({
  data,
  taskType,
  period,
}) => {
  const screenWidth = Dimensions.get('window').width;
  
  // グラフ設定（Web版と同じ色設定）
  const chartConfig = {
    backgroundGradientFrom: '#ffffff',
    backgroundGradientFromOpacity: 0,
    backgroundGradientTo: '#ffffff',
    backgroundGradientToOpacity: 0,
    color: (opacity = 1) => {
      // 通常タスク: #59B9C6, グループタスク: #8B5CF6
      const color = taskType === 'normal' ? 'rgba(89, 185, 198,' : 'rgba(139, 92, 246,';
      return `${color} ${opacity})`;
    },
    strokeWidth: 2,
    barPercentage: 0.6,
    useShadowColorFromDataset: false,
    decimalPlaces: 0,
    propsForBackgroundLines: {
      strokeDasharray: '', // 実線
      stroke: '#e0e0e0',
      strokeWidth: 1,
    },
    propsForLabels: {
      fontSize: 10,
    },
  };

  // 棒グラフ用データ変換
  const barDatasets = data.datasets.filter(ds => ds.type !== 'line');
  const barChartData = {
    labels: data.labels,
    datasets: barDatasets.map(ds => ({
      data: ds.data,
    })),
  };

  // 折れ線グラフ用データ変換
  const lineDatasets = data.datasets.filter(ds => ds.type === 'line');
  const hasLineChart = lineDatasets.length > 0;

  return (
    <View style={styles.container}>
      {/* グラフタイトル */}
      <View style={styles.titleContainer}>
        <Text style={styles.title}>
          {taskType === 'normal' ? '通常タスク実績' : 'グループタスク実績'}
        </Text>
      </View>

      {/* 棒グラフ */}
      {barDatasets.length > 0 && (
        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
          <BarChart
            data={barChartData}
            width={Math.max(screenWidth - 40, data.labels.length * 50)}
            height={220}
            chartConfig={chartConfig}
            style={styles.chart}
            yAxisSuffix=""
            yAxisLabel=""
            fromZero
            showValuesOnTopOfBars
          />
        </ScrollView>
      )}

      {/* 折れ線グラフ（累積データ） */}
      {hasLineChart && lineDatasets.map((dataset, index) => (
        <View key={index} style={styles.lineChartContainer}>
          <Text style={styles.lineChartTitle}>{dataset.label}</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <LineChart
              data={{
                labels: data.labels,
                datasets: [{ data: dataset.data }],
              }}
              width={Math.max(screenWidth - 40, data.labels.length * 50)}
              height={180}
              chartConfig={{
                ...chartConfig,
                color: (opacity = 1) => {
                  // dataset.borderColorがあればそれを使用（報酬グラフなど）
                  if (dataset.borderColor) {
                    // rgba(251, 191, 36, 1) → rgba(251, 191, 36, opacity)
                    return dataset.borderColor.replace(/[\d.]+\)$/g, `${opacity})`);
                  }
                  // デフォルトはタスク種別の色
                  const baseColor = taskType === 'normal' ? '89, 185, 198' : '139, 92, 246';
                  return `rgba(${baseColor}, ${opacity})`;
                },
              }}
              bezier
              style={styles.chart}
              yAxisSuffix=""
              yAxisLabel=""
              fromZero
            />
          </ScrollView>
        </View>
      ))}

      {/* 凡例 */}
      <View style={styles.legendContainer}>
        {data.datasets.map((dataset, index) => (
          <View key={index} style={styles.legendItem}>
            <View
              style={[
                styles.legendColor,
                {
                  backgroundColor: 
                    dataset.backgroundColor || 
                    dataset.borderColor ||
                    (taskType === 'normal' ? '#59B9C6' : '#8B5CF6'),
                },
              ]}
            />
            <Text style={styles.legendText}>{dataset.label}</Text>
          </View>
        ))}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
    marginVertical: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  titleContainer: {
    marginBottom: 12,
  },
  title: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
  chart: {
    marginVertical: 8,
    borderRadius: 16,
  },
  lineChartContainer: {
    marginTop: 16,
  },
  lineChartTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#6b7280',
    marginBottom: 8,
  },
  legendContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: 12,
    gap: 12,
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  legendColor: {
    width: 12,
    height: 12,
    borderRadius: 2,
    marginRight: 6,
  },
  legendText: {
    fontSize: 12,
    color: '#6b7280',
  },
});
