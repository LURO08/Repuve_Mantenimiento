
  document.querySelectorAll(".btnEditar").forEach(btn => {
    btn.addEventListener("click", () => {
      let tr = btn.closest("tr");

      edit_id.value = tr.dataset.id;
      edit_user.value = tr.dataset.username;
      edit_role.value = tr.dataset.role;

      edit_pass.value = ""; // limpiar campo de contraseña siempre
    });
  });

  document.querySelectorAll(".btnEliminar").forEach(btn => {
    btn.addEventListener("click", () => {
      let tr = btn.closest("tr");
      delete_id.value = tr.dataset.id;
    });
  });

  // PAGINACIÓN
  const config = {
    usuariostable: {
      page: 1,
      limit: 5
    }
  };


  function renderPagination(tableId) {
    const state = config[tableId];
    const rows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`))
      .filter(r => r.dataset.visible !== "0");

    const total = rows.length;
    const totalPages = Math.ceil(total / state.limit);
    const start = (state.page - 1) * state.limit;
    const end = start + state.limit;

    rows.forEach((r, i) => {
      r.style.display = (i >= start && i < end) ? "" : "none";
    });

    const pag = document.getElementById("pagination-usuarios");
    pag.innerHTML = "";

    if (totalPages <= 1) return;

    let html = `<nav><ul class="pagination pagination-sm">`;

    html += `
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
    `;

    html += `</ul></nav>`;
    pag.innerHTML = html;
  }

  function changePage(tableId, page) {
    config[tableId].page = page;
    renderPagination(tableId);
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("#usuariostable tbody tr").forEach(r => r.dataset.visible = "1");
    renderPagination("usuariostable");
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
