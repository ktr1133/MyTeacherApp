/**
 * API関連の型定義
 */

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar_url?: string;
  created_at: string;
  group_id?: number;
  group?: {
    id: number;
    name: string;
    owner_user_id: number;
  };
  group_edit_flg?: boolean;
  teacher_avatar_id?: number;
  theme?: 'adult' | 'child';
}

export interface AuthResponse {
  token: string;
  user: User;
}
