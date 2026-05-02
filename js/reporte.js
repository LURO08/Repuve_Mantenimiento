
  const config = {
    reportesTable: {
      page: 1,
      limit: 2
    }
  };

  // RENDER PAGINACIÓN
  function renderPagination(tableId) {
    const state = config[tableId];
    const rows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`))
      .filter(r => r.dataset.visible !== "0");

    const total = rows.length;
    const totalPages = Math.ceil(total / state.limit);
    const start = (state.page - 1) * state.limit;
    const end = start + state.limit;

    // Mostrar sólo la página activa
    rows.forEach((r, i) => {
      r.style.display = i >= start && i < end ? "" : "none";
    });

    // Contenedor correcto
    const name = tableId.replace("Table", "");
    const pagContainer = document.getElementById(`pagination-${name}`);
    pagContainer.innerHTML = "";

    if (totalPages <= 1) return;

    let html = `
        <nav>
            <ul class="pagination pagination-sm">
                <li class="page-item ${state.page === 1 ? 'disabled' : ''}">
                    <button class="page-link" onclick="changePage('${tableId}', ${state.page - 1})">Anterior</button>
                </li>
    `;

    for (let i = 1; i <= totalPages; i++) {
      html += `
            <li class="page-item ${i === state.page ? 'active' : ''}">
                <button class="page-link" onclick="changePage('${tableId}', ${i})">${i}</button>
            </li>`;
    }

    html += `
                <li class="page-item ${state.page === totalPages ? 'disabled' : ''}">
                    <button class="page-link" onclick="changePage('${tableId}', ${state.page + 1})">Siguiente</button>
                </li>
            </ul>
        </nav>
    `;

    pagContainer.innerHTML = html;
  }

  function changePage(tableId, page) {
    config[tableId].page = page;
    renderPagination(tableId);
  }

 document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("tbody tr").forEach(r => r.dataset.visible = "1");
    renderPagination("reportesTable");
  });

  function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    const q = input.value.trim().toLowerCase();
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    Array.from(tbody.querySelectorAll('tr')).forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
    });
  }


document.querySelectorAll('.btnVerArcos').forEach(btn => {
    btn.addEventListener('click', () => {

        const id = btn.dataset.id;
        const nombre = btn.dataset.nombre;

        document.getElementById('modalMaterialNombre').textContent = nombre;

        const cont = document.getElementById('contenidoArcos');
        cont.innerHTML = '<div class="text-center">Cargando...</div>';

        fetch(`../controllers/reportes_materiales_ajax.php?material_id=${id}`)
            .then(res => res.text())
            .then(html => cont.innerHTML = html)
            .catch(() => cont.innerHTML = '<p class="text-danger">Error al cargar datos</p>');
    });
});
