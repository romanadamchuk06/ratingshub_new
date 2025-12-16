/**
 * Toast Composable
 *
 * Einfaches Toast-Management System
 *
 * Usage:
 * const { toast } = useToast();
 * toast.success('Gespeichert!');
 * toast.error('Fehler!', 'Details hier');
 */

import { ref } from 'vue';

// Global state für alle Toasts
const toasts = ref([]);
let toastIdCounter = 0;

export function useToast() {
    /**
     * Fügt einen neuen Toast hinzu
     */
    const addToast = (variant, title, description = null, duration = 5000) => {
        const id = toastIdCounter++;

        toasts.value.push({
            id,
            variant,
            title,
            description,
        });

        // Auto-remove nach duration
        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }

        return id;
    };

    /**
     * Entfernt einen Toast
     */
    const removeToast = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    /**
     * Helper-Methoden für verschiedene Varianten
     */
    const toast = {
        success: (title, description = null, duration = 5000) => {
            return addToast('success', title, description, duration);
        },
        error: (title, description = null, duration = 7000) => {
            return addToast('error', title, description, duration);
        },
        warning: (title, description = null, duration = 6000) => {
            return addToast('warning', title, description, duration);
        },
        info: (title, description = null, duration = 5000) => {
            return addToast('default', title, description, duration);
        },
        // Für custom Toasts
        custom: (variant, title, description = null, duration = 5000) => {
            return addToast(variant, title, description, duration);
        },
    };

    return {
        toasts,
        toast,
        removeToast,
    };
}
