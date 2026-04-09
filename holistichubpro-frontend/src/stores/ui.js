import { defineStore } from 'pinia'
import { ref } from 'vue'

// ✅ F-18: Incrementing counter for unique notification IDs
let nextNotificationId = 1

export const useUiStore = defineStore('ui', () => {
    const sidebarOpen = ref(true)
    const notifications = ref([])

    const toggleSidebar = () => {
        sidebarOpen.value = !sidebarOpen.value
    }

    const addNotification = (notification) => {
        // ✅ F-18: Use incrementing counter instead of Date.now()
        const id = nextNotificationId++
        notifications.value.push({ id, ...notification })
        setTimeout(() => {
            removeNotification(id)
        }, notification.duration || 5000)
    }

    const removeNotification = (id) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    return {
        sidebarOpen,
        notifications,
        toggleSidebar,
        addNotification,
        removeNotification,
    }
})
