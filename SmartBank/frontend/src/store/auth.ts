import { create } from 'zustand';

export type Role =
  | 'WALLET_USER'
  | 'RETAIL'
  | 'RETAIL_CUSTOMER'
  | 'TELLER'
  | 'MANAGER'
  | 'ADMIN'
  | 'CENTRAL_BANK_ADMIN';

export interface User {
  id: string;
  phone?: string;
  email?: string;
  role: Role;
  name?: string;
  status: string;
  kycTier?: string;
  walletId?: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  // `hydrated` menandai apakah store sudah membaca localStorage di client.
  // Selama belum hydrated, nilai `user` SSR = null sehingga komponen yang
  // gate pada `user` (AppShell, RolePage) WAJIB menampilkan placeholder
  // identik di server & client — jika tidak, hydration mismatch.
  hydrated: boolean;
  setAuth: (token: string, user: User) => void;
  logout: () => void;
  rehydrate: () => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  // Default values: di server (dan first client render sebelum useEffect)
  // kita set `user = null` dan `hydrated = false`. Pembacaan localStorage
  // dilakukan via `rehydrate()` di client useEffect.
  token: null,
  user: null,
  hydrated: false,

  setAuth: (token: string, user: User) => {
    if (typeof window !== 'undefined') {
      localStorage.setItem('cbdc_token', token);
      localStorage.setItem('cbdc_user', JSON.stringify(user));
    }
    set({ token, user, hydrated: true });
  },

  logout: () => {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('cbdc_token');
      localStorage.removeItem('cbdc_user');
    }
    set({ token: null, user: null, hydrated: true });
  },

  rehydrate: () => {
    if (get().hydrated) return;
    if (typeof window === 'undefined') return;
    const token = localStorage.getItem('cbdc_token');
    const rawUser = localStorage.getItem('cbdc_user');
    const user = rawUser ? JSON.parse(rawUser) : null;
    set({ token, user, hydrated: true });
  },
}));
