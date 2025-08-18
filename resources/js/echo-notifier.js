import { echo } from './echo';

document.addEventListener('DOMContentLoaded', () => {
    const meta = document.querySelector('meta[name="current-user-id"]');
    const userId = meta && meta.content ? meta.content : null;
    if (!userId) {
        console.warn('[Echo] current-user-id meta not found; skip subscribe.');
        return;
    }

    const channel = `App.Models.User.${userId}`;

    echo.private(channel)
        .notification((notification) => {
            window.dispatchEvent(new CustomEvent('echo:notification', { detail: notification }));
            window.Livewire?.dispatch('db-notification-arrived');
        });
});
