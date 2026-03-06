import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '@/components/layout/PublicLayout.vue'
import HomeView from '@/views/HomeView.vue'
import AuthView from '@/views/AuthView.vue'
import ProfileView from '@/views/ProfileView.vue'
import TermsView from '@/views/TermsView.vue'
import PrivacyView from '@/views/PrivacyView.vue'
import ContactView from '@/views/ContactView.vue'
import DoctorsView from '@/views/DoctorsView.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        {
          path: '',
          name: 'home',
          component: HomeView,
        },
        {
          path: 'doctors',
          name: 'doctors',
          component: DoctorsView,
        },
        {
          path: 'terms',
          name: 'terms',
          component: TermsView,
        },
        {
          path: 'privacy',
          name: 'privacy',
          component: PrivacyView,
        },
        {
          path: 'contact',
          name: 'contact',
          component: ContactView,
        },
        {
          path: 'me',
          name: 'me',
          component: ProfileView,
          meta: { requiresAuth: true },
        },
      ],
    },

    { path: '/login', name: 'login', component: AuthView },
    { path: '/register', name: 'register', component: AuthView },

    {
      path: '/admin',
      component: () => import('@/components/layout/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        { path: '', redirect: '/admin/doctors' },
        { path: 'doctors', name: 'admin-doctors', component: () => import('@/views/admin/AdminDoctorsView.vue') },
        { path: 'clinics', name: 'admin-clinics', component: () => import('@/views/admin/AdminClinicsView.vue') },
        { path: 'appointments', name: 'admin-appointments', component: () => import('@/views/admin/AdminAppointmentsView.vue') },
        { path: 'users', name: 'admin-users', component: () => import('@/views/admin/AdminUsersView.vue'), meta: { requiresAdmin: true, globalAdminOnly: true } },
        { path: 'content', name: 'admin-content', component: () => import('@/views/admin/AdminContentView.vue') },
        { path: 'analytics', name: 'admin-analytics', component: () => import('@/views/admin/AdminAnalyticsView.vue') },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    await auth.fetchMe()
  }

  if (to.meta.requiresAuth && !auth.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin) {
    if (!auth.token) return { name: 'login', query: { redirect: to.fullPath } }
    if (!auth.isAdmin && !auth.isClinicAdmin) return { name: 'home' }
  }

  if (to.meta.globalAdminOnly && !auth.isAdmin) {
    return { name: 'admin-doctors' }
  }
})

export default router
