// La validación principal siempre está en PHP; esto solamente mejora la comodidad.
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', event => {
        if (form.dataset.confirmar && !window.confirm(form.dataset.confirmar)) {
            event.preventDefault(); return;
        }
        form.querySelectorAll('button[type="submit"]').forEach(button => {
            button.disabled = true; button.textContent = 'Guardando…';
        });
    });
});
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
