import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './index.css' // Tailwind

// Optionally register UI components globally
import AppButton from '@/components/ui/AppButton.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppPagination from '@/components/ui/AppPagination.vue'

const app = createApp(App)
app.use(createPinia())
app.use(router)

// Global registration for convenience (or you can import per component)
app.component('AppButton', AppButton)
app.component('AppCard', AppCard)
app.component('AppModal', AppModal)
app.component('AppTable', AppTable)
app.component('AppBadge', AppBadge)
app.component('AppAvatar', AppAvatar)
app.component('AppFormField', AppFormField)
app.component('AppAlert', AppAlert)
app.component('AppPagination', AppPagination)

app.mount('#app')
