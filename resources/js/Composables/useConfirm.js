import { reactive } from 'vue'

const state = reactive({
    open: false,
    message: '',
    confirmLabel: 'Confirm',
    danger: false,
    resolve: null,
})

export function useConfirm() {
    function confirm(message, options = {}) {
        return new Promise((resolve) => {
            state.message = message
            state.confirmLabel = options.confirmLabel ?? 'Confirm'
            state.danger = options.danger ?? false
            state.open = true
            state.resolve = resolve
        })
    }

    function accept() {
        state.open = false
        state.resolve?.(true)
        state.resolve = null
    }

    function reject() {
        state.open = false
        state.resolve?.(false)
        state.resolve = null
    }

    return { confirmState: state, confirm, accept, reject }
}
