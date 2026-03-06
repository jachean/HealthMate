<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  adminGetUsers, adminDeactivateUser, adminActivateUser,
  adminMakeClinicAdmin, adminRemoveClinicAdmin, adminGetClinics,
} from '@/services/adminService'

const { t } = useI18n()

const users = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const loading = ref(false)
const search = ref('')
const errorMsg = ref('')

let searchTimer = null
function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    fetchUsers()
  }, 300)
}

// ── Activate / Deactivate dialog ──────────────────────────────────────────────
const actionDialog = ref(false)
const userTarget = ref(null)
const actioning = ref(false)
const actionError = ref('')

// ── Make clinic admin dialog ──────────────────────────────────────────────────
const clinicAdminDialog = ref(false)
const clinicAdminTarget = ref(null)
const clinicAdminClinicId = ref(null)
const clinicAdminSaving = ref(false)
const clinicAdminError = ref('')
const clinics = ref([])

// ── Remove clinic admin dialog ────────────────────────────────────────────────
const removeClinicAdminDialog = ref(false)
const removeClinicAdminTarget = ref(null)
const removeClinicAdminSaving = ref(false)
const removeClinicAdminError = ref('')

const headers = [
  { title: t('admin.users.name'), key: 'name', sortable: false },
  { title: t('admin.users.email'), key: 'email', sortable: false },
  { title: t('admin.users.username'), key: 'username', sortable: false },
  { title: t('admin.users.role'), key: 'role', sortable: false, align: 'center' },
  { title: t('admin.users.clinic'), key: 'clinic', sortable: false },
  { title: t('admin.users.status'), key: 'status', sortable: false, align: 'center' },
  { title: t('admin.users.actions'), key: 'actions', sortable: false, align: 'center' },
]

const dialogTitle = computed(() =>
  userTarget.value?.isActive
    ? t('admin.users.deactivateTitle')
    : t('admin.users.activateTitle')
)
const dialogText = computed(() =>
  userTarget.value?.isActive
    ? t('admin.users.deactivateText')
    : t('admin.users.activateText')
)
const dialogConfirmLabel = computed(() =>
  userTarget.value?.isActive
    ? t('admin.users.deactivateConfirm')
    : t('admin.users.activateConfirm')
)
const dialogConfirmColor = computed(() =>
  userTarget.value?.isActive ? 'error' : 'success'
)

watch(page, fetchUsers)

async function fetchUsers() {
  loading.value = true
  errorMsg.value = ''
  try {
    const result = await adminGetUsers({
      page: page.value,
      limit: limit.value,
      search: search.value || undefined,
    })
    users.value = result.data
    total.value = result.total
  } catch {
    errorMsg.value = t('admin.errors.loadFailed')
  } finally {
    loading.value = false
  }
}

function openActionDialog(user) {
  userTarget.value = user
  actionError.value = ''
  actionDialog.value = true
}

async function confirmAction() {
  if (!userTarget.value) return
  actioning.value = true
  actionError.value = ''
  try {
    if (userTarget.value.isActive) {
      await adminDeactivateUser(userTarget.value.id)
    } else {
      await adminActivateUser(userTarget.value.id)
    }
    actionDialog.value = false
    userTarget.value = null
    await fetchUsers()
  } catch {
    actionError.value = t('admin.users.errorAction')
  } finally {
    actioning.value = false
  }
}

async function openMakeClinicAdmin(user) {
  clinicAdminTarget.value = user
  clinicAdminClinicId.value = user.clinicAdminClinicId ?? null
  clinicAdminError.value = ''
  if (clinics.value.length === 0) {
    clinics.value = await adminGetClinics()
  }
  clinicAdminDialog.value = true
}

async function confirmMakeClinicAdmin() {
  if (!clinicAdminTarget.value || !clinicAdminClinicId.value) return
  clinicAdminSaving.value = true
  clinicAdminError.value = ''
  try {
    await adminMakeClinicAdmin(clinicAdminTarget.value.id, clinicAdminClinicId.value)
    clinicAdminDialog.value = false
    await fetchUsers()
  } catch {
    clinicAdminError.value = t('admin.users.errorAction')
  } finally {
    clinicAdminSaving.value = false
  }
}

