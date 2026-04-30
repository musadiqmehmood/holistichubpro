<template>
    <div class="w-full bg-[var(--surface-color)] rounded-xl shadow-sm border border-[var(--border-color)] overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-[var(--border-color)]">
                <thead class="bg-[var(--background-color)]">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider whitespace-nowrap"
                    >
                        {{ col.label }}
                    </th>
                    <th v-if="$slots.actions" class="px-6 py-4 text-right text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-[var(--surface-color)] divide-y divide-[var(--border-color)]">
                <tr v-if="data.length === 0">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-6 py-12 text-center text-[var(--text-secondary)] font-medium">
                        No data available
                    </td>
                </tr>
                <tr v-for="item in data" :key="item.id" class="hover:bg-[var(--primary-color)]/5 transition-colors duration-200">
                    <td v-for="col in columns" :key="col.key" class="px-6 py-4 whitespace-nowrap text-sm text-[var(--text-primary)]">
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
        <div v-if="$slots.footer" class="border-t border-[var(--border-color)] bg-[var(--background-color)]">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    columns: Array,
    data: { type: Array, default: () => [] },
})

const getNestedValue = (obj, path) => {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj)
}
</script>
