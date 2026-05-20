import { ref, onMounted } from 'vue'

// Module-level reactive state — shared across all composable instances
const isDark = ref(true)

function applyTheme(dark) {
    if (dark) {
        document.documentElement.classList.remove('light-mode')
    } else {
        document.documentElement.classList.add('light-mode')
    }
    localStorage.setItem('gb_theme', dark ? 'dark' : 'light')
}

export function useTheme() {
    onMounted(() => {
        const saved = localStorage.getItem('gb_theme')
        isDark.value = saved !== 'light'
        applyTheme(isDark.value)
    })

    function toggleTheme() {
        isDark.value = !isDark.value
        applyTheme(isDark.value)
    }

    return { isDark, toggleTheme }
}
