<template>
    <div class="mt-2">
        <div class="flex space-x-1 mb-2">
            <!-- ✅ F-31: Changed from 4 to 5 bars -->
            <div
                v-for="i in 5"
                :key="i"
                :class="[
                    'h-1 flex-1 rounded-full transition-colors',
                    i <= strength ? strengthColor : 'bg-neutral-30'
                ]"
            />
        </div>
        <ul class="text-xs space-y-1 text-neutral-60">
            <li :class="{ 'text-success': hasLength }">✓ At least 8 characters</li>
            <li :class="{ 'text-success': hasUpper }">✓ One uppercase letter</li>
            <li :class="{ 'text-success': hasLower }">✓ One lowercase letter</li>
            <li :class="{ 'text-success': hasNumber }">✓ One number</li>
            <!-- ✅ F-32: Updated special character message and regex -->
            <li :class="{ 'text-success': hasSpecial }">✓ One special character (e.g., @, #, $, %, ^, &, *, !)</li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    password: String,
})

const hasLength = computed(() => props.password?.length >= 8)
const hasUpper = computed(() => /[A-Z]/.test(props.password))
const hasLower = computed(() => /[a-z]/.test(props.password))
const hasNumber = computed(() => /[0-9]/.test(props.password))
// ✅ F-32: Match any non-alphanumeric character (same as backend)
const hasSpecial = computed(() => /[^A-Za-z0-9]/.test(props.password))

const strength = computed(() => {
    let score = 0
    if (hasLength.value) score++
    if (hasUpper.value) score++
    if (hasLower.value) score++
    if (hasNumber.value) score++
    if (hasSpecial.value) score++
    return score
})

// ✅ F-30: Added 5th color for full strength (score 5)
const strengthColor = computed(() => {
    const colors = [
        'bg-error',      // 1
        'bg-warning',    // 2
        'bg-yellow-500', // 3
        'bg-primary-400',// 4
        'bg-success'     // 5
    ]
    return colors[strength.value - 1] || 'bg-neutral-30'
})
</script>
