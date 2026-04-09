<template>
    <div class="w-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap"
                    >
                        {{ col.label }}
                    </th>
                    <th v-if="$slots.actions" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="data.length === 0">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-6 py-12 text-center text-gray-500 font-medium">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span>No data available</span>
                        </div>
                    </td>
                </tr>
                <tr v-for="item in data" :key="item.id" class="hover:bg-blue-50/50 transition-colors duration-200 group">
                    <td v-for="col in columns" :key="col.key" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 group-hover:text-gray-900">
                        <slot :name="col.key" :item="item">
                            {{ getNestedValue(item, col.key) }}
                        </slot>
                    </td>
                    <td v-if="$slots.actions" class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <slot name="actions" :item="item" />
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div v-if="$slots.footer" class="border-t border-gray-200 bg-gray-50">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
// ✅ F-20: Added default value for data prop to prevent undefined errors
defineProps({
    columns: Array,
    data: { type: Array, default: () => [] },
})

const getNestedValue = (obj, path) => {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj)
}
</script>
