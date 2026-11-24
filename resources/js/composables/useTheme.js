import { ref, watch, onMounted } from 'vue';

const isDark = ref(true);

export function useTheme() {
    const toggle = () => {
        isDark.value = !isDark.value;
    };

    const setDark = (value) => {
        isDark.value = Boolean(value);
    };

    onMounted(() => {
        const saved = localStorage.getItem('theme_mode');
        if (saved) {
            isDark.value = saved === 'dark';
        } else {
            isDark.value = true; // default dark for dashboard
        }
        document.documentElement.classList.toggle('dark', isDark.value);
    });

    watch(isDark, (value) => {
        if (typeof window !== 'undefined') {
            localStorage.setItem('theme_mode', value ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', value);
        }
    });

    return {
        isDark,
        toggle,
        setDark,
    };
}
