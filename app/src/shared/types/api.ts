export interface ApiError {
  error: string;
  message: string;
  code?: number;
  details?: Record<string, any>;
}

export interface PaginatedResult<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
}
