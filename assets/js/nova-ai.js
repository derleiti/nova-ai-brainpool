document.addEventListener("DOMContentLoaded", function () {
    const widget = document.getElementById("nova-ai-widget");
    const theme = nova_ai_settings.theme;

    if (theme === "dark") {
        widget.classList.add("dark");
    } else if (theme === "light") {
        widget.classList.remove("dark");
    } else if (theme === "auto") {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            widget.classList.add("dark");
        }
    }
});
