<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  DashboardOutlined,
  TeamOutlined,
  SafetyCertificateOutlined,
  ApiOutlined,
  SettingOutlined,
  CustomerServiceOutlined,
  VideoCameraOutlined,
  ShoppingOutlined,
  ScheduleOutlined,
  GoldOutlined,
  DollarCircleOutlined
} from '@ant-design/icons-vue'
import { usePermission } from '@/core/composables/usePermissions'

defineProps<{ collapsed: boolean }>()

const route = useRoute()
const { can } = usePermission()

const systemNavItems = [
  { path: '/dashboard',        label: 'Dashboard',        icon: DashboardOutlined,         permission: null                    },
  { path: '/users',            label: 'Usuarios',          icon: TeamOutlined,              permission: 'users.read'            },
  { path: '/roles',            label: 'Roles y Permisos',  icon: SafetyCertificateOutlined, permission: 'roles.read'            },
  { path: '/products',         label: 'Productos',         icon: ShoppingOutlined,          permission: 'products.read'         },
  { path: '/auctions',         label: 'Subastas',          icon: ScheduleOutlined,          permission: 'auctions.read'         },
  { path: '/lots',             label: 'Lotes',             icon: GoldOutlined,               permission: 'lots.read'             },
  { path: '/currencies',       label: 'Monedas',           icon: DollarCircleOutlined,      permission: 'currencies.read'       },
  { path: '/api-clients',      label: 'Clientes API',      icon: ApiOutlined,               permission: 'api-clients.read'      },
  { path: '/influencers',      label: 'Influencers',       icon: VideoCameraOutlined,       permission: 'influencers.read'      },
  { path: '/settings',         label: 'Configuración',     icon: SettingOutlined,           permission: null                    },
  { path: '/support-messages', label: 'Soporte',           icon: CustomerServiceOutlined,   permission: 'support-messages.read' }
  //{ path: '/tutorials',        label: 'Tutoriales',        icon: PlayCircleOutlined,        permission: 'tutorials.read'        },
]

const navItems = computed(() =>
  systemNavItems.filter(item => !item.permission || can(item.permission))
)
</script>

<template>
  <nav class="dash-nav">
    <Transition name="label-fade">
      <span v-if="!collapsed" class="dash-nav-section">Sistema</span>
    </Transition>
    <RouterLink
      v-for="item in navItems"
      :key="item.path"
      :to="item.path"
      class="dash-nav-item"
      :class="{ 'is-active': route.path === item.path }"
      :title="collapsed ? item.label : undefined"
    >
      <component :is="item.icon" class="dash-nav-icon" />
      <Transition name="label-fade">
        <span v-if="!collapsed" class="dash-nav-label">{{ item.label }}</span>
      </Transition>
    </RouterLink>

  </nav>
</template>
