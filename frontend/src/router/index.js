/**
 * router/index.js — Vue Router 4 configuration
 *
 * Route guard: unauthenticated users are redirected to /auth.
 */

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

// ── Lazy-loaded views ──
const HomeView          = () => import('@/views/HomeView.vue')
const AuthView          = () => import('@/views/AuthView.vue')
const SwipeView         = () => import('@/views/SwipeView.vue')
const ProfileView       = () => import('@/views/ProfileView.vue')
const NotificationsView = () => import('@/views/NotificationsView.vue')
const SearchSettingsView = () => import('@/views/SearchSettingsView.vue')
const ChatsView         = () => import('@/views/ChatsView.vue')
const ConnectionsView   = () => import('@/views/ConnectionsView.vue')

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { public: true },
  },
  {
    path: '/auth',
    name: 'auth',
    component: AuthView,
    meta: { public: true },
  },
  {
    path: '/discover',
    name: 'discover',
    component: SwipeView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: NotificationsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/chats',
    name: 'chats',
    component: ChatsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/connections',
    name: 'connections',
    component: ConnectionsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/search-settings',
    name: 'search-settings',
    component: SearchSettingsView,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

// ── Navigation Guard ──
router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'auth', query: { redirect: to.fullPath } }
  }
  if (to.name === 'auth' && authStore.isAuthenticated) {
    return { name: 'discover' }
  }
})

export default router
