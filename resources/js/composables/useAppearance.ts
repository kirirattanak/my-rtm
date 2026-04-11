export function initializeTheme() {
    // RTM always uses light mode. Dark mode support can be added later
    // when a theme switcher is introduced in the settings.
    document.documentElement.classList.remove('dark');
}
