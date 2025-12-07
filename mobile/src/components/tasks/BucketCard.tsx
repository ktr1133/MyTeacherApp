/**
 * バケットカードコンポーネント
 * 
 * タグ別にグループ化されたタスクをカード形式で表示
 */
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Task } from '../../types/task.types';

interface BucketCardProps {
  tagId: number;
  tagName: string;
  tasks: Task[];
  onPress: () => void;
  theme: 'adult' | 'child';
}

/**
 * バケットカードコンポーネント
 */
export default function BucketCard({ tagName, tasks, onPress }: BucketCardProps) {
  const previewTasks = tasks.slice(0, 3);
  const remainingCount = Math.max(0, tasks.length - 3);

  return (
    <TouchableOpacity
      style={styles.card}
      onPress={onPress}
      activeOpacity={0.7}
    >
      {/* ヘッダー */}
      <View style={styles.header}>
        <View style={styles.titleContainer}>
          <Text style={styles.tagIcon}>🏷️</Text>
          <Text style={styles.tagName} numberOfLines={1}>
            {tagName}
          </Text>
        </View>
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{tasks.length}</Text>
        </View>
      </View>

      {/* タスクプレビュー */}
      <View style={styles.taskPreview}>
        {previewTasks.map((task) => (
          <View key={task.id} style={styles.previewItem}>
            <Text style={styles.checkBox}>{task.is_completed ? '✓' : '□'}</Text>
            <Text
              style={[
                styles.taskTitle,
                task.is_completed && styles.taskTitleCompleted,
              ]}
              numberOfLines={1}
            >
              {task.title}
            </Text>
          </View>
        ))}
        {remainingCount > 0 && (
          <View style={styles.remainingContainer}>
            <Text style={styles.remainingText}>+ あと{remainingCount}件</Text>
          </View>
        )}
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  titleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    marginRight: 8,
  },
  tagIcon: {
    fontSize: 20,
    marginRight: 8,
  },
  tagName: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    flex: 1,
  },
  badge: {
    backgroundColor: '#8B5CF6',
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 4,
    minWidth: 32,
    alignItems: 'center',
  },
  badgeText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  taskPreview: {
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    paddingTop: 12,
  },
  previewItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  checkBox: {
    fontSize: 16,
    marginRight: 8,
    color: '#6B7280',
  },
  taskTitle: {
    fontSize: 14,
    color: '#374151',
    flex: 1,
  },
  taskTitleCompleted: {
    textDecorationLine: 'line-through',
    color: '#9CA3AF',
  },
  remainingContainer: {
    marginTop: 4,
  },
  remainingText: {
    fontSize: 12,
    color: '#6B7280',
    fontStyle: 'italic',
  },
});
