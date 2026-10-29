const filasFactura = document.getElementById('renglones');
const agregarFactura = document.getElementById('agregar-renglon');
if (filasFactura && agregarFactura) {
  agregarFactura.hidden = false;
  agregarFactura.addEventListener('click', () => {
    const indice = filasFactura.rows.length;
    if (indice >= 50) return;
    const fila = filasFactura.rows[0].cloneNode(true);
    fila.querySelectorAll('input, select').forEach(campo => {
      campo.name = campo.name.replace(/items\[\d+\]/, `items[${indice}]`);
      campo.value = '';
      campo.setAttribute('aria-label', campo.getAttribute('aria-label').replace(/\d+$/, indice + 1));
    });
    filasFactura.appendChild(fila);
    fila.querySelector('select').focus();
    if (filasFactura.rows.length >= 50) agregarFactura.disabled = true;
  });
}
