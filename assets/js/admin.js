// Trust Widget — admin UI helpers

// Auto-dismiss alerts after 4s
document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
    setTimeout(() => el.remove(), 4000);
});

// Copy text helper
window.copyText = function(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Скопировано!';
        setTimeout(() => btn.textContent = orig, 2000);
    });
};
