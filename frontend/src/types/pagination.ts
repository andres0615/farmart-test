export interface PaginationMeta {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedResult<T> {
  data: T[];
  pagination: PaginationMeta;
}