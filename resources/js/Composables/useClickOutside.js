import { onMounted, onBeforeUnmount } from 'vue'

/**
 * Close dropdowns when clicking outside their container elements.
 * @param {Array<{elRef: Ref<HTMLElement>, close: () => void}>} entries
 */
export function useClickOutside(entries) {
    function handler(e) {
        for (const { elRef, close } of entries) {
            if (elRef.value && !elRef.value.contains(e.target)) close()
        }
    }
    onMounted(()       => document.addEventListener('mousedown', handler))
    onBeforeUnmount(() => document.removeEventListener('mousedown', handler))
}
