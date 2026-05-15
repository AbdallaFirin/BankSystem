import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: JSON.parse(localStorage.getItem('user')) || null,
        permissions: JSON.parse(localStorage.getItem('permissions')) || [],
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,
    },
    actions: {
        setAuth(data) {
            this.token = data.token;
            this.user = data.user;
            this.permissions = data.permissions;
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            localStorage.setItem('permissions', JSON.stringify(data.permissions));
            axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
        },
        clearAuth() {
            this.token = null;
            this.user = null;
            this.permissions = [];
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('permissions');
            delete axios.defaults.headers.common['Authorization'];
        },
        can(permissionKey) {
            // Super Admin override or specific permission check
            if (this.user?.role?.role_name === 'Super Admin' || this.user?.role_id === 1) return true;
            return this.permissions.includes(permissionKey);
        }
    }
});
