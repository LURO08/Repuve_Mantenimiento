document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modalTecnico");
  const form = document.getElementById("formTecnico");
  const action = document.getElementById("tecnicoAction");
  const id = document.getElementById("tecnicoId");
  const nombre = document.getElementById("tecnicoNombre");
  const puesto = document.getElementById("tecnicoPuesto");
  const telefono = document.getElementById("tecnicoTelefono");
  const activo = document.getElementById("tecnicoActivo");
  const activoGroup = document.getElementById("tecnicoActivoGroup");
  const titulo = document.getElementById("modalTecnicoTitulo");
  const buscar = document.getElementById("buscarTecnico");
  const tabla = document.getElementById("tecnicosTable");
  const paginacion = document.getElementById("pagination-Tecnicos");
  const notif = document.getElementById("notifTecnicos");
  const estado = {
    page: 1,
    limit: 8
  };

  function filasFiltrables() {
    return Array.from(tabla?.querySelectorAll("tbody tr") || []).filter(row => !row.querySelector("td[colspan]"));
  }

  function renderTecnicos() {
    const rows = filasFiltrables();
    const visibles = rows.filter(row => row.dataset.visible !== "0");
    const total = visibles.length;
    const pages = Math.max(1, Math.ceil(total / estado.limit));

    if (estado.page > pages) estado.page = pages;
    if (estado.page < 1) estado.page = 1;

    rows.forEach(row => row.style.display = "none");

    const start = (estado.page - 1) * estado.limit;
    const end = start + estado.limit;
    visibles.slice(start, end).forEach(row => row.style.display = "");

    if (!paginacion) return;

    if (total <= estado.limit) {
      paginacion.innerHTML = total ? `<span class="small text-muted">Mostrando ${total} tecnico(s)</span>` : "";
      return;
    }

    const shownStart = start + 1;
    const shownEnd = Math.min(end, total);
    let buttons = `
      <span class="small text-muted me-2">Mostrando ${shownStart}-${shownEnd} de ${total}</span>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-success" data-page="${estado.page - 1}" ${estado.page === 1 ? "disabled" : ""}>Anterior</button>
    `;

    for (let i = 1; i <= pages; i++) {
      buttons += `<button type="button" class="btn ${i === estado.page ? "btn-success" : "btn-outline-success"}" data-page="${i}">${i}</button>`;
    }

    buttons += `
        <button type="button" class="btn btn-outline-success" data-page="${estado.page + 1}" ${estado.page === pages ? "disabled" : ""}>Siguiente</button>
      </div>
    `;

    paginacion.innerHTML = buttons;
  }

  if (notif) {
    setTimeout(() => {
      notif.style.opacity = "0";
      setTimeout(() => notif.remove(), 350);
    }, 3000);
  }

  modal?.addEventListener("show.bs.modal", event => {
    const btn = event.relatedTarget?.closest(".editarTecnicoBtn");

    form?.reset();
    id.value = "";
    action.value = "add";
    activo.checked = true;
    activoGroup?.classList.add("d-none");
    titulo.innerHTML = '<i class="bi bi-person-badge"></i> Agregar tecnico';

    if (!btn) return;

    action.value = "update";
    id.value = btn.dataset.id || "";
    nombre.value = btn.dataset.nombre || "";
    puesto.value = btn.dataset.puesto || "";
    telefono.value = btn.dataset.telefono || "";
    activo.checked = btn.dataset.activo === "1";
    activoGroup?.classList.remove("d-none");
    titulo.innerHTML = '<i class="bi bi-pencil-square"></i> Editar tecnico';
  });

  buscar?.addEventListener("input", () => {
    const q = buscar.value.trim().toLowerCase();
    filasFiltrables().forEach(row => {
      row.dataset.visible = row.innerText.toLowerCase().includes(q) ? "1" : "0";
    });
    estado.page = 1;
    renderTecnicos();
  });

  paginacion?.addEventListener("click", event => {
    const btn = event.target.closest("[data-page]");
    if (!btn || btn.disabled) return;
    estado.page = Number(btn.dataset.page || 1);
    renderTecnicos();
  });

  filasFiltrables().forEach(row => {
    row.dataset.visible = "1";
  });
  renderTecnicos();

  if (new URLSearchParams(window.location.search).get("tab") === "tecnicos") {
    document.getElementById("seccionTecnicos")?.scrollIntoView({ behavior: "smooth", block: "start" });
  }
});