function openRemoveClinicAdmin(user) {
  removeClinicAdminTarget.value = user
  removeClinicAdminError.value = ''
  removeClinicAdminDialog.value = true
}

async function confirmRemoveClinicAdmin() {
  if (!removeClinicAdminTarget.value) return
  removeClinicAdminSaving.value = true
  removeClinicAdminError.value = ''
  try {
    await adminRemoveClinicAdmin(removeClinicAdminTarget.value.id)
    removeClinicAdminDialog.value = false
    await fetchUsers()
  } catch {
    removeClinicAdminError.value = t('admin.users.errorAction')
  } finally {
    removeClinicAdminSaving.value = false
  }
}

onMounted(fetchUsers)
</script>

<template>
  <!-- Confirm activate/deactivate dialog -->
  <v-dialog v-model="actionDialog" max-width="420">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ dialogTitle }}</v-card-title>
      <v-card-text class="pa-4 pt-0">
        <p class="text-body-2 text-medium-emphasis mb-3">{{ dialogText }}</p>
        <template v-if="userTarget">
          <div class="text-body-2">
            <strong>{{ userTarget.firstName }} {{ userTarget.lastName }}</strong>
          </div>
          <div class="text-caption text-medium-emphasis">{{ userTarget.email }}</div>
        </template>
        <v-alert v-if="actionError" type="error" density="compact" class="mt-3">{{ actionError }}</v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="actionDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn :color="dialogConfirmColor" variant="tonal" :loading="actioning" @click="confirmAction">
          {{ dialogConfirmLabel }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Make clinic admin dialog -->
  <v-dialog v-model="clinicAdminDialog" max-width="440">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.users.makeClinicAdminTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-2">
        <template v-if="clinicAdminTarget">
          <div class="text-body-2 mb-3">
            <strong>{{ clinicAdminTarget.firstName }} {{ clinicAdminTarget.lastName }}</strong>
            <span class="text-medium-emphasis ml-1">{{ clinicAdminTarget.email }}</span>
          </div>
        </template>
        <v-select
          v-model="clinicAdminClinicId"
          :items="clinics"
          item-value="id"
          item-title="name"
          :label="t('admin.users.makeClinicAdminClinic')"
          variant="outlined"
          density="compact"
        />
        <v-alert v-if="clinicAdminError" type="error" density="compact" class="mt-3">{{ clinicAdminError }}</v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="clinicAdminDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="primary" variant="tonal" :loading="clinicAdminSaving" :disabled="!clinicAdminClinicId" @click="confirmMakeClinicAdmin">
          {{ t('admin.users.makeClinicAdminConfirm') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Remove clinic admin dialog -->
  <v-dialog v-model="removeClinicAdminDialog" max-width="420">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.users.removeClinicAdmin') }}</v-card-title>
      <v-card-text class="pa-4 pt-0">
        <template v-if="removeClinicAdminTarget">
          <div class="text-body-2">
            <strong>{{ removeClinicAdminTarget.firstName }} {{ removeClinicAdminTarget.lastName }}</strong>
          </div>
          <div class="text-caption text-medium-emphasis">{{ removeClinicAdminTarget.email }}</div>
        </template>
        <v-alert v-if="removeClinicAdminError" type="error" density="compact" class="mt-3">{{ removeClinicAdminError }}</v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="removeClinicAdminDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="error" variant="tonal" :loading="removeClinicAdminSaving" @click="confirmRemoveClinicAdmin">
          {{ t('admin.form.confirm') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-5">
      <div class="d-flex align-center ga-3">
        <v-avatar color="primary" variant="tonal" rounded="lg" size="44">
          <v-icon size="24">mdi-account-group</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">{{ t('admin.users.title') }}</h1>
          <div class="text-body-2 text-medium-emphasis">{{ total }} {{ t('admin.users.total') }}</div>
        </div>
      </div>
    </div>

    <!-- Search -->
    <v-card variant="outlined" class="mb-4 pa-3">
      <v-row dense align="center">
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="search"
            :label="t('admin.users.searchPlaceholder')"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @input="onSearchInput"
            @click:clear="() => { search = ''; page = 1; fetchUsers() }"
          />
        </v-col>
      </v-row>
    </v-card>

    <v-alert v-if="errorMsg" type="error" density="compact" class="mb-4">{{ errorMsg }}</v-alert>

    <!-- Data table -->
    <v-data-table-server
      :headers="headers"
      :items="users"
      :items-length="total"
      :page="page"
      :items-per-page="limit"
      :items-per-page-options="[
        { value: 10, title: '10' },
        { value: 20, title: '20' },
        { value: 50, title: '50' },
      ]"
      :loading="loading"
      :no-data-text="t('admin.users.searchPlaceholder')"
      @update:page="page = $event"
      @update:items-per-page="limit = $event; page = 1; fetchUsers()"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center ga-2 py-1">
          <v-avatar :color="item.isActive ? 'primary' : 'error'" variant="tonal" size="32">
            <v-icon size="16">{{ item.isActive ? 'mdi-account' : 'mdi-account-cancel' }}</v-icon>
          </v-avatar>
          <span class="font-weight-medium">{{ item.firstName }} {{ item.lastName }}</span>
        </div>
      </template>

      <template #item.email="{ item }">
        <div class="text-body-2">{{ item.email }}</div>
      </template>

      <template #item.username="{ item }">
        <div class="text-caption text-medium-emphasis">{{ item.username }}</div>
      </template>

      <template #item.role="{ item }">
        <v-chip
          :color="item.isAdmin ? 'primary' : item.isClinicAdmin ? 'secondary' : 'default'"
          :variant="(item.isAdmin || item.isClinicAdmin) ? 'tonal' : 'outlined'"
          size="small"
        >
          {{ item.isAdmin ? t('admin.users.admin') : item.isClinicAdmin ? t('admin.users.roleClinicAdmin') : t('admin.users.user') }}
        </v-chip>
      </template>

      <template #item.clinic="{ item }">
        <span v-if="item.clinicAdminClinicName" class="text-body-2 text-medium-emphasis">
          {{ item.clinicAdminClinicName }}
        </span>
        <span v-else class="text-caption text-disabled">—</span>
      </template>

      <template #item.status="{ item }">
        <v-chip
          :color="item.isActive ? 'success' : 'error'"
          variant="tonal"
          size="small"
        >
          {{ item.isActive ? t('admin.users.active') : t('admin.users.inactive') }}
        </v-chip>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex align-center ga-1">
          <!-- Activate/Deactivate -->
          <v-btn
            size="small"
            icon
            variant="text"
            :color="item.isActive ? 'error' : 'success'"
            :disabled="item.isAdmin"
            @click="openActionDialog(item)"
          >
            <v-icon size="18">{{ item.isActive ? 'mdi-account-cancel' : 'mdi-account-check' }}</v-icon>
            <v-tooltip activator="parent" location="top">
              {{ item.isActive ? t('admin.users.deactivate') : t('admin.users.activate') }}
            </v-tooltip>
          </v-btn>

          <!-- Make clinic admin (for non-admin users) -->
          <v-btn
            v-if="!item.isAdmin"
            size="small"
            icon
            variant="text"
            color="primary"
            @click="openMakeClinicAdmin(item)"
          >
            <v-icon size="18">mdi-shield-account</v-icon>
            <v-tooltip activator="parent" location="top">{{ t('admin.users.makeClinicAdmin') }}</v-tooltip>
          </v-btn>

          <!-- Remove clinic admin -->
          <v-btn
            v-if="item.isClinicAdmin"
            size="small"
            icon
            variant="text"
            color="warning"
            @click="openRemoveClinicAdmin(item)"
          >
            <v-icon size="18">mdi-shield-off</v-icon>
            <v-tooltip activator="parent" location="top">{{ t('admin.users.removeClinicAdmin') }}</v-tooltip>
          </v-btn>
        </div>
      </template>
    </v-data-table-server>
  </div>
</template>
