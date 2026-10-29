export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  password: string;
  remember_token: string | null;
  created_at: string;
  updated_at: string;
}

export type CreateUserPayload = Omit<User, 'id' | 'created_at' | 'updated_at'> & {
  password_confirmation: string;
};

export type UpdateUserPayload = Partial<Omit<User, 'id' | 'created_at' | 'updated_at'>> & {
  password_confirmation?: string;
};
