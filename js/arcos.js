
let materialSeleccionadoArco = null;
let materialesAgregadosArco = [];
let materialesEditandoArco = [];
let infraestructurasAgregadasArco = [];
let materialContextoActivo = "agregar";
let seleccionandoMaterialParaEditar = false;
let materialOperacionActiva = "agregar";
let materialEditarIndex = null;
let materialSeleccionadoEditarIndex = null;

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

const config = {
  ArcosTable: {
    page: 1,
    limit: 3
  },
  BajasTable: {
    page: 1,
    limit: 3
  },
  InfraTable: {
    page: 1,
    limit: 3
  }
};
const paginationLimitOptions = [3, 10, 20, 30, 40, 50, 60, 100];

// RENDER PAGINACIÓN
function renderPagination(tableId) {
  const state = config[tableId];
  const allRows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));
  const dataRows = allRows.filter(row => !row.classList.contains("pagination-empty-row"));
  const emptyRows = allRows.filter(row => row.classList.contains("pagination-empty-row"));

  // SOLO filas visibles por búsqueda
  const visibleRows = dataRows.filter(row => row.dataset.visible !== "0");

  const total = visibleRows.length;
  state.limit = normalizePaginationLimit(total, state.limit);
  const totalPages = Math.ceil(total / state.limit) || 1;

  // corregir página si se pasa
  if (state.page > totalPages) {
    state.page = totalPages;
  }
  if (state.page < 1) {
    state.page = 1;
  }

  const start = (state.page - 1) * state.limit;
  const end = start + state.limit;
  const shownStart = total === 0 ? 0 : start + 1;
  const shownEnd = Math.min(end, total);

  // ocultar TODAS primero
  allRows.forEach(row => row.style.display = "none");
  if (total === 0) {
    emptyRows.forEach(row => row.style.display = "");
  }

  // mostrar SOLO las de la página actual
  visibleRows.slice(start, end).forEach(row => {
    row.style.display = "";
  });

  renderPaginationButtons(tableId, totalPages, shownStart, shownEnd, total);
  ajustarScrollTabla(tableId);
}

function ajustarScrollTabla(tableId) {
  const table = document.getElementById(tableId);
  const scrollBox = table?.closest(".tabla-scroll");
  if (!scrollBox) return;

  scrollBox.classList.remove("is-scroll-limited");

  window.requestAnimationFrame(() => {
    const pageWouldScroll = document.documentElement.scrollHeight > window.innerHeight + 8;
    scrollBox.classList.toggle("is-scroll-limited", pageWouldScroll);
  });
}

function renderPaginationButtons(tableId, totalPages, shownStart = 0, shownEnd = 0, total = 0) {
  const state = config[tableId];
  const name = tableId.replace("Table", "");
  const pag = document.getElementById(`pagination-${name}`);

  if (!pag) return;
  pag.innerHTML = "";

  const options = getPaginationLimitOptions(total, state.limit);

  let html = `
    <div class="pagination-toolbar">
      <div class="pagination-summary">
        ${shownEnd - shownStart + (total > 0 ? 1 : 0)} de ${total} ${getPaginationItemLabel(tableId)} &middot; Pagina ${state.page} de ${totalPages}
        <span>Mostrando ${shownStart}-${shownEnd}</span>
      </div>
      <label class="pagination-limit">
        <span>Mostrar</span>
        <select class="form-select form-select-sm" onchange="changePageLimit('${tableId}', this.value)">
          ${options.map(opt => `<option value="${opt}" ${opt === state.limit ? "selected" : ""}>${opt}</option>`).join("")}
        </select>
      </label>
    </div>
  `;

  if (totalPages <= 1) {
    pag.innerHTML = html;
    return;
  }

  html += `
    <nav><ul class="pagination pagination-sm mb-0">
  `;

  html += `
        <li class="page-item ${state.page === 1 ? 'disabled' : ''}">
            <button type="button" class="page-link" onclick="changePage('${tableId}', ${state.page - 1})">
                Anterior
            </button>
        </li>
    `;

  getPaginationPages(state.page, totalPages).forEach(item => {
    if (item === "...") {
      html += `<li class="page-item disabled"><span class="page-link pagination-ellipsis">...</span></li>`;
      return;
    }

    html += `
      <li class="page-item ${item === state.page ? 'active' : ''}">
        <button type="button" class="page-link" onclick="changePage('${tableId}', ${item})">${item}</button>
      </li>
    `;
  });

  html += `
        <li class="page-item ${state.page === totalPages ? 'disabled' : ''}">
            <button type="button" class="page-link" onclick="changePage('${tableId}', ${state.page + 1})">
                Siguiente
            </button>
        </li>
    `;

  html += `</ul></nav>`;
  pag.innerHTML = html;
}

function getPaginationPages(current, totalPages) {
  if (totalPages <= 7) {
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }

  const pages = new Set([1, totalPages, current, current - 1, current + 1]);
  if (current <= 3) {
    pages.add(2);
    pages.add(3);
    pages.add(4);
  }
  if (current >= totalPages - 2) {
    pages.add(totalPages - 1);
    pages.add(totalPages - 2);
    pages.add(totalPages - 3);
  }

  const sorted = [...pages].filter(p => p >= 1 && p <= totalPages).sort((a, b) => a - b);
  return sorted.reduce((acc, page, index) => {
    if (index > 0 && page - sorted[index - 1] > 1) acc.push("...");
    acc.push(page);
    return acc;
  }, []);
}

function getPaginationLimitOptions(total, currentLimit) {
  if (total <= 0) return [];
  const maxOption = paginationLimitOptions.find(opt => opt >= total) || paginationLimitOptions[paginationLimitOptions.length - 1];
  const currentOption = currentLimit <= maxOption ? [currentLimit] : [];
  const options = [...paginationLimitOptions.filter(opt => opt <= maxOption), ...currentOption];
  return [...new Set(options)]
    .filter(opt => Number.isFinite(Number(opt)) && Number(opt) > 0)
    .map(Number)
    .sort((a, b) => a - b);
}

function normalizePaginationLimit(total, currentLimit) {
  if (total <= 0) return currentLimit;
  const maxOption = paginationLimitOptions.find(opt => opt >= total) || paginationLimitOptions[paginationLimitOptions.length - 1];
  return currentLimit > maxOption ? maxOption : currentLimit;
}

function getPaginationItemLabel(tableId) {
  if (tableId === "BajasTable") return "bajas";
  if (tableId === "InfraTable") return "puentes/sitios";
  return "arcos";
}

function normalizarTextoOrden(valor) {
  return String(valor || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
    .toLowerCase();
}

function obtenerValorOrden(row, columnIndex, sortType) {
  const cell = row.children[columnIndex];
  const text = cell?.innerText || "";

  if (sortType === "number") {
    const match = text.replace(/,/g, "").match(/-?\d+(\.\d+)?/);
    return match ? Number(match[0]) : 0;
  }

  if (sortType === "date") {
    const dateMatch = text.match(/(\d{2})-(\d{2})-(\d{4})/);
    if (dateMatch) {
      return new Date(`${dateMatch[3]}-${dateMatch[2]}-${dateMatch[1]}T00:00:00`).getTime();
    }
    const parsed = Date.parse(text);
    return Number.isNaN(parsed) ? 0 : parsed;
  }

  return normalizarTextoOrden(text);
}

function ordenarTablaPorEncabezado(tableId, th) {
  const table = document.getElementById(tableId);
  if (!table || !config[tableId]) return;

  const tbody = table.querySelector("tbody");
  const columnIndex = Array.from(th.parentElement.children).indexOf(th);
  const sortType = th.dataset.sortType || "text";
  const currentDirection = th.dataset.sortDirection === "asc" ? "asc" : "desc";
  const nextDirection = currentDirection === "asc" ? "desc" : "asc";
  const multiplier = nextDirection === "asc" ? 1 : -1;

  const dataRows = Array.from(tbody.querySelectorAll("tr:not(.pagination-empty-row)"));
  dataRows.sort((a, b) => {
    const valA = obtenerValorOrden(a, columnIndex, sortType);
    const valB = obtenerValorOrden(b, columnIndex, sortType);
    if (typeof valA === "number" && typeof valB === "number") {
      return (valA - valB) * multiplier;
    }
    return String(valA).localeCompare(String(valB), "es", { numeric: true }) * multiplier;
  });

  dataRows.forEach(row => tbody.appendChild(row));
  table.querySelectorAll(".sortable-header").forEach(header => {
    header.classList.remove("sort-asc", "sort-desc");
    delete header.dataset.sortDirection;
  });
  th.dataset.sortDirection = nextDirection;
  th.classList.add(nextDirection === "asc" ? "sort-asc" : "sort-desc");

  config[tableId].page = 1;
  renderPagination(tableId);
}

function initSortableTables() {
  Object.keys(config).forEach(tableId => {
    const table = document.getElementById(tableId);
    if (!table || table.dataset.sortableReady === "1") return;
    table.dataset.sortableReady = "1";

    if (tableId === "InfraTable") {
      const ubicacionHeader = table.querySelector("thead tr")?.children[3];
      ubicacionHeader?.classList.add("sortable-header");
      if (ubicacionHeader && !ubicacionHeader.dataset.sortType) {
        ubicacionHeader.dataset.sortType = "text";
      }
    }

    table.querySelectorAll("thead .sortable-header").forEach(th => {
      th.setAttribute("role", "button");
      th.setAttribute("tabindex", "0");
      th.addEventListener("click", event => {
        event.preventDefault();
        ordenarTablaPorEncabezado(tableId, th);
      });
      th.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          ordenarTablaPorEncabezado(tableId, th);
        }
      });
    });
  });
}

function renderArcosVinculadosModal(nombre, arcos, enlaces = [], saltos = []) {
  const titulo = document.getElementById("modalArcosVinculadosTitulo");
  const contenedor = document.getElementById("modalArcosVinculadosContenido");
  if (titulo) titulo.textContent = `${nombre || "Nodo"} - Vínculos y Conexiones de Red`;
  if (!contenedor) return;

  const totalArcos = Array.isArray(arcos) ? arcos.length : 0;
  const listaEnlaces = Array.isArray(enlaces) ? enlaces : [];
  const listaSaltos = Array.isArray(saltos) ? saltos : [];
  const sitios = listaEnlaces.filter(e => e.tipo === 'Sitio/Torre');
  const postes = listaEnlaces.filter(e => e.tipo === 'Puente/Poste');
  const totalEnlaces = listaEnlaces.length;
  const totalSaltos = listaSaltos.length;

  if (totalArcos === 0 && totalEnlaces === 0 && totalSaltos === 0) {
    contenedor.innerHTML = '<div class="alert alert-light border text-center py-4 mb-0"><i class="bi bi-info-circle text-muted fs-3 d-block mb-2"></i>Este nodo no tiene arcos, enlaces ni saltos vinculados actualmente.</div>';
    return;
  }

  let html = '<div class="row g-3">';

  // COLUMNA ARCOS VINCULADOS
  html += `
    <div class="col-md-${(totalEnlaces > 0 || totalSaltos > 0) ? '6' : '12'}">
      <div class="card border h-100 shadow-sm">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
          <span class="fw-bold text-success small"><i class="bi bi-bounding-box-circles me-1"></i> Arcos vinculados</span>
          <span class="badge bg-success">${totalArcos}</span>
        </div>
        <div class="card-body p-2" style="max-height: 380px; overflow-y: auto;">
          ${totalArcos === 0 ? '<div class="text-muted small text-center py-3">Sin arcos vinculados</div>' : `
            <div class="d-flex flex-column gap-2">
              ${arcos.map(arco => `
                <div class="d-flex justify-content-between align-items-center p-2 border rounded bg-white">
                  <div class="text-truncate me-2">
                    <strong class="d-block small text-dark text-truncate">${escapeHtml(arco.nombre || "Sin nombre")}</strong>
                    <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger"></i> ${escapeHtml(arco.ubicacion || "Sin ubicación")}</small>
                  </div>
                  <span class="badge ${String(arco.estado || "Activo").toLowerCase() === "baja" ? "bg-danger" : "bg-success"} rounded-pill small">
                    ${escapeHtml(arco.estado || "Activo")}
                  </span>
                </div>
              `).join("")}
            </div>
          `}
        </div>
      </div>
    </div>
  `;

  // COLUMNA ENLACES DE RED Y SALTOS
  if (totalEnlaces > 0 || totalSaltos > 0 || totalArcos === 0) {
    html += `
      <div class="col-md-${totalArcos > 0 ? '6' : '12'}">
        <div class="card border h-100 shadow-sm">
          <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary small"><i class="bi bi-hdd-network me-1"></i> Enlaces y Saltos de Red</span>
            <div class="d-flex gap-1">
              ${totalEnlaces > 0 ? `<span class="badge bg-primary">${totalEnlaces} enlaces</span>` : ''}
              ${totalSaltos > 0 ? `<span class="badge bg-info text-dark">${totalSaltos} saltos</span>` : ''}
            </div>
          </div>
          <div class="card-body p-2" style="max-height: 380px; overflow-y: auto;">
            <div class="d-flex flex-column gap-2">
              ${sitios.map(s => `
                <div class="d-flex justify-content-between align-items-center p-2 border border-primary-subtle rounded bg-light-subtle">
                  <div class="text-truncate me-2">
                    <strong class="d-block small text-primary text-truncate"><i class="bi bi-broadcast me-1"></i> ${escapeHtml(s.nombre || "Sitio")}</strong>
                    <small class="text-muted"><i class="bi bi-geo-alt"></i> ${escapeHtml(s.ubicacion || "Sin ubicación")}</small>
                  </div>
                  <span class="badge bg-primary rounded-pill small">Sitio/Torre</span>
                </div>
              `).join("")}
              ${postes.map(p => `
                <div class="d-flex justify-content-between align-items-center p-2 border border-warning-subtle rounded bg-light-subtle">
                  <div class="text-truncate me-2">
                    <strong class="d-block small text-dark text-truncate"><i class="bi bi-signpost-2 me-1"></i> ${escapeHtml(p.nombre || "Poste")}</strong>
                    <small class="text-muted"><i class="bi bi-geo-alt"></i> ${escapeHtml(p.ubicacion || "Sin ubicación")}</small>
                  </div>
                  <span class="badge bg-warning text-dark rounded-pill small">Puente/Poste</span>
                </div>
              `).join("")}
              ${listaSaltos.map(salto => `
                <div class="d-flex justify-content-between align-items-center p-2 border border-info-subtle rounded bg-info-subtle">
                  <div class="text-truncate me-2">
                    <strong class="d-block small text-dark text-truncate">
                      <i class="bi bi-link-45deg text-info"></i> ${escapeHtml(salto.origen_nombre || "Origen")}
                      <i class="bi bi-arrow-left-right text-muted mx-1"></i>
                      <i class="bi bi-broadcast text-info"></i> ${escapeHtml(salto.destino_nombre || "Destino")}
                    </strong>
                    <small class="text-muted"><i class="bi bi-shuffle"></i> ${escapeHtml(salto.tipo_salto || "Salto de Enlace")}</small>
                  </div>
                  <span class="badge bg-info text-dark rounded-pill small">Salto de Red</span>
                </div>
              `).join("")}
            </div>
          </div>
        </div>
      </div>
    `;
  }

  html += '</div>';
  contenedor.innerHTML = html;
}

function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);

  if (!input || !table) return;

  const q = input.value.trim().toLowerCase();
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const texto = row.innerText.toLowerCase();

    row.dataset.visible = texto.includes(q) || q === ""
      ? "1"
      : "0";
  });

  config[tableId].page = 1;
  renderPagination(tableId);
}

function changePage(tableId, page) {
  if (!config[tableId]) return;
  config[tableId].page = Math.max(1, page);
  renderPagination(tableId);
}

function changePageLimit(tableId, limit) {
  if (!config[tableId]) return;
  const nextLimit = Number(limit);
  if (!Number.isFinite(nextLimit) || nextLimit <= 0) return;
  config[tableId].limit = nextLimit;
  config[tableId].page = 1;
  renderPagination(tableId);
}

window.changePage = changePage;
window.changePageLimit = changePageLimit;
window.filterTable = filterTable;

const ModalManager = {

    get(id) {

        const el = document.getElementById(id);

        if (!el) {
            console.warn(`Modal no encontrado: ${id}`);
            return null;
        }

        if (!window.bootstrap || !bootstrap.Modal) {
            console.error("Bootstrap JS no está cargado. No se puede abrir el modal:", id);
            return null;
        }

        // reutiliza instancia existente
        return bootstrap.Modal.getOrCreateInstance(el);
    },

    show(id, relatedTarget = null) {

        const modal = this.get(id);

        if (modal) {
            modal.show(relatedTarget);
        }
    },

    hide(id) {

        const modal = this.get(id);

        if (modal) {
            modal.hide();
        }
    }
};


document.addEventListener("DOMContentLoaded", () => {
  ["ArcosTable", "BajasTable", "InfraTable"].forEach(tableId => {
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(r => r.dataset.visible = "1");
    renderPagination(tableId);
  });
  initSortableTables();
  let bajaArchivosSeleccionados = [];

  document.querySelectorAll(".gestionarArcoBtn").forEach(btn => {
    btn.addEventListener("click", () => {
      const idInput = document.getElementById("baja_arco_id");
      const nombreEl = document.getElementById("baja_arco_nombre");
      const ubicacionEl = document.getElementById("baja_arco_ubicacion");
      const observaciones = document.getElementById("baja_observaciones");
      const motivo = document.getElementById("baja_motivo");
      const deleteLink = document.getElementById("gestionEliminarArcoLink");
      const evidenciasInput = document.getElementById("bajaEvidenciasInput");
      const evidenciasPreview = document.getElementById("bajaEvidenciasPreview");
      const opciones = document.getElementById("gestionOpcionesArco");
      const camposBaja = document.getElementById("gestionBajaCampos");
      const btnConfirmar = document.getElementById("btnConfirmarBajaArco");

      if (idInput) idInput.value = btn.dataset.id || "";
      if (nombreEl) nombreEl.textContent = btn.dataset.nombre || "Arco seleccionado";
      if (ubicacionEl) ubicacionEl.textContent = btn.dataset.ubicacion || "Ubicacion no indicada";
      if (observaciones) observaciones.value = "";
      if (motivo) motivo.value = "";
      if (deleteLink) deleteLink.href = btn.dataset.deleteUrl || "#";
      if (evidenciasInput) evidenciasInput.value = "";
      if (evidenciasPreview) evidenciasPreview.innerHTML = "";
      bajaArchivosSeleccionados = [];
      opciones?.classList.remove("d-none");
      camposBaja?.classList.add("d-none");
      btnConfirmar?.classList.add("d-none");
    });
  });

  const btnMostrarBaja = document.getElementById("btnMostrarBajaArco");
  const btnVolverGestion = document.getElementById("btnVolverGestionArco");
  const opcionesGestion = document.getElementById("gestionOpcionesArco");
  const camposBajaGestion = document.getElementById("gestionBajaCampos");
  const btnConfirmarBaja = document.getElementById("btnConfirmarBajaArco");

  btnMostrarBaja?.addEventListener("click", () => {
    opcionesGestion?.classList.add("d-none");
    camposBajaGestion?.classList.remove("d-none");
    btnConfirmarBaja?.classList.remove("d-none");
  });

  btnVolverGestion?.addEventListener("click", () => {
    camposBajaGestion?.classList.add("d-none");
    opcionesGestion?.classList.remove("d-none");
    btnConfirmarBaja?.classList.add("d-none");
  });

  const bajaEvidenciasInput = document.getElementById("bajaEvidenciasInput");
  const bajaEvidenciasPreview = document.getElementById("bajaEvidenciasPreview");

  function actualizarInputEvidenciasBaja() {
    if (!bajaEvidenciasInput) return;
    const dt = new DataTransfer();
    bajaArchivosSeleccionados.forEach(file => dt.items.add(file));
    bajaEvidenciasInput.files = dt.files;
  }

  function renderPreviewEvidenciasBaja() {
    if (!bajaEvidenciasPreview) return;
    bajaEvidenciasPreview.innerHTML = "";

    bajaArchivosSeleccionados.forEach((file, index) => {
      const item = document.createElement("div");
      item.className = "preview-item";

      if (file.type.startsWith("image/")) {
        const img = document.createElement("img");
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        item.appendChild(img);
      } else {
        const pdf = document.createElement("div");
        pdf.className = "preview-pdf";
        pdf.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i>';
        item.appendChild(pdf);
      }

      const remove = document.createElement("button");
      remove.type = "button";
      remove.className = "preview-remove";
      remove.setAttribute("aria-label", "Quitar evidencia");
      remove.innerHTML = "&times;";
      remove.addEventListener("click", () => {
        bajaArchivosSeleccionados.splice(index, 1);
        actualizarInputEvidenciasBaja();
        renderPreviewEvidenciasBaja();
      });

      const name = document.createElement("div");
      name.className = "preview-name";
      name.title = file.name;
      name.textContent = file.name;

      item.appendChild(remove);
      item.appendChild(name);
      bajaEvidenciasPreview.appendChild(item);
    });
  }

  bajaEvidenciasInput?.addEventListener("change", () => {
    const nuevos = Array.from(bajaEvidenciasInput.files || []);
    const existentes = new Set(
      bajaArchivosSeleccionados.map(file => `${file.name}_${file.size}_${file.lastModified}`)
    );

    nuevos.forEach(file => {
      const key = `${file.name}_${file.size}_${file.lastModified}`;
      if (!existentes.has(key)) {
        bajaArchivosSeleccionados.push(file);
        existentes.add(key);
      }
    });

    actualizarInputEvidenciasBaja();
    renderPreviewEvidenciasBaja();
  });

  document.querySelectorAll(".verBajaEvidenciasBtn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const contenedor = document.getElementById("bajaEvidenciasGuardadas");
      const arcoLabel = document.getElementById("bajaEvidenciasArco");
      if (!contenedor) return;

      if (arcoLabel) arcoLabel.textContent = btn.dataset.arco || "";
      contenedor.innerHTML = `
        <div class="detalle-evidencias-loading">
          <span class="spinner-border spinner-border-sm"></span>
          Cargando evidencias...
        </div>
      `;

      try {
        const response = await fetch(
          `../controllers/arcos_controller.php?action=get_baja_evidencias&baja_id=${encodeURIComponent(btn.dataset.bajaId || "")}`
        );
        const evidencias = await response.json();

        if (!Array.isArray(evidencias) || !evidencias.length) {
          contenedor.innerHTML = '<div class="alert alert-warning mb-0">No hay evidencias registradas.</div>';
          return;
        }

        contenedor.innerHTML = "";
        evidencias.forEach(evidencia => {
          const original = `../uploads/bajas/${encodeURIComponent(evidencia.filename)}`;
          const esPdf = String(evidencia.mimetype || "").includes("pdf");
          const card = document.createElement("div");
          card.className = "card shadow-sm border-0 evidencia-card";
          card.tabIndex = 0;
          card.title = evidencia.filename;

          if (esPdf) {
            card.classList.add("pdf-card");
            card.innerHTML = `
              <div class="pdf-preview">
                <i class="bi bi-file-earmark-pdf-fill pdf-icon"></i>
              </div>
              <div class="pdf-info">
                <p class="pdf-name mb-0"></p>
              </div>
            `;
          } else {
            const thumb = `../controllers/arcos_controller.php?action=thumb_baja_evidencia&id=${encodeURIComponent(evidencia.id)}&w=260`;
            card.innerHTML = `
              <div class="detalle-evidencia-preview">
                <img class="detalle-evidencia-thumb" loading="lazy" decoding="async" alt="Evidencia de baja">
                <i class="bi bi-image d-none"></i>
              </div>
              <div class="detalle-evidencia-info">
                <p class="pdf-name mb-0"></p>
              </div>
            `;
            const img = card.querySelector("img");
            img.src = thumb;
            img.onerror = () => {
              img.classList.add("d-none");
              img.nextElementSibling?.classList.remove("d-none");
            };
          }

          card.querySelector(".pdf-name").textContent = evidencia.filename;
          card.addEventListener("click", () => abrirVisorEvidenciaBaja(original, evidencia.filename, esPdf));
          card.addEventListener("keydown", event => {
            if (event.key === "Enter" || event.key === " ") {
              event.preventDefault();
              abrirVisorEvidenciaBaja(original, evidencia.filename, esPdf);
            }
          });
          contenedor.appendChild(card);
        });
      } catch (error) {
        contenedor.innerHTML = '<div class="alert alert-danger mb-0">No se pudieron cargar las evidencias.</div>';
      }
    });
  });

  document.querySelectorAll(".verArcosVinculadosBtn, .verEnlacesVinculadosBtn").forEach(btn => {
    btn.addEventListener("click", () => {
      let arcos = [];
      let enlaces = [];
      let saltos = [];
      try {
        arcos = JSON.parse(btn.dataset.arcos || "[]");
      } catch (error) {
        arcos = [];
      }
      try {
        enlaces = JSON.parse(btn.dataset.enlaces || "[]");
      } catch (error) {
        enlaces = [];
      }
      try {
        saltos = JSON.parse(btn.dataset.saltos || "[]");
      } catch (error) {
        saltos = [];
      }
      renderArcosVinculadosModal(
        btn.dataset.nombre || "",
        Array.isArray(arcos) ? arcos : [],
        Array.isArray(enlaces) ? enlaces : [],
        Array.isArray(saltos) ? saltos : []
      );
    });
  });

  function abrirVisorEvidenciaBaja(src, nombre, esPdf) {
    const body = document.getElementById("bajaEvidenciaVisorBody");
    const titulo = document.getElementById("bajaEvidenciaVisorTitulo");
    const modalEl = document.getElementById("modalBajaEvidenciaVisor");
    if (!body || !modalEl) return;

    const galeriaEl = document.getElementById("modalBajaEvidencias");
    if (galeriaEl) {
      bootstrap.Modal.getInstance(galeriaEl)?.hide();
    }

    if (titulo) titulo.textContent = nombre || "Evidencia";
    body.innerHTML = "";

    if (esPdf) {
      const iframe = document.createElement("iframe");
      iframe.src = src;
      iframe.className = "baja-evidencia-pdf";
      iframe.title = nombre || "Documento PDF";
      body.appendChild(iframe);
    } else {
      const img = document.createElement("img");
      img.src = src;
      img.alt = nombre || "Evidencia de baja";
      img.className = "baja-evidencia-full";
      body.appendChild(img);
    }

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
});

window.addEventListener("resize", () => {
  Object.keys(config).forEach(renderPagination);
});

document.addEventListener("DOMContentLoaded", () => {
  const STORAGE_KEY = "repuve_arcos_active_tab";
  const botones = document.querySelectorAll(".tabla-toggle-btn");
  const vistas = document.querySelectorAll(".arcos-table-view");

  function normalizarTab(targetId) {
    if (!targetId) return null;
    const lower = String(targetId).trim().toLowerCase().replace("#", "").replace("tableview", "");
    if (lower === "infra" || lower === "puentes" || lower === "sitios" || lower === "puente" || lower === "sitio") {
      return "tableViewInfra";
    }
    if (lower === "bajas" || lower === "baja") {
      return "tableViewBajas";
    }
    if (lower === "arcos" || lower === "arco") {
      return "tableViewArcos";
    }
    if (["tableViewArcos", "tableViewBajas", "tableViewInfra"].includes(targetId)) {
      return targetId;
    }
    return null;
  }

  function cambiarTabla(targetId, guardar = true, scroll = false) {
    const validTarget = normalizarTab(targetId) || "tableViewArcos";

    vistas.forEach(vista => {
      vista.classList.toggle("d-none", vista.id !== validTarget);
    });

    botones.forEach(btn => {
      const activo = btn.dataset.tableViewTarget === validTarget;
      const esInfra = btn.dataset.tableViewTarget === "tableViewInfra";
      const esBajas = btn.dataset.tableViewTarget === "tableViewBajas";
      btn.classList.toggle("active", activo);
      btn.classList.toggle("btn-success", activo && !esInfra && !esBajas);
      btn.classList.toggle("btn-primary", activo && esInfra);
      btn.classList.toggle("btn-danger", activo && esBajas);
      btn.classList.toggle("btn-outline-success", !activo && !esInfra && !esBajas);
      btn.classList.toggle("btn-outline-primary", !activo && esInfra);
      btn.classList.toggle("btn-outline-danger", !activo && esBajas);
      btn.classList.remove(
        activo
          ? (esInfra ? "btn-outline-primary" : esBajas ? "btn-outline-danger" : "btn-outline-success")
          : (esInfra ? "btn-primary" : esBajas ? "btn-danger" : "btn-success")
      );
    });

    if (guardar) {
      try {
        localStorage.setItem(STORAGE_KEY, validTarget);
      } catch (e) {}

      try {
        const url = new URL(window.location);
        url.searchParams.set("tab", validTarget);
        window.history.replaceState(null, "", url.toString());
      } catch (e) {}
    }

    if (scroll) {
      document.getElementById(validTarget)?.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    const tableIdByView = {
      tableViewArcos: "ArcosTable",
      tableViewBajas: "BajasTable",
      tableViewInfra: "InfraTable"
    };
    renderPagination(tableIdByView[validTarget] || "ArcosTable");
  }

  botones.forEach(btn => {
    btn.addEventListener("click", () => cambiarTabla(btn.dataset.tableViewTarget, true, false));
  });

  // Determinar pestaña inicial al cargar la página:
  // 1. Parámetro en URL (?tab=...)
  // 2. Hash en URL (#...)
  // 3. localStorage previo
  // 4. Default: tableViewArcos
  const urlParams = new URLSearchParams(window.location.search);
  const tabParam = normalizarTab(urlParams.get("tab"));
  const hashParam = normalizarTab(window.location.hash);
  let storedTab = null;
  try {
    storedTab = normalizarTab(localStorage.getItem(STORAGE_KEY));
  } catch (e) {}

  const initialTab = tabParam || hashParam || storedTab || "tableViewArcos";
  cambiarTabla(initialTab, true, false);

  // Asegurar paginación inicial para todas las tablas
  Object.keys(config).forEach(renderPagination);
});

function obtenerFechaMaterial(m) {
  return m.fecha_mantenimiento || m.fecha_instalacion || "1900-01-01";
}

function fechaMaterialKey(fecha) {
  return String(fecha || "1900-01-01").slice(0, 10);
}

function compararFechasMaterial(a, b) {
  return new Date(fechaMaterialKey(b)) - new Date(fechaMaterialKey(a));
}

function formatearFechaHoraMaterial(fecha) {
  if (!fecha) return "";

  const texto = String(fecha).trim();
  const normalizada = texto.includes("T") ? texto : texto.replace(" ", "T");
  const date = new Date(normalizada);

  if (Number.isNaN(date.getTime())) {
    return texto;
  }

  const fechaFormateada = date.toLocaleDateString("es-MX", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  });

  const tieneHora = /[T\s]\d{2}:\d{2}/.test(texto);
  if (!tieneHora) {
    return fechaFormateada;
  }

  return `${fechaFormateada} ${date.toLocaleTimeString("es-MX", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false
  })}`;
}

function textoFechaMaterial(m) {
  const fechaMantenimiento = m?.fecha_mantenimiento;
  const fechaInstalacion = m?.fecha_instalacion;

  if (fechaMantenimiento) {
    return `Instalado por mantenimiento el: ${formatearFechaHoraMaterial(fechaMantenimiento)}`;
  }

  if (fechaInstalacion) {
    return `Instalado el: ${formatearFechaHoraMaterial(fechaInstalacion)}`;
  }

  return "";
}

function obtenerSeriesMaterial(m) {
  if (Array.isArray(m?.series)) {
    return m.series.filter(Boolean);
  }

  if (m?.serie && String(m.serie).trim() !== "") {
    return [String(m.serie).trim()];
  }

  return [];
}

function ordenarPorRelacionMaterial(a, b) {
  const relA = Number(a.relacion_id || a.id || 0);
  const relB = Number(b.relacion_id || b.id || 0);

  if (relA !== relB) {
    return relA - relB;
  }

  return String(a.serie || "").localeCompare(String(b.serie || ""));
}

function obtenerRelacionOriginalComponente(m) {
  if (!m) return "";

  if (m.arco_material_id && Number(m.arco_material_id) > 0) {
    return String(m.arco_material_id);
  }

  if (!m.fecha_mantenimiento && m.relacion_id && Number(m.relacion_id) > 0) {
    return String(m.relacion_id);
  }

  return "";
}

function obtenerClaveGrupoComponente(m) {
  const relacionOriginal = obtenerRelacionOriginalComponente(m);
  if (relacionOriginal) {
    return `rel_${relacionOriginal}`;
  }

  return `${m.material}_${m.medida}`;
}

function materialEstaRetirado(m) {
  return String(m?.accion || '').toLowerCase() === 'retiro';
}

function buscarConOrden(input) {
  // filtrar tabla como siempre
  filterTable('searchArcos', 'ArcosTable');

  // guardar búsqueda en URL sin recargar
  const url = new URL(window.location.href);
  url.searchParams.set("search", input.value);

  // mantener el orden actual
  const order = url.searchParams.get("order") || "desc";
  url.searchParams.set("order", order);

  window.history.replaceState(null, "", url.toString());
}

document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.verMaterialesBtn');
  if (!btn) return;

  const contenedor = document.getElementById("contenedorMateriales");
  if (!contenedor) return;

  try {
    if (!btn.dataset.nuevos && !btn.dataset.anteriores && !btn.dataset.infraestructura) {
      contenedor.innerHTML = `
        <div class="text-center text-muted p-4">
          <div class="spinner-border text-primary mb-2" role="status"></div>
          <div>Cargando componentes...</div>
        </div>
      `;

      try {
        const arcoId = btn.dataset.id || btn.getAttribute('data-id') || "";
        const res = await fetch(`../controllers/arcos_controller.php?action=get_componentes&id=${encodeURIComponent(arcoId)}`);
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        btn.dataset.nuevos = JSON.stringify(data.nuevos || []);
        btn.dataset.anteriores = JSON.stringify(data.anteriores || []);
        btn.dataset.infraestructura = JSON.stringify(data.infraestructura || []);
      } catch (error) {
        console.error("Error al obtener componentes:", error);
        contenedor.innerHTML = `
          <div class="alert alert-danger mb-0">
            No se pudieron cargar los componentes: ${error.message || "Error desconocido"}
          </div>
        `;
        return;
      }
    }

    let nuevos = JSON.parse(btn.dataset.nuevos || "[]");       // 🔴 PRINCIPALES
    let anteriores = JSON.parse(btn.dataset.anteriores || "[]"); // 🔽 HISTORIAL
    let infraestructuras = JSON.parse(btn.dataset.infraestructura || "[]");

    if ((!anteriores || anteriores.length === 0) && (!nuevos || nuevos.length === 0) && (!infraestructuras || infraestructuras.length === 0)){
      contenedor.innerHTML = `
        <div class="text-center p-3">
          <span class="badge bg-warning text-dark">
            <i class="bi bi-exclamation-circle"></i> Sin materiales
          </span>
        </div>`;
      return;
    }

    // 🔽 AGRUPAR ANTERIORES
    let anterioresMap = {};

    anteriores.forEach(m => {
      let key = m.material + "_" + m.medida + "_" + (m.fecha_instalacion || "");

      if (!anterioresMap[key]) {
        anterioresMap[key] = {
          material: m.material,
          medida: m.medida,
          cantidad: 0,
          series: [],
          ip: m.ip || "",
          mac: m.mac || "",
          foto: m.foto,
          id: m.id,
          fecha_instalacion: m.fecha_instalacion
        };
      }

      anterioresMap[key].cantidad += parseFloat(m.cantidad || 0);

      if (m.serie) {
        anterioresMap[key].series.push(m.serie);
      }
    });

    // 🔴 AGRUPAR NUEVOS
    let nuevosMap = {};

    nuevos.forEach(m => {
      let key = m.material + "_" + m.medida + "_" + (m.fecha_mantenimiento || "");

      if (!nuevosMap[key]) {
        nuevosMap[key] = {
          material: m.material,
          medida: m.medida,
          cantidad: 0,
          series: [],
          ip: m.ip || "",
          mac: m.mac || "",
          foto: m.foto,
          fecha_mantenimiento: m.fecha_mantenimiento
        };
      }

      nuevosMap[key].cantidad += parseFloat(m.cantidad || 0);

      if (m.serie) {
        nuevosMap[key].series.push(m.serie);
      }
    });

// =======================================
// OBTENER SOLO EL MATERIAL ACTUAL
// =======================================

let materiales = [];

// TODOS los materiales agrupados
let todos = [...anteriores, ...nuevos];

// AGRUPAR POR TIPO DE MATERIAL
let materialesAgrupados = {};

todos.forEach(m => {

    let key = obtenerClaveGrupoComponente(m);

    // FECHA MÁS RECIENTE
    let fecha = obtenerFechaMaterial(m);

    if (!materialesAgrupados[key]) {
        materialesAgrupados[key] = [];
    }

    materialesAgrupados[key].push({
        ...m,
        fecha_real: fecha,
        fecha_key: fechaMaterialKey(fecha)
    });

});

// RECORRER CADA MATERIAL
Object.keys(materialesAgrupados).forEach(key => {

    let lista = materialesAgrupados[key];

    // ORDENAR DEL MÁS NUEVO AL MÁS VIEJO
    lista.sort((a, b) => compararFechasMaterial(a.fecha_real, b.fecha_real));

    // TODOS LOS DE LA FECHA MAS RECIENTE SON ACTUALES
    let fechaActual = lista[0]?.fecha_key || "1900-01-01";
    let actuales = lista
      .filter(m => m.fecha_key === fechaActual)
      .filter(m => !materialEstaRetirado(m))
      .sort(ordenarPorRelacionMaterial);

    if (!actuales.length) {
      return;
    }

    // LOS DEMÁS SON HISTORIAL
    let historialPorFecha = {};
    lista
      .filter(m => m.fecha_key !== fechaActual)
      .forEach(m => {
        if (!historialPorFecha[m.fecha_key]) {
          historialPorFecha[m.fecha_key] = [];
        }
        historialPorFecha[m.fecha_key].push(m);
      });

    Object.keys(historialPorFecha).forEach(fecha => {
      historialPorFecha[fecha].sort(ordenarPorRelacionMaterial);
    });

    let fechasHistorial = Object.keys(historialPorFecha)
      .sort((a, b) => compararFechasMaterial(a, b));

    actuales.forEach((actual, posicion) => {
      let historial = fechasHistorial
        .map(fecha => historialPorFecha[fecha][posicion])
        .filter(Boolean);
      let seriesActuales = obtenerSeriesMaterial(actual);

      if (actual.fecha_mantenimiento && seriesActuales.length) {
        const serieActualKey = seriesActuales.join("|");
        const serieCopiadaDelAnterior = historial.some(anterior =>
          obtenerSeriesMaterial(anterior).join("|") === serieActualKey
        );

        if (serieCopiadaDelAnterior) {
          seriesActuales = [];
        }
      }

      materiales.push({
        tipo: historial.length ? "cambiado" : "existente",
        nuevo: {
            material: actual.material,
            medida: actual.medida,
            cantidad: actual.cantidad,
            series: seriesActuales,
            ip: actual.ip || "",
            mac: actual.mac || "",
            foto: actual.foto,
            fecha_mantenimiento: actual.fecha_mantenimiento,
            fecha_instalacion: actual.fecha_instalacion,
            relacion_id: actual.relacion_id || actual.id || ""
        },
        historial: historial
      });
    });

});


    let html = `<div class="material-grid">`;

    materiales.forEach((item, index) => {

    let m = item.nuevo;
    let historial = item.historial || [];
    let anterior = historial.length ? historial[0] : null;

    console.log("Material anterior:", anterior);
    let esNuevo = item.tipo === "cambiado";
    let histId = `hist_${index}_${Math.random().toString(36).substr(2, 9)}`;

    // console.log('Procesando material:', m, 'Anterior:', anterior);
    // let histId = `hist_${index}_${item.anterior.id}`;
    // console.log('ID del historial:', histId);

    let imagenHtml = (!m.foto || m.foto === "null" || m.foto.trim() === "")
      ? `<div class="d-flex align-items-center justify-content-center bg-secondary text-white material-img">Sin foto</div>`
      : `<img src="../uploads/materiales/${m.foto}" class="material-img">`;

      let medida2 = m.medida === 'm' ? 'metros' : m.medida === 'pz' ? 'piezas' : m.medida;
      if (medida2 === 'piezas' && m.cantidad === 1) medida2 = "pieza";

      let seriesList = obtenerSeriesMaterial(m);
      let totalSeries = seriesList.length;
      let ipVal = (m.ip || "").trim();
      let macVal = (m.mac || "").trim();
      let hasTechData = totalSeries > 0 || ipVal !== "" || macVal !== "";

      let techDataHtml = "";
      if (hasTechData) {
        let techCollapseId = `tech_${index}_${Math.random().toString(36).substr(2, 7)}`;
        let summaryParts = [];
        if (totalSeries > 0) summaryParts.push(`${totalSeries} serie${totalSeries > 1 ? 's' : ''}`);
        if (ipVal) summaryParts.push("IP");
        if (macVal) summaryParts.push("MAC");

        techDataHtml = `
          <div class="mt-2 pt-2 border-top">
            <div class="d-flex justify-content-between align-items-center gap-1">
              <span class="text-muted d-flex align-items-center" style="font-size: 11px;">
                <i class="bi bi-cpu text-primary me-1"></i> ${summaryParts.join(" • ")}
              </span>
              <button class="btn btn-sm btn-outline-primary tech-toggle-btn"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#${techCollapseId}">
                Ver detalles <i class="bi bi-chevron-down"></i>
              </button>
            </div>

            <div class="collapse mt-2" id="${techCollapseId}">
              <div class="tech-details-panel">
                ${totalSeries > 0 ? `
                  <div class="mb-1">
                    <small class="text-muted d-block fw-semibold mb-1" style="font-size: 10.5px;">Series (${totalSeries}):</small>
                    <div class="d-flex flex-wrap gap-1">
                      ${seriesList.map(s => `<span class="series-chip">${escapeHtml(s)}</span>`).join("")}
                    </div>
                  </div>
                ` : ""}
                ${ipVal ? `
                  <div class="mt-1">
                    <span class="tech-badge"><i class="bi bi-hdd-network text-primary me-1"></i>IP: <strong class="ms-1">${escapeHtml(ipVal)}</strong></span>
                  </div>
                ` : ""}
                ${macVal ? `
                  <div class="mt-1">
                    <span class="tech-badge"><i class="bi bi-ethernet text-success me-1"></i>MAC: <strong class="ms-1">${escapeHtml(macVal)}</strong></span>
                  </div>
                ` : ""}
              </div>
            </div>
          </div>
        `;
      }

      let fechaActualHtml = textoFechaMaterial(m);

      let anteriorTechHtml = "";
      if (anterior) {
        let antSeries = obtenerSeriesMaterial(anterior);
        let antIp = (anterior.ip || "").trim();
        let antMac = (anterior.mac || "").trim();
        let hasAntTech = antSeries.length > 0 || antIp !== "" || antMac !== "";
        if (hasAntTech) {
          anteriorTechHtml = `
            <div class="tech-details-panel mt-2">
              ${antSeries.length > 0 ? `
                <div class="mb-1">
                  <small class="text-muted d-block fw-semibold mb-1" style="font-size: 10.5px;">Series:</small>
                  <div class="d-flex flex-wrap gap-1">
                    ${antSeries.map(s => `<span class="series-chip">${escapeHtml(s)}</span>`).join("")}
                  </div>
                </div>
              ` : ""}
              ${antIp ? `
                <div class="mt-1">
                  <span class="tech-badge"><i class="bi bi-hdd-network text-primary me-1"></i>IP: <strong class="ms-1">${escapeHtml(antIp)}</strong></span>
                </div>
              ` : ""}
              ${antMac ? `
                <div class="mt-1">
                  <span class="tech-badge"><i class="bi bi-ethernet text-success me-1"></i>MAC: <strong class="ms-1">${escapeHtml(antMac)}</strong></span>
                </div>
              ` : ""}
            </div>
          `;
        }
      }

      html += `
         ${esNuevo 
                  ? `<div class="material-card border border-danger">` 
                  : `<div class="material-card border">`
                }
          <div class="d-flex align-items-center gap-2 mb-2 justify-content-between">
            <div class="d-flex align-items-center gap-2">
              ${imagenHtml}
              <div>
                <div class="fw-bold">${escapeHtml(m.material)}</div>
              </div>
              ${esNuevo 
                  ? `<span class="badge bg-danger">NUEVO</span>` 
                  : ``
                }
            </div>

            <div class="d-flex align-items-center">
              <span class="badge bg-success fs-6 px-3 py-2 me-2">
                ${m.cantidad}
              </span>
              <span class="text-muted small">${medida2}</span>
            </div>
          </div>

          ${fechaActualHtml ? `
            <div class="text-muted small">
              <i class="bi bi-calendar-event"></i> ${fechaActualHtml}
            </div>
          ` : ""}

          ${techDataHtml}

          ${ esNuevo && anterior ? `
            <div class="mt-2 pt-1 border-top">
              <button class="btn btn-sm btn-outline-secondary ver-anterior-btn w-100"
                      data-anterior='${JSON.stringify(historial)}'>
                <i class="bi bi-clock-history me-1"></i> Ver historial anterior
              </button>

              <div class="collapse material mt-2" id="${histId}">
                <div class="material-card border border-secondary mt-2 p-2 rounded">
                  <div class="d-flex align-items-center gap-2 justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                      ${
                        (!anterior.foto || anterior.foto === "null")
                        ? `<div class="material-img bg-secondary text-white d-flex align-items-center justify-content-center">Sin foto</div>`
                        : `<img src="../uploads/materiales/${escapeHtml(anterior.foto)}" class="material-img">`
                      }
                      <div>
                        <div class="fw-bold">${escapeHtml(anterior.material)}</div>
                        <small class="text-muted">Material anterior</small>
                      </div>
                    </div>

                    <div>
                      <span class="badge bg-secondary fs-6 px-3 py-2">
                        ${escapeHtml(anterior.cantidad)}
                      </span>
                    </div>
                  </div>

                  ${anteriorTechHtml}
                  ${textoFechaMaterial(anterior) ? `<div class="text-muted small mt-2"><i class="bi bi-calendar-event"></i> ${textoFechaMaterial(anterior)}</div>` : ""}

                </div>
              </div>
            </div>
          ` : ``}
        </div>
      `;
    });

    html += `</div>`;
    html += renderInfraestructurasComponentes(infraestructuras);
    contenedor.innerHTML = html;

    // ✅ ACTIVAR COLLAPSE DE SERIES Y DATOS TECNICOS
    contenedor.querySelectorAll('.collapse').forEach(collapseEl => {
      collapseEl.addEventListener('show.bs.collapse', function () {
        let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
        if (btn && btn.classList.contains('tech-toggle-btn')) {
          btn.innerHTML = 'Ocultar <i class="bi bi-chevron-up"></i>';
        } else if (btn && btn.classList.contains('series-btn')) {
          btn.innerHTML = 'Ocultar series <i class="bi bi-chevron-up"></i>';
        }
      });

      collapseEl.addEventListener('hide.bs.collapse', function () {
        let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
        if (btn && btn.classList.contains('tech-toggle-btn')) {
          btn.innerHTML = 'Ver detalles <i class="bi bi-chevron-down"></i>';
        } else if (btn && btn.classList.contains('series-btn')) {
          btn.innerHTML = 'Ver series <i class="bi bi-chevron-down"></i>';
        }
      });
    });

    // ✅ ACTIVAR TOOLTIP BOOTSTRAP
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
  } catch (renderError) {
    console.error("Error al renderizar componentes:", renderError);
    contenedor.innerHTML = `
      <div class="alert alert-danger mb-0">
        Error al mostrar componentes: ${renderError.message || "Error desconocido"}
      </div>
    `;
  }
});


document.addEventListener("click", function(e) {

      if (e.target.closest(".ver-anterior-btn")) {

        let btn = e.target.closest(".ver-anterior-btn");
        let historial = JSON.parse(btn.dataset.anterior || "[]");


        let modalBody = document.getElementById("modalAnteriorBody");

        if (!historial.length) {
    modalBody.innerHTML = `
        <div class="alert alert-warning">
            Sin historial de materiales.
        </div>
    `;
} else {
   // AGRUPAR HISTORIAL POR FECHA
  let grupos = {};
  historial.forEach(mat => {
      let fecha =
          mat.fecha_mantenimiento ||
          mat.fecha_instalacion ||
          "Sin fecha";
      if (!grupos[fecha]) {
          grupos[fecha] = [];
      }
      grupos[fecha].push(mat);
  });

  // ORDENAR FECHAS DESCENDENTE
  let fechas = Object.keys(grupos).sort((a, b) =>
    new Date(b) - new Date(a)
  );

  modalBody.innerHTML = fechas.map(fecha => {
    let materiales = grupos[fecha];
    return `
        <div class="mb-4">
            <div class="bg-dark text-white px-3 py-2 rounded mb-2">
                <i class="bi bi-tools"></i>
                Fecha:
                ${
                    fecha !== "Sin fecha"
                    ? formatearFechaHoraMaterial(fecha)
                    : "Sin fecha"
                }
            </div>

            <div class="material-grid">

            ${materiales.map(anterior => `
                <div class="material-card border border-secondary p-3 rounded">
                    <div class="d-flex gap-3 align-items-center">
                        ${
                            (!anterior.foto || anterior.foto === "null")
                            ? ` <div class="material-img bg-secondary text-white d-flex align-items-center justify-content-center">
                                    Sin foto
                                </div> `
                            : `<img src="../uploads/materiales/${anterior.foto}"
                                     class="material-img">`
                        }
                        <div>
                            <h6 class="mb-1">
                                ${anterior.material}
                            </h6>
                            <div>
                                <strong>Cantidad:</strong>
                                ${anterior.cantidad}
                            </div>
                             <div class="mt-1">
                                 <strong>Serie:</strong>
                                 ${obtenerSeriesMaterial(anterior).map(s => `<span class="series-chip">${escapeHtml(s)}</span>`).join("") || '<span class="text-muted small">Sin serie</span>'}
                             </div>
                             ${anterior.ip ? `
                               <div class="mt-1">
                                 <span class="tech-badge"><i class="bi bi-hdd-network text-primary me-1"></i>IP: <strong class="ms-1">${escapeHtml(anterior.ip)}</strong></span>
                               </div>
                             ` : ""}
                             ${anterior.mac ? `
                               <div class="mt-1">
                                 <span class="tech-badge"><i class="bi bi-ethernet text-success me-1"></i>MAC: <strong class="ms-1">${escapeHtml(anterior.mac)}</strong></span>
                               </div>
                             ` : ""}
                             <div>
                                 <strong>Fecha:</strong>
                                 ${textoFechaMaterial(anterior) || "Sin fecha"}
                             </div>
                         </div>
                    </div>
                </div>
            `).join("")}
            </div>
        </div>
    `;
  }).join("");
}
    ModalManager.show("modalAnterior");
      }
    });

document.addEventListener("click", function (e) {
  if (e.target.closest(".toggle-materials")) {
    let btn = e.target.closest(".toggle-materials");
    let target = document.querySelector(btn.dataset.target);

    target.classList.toggle("d-none");

    // Cambiar texto del botón según estado
    if (target.classList.contains("d-none")) {
      btn.innerHTML = '<i class="bi bi-box-seam"></i> Ver materiales';
      btn.classList.remove("btn-primary");
      btn.classList.add("btn-outline-primary");
    } else {
      btn.innerHTML = '<i class="bi bi-eye-slash"></i> Ocultar materiales';
      btn.classList.remove("btn-outline-primary");
      btn.classList.add("btn-primary");
    }
  }
});


document.querySelectorAll(".material-select").forEach(select => {
  select.addEventListener("change", function () {

    let medida = this.selectedOptions[0].dataset.medida;
    let inputCantidad = this.closest(".row").querySelector(".cantidad-input");

    if (medida === "pz") {
      inputCantidad.placeholder = "Ejem: 10 (piezas)";
    } else if (medida === "m") {
      inputCantidad.placeholder = "Ejem: 5 (metros)";
    } else {
      inputCantidad.placeholder = "Cantidad";
    }
  });
});

document.addEventListener("change", function (e) {
  if (e.target.classList.contains("sinserie-input")) {
    const inputSerie = e.target.closest(".d-flex").querySelector(".serie-input");

    if (e.target.checked) {
      inputSerie.value = "";
      inputSerie.classList.remove("d-none");

    } else {
      inputSerie.classList.add("d-none");
    }
  }
});


document.addEventListener("DOMContentLoaded", () => {
  const materialContainer = document.getElementById("materialesContainer");
  const addMaterialBtn = document.getElementById("addMaterial");
  // addMaterialBtn.addEventListener("click", () => {
  //   const newRow = materialContainer.firstElementChild.cloneNode(true);
  //   newRow.querySelectorAll("input, select").forEach(el => el.value = "");
  //   newRow.querySelectorAll("input[type='checkbox']").forEach(cb => cb.checked = false);
  //   newRow.querySelector(".serie-input").classList.add("d-none");
  //   newRow.querySelector(".cantidad-input").value = 1;

  //   // newRow.querySelector(".sinserie_input").checked = false;
  //   materialContainer.appendChild(newRow);
  // });

  document.addEventListener("change", function (e) {
    if (e.target.classList.contains("material-select")) {
      let medida = e.target.selectedOptions[0].dataset.medida || "";
      let medidaSpan = e.target.closest(".material-row")
        .querySelector(".medida-input");
      let cantidad = e.target.closest(".material-row")
        .querySelector(".cantidadform");
      let medida2 = "";
      if (medida === "m") {
        medida2 = "metros";
        cantidad.classList.remove("d-none");
      } else if (medida === "pz") {
        medida2 = "piezas";
        cantidad.classList.add("d-none");

      } else {
        medida2 = medida;
        cantidad.classList.remove("d-none");

      }

      const cantidadInput = e.target.closest(".material-row")
        .querySelector(".cantidad-input");
      if (cantidadInput) {
        cantidadInput.min = medida === "pz" ? "1" : "0.1";
        cantidadInput.step = medida === "pz" ? "1" : "0.1";
        if (medida === "pz" || !cantidadInput.value || parseFloat(cantidadInput.value) <= 0) {
          cantidadInput.value = "1";
        }
      }

      e.target.closest(".material-row")
        .querySelector(".medida-input").innerHTML = medida2;
      medidaSpan.classList.remove("d-none");
    }
  });


  document.addEventListener("click", e => {
    if (e.target.closest(".remove-material")) {
      const row = e.target.closest(".material-row");
      const total = materialContainer.querySelectorAll(".material-row").length;
      if (total > 1) row.remove();
    }
  });

  document.querySelectorAll(".editarArcoBtn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;
      const nombre = document.getElementById("editar_nombre");
      const ubicacion = document.getElementById("editar_ubicacion");
      const fecha = document.getElementById("editar_fecha");
      const materialesContainer = document.getElementById("listaMaterialesEditar");
      document.getElementById("editar_id").value = id;

      if (materialesContainer) {
        materialesContainer.innerHTML = `
          <div class="text-center text-muted py-4">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="mt-2 mb-0">Cargando datos...</p>
          </div>`;
      }
      try {
        const res = await fetch(`../controllers/arcos_controller.php?action=get&id=${id}`);
        if (!res.ok) {
          throw new Error(`Error en servidor (HTTP ${res.status})`);
        }
        const data = await res.json();
        if (data.error) {
          throw new Error(data.error);
        }

        if (nombre) nombre.value = data.nombre || "";
        if (ubicacion) ubicacion.value = data.ubicacion_id || "";
        if (fecha) fecha.value = (data.fecha_instalacion || "").replace(" ", "T").substring(0, 16);

        // lat/lng (para editar)
        const latEl = document.getElementById("editar_lat");
        const lngEl = document.getElementById("editar_lng");
        if (latEl) latEl.value = data.lat ?? "";
        if (lngEl) lngEl.value = data.lng ?? "";

        const infraEl = document.getElementById("editar_infra_vinculada");
        if (infraEl) {
          const ubiId = String(data.ubicacion_id || "");
          const infraVal = (data.infra_ids && data.infra_ids.length > 0) ? String(data.infra_ids[0]) : "";
          Array.from(infraEl.options).forEach(opt => {
            if (!opt.value) { opt.style.display = ""; return; }
            const match = !ubiId || String(opt.dataset.ubicacionId || "") === ubiId;
            opt.style.display = match ? "" : "none";
          });
          infraEl.querySelectorAll("optgroup").forEach(og => {
            const hasVisible = Array.from(og.querySelectorAll("option")).some(o => o.style.display !== "none");
            og.style.display = hasVisible ? "" : "none";
          });
          infraEl.value = infraVal;
        }

        if (typeof syncSmartCardPicker === "function") {
          syncSmartCardPicker("editar_ubicacion");
          syncSmartCardPicker("editar_infra_vinculada");
        } else if (typeof syncSearchableSelect === "function") {
          syncSearchableSelect("editar_ubicacion");
          syncSearchableSelect("editar_infra_vinculada");
        }

        materialesEditandoArco = (data.materiales || []).map(mat => ({
          id: mat.material_id,
          nombre: mat.nombre,
          medida: mat.medida,
          serie: mat.serie || "",
          ip: mat.ip || "",
          mac: mat.mac || "",
          cantidad: (mat.medida === "pz" ? "1" : (mat.cantidad || "1")),
          foto: mat.foto || "",
          relacion_id: mat.relacion_id || ""
        }));
        materialSeleccionadoEditarIndex = null;
        renderMaterialesEditarArco();
      } catch (error) {
        console.error("Error al cargar datos del arco:", error);
        if (materialesContainer) {
          materialesContainer.innerHTML = `<div class="alert alert-danger text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error al cargar los datos: ${escapeHtml(error.message || "Error de conexión")}</div>`;
        }
      }
    });
  });

  function crearFilaMaterial(lista, seleccionado = "", cantidad = "1.0", medida = "", serie = "") {
    const div = document.createElement("div");
    div.className = "material-row d-flex align-items-center gap-2 mb-2 bg-light p-2 rounded flex-wrap";

    div.innerHTML = `
      <div class="material-left d-flex flex-wrap flex-grow-1 gap-2">

        <div class="col flex-grow-2">

          <!-- MATERIAL -->
          <div class="flex-grow-2 col" style="min-width: 200px;">
            <label class="form-label fw-semibold">Material</label>
            <select name="material_id[]" class="form-select material-select" required>
              <option value="">Seleccione material...</option>
              ${lista.map(m =>
      `<option value="${m.id}" data-medida="${m.medida}" ${m.id == seleccionado ? "selected" : ""}>
                  ${m.nombre}
                </option>`
    ).join("")}
            </select>
          </div>

          <!-- SERIE -->
          <div class="flex-grow-2 col" style="min-width: 160px;">
            <label class="form-label fw-semibold">Serie</label>
            <div class="d-flex align-items-center gap-2">

              <input type="text" name="serie[]" 
                class="form-control serie-input ${serie ? '' : 'd-none'}"
                value="${serie}" placeholder="Ingrese la serie">

              <div class="form-check">
                <input type="checkbox" name="serieactive[]" class="form-check-input sinserie-input" ${serie ? 'checked' : ''}>
                <label class="form-check-label">Tiene serie</label>
              </div>

            </div>
          </div>

          <!-- CANTIDAD -->
          
          <div style="min-width: 140px;" class="flex-grow-1 ${medida === "pz" ? 'd-none' : ''} cantidadform">
            <label class="form-label fw-semibold">Cantidad</label>
            <div class="d-flex align-items-center gap-2">
              <input type="number" name="cantidad[]" class="form-control cantidad-input"
                min="${medida === "pz" ? "1" : "0.1"}" step="${medida === "pz" ? "1" : "0.1"}" value="${cantidad > 0 ? cantidad : '1'}">
              <span class="badge bg-secondary medida-input px-3 py-2 ${medida ? '' : 'd-none'}">
                ${medida === 'm' ? 'metros' : (medida === 'pz' ? 'piezas' : medida)}
              </span>
            </div>
          </div>

        </div>
      </div>

      <!-- BOTÓN ELIMINAR -->
      <div class="material-right">
        <div class="remove-container d-flex align-items-center ms-2">
          <button type="button" class="btn btn-danger remove-material">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
      `;

    return div;
  }

  function configurarEventosMateriales(container, botonID, lista = []) {
    const btnAdd = document.getElementById(botonID);
    if (!btnAdd) return;

    btnAdd.onclick = () => {
      const firstRow = container.querySelector(".material-row");
      if (firstRow) {
        const clone = firstRow.cloneNode(true);

        // LIMPIAR VALORES
        clone.querySelectorAll("input, select").forEach(el => el.value = "");


        // LIMPIAR la medida
        const inputMedida = clone.querySelector(".medida-input");
        if (inputMedida) inputMedida.value = "";

        container.appendChild(clone);
      } else if (lista.length) {
        container.innerHTML = "";
        container.appendChild(crearFilaMaterial(lista));
      }
    };

    container.addEventListener("click", e => {
      if (e.target.closest(".remove-material") && container.querySelectorAll(".material-row").length > 1) {
        e.target.closest(".material-row").remove();
      }
    });

    container.addEventListener("change", function (e) {
      if (e.target.classList.contains("material-select")) {
        let medida = e.target.selectedOptions[0].dataset.medida || "";
        let medida2 = medida === "m" ? "metros" : medida === "pz" ? "piezas" : medida;
        e.target.closest(".material-row")
          .querySelector(".medida-input").value = medida2;

        const row = e.target.closest(".material-row");
        const cantidadForm = row.querySelector(".cantidadform");
        const cantidadInput = row.querySelector("input[name='cantidad[]']");

        if (medida === "pz") {
          cantidadForm.classList.add("d-none");
        } else {
          cantidadForm.classList.remove("d-none");
        }
        cantidadInput.min = medida === "pz" ? "1" : "0.1";
        cantidadInput.step = medida === "pz" ? "1" : "0.1";
        if (medida === "pz" || !cantidadInput.value || parseFloat(cantidadInput.value) <= 0) {
          cantidadInput.value = "1";
        }
      }
    });
  }
});

function renderInfraestructurasComponentes(infraestructuras = []) {
  if (!Array.isArray(infraestructuras) || infraestructuras.length === 0) {
    return "";
  }

  const html = infraestructuras.map((infra, index) => {
    const materiales = Array.isArray(infra.materiales) ? infra.materiales : [];
    const materialesHtml = materiales.length
      ? `<div class="material-grid infra-material-grid mt-3">
          ${materiales.map((m, matIndex) => {
            const foto = m.foto
              ? `<img src="../uploads/materiales/${escapeHtml(m.foto)}" class="material-img">`
              : `<div class="d-flex align-items-center justify-content-center bg-secondary text-white material-img">Sin foto</div>`;
            const serieChip = m.serie ? `<span class="series-chip">${escapeHtml(m.serie)}</span>` : "";
            const ipBadge = m.ip ? `<span class="tech-badge"><i class="bi bi-hdd-network text-primary me-1"></i>IP: ${escapeHtml(m.ip)}</span>` : "";
            const macBadge = m.mac ? `<span class="tech-badge"><i class="bi bi-ethernet text-success me-1"></i>MAC: ${escapeHtml(m.mac)}</span>` : "";
            const hasTech = serieChip || ipBadge || macBadge;
            const fecha = m.fecha_instalacion ? `<div class="text-muted small mt-2"><i class="bi bi-calendar-event"></i> Instalado: ${escapeHtml(formatearFechaHoraMaterial(m.fecha_instalacion))}</div>` : "";

            let matMedida = m.medida === 'm' ? 'metros' : m.medida === 'pz' ? 'piezas' : (m.medida || '');
            if (matMedida === 'piezas' && (m.cantidad == 1 || !m.cantidad)) matMedida = "pieza";

            return `
              <div class="material-card border infra-component-card" data-infra-material="${matIndex}">
                <div class="d-flex align-items-center gap-2 justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    ${foto}
                    <div>
                      <div class="fw-bold">${escapeHtml(m.material)}</div>
                      <small class="text-muted">${escapeHtml(matMedida)}</small>
                    </div>
                  </div>
                  <span class="badge bg-primary fs-6 px-3 py-2">${escapeHtml(m.cantidad || 1)}</span>
                </div>
                ${hasTech ? `<div class="mt-2 pt-2 border-top d-flex flex-wrap gap-1 align-items-center">${serieChip}${ipBadge}${macBadge}</div>` : ""}
                ${fecha}
              </div>
            `;
          }).join("")}
        </div>`
      : `<div class="alert alert-light border mb-0 mt-3">Sin componentes registrados.</div>`;

    return `
      <div class="infra-view-card">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
          <div>
            <span class="badge bg-primary mb-2">${escapeHtml(infra.tipo)}</span>
            <h6 class="fw-bold mb-1">${escapeHtml(infra.nombre)}</h6>
            ${infra.ubicacion ? `<div class="text-muted small"><i class="bi bi-geo-alt-fill"></i> ${escapeHtml(infra.ubicacion)}</div>` : ""}
          </div>
          ${(infra.lat || infra.lng) ? `<div class="text-muted small"><i class="bi bi-geo-alt"></i> ${escapeHtml(infra.lat || "")}, ${escapeHtml(infra.lng || "")}</div>` : ""}
        </div>
        ${materialesHtml}
      </div>
    `;
  }).join("");

  return `
    <div class="infra-components-section mt-4">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-broadcast-pin text-primary"></i>
        <h6 class="mb-0 fw-bold text-primary">Puentes / Sitios conectados</h6>
      </div>
      ${html}
    </div>
  `;
}

function catalogoMaterialesInfraestructura() {
  const template = document.getElementById("infraMaterialOptionsTemplate");
  if (!template) return [];

  return Array.from(template.content.querySelectorAll("option"))
    .filter(option => option.value)
    .map(option => ({
      id: option.value,
      nombre: option.textContent.trim(),
      medida: option.dataset.medida || "",
      foto: option.dataset.foto || ""
    }));
}

function buscarMaterialInfraestructura(id) {
  return catalogoMaterialesInfraestructura().find(material => String(material.id) === String(id));
}

function opcionesMaterialInfraestructura(selectedId = "") {
  const opciones = catalogoMaterialesInfraestructura();
  return [
    '<option value="">Seleccione material...</option>',
    ...opciones.map(material => `
      <option value="${escapeHtml(material.id)}" data-medida="${escapeHtml(material.medida)}" ${String(material.id) === String(selectedId) ? "selected" : ""}>
        ${escapeHtml(material.nombre)}
      </option>
    `)
  ].join("");
}

function renderInfraestructurasArco() {
  const contenedor = document.getElementById("listaInfraestructurasArco");
  if (!contenedor) return;

  if (!infraestructurasAgregadasArco.length) {
    contenedor.innerHTML = `
      <div class="empty-materials-state">
        <i class="bi bi-broadcast-pin"></i>
        <span class="fw-semibold">Ningun puente o sitio agregado</span>
      </div>
    `;
    return;
  }

  contenedor.innerHTML = infraestructurasAgregadasArco.map((infra, index) => `
    <div class="infra-edit-card" data-index="${index}">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div class="fw-bold text-primary">
          <i class="bi bi-broadcast-pin"></i> Puente / Sitio ${index + 1}
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger infra-delete" data-index="${index}">
          <i class="bi bi-trash"></i>
        </button>
      </div>

      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Tipo</label>
          <select name="infra_tipo[]" class="form-select infra-field" data-index="${index}" data-field="tipo">
            <option value="Puente/Poste" ${infra.tipo === "Puente/Poste" ? "selected" : ""}>Puente/Poste</option>
            <option value="Sitio/Torre" ${infra.tipo === "Sitio/Torre" ? "selected" : ""}>Sitio/Torre</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Nombre</label>
          <input name="infra_nombre[]" class="form-control infra-field" data-index="${index}" data-field="nombre" value="${escapeHtml(infra.nombre)}" list="infraNodosExistentes" required>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Latitud</label>
          <input name="infra_lat[]" class="form-control infra-field" data-index="${index}" data-field="lat" value="${escapeHtml(infra.lat)}">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Longitud</label>
          <input name="infra_lng[]" class="form-control infra-field" data-index="${index}" data-field="lng" value="${escapeHtml(infra.lng)}">
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
        <span class="fw-semibold">Componentes</span>
        <button type="button" class="btn btn-sm btn-outline-primary infra-add-material" data-index="${index}">
          <i class="bi bi-plus-lg"></i> Material
        </button>
      </div>

      <div class="infra-material-list">
        ${renderMaterialesInfraestructura(infra, index)}
      </div>
    </div>
  `).join("");
}

function renderMaterialesInfraestructura(infra, infraIndex) {
  const materiales = Array.isArray(infra.materiales) ? infra.materiales : [];

  if (!materiales.length) {
    return `<div class="text-muted small border rounded p-2">Sin componentes.</div>`;
  }

  return materiales.map((material, materialIndex) => {
    const catalogo = buscarMaterialInfraestructura(material.id);
    const medida = catalogo?.medida || material.medida || "";
    const esPieza = medida === "pz";

    return `
      <div class="infra-material-row" data-index="${infraIndex}" data-material-index="${materialIndex}">
        <select name="infra_material_id[${infraIndex}][]" class="form-select infra-material-select" data-index="${infraIndex}" data-material-index="${materialIndex}" required>
          ${opcionesMaterialInfraestructura(material.id)}
        </select>
        <input type="number" name="infra_cantidad[${infraIndex}][]" class="form-control infra-material-input ${esPieza ? "d-none" : ""}"
          data-index="${infraIndex}" data-material-index="${materialIndex}" data-field="cantidad"
          min="${esPieza ? "1" : "0.1"}" step="${esPieza ? "1" : "0.1"}" value="${escapeHtml(esPieza ? "1" : (material.cantidad || "1"))}">
        <input type="text" name="infra_serie[${infraIndex}][]" class="form-control infra-material-input"
          data-index="${infraIndex}" data-material-index="${materialIndex}" data-field="serie"
          value="${escapeHtml(material.serie || "")}" placeholder="Serie">

        <input type="text" name="infra_ip[${infraIndex}][]" class="form-control infra-material-input"
          data-index="${infraIndex}" data-material-index="${materialIndex}" data-field="ip"
          value="${escapeHtml(material.ip || "")}" placeholder="IP">

        <input type="text" name="infra_mac[${infraIndex}][]" class="form-control infra-material-input"
          data-index="${infraIndex}" data-material-index="${materialIndex}" data-field="mac"
          value="${escapeHtml(material.mac || "")}" placeholder="MAC">
        

        <button type="button" class="btn btn-outline-danger infra-delete-material" data-index="${infraIndex}" data-material-index="${materialIndex}">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    `;
  }).join("");
}

let materialesEditandoInfraestructura = [];

function renderEditarInfraMateriales() {
  const contenedor = document.getElementById("editarInfraMateriales");
  if (!contenedor) return;

  if (!materialesEditandoInfraestructura.length) {
    contenedor.innerHTML = `
      <div class="empty-materials-state">
        <i class="bi bi-box-seam"></i>
        <span class="fw-semibold">Ningun material agregado</span>
      </div>
    `;
    return;
  }

  contenedor.innerHTML = materialesEditandoInfraestructura.map((material, index) => {
    const catalogo = buscarMaterialInfraestructura(material.id);
    const medida = material.medida || catalogo?.medida || "";
    const nombre = escapeHtml(material.nombre || catalogo?.nombre || "");
    const cantidad = escapeHtml(medida === "pz" ? "1" : (material.cantidad || "1"));
    const serie = escapeHtml(material.serie || "");
    const ip = escapeHtml(material.ip || "");
    const mac = escapeHtml(material.mac || "");
    const foto = escapeHtml(material.foto || catalogo?.foto || "");
    const imagen = foto
      ? `<img src="../uploads/materiales/${foto}" class="material-image" alt="${nombre}">`
      : `<div class="material-placeholder"><i class="bi bi-box-seam"></i></div>`;

    const tieneDetalles = Boolean(serie || ip || mac);

    return `
      <div class="material-card-added shadow-sm is-edit-list" data-index="${index}">
        <div class="material-card-buttons">
          <button type="button" class="material-edit editar-infra-material-edit" data-index="${index}" title="Editar material">
            <i class="bi bi-pencil"></i>
          </button>
          <button type="button" class="material-delete editar-infra-material-delete" data-index="${index}" title="Eliminar material">
            <i class="bi bi-trash"></i>
          </button>
        </div>

        <div class="material-card-top">
          <div class="material-image-container">${imagen}</div>
          <div class="flex-grow-1 min-w-0">
            <div class="material-title text-capitalize">${nombre}</div>
            <div class="material-subtitle d-flex align-items-center flex-wrap gap-2">
              <span>${medida === "pz" ? "Por pieza" : `${cantidad} ${escapeHtml(medida)}`}</span>
              ${tieneDetalles ? `
                <button type="button" class="material-details-toggle" title="Mostrar u ocultar detalles (Serie, IP, MAC)">
                  <i class="bi bi-eye"></i>
                  <span>Datos</span>
                </button>
              ` : ""}
            </div>
          </div>
        </div>

        ${tieneDetalles ? `
          <div class="material-data is-hidden">
            ${serie ? `<span class="material-tag material-tag-serie" title="Serie: ${serie}"><i class="bi bi-upc-scan me-1"></i>${serie}</span>` : ""}
            ${ip ? `<span class="material-tag material-tag-ip" title="IP: ${ip}"><i class="bi bi-hdd-network me-1"></i>IP: ${ip}</span>` : ""}
            ${mac ? `<span class="material-tag material-tag-mac" title="MAC: ${mac}"><i class="bi bi-ethernet me-1"></i>MAC: ${mac}</span>` : ""}
          </div>
        ` : ""}

        <input type="hidden" name="material_id[]" value="${escapeHtml(material.id)}">
        <input type="hidden" name="relacion_id[]" value="${escapeHtml(material.relacion_id || "")}">
        <input type="hidden" name="cantidad[]" value="${cantidad}">
        <input type="hidden" name="serie[]" value="${serie}">
        <input type="hidden" name="ip[]" value="${ip}">
        <input type="hidden" name="mac[]" value="${mac}">

      </div>
    `;
  }).join("");
}

function renderChipsEditarInfra(tipo) {
  if (!tipo || tipo === 'arcos') {
    const chipsCont = document.getElementById("chipsEditarInfraArcos");
    if (chipsCont) {
      const checkedArcos = Array.from(document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check:checked"));
      if (checkedArcos.length === 0) {
        chipsCont.innerHTML = "";
        chipsCont.classList.add("d-none");
      } else {
        chipsCont.classList.remove("d-none");
        chipsCont.innerHTML = checkedArcos.map(chk => {
          const card = chk.closest(".infra-node-card");
          const nombre = card?.dataset.nombre || chk.value;
          return `
            <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 py-1 px-2">
              <i class="bi bi-bounding-box-circles"></i> ${escapeHtml(nombre)}
              <button type="button" class="btn-close btn-close-xs ms-1 chip-remove-btn" data-target-tipo="arcos" data-id="${escapeHtml(chk.value)}" title="Quitar"></button>
            </span>
          `;
        }).join("");
      }
    }
  }

  if (!tipo || tipo === 'sitios') {
    const chipsCont = document.getElementById("chipsEditarInfraSitios");
    if (chipsCont) {
      const checkedSitios = Array.from(document.querySelectorAll("#editarInfraSitiosLista .editar-infra-sitio-check:checked"));
      if (checkedSitios.length === 0) {
        chipsCont.innerHTML = "";
        chipsCont.classList.add("d-none");
      } else {
        chipsCont.classList.remove("d-none");
        chipsCont.innerHTML = checkedSitios.map(chk => {
          const card = chk.closest(".infra-node-card");
          const nombre = card?.dataset.nombre || chk.value;
          return `
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1 px-2">
              <i class="bi bi-broadcast"></i> ${escapeHtml(nombre)}
              <button type="button" class="btn-close btn-close-xs ms-1 chip-remove-btn" data-target-tipo="sitios" data-id="${escapeHtml(chk.value)}" title="Quitar"></button>
            </span>
          `;
        }).join("");
      }
    }
  }

  if (!tipo || tipo === 'postes') {
    const chipsCont = document.getElementById("chipsEditarInfraPostes");
    if (chipsCont) {
      const checkedPostes = Array.from(document.querySelectorAll("#editarInfraPostesLista .editar-infra-poste-check:checked"));
      if (checkedPostes.length === 0) {
        chipsCont.innerHTML = "";
        chipsCont.classList.add("d-none");
      } else {
        chipsCont.classList.remove("d-none");
        chipsCont.innerHTML = checkedPostes.map(chk => {
          const card = chk.closest(".infra-node-card");
          const nombre = card?.dataset.nombre || chk.value;
          return `
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle d-inline-flex align-items-center gap-1 py-1 px-2">
              <i class="bi bi-signpost-2"></i> ${escapeHtml(nombre)}
              <button type="button" class="btn-close btn-close-xs ms-1 chip-remove-btn" data-target-tipo="postes" data-id="${escapeHtml(chk.value)}" title="Quitar"></button>
            </span>
          `;
        }).join("");
      }
    }
  }

  if (!tipo || tipo === 'enlaces_enlaces') {
    const chipsCont = document.getElementById("chipsEditarInfraEnlacesEnlaces");
    if (chipsCont) {
      const checkedEnlaces = Array.from(document.querySelectorAll("#editarInfraEnlacesEnlacesLista .editar-infra-enlace-enlace-check:checked"));
      if (checkedEnlaces.length === 0) {
        chipsCont.innerHTML = "";
        chipsCont.classList.add("d-none");
      } else {
        chipsCont.classList.remove("d-none");
        chipsCont.innerHTML = checkedEnlaces.map(chk => {
          const card = chk.closest(".infra-node-card");
          const nombre = card?.dataset.nombre || chk.value;
          return `
            <span class="badge bg-info-subtle text-info border border-info-subtle d-inline-flex align-items-center gap-1 py-1 px-2">
              <i class="bi bi-link-45deg"></i> ${escapeHtml(nombre)}
              <button type="button" class="btn-close btn-close-xs ms-1 chip-remove-btn" data-target-tipo="enlaces_enlaces" data-id="${escapeHtml(chk.value)}" title="Quitar"></button>
            </span>
          `;
        }).join("");
      }
    }
  }
}

function actualizarContadoresEditarInfra() {
  const arcosCount = document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check:checked").length;
  const sitiosCount = document.querySelectorAll("#editarInfraSitiosLista .editar-infra-sitio-check:checked").length;
  const postesCount = document.querySelectorAll("#editarInfraPostesLista .editar-infra-poste-check:checked").length;
  const enlacesEnlacesCount = document.querySelectorAll("#editarInfraEnlacesEnlacesLista .editar-infra-enlace-enlace-check:checked").length;
  const totalEnlaces = sitiosCount + postesCount;
  const totalVinculos = arcosCount + totalEnlaces + enlacesEnlacesCount;

  const countArcosEl = document.getElementById("editarInfraArcosCount");
  const badgeArcosEl = document.getElementById("badgeCountArcos");
  const countSitiosEl = document.getElementById("editarInfraSitiosCount");
  const badgeSitiosEl = document.getElementById("badgeCountSitios");
  const countPostesEl = document.getElementById("editarInfraPostesCount");
  const badgePostesEl = document.getElementById("badgeCountPostes");
  const countEnlacesEnlacesEl = document.getElementById("editarInfraEnlacesEnlacesCount");
  const badgeEnlacesEnlacesEl = document.getElementById("badgeCountEnlacesEnlaces");
  const totalBadgeEl = document.getElementById("editarInfraTotalEnlacesBadge");

  if (countArcosEl) countArcosEl.textContent = `${arcosCount} seleccionado${arcosCount === 1 ? '' : 's'}`;
  if (badgeArcosEl) badgeArcosEl.textContent = String(arcosCount);
  if (countSitiosEl) countSitiosEl.textContent = `${sitiosCount} seleccionado${sitiosCount === 1 ? '' : 's'}`;
  if (badgeSitiosEl) badgeSitiosEl.textContent = String(sitiosCount);
  if (countPostesEl) countPostesEl.textContent = `${postesCount} seleccionado${postesCount === 1 ? '' : 's'}`;
  if (badgePostesEl) badgePostesEl.textContent = String(postesCount);
  if (countEnlacesEnlacesEl) countEnlacesEnlacesEl.textContent = `${enlacesEnlacesCount} seleccionado${enlacesEnlacesCount === 1 ? '' : 's'}`;
  if (badgeEnlacesEnlacesEl) badgeEnlacesEnlacesEl.textContent = String(enlacesEnlacesCount);

  if (totalBadgeEl) {
    const parts = [];
    if (arcosCount > 0) parts.push(`${arcosCount} arcos`);
    if (totalEnlaces > 0) parts.push(`${totalEnlaces} enlaces`);
    if (enlacesEnlacesCount > 0) parts.push(`${enlacesEnlacesCount} saltos de enlace`);
    const summary = parts.length > 0 ? ` (${parts.join(", ")})` : "";
    totalBadgeEl.textContent = `${totalVinculos} vínculo${totalVinculos === 1 ? '' : 's'}${summary}`;
  }
}

function filtrarEditarInfraArcos() {
  const ubicacionId = String(document.getElementById("editarInfraUbicacion")?.value || "");
  const filtro = (document.getElementById("buscarEditarInfraArcos")?.value || "").trim().toLowerCase();
  let visibles = 0;

  document.querySelectorAll("#editarInfraArcosLista .arco-node-item").forEach(item => {
    const itemUbicacion = String(item.dataset.ubicacionId || "");
    const itemNombre = (item.dataset.nombre || item.textContent || "").toLowerCase();

    const coincideUbicacion = ubicacionId !== "" && itemUbicacion === ubicacionId;
    const coincideBusqueda = filtro === "" || itemNombre.includes(filtro);
    const visible = coincideUbicacion && coincideBusqueda;

    item.style.display = visible ? "" : "none";
    if (visible) visibles++;
  });

  const emptyEl = document.getElementById("editarInfraArcosEmpty");
  if (emptyEl) {
    emptyEl.textContent = ubicacionId
      ? "No se encontraron arcos en esta ubicación con ese filtro."
      : "Seleccione una ubicación en 'Datos del Nodo' para ver los arcos disponibles.";
    emptyEl.classList.toggle("d-none", visibles > 0);
  }
  actualizarContadoresEditarInfra();
}

function filtrarEditarInfraSitios(currentId = null) {
  const currentInfraId = String(currentId || document.getElementById("editarInfraId")?.value || "");
  const ubicacionId = String(document.getElementById("editarInfraUbicacion")?.value || "");
  const filtro = (document.getElementById("buscarEditarInfraSitios")?.value || "").trim().toLowerCase();
  let visibles = 0;

  document.querySelectorAll("#editarInfraSitiosLista .sitio-node-item").forEach(item => {
    const itemId = String(item.dataset.id || "");
    const itemUbicacion = String(item.dataset.ubicacionId || "");

    if (currentInfraId !== "" && itemId === currentInfraId) {
      item.style.display = "none";
      return;
    }

    const itemNombre = (item.dataset.nombre || item.textContent || "").toLowerCase();
    const coincideUbicacion = ubicacionId !== "" && itemUbicacion === ubicacionId;
    const coincideBusqueda = filtro === "" || itemNombre.includes(filtro);
    const visible = coincideUbicacion && coincideBusqueda;

    item.style.display = visible ? "" : "none";
    if (visible) visibles++;
  });

  const emptyEl = document.getElementById("editarInfraSitiosEmpty");
  if (emptyEl) {
    emptyEl.textContent = ubicacionId
      ? "No se encontraron sitios/torres en esta ubicación con ese filtro."
      : "Seleccione una ubicación en 'Datos del Nodo' para ver los sitios disponibles.";
    emptyEl.classList.toggle("d-none", visibles > 0);
  }
  actualizarContadoresEditarInfra();
}

function filtrarEditarInfraPostes(currentId = null) {
  const currentInfraId = String(currentId || document.getElementById("editarInfraId")?.value || "");
  const ubicacionId = String(document.getElementById("editarInfraUbicacion")?.value || "");
  const filtro = (document.getElementById("buscarEditarInfraPostes")?.value || "").trim().toLowerCase();
  let visibles = 0;

  document.querySelectorAll("#editarInfraPostesLista .poste-node-item").forEach(item => {
    const itemId = String(item.dataset.id || "");
    const itemUbicacion = String(item.dataset.ubicacionId || "");

    if (currentInfraId !== "" && itemId === currentInfraId) {
      item.style.display = "none";
      return;
    }

    const itemNombre = (item.dataset.nombre || item.textContent || "").toLowerCase();
    const coincideUbicacion = ubicacionId !== "" && itemUbicacion === ubicacionId;
    const coincideBusqueda = filtro === "" || itemNombre.includes(filtro);
    const visible = coincideUbicacion && coincideBusqueda;

    item.style.display = visible ? "" : "none";
    if (visible) visibles++;
  });

  const emptyEl = document.getElementById("editarInfraPostesEmpty");
  if (emptyEl) {
    emptyEl.textContent = ubicacionId
      ? "No se encontraron postes/puentes en esta ubicación con ese filtro."
      : "Seleccione una ubicación en 'Datos del Nodo' para ver los postes disponibles.";
    emptyEl.classList.toggle("d-none", visibles > 0);
  }
  actualizarContadoresEditarInfra();
}

function filtrarEditarInfraEnlacesEnlaces(currentId = null) {
  const currentInfraId = String(currentId || document.getElementById("editarInfraId")?.value || "");
  const ubicacionId = String(document.getElementById("editarInfraUbicacion")?.value || "");
  const filtro = (document.getElementById("buscarEditarInfraEnlacesEnlaces")?.value || "").trim().toLowerCase();
  let visibles = 0;

  document.querySelectorAll("#editarInfraEnlacesEnlacesLista .enlace-node-item").forEach(item => {
    const itemOrigenId = String(item.dataset.origenId || "");
    const itemDestinoId = String(item.dataset.destinoId || "");
    const itemUbicacion = String(item.dataset.ubicacionId || "");

    if (currentInfraId !== "" && (itemOrigenId === currentInfraId || itemDestinoId === currentInfraId)) {
      item.style.display = "none";
      return;
    }

    const itemNombre = (item.dataset.nombre || item.textContent || "").toLowerCase();
    const coincideUbicacion = ubicacionId !== "" && itemUbicacion === ubicacionId;
    const coincideBusqueda = filtro === "" || itemNombre.includes(filtro);
    const visible = coincideUbicacion && coincideBusqueda;

    item.style.display = visible ? "" : "none";
    if (visible) visibles++;
  });

  const emptyEl = document.getElementById("editarInfraEnlacesEnlacesEmpty");
  if (emptyEl) {
    emptyEl.textContent = ubicacionId
      ? "No se encontraron enlaces en esta ubicación con ese filtro."
      : "Seleccione una ubicación en 'Datos del Nodo' para ver los enlaces disponibles.";
    emptyEl.classList.toggle("d-none", visibles > 0);
  }
  actualizarContadoresEditarInfra();
}

function esEdicionDeMaterial() {
  return materialOperacionActiva === "actualizar";
}

function clasesColorMaterialSeleccionado() {
  return ["border-primary"];
}

function limpiarClasesSeleccionMaterial(card) {
  card.classList.remove(
    "selected-material",
    "border-primary",
    "border-success",
    "border-warning",
    "border-3",
    "shadow"
  );
}

function marcarMaterialSeleccionado(card) {
  if (!card) return;
  limpiarClasesSeleccionMaterial(card);
  card.classList.add("selected-material", ...clasesColorMaterialSeleccionado(), "border-3", "shadow");
}

function configurarModoModalMaterial() {
  const modal = document.getElementById("modalAgregarMaterial");
  if (!modal) return;

  const editando = esEdicionDeMaterial();
  const objetivoMaterial = materialContextoActivo === "infra" ? "Puente/Sitio" : "Arco";
  modal.classList.remove("modal-material-agregar", "modal-material-editar");

  const header = modal.querySelector(".modal-header");
  header?.classList.remove("bg-success", "bg-warning", "text-dark");
  header?.classList.add("bg-primary", "text-white");

  const closeBtn = header?.querySelector(".btn-close");
  closeBtn?.classList.add("btn-close-white");

  const title = header?.querySelector(".modal-title");
  if (title) {
    title.innerHTML = editando
      ? `<i class="bi bi-pencil-square me-2"></i>Editar Material del ${objetivoMaterial}`
      : `<i class="bi bi-box-seam me-2"></i>Agregar Material al ${objetivoMaterial}`;
  }

  const guardarBtn = document.getElementById("guardarMaterialModal");
  guardarBtn?.classList.remove("btn-success", "btn-warning");
  guardarBtn?.classList.add("btn-primary");
  if (guardarBtn) {
    guardarBtn.innerHTML = editando
      ? '<i class="bi bi-pencil-square me-2"></i>Actualizar Material'
      : '<i class="bi bi-check-circle me-2"></i>Agregar Material';
  }

  const configIconBox = document.querySelector("#camposDinamicos .rounded-circle");
  configIconBox?.classList.remove("bg-success", "bg-warning");
  configIconBox?.classList.add("bg-primary", "bg-opacity-10");

  const configIcon = document.querySelector("#camposDinamicos .bi-sliders");
  configIcon?.classList.remove("text-success", "text-warning");
  configIcon?.classList.add("text-primary");

  const configTitle = document.querySelector("#camposDinamicos h5");
  configTitle?.classList.remove("text-success", "text-warning");
  configTitle?.classList.add("text-primary");

  const materialSeleccionado = document.getElementById("materialSeleccionado");
  materialSeleccionado?.classList.remove("alert-success", "alert-warning");
  materialSeleccionado?.classList.add("alert-primary");
}

function resetModalAgregarMaterial() {
  materialSeleccionadoArco = null;
  configurarModoModalMaterial();

  const buscarInput = document.getElementById("buscarMaterial");
  if (buscarInput) buscarInput.value = "";
  const checkSerie = document.getElementById("checkSerie");
  if (checkSerie) checkSerie.checked = false;
  const serieInput = document.getElementById("serieInput");
  if (serieInput) serieInput.value = "";
  const checkIp = document.getElementById("checkIp");
  if (checkIp) checkIp.checked = false;
  const ipInput = document.getElementById("ipInput");
  if (ipInput) ipInput.value = "";
  const checkMac = document.getElementById("checkMac");
  if (checkMac) checkMac.checked = false;
  const macInput = document.getElementById("macInput");
  if (macInput) macInput.value = "";
  const cantidadInput = document.getElementById("cantidadInput");
  if (cantidadInput) cantidadInput.value = "";
  const materialSeleccionado = document.getElementById("materialSeleccionado");
  if (materialSeleccionado) materialSeleccionado.innerHTML = "Selecciona un material del catálogo";

  document.getElementById("serieContainer")?.classList.add("d-none");
  document.getElementById("ipContainer")?.classList.add("d-none");
  document.getElementById("macContainer")?.classList.add("d-none");
  document.getElementById("cantidadContainer")?.classList.add("d-none");

  document.querySelectorAll("#modalAgregarMaterial .material-item").forEach(item => {
    item.style.display = "";
    limpiarClasesSeleccionMaterial(item);
  });
}

/* ========================================================
   COMPONENTE SMART CARD PICKER (CENTRALIZADO EN JS/SMART_PICKER.JS)
   ======================================================== */
// Las funciones y clase SmartCardPicker se cargan globalmente desde js/smart_picker.js

function renderListaMaterialesArco(lista, containerId) {
  const contenedor = document.getElementById(containerId);
  if (!contenedor) return;
  const esListaEditar = containerId === "listaMaterialesEditar";

  if (!lista.length) {
    if (esListaEditar) {
      materialSeleccionadoEditarIndex = null;
    }

    contenedor.innerHTML = `
      <div class="empty-materials-state">
        <i class="bi bi-box-seam"></i>
        <span class="fw-semibold">Ningún material agregado</span>
      </div>
    `;
    return;
  }

  contenedor.innerHTML = lista.map((material, index) => {
    const editListClass = esListaEditar ? " is-edit-list" : "";
    const nombre = escapeHtml(material.nombre);
    const medida = escapeHtml(material.medida);
    const cantidad = escapeHtml(material.cantidad);
    const serie = escapeHtml(material.serie);
    const ip = escapeHtml(material.ip || "");
    const mac = escapeHtml(material.mac || "");
    const foto = escapeHtml(material.foto);
    const imagen = foto
      ? `<img src="../uploads/materiales/${foto}" class="material-image" alt="${nombre}">`
      : `<div class="material-placeholder"><i class="bi bi-box-seam"></i></div>`;

    const tieneDetalles = Boolean(serie || ip || mac);

    return `
      <div class="material-card-added shadow-sm${editListClass}" data-index="${index}">
        <div class="material-card-buttons">
          ${esListaEditar ? `
            <button type="button" class="material-edit" data-index="${index}" title="Editar material">
              <i class="bi bi-pencil"></i>
            </button>
          ` : ""}
          <button type="button" class="material-delete" data-index="${index}" title="Eliminar material">
            <i class="bi bi-trash"></i>
          </button>
        </div>

        <div class="material-card-top">
          <div class="material-image-container">${imagen}</div>
          <div class="flex-grow-1 min-w-0">
            <div class="material-title text-capitalize">${nombre}</div>
            <div class="material-subtitle d-flex align-items-center flex-wrap gap-2">
              <span>${material.medida === "pz" ? "Por pieza" : `${cantidad} ${medida}`}</span>
              ${tieneDetalles ? `
                <button type="button" class="material-details-toggle" title="Mostrar u ocultar detalles (Serie, IP, MAC)">
                  <i class="bi bi-eye"></i>
                  <span>Datos</span>
                </button>
              ` : ""}
            </div>
          </div>
        </div>

        ${tieneDetalles ? `
          <div class="material-data is-hidden">
            ${serie ? `<span class="material-tag material-tag-serie" title="Serie: ${serie}"><i class="bi bi-upc-scan me-1"></i>${serie}</span>` : ""}
            ${ip ? `<span class="material-tag material-tag-ip" title="IP: ${ip}"><i class="bi bi-hdd-network me-1"></i>IP: ${ip}</span>` : ""}
            ${mac ? `<span class="material-tag material-tag-mac" title="MAC: ${mac}"><i class="bi bi-ethernet me-1"></i>MAC: ${mac}</span>` : ""}
          </div>
        ` : ""}

        <input type="hidden" name="material_id[]" value="${escapeHtml(material.id)}">
        <input type="hidden" name="cantidad[]" value="${cantidad}">
        <input type="hidden" name="serie[]" value="${serie}">
        <input type="hidden" name="ip[]" value="${ip}">
        <input type="hidden" name="mac[]" value="${mac}">
      </div>
    `;
  }).join("");
}

function renderMaterialesAgregadosArco() {
  renderListaMaterialesArco(materialesAgregadosArco, "listaMaterialesAgregados");
}

function renderMaterialesEditarArco() {
  renderListaMaterialesArco(materialesEditandoArco, "listaMaterialesEditar");
}

function seleccionarMaterialEnModal(material) {
  if (!material) return;

  materialSeleccionadoArco = {
    id: material.id,
    nombre: material.nombre,
    medida: material.medida,
    foto: material.foto || ""
  };

  document.querySelectorAll("#modalAgregarMaterial .material-item").forEach(limpiarClasesSeleccionMaterial);

  const item = document.querySelector(`#modalAgregarMaterial .material-item[data-id="${CSS.escape(String(material.id))}"]`);
  if (item) {
    marcarMaterialSeleccionado(item);
  }

  const materialSeleccionado = document.getElementById("materialSeleccionado");
  if (materialSeleccionado) {
    materialSeleccionado.innerHTML = `<i class="bi bi-box-seam me-2"></i>${escapeHtml(material.nombre)}`;
  }

  const checkSerie = document.getElementById("checkSerie");
  const serieInput = document.getElementById("serieInput");
  const serieContainer = document.getElementById("serieContainer");
  const checkIp = document.getElementById("checkIp");
  const ipInput = document.getElementById("ipInput");
  const ipContainer = document.getElementById("ipContainer");
  const checkMac = document.getElementById("checkMac");
  const macInput = document.getElementById("macInput");
  const macContainer = document.getElementById("macContainer");
  const cantidadContainer = document.getElementById("cantidadContainer");
  const cantidadInput = document.getElementById("cantidadInput");
  const unidadMedida = document.getElementById("unidadMedida");
  const esPieza = material.medida === "pz";

  if (checkSerie) checkSerie.checked = Boolean(material.serie);
  if (serieInput) serieInput.value = material.serie || "";
  serieContainer?.classList.toggle("d-none", !material.serie);

  if (checkIp) checkIp.checked = Boolean(material.ip);
  if (ipInput) ipInput.value = material.ip || "";
  ipContainer?.classList.toggle("d-none", !material.ip);

  if (checkMac) checkMac.checked = Boolean(material.mac);
  if (macInput) macInput.value = material.mac || "";
  macContainer?.classList.toggle("d-none", !material.mac);

  document.getElementById("serieConfigGroup")?.classList.toggle("d-none", !esPieza);
  document.getElementById("ipConfigGroup")?.classList.toggle("d-none", !esPieza);
  document.getElementById("macConfigGroup")?.classList.toggle("d-none", !esPieza);

  cantidadContainer?.classList.toggle("d-none", esPieza);

  if (unidadMedida) unidadMedida.textContent = material.medida;
  if (cantidadInput) {
    cantidadInput.min = esPieza ? "1" : "0.1";
    cantidadInput.step = esPieza ? "1" : "0.1";
    cantidadInput.value = material.cantidad || (esPieza ? "1" : "");
  }
}

function hayModalArcoAbierto() {
  return Boolean(document.querySelector("#modalAgregarArco.show, #modalEditarArco.show, #modalEditarInfraestructura.show"));
}

function prepararModalMaterialSobreArco() {
  if (!hayModalArcoAbierto()) return;

  const modalMaterial = document.getElementById("modalAgregarMaterial");
  modalMaterial?.classList.add("modal-material-on-top");
  document.body.classList.add("material-modal-stacked");
}

function marcarBackdropMaterialSobreArco() {
  if (!hayModalArcoAbierto()) return;

  prepararModalMaterialSobreArco();
  const backdrops = document.querySelectorAll(".modal-backdrop");
  backdrops.forEach(backdrop => backdrop.classList.remove("material-backdrop-on-top"));
  backdrops[backdrops.length - 1]?.classList.add("material-backdrop-on-top");
  document.body.classList.add("modal-open");
}

function limpiarModalMaterialSobreArco() {
  document.getElementById("modalAgregarMaterial")?.classList.remove("modal-material-on-top");
  document.body.classList.remove("material-modal-stacked");
  document.querySelectorAll(".modal-backdrop.material-backdrop-on-top").forEach(backdrop => {
    backdrop.classList.remove("material-backdrop-on-top");
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const modalAgregarMaterial = document.getElementById("modalAgregarMaterial");
  const modalAgregarArco = document.getElementById("modalAgregarArco");
      const buscarMaterial = document.getElementById("buscarMaterial");
      const checkSerie = document.getElementById("checkSerie");
      const guardarMaterialModal = document.getElementById("guardarMaterialModal");
      const listaMateriales = document.getElementById("listaMaterialesAgregados");
      const listaMaterialesEditar = document.getElementById("listaMaterialesEditar");
      const btnAgregarMaterialArco = document.getElementById("btnAgregarMaterial");
      const btnEditarAgregarMaterial = document.getElementById("editarAddMaterial");
      const btnAgregarInfraestructura = document.getElementById("btnAgregarInfraestructura");
      const listaInfraestructurasArco = document.getElementById("listaInfraestructurasArco");
      const checkPuenteSitio = document.getElementById("checkPuenteSitio");
      const camposPuenteSitio = document.getElementById("camposPuenteSitio");
      const ubicacionPrincipalGroup = document.getElementById("ubicacionPrincipalGroup");
      const ubicacionPrincipalSelect = document.getElementById("ubicacionPrincipalSelect");
      const nombrePrincipalLabel = document.getElementById("nombrePrincipalLabel");
      const infraArcosVinculados = document.getElementById("infraArcosVinculados");
      const buscarInfraArcos = document.getElementById("buscarInfraArcos");
      const infraArcosSeleccionados = document.getElementById("infraArcosSeleccionados");
      const infraArcosEmpty = document.getElementById("infraArcosEmpty");
      const infraArcosGroup = document.getElementById("infraArcosGroup");
      const tipoPuenteSitioGroup = document.getElementById("tipoPuenteSitioGroup");
      const filaFechaCoordenadas = document.getElementById("filaFechaCoordenadas");

  if (!modalAgregarMaterial) return;

      if (tipoPuenteSitioGroup && camposPuenteSitio) {
        const tipoOriginal = camposPuenteSitio.querySelector(".col-md-5");
        if (tipoOriginal) {
          tipoPuenteSitioGroup.append(...Array.from(tipoOriginal.childNodes));
          tipoOriginal.remove();
        }
        const arcosGroup = infraArcosGroup || camposPuenteSitio.querySelector(".col-md-7");
        arcosGroup?.classList.remove("col-md-7");
        arcosGroup?.classList.add("col-12");
        camposPuenteSitio.classList.remove("mb-3");
        camposPuenteSitio.classList.add("mt-3");
      }

      if (filaFechaCoordenadas && camposPuenteSitio && camposPuenteSitio.previousElementSibling !== filaFechaCoordenadas) {
        filaFechaCoordenadas.after(camposPuenteSitio);
      }

      function actualizarContadorInfraArcos() {
        const total = infraArcosVinculados?.querySelectorAll(".infra-arco-check:checked").length || 0;
        if (infraArcosSeleccionados) {
          infraArcosSeleccionados.textContent = `${total} seleccionado${total === 1 ? "" : "s"}`;
        }
      }

      function filtrarInfraArcos() {
        const filtro = (buscarInfraArcos?.value || "").trim().toLowerCase();
        const ubicacionId = ubicacionPrincipalSelect?.value || "";
        let visibles = 0;

        infraArcosGroup?.classList.toggle("d-none", ubicacionId === "");

        infraArcosVinculados?.querySelectorAll(".infra-arco-option").forEach(option => {
          const coincideUbicacion = ubicacionId !== "" && option.dataset.ubicacionId === ubicacionId;
          const coincideBusqueda = option.textContent.toLowerCase().includes(filtro);
          const visible = coincideUbicacion && coincideBusqueda;
          option.style.display = visible ? "" : "none";

          const check = option.querySelector(".infra-arco-check");
          if (!coincideUbicacion && check) {
            check.checked = false;
          }

          if (visible) visibles++;
        });

        if (infraArcosEmpty) {
          infraArcosEmpty.textContent = ubicacionId
            ? "No hay arcos en esta ubicacion."
            : "Seleccione una ubicacion para ver los arcos.";
          infraArcosEmpty.classList.toggle("d-none", visibles > 0);
        }

        actualizarContadorInfraArcos();
      }

      buscarInfraArcos?.addEventListener("input", filtrarInfraArcos);
      ubicacionPrincipalSelect?.addEventListener("change", function() {
        filtrarInfraArcos();
        const ubiId = String(this.value || "");
        const select = document.getElementById("agregar_infra_vinculada");
        if (select) {
          let matchCurrent = false;
          Array.from(select.options).forEach(opt => {
            if (!opt.value) { opt.style.display = ""; return; }
            const match = !ubiId || String(opt.dataset.ubicacionId || "") === ubiId;
            opt.style.display = match ? "" : "none";
            if (opt.value === select.value && match) matchCurrent = true;
          });
          select.querySelectorAll("optgroup").forEach(og => {
            const hasVisible = Array.from(og.querySelectorAll("option")).some(o => o.style.display !== "none");
            og.style.display = hasVisible ? "" : "none";
          });
          if (!matchCurrent) select.value = "";
          syncSearchableSelect("agregar_infra_vinculada");
        }
      });
      document.getElementById("editar_ubicacion")?.addEventListener("change", function() {
        const ubiId = String(this.value || "");
        const select = document.getElementById("editar_infra_vinculada");
        if (select) {
          let matchCurrent = false;
          Array.from(select.options).forEach(opt => {
            if (!opt.value) { opt.style.display = ""; return; }
            const match = !ubiId || String(opt.dataset.ubicacionId || "") === ubiId;
            opt.style.display = match ? "" : "none";
            if (opt.value === select.value && match) matchCurrent = true;
          });
          select.querySelectorAll("optgroup").forEach(og => {
            const hasVisible = Array.from(og.querySelectorAll("option")).some(o => o.style.display !== "none");
            og.style.display = hasVisible ? "" : "none";
          });
          if (!matchCurrent) select.value = "";
          syncSearchableSelect("editar_infra_vinculada");
        }
      });
      infraArcosVinculados?.addEventListener("change", e => {
        if (e.target.classList.contains("infra-arco-check")) {
          actualizarContadorInfraArcos();
        }
      });

      document.getElementById("btnSelectAllInfraArcos")?.addEventListener("click", () => {
        infraArcosVinculados?.querySelectorAll(".infra-arco-option").forEach(opt => {
          if (opt.style.display !== "none") {
            const chk = opt.querySelector(".infra-arco-check");
            if (chk) chk.checked = true;
          }
        });
        actualizarContadorInfraArcos();
      });

      document.getElementById("btnClearInfraArcos")?.addEventListener("click", () => {
        infraArcosVinculados?.querySelectorAll(".infra-arco-check").forEach(chk => {
          chk.checked = false;
        });
        actualizarContadorInfraArcos();
      });

      function actualizarModoPuenteSitio() {
        const activo = Boolean(checkPuenteSitio?.checked);
        camposPuenteSitio?.classList.toggle("d-none", !activo);
        tipoPuenteSitioGroup?.classList.toggle("d-none", !activo);
        ubicacionPrincipalGroup?.classList.remove("d-none");

        const grupoInfraNuevo = document.getElementById("grupoInfraVinculadaArcoNuevo");
        if (grupoInfraNuevo) {
          grupoInfraNuevo.classList.toggle("d-none", activo);
        }

        if (ubicacionPrincipalSelect) {
          ubicacionPrincipalSelect.required = true;
          ubicacionPrincipalSelect.disabled = false;
        }

        if (nombrePrincipalLabel) {
          nombrePrincipalLabel.textContent = activo ? "Nombre del Puente/Sitio" : "Nombre del Arco";
        }

        infraArcosVinculados?.querySelectorAll(".infra-arco-check").forEach(check => {
          check.disabled = !activo;
        });
        actualizarContadorInfraArcos();
      }

      checkPuenteSitio?.addEventListener("change", actualizarModoPuenteSitio);

      modalAgregarArco?.addEventListener("hidden.bs.modal", () => {
        materialesAgregadosArco = [];
        infraestructurasAgregadasArco = [];
        modalAgregarArco.querySelector("form")?.reset();
        if (buscarInfraArcos) buscarInfraArcos.value = "";
        filtrarInfraArcos();
        actualizarContadorInfraArcos();
        actualizarModoPuenteSitio();
        renderMaterialesAgregadosArco();
        renderInfraestructurasArco();
        syncSearchableSelect("ubicacionPrincipalSelect");
        syncSearchableSelect("agregar_infra_vinculada");
      });

      document.getElementById("modalEditarArco")?.addEventListener("hidden.bs.modal", () => {
        if (seleccionandoMaterialParaEditar) {
          return;
        }
        materialesEditandoArco = [];
        renderMaterialesEditarArco();
      });

      btnAgregarMaterialArco?.addEventListener("click", e => {
        e.preventDefault();
        materialContextoActivo = "agregar";
        materialOperacionActiva = "agregar";
        materialEditarIndex = null;
        seleccionandoMaterialParaEditar = true;
        configurarModoModalMaterial();
        prepararModalMaterialSobreArco();
        ModalManager.show("modalAgregarMaterial");
      });

      btnEditarAgregarMaterial?.addEventListener("click", e => {
        e.preventDefault();
        materialContextoActivo = "editar";
        materialOperacionActiva = "agregar";
        materialEditarIndex = null;
        seleccionandoMaterialParaEditar = true;
        configurarModoModalMaterial();
        prepararModalMaterialSobreArco();
        ModalManager.show("modalAgregarMaterial");
      });

      btnAgregarInfraestructura?.addEventListener("click", e => {
        e.preventDefault();
        infraestructurasAgregadasArco.push({
          tipo: "Puente/Poste",
          nombre: "",
          lat: "",
          lng: "",
          descripcion: "",
          materiales: []
        });
        renderInfraestructurasArco();
      });

      listaInfraestructurasArco?.addEventListener("input", e => {
        const field = e.target.closest(".infra-field, .infra-material-input");
        if (!field) return;

        const index = Number(field.dataset.index);
        if (Number.isNaN(index) || !infraestructurasAgregadasArco[index]) return;

        if (field.classList.contains("infra-field")) {
          infraestructurasAgregadasArco[index][field.dataset.field] = field.value;
          return;
        }

        const materialIndex = Number(field.dataset.materialIndex);
        if (Number.isNaN(materialIndex) || !infraestructurasAgregadasArco[index].materiales[materialIndex]) return;

        infraestructurasAgregadasArco[index].materiales[materialIndex][field.dataset.field] = field.value;
      });

      listaInfraestructurasArco?.addEventListener("change", e => {
        const infraField = e.target.closest(".infra-field");
        if (infraField) {
          const index = Number(infraField.dataset.index);
          if (!Number.isNaN(index) && infraestructurasAgregadasArco[index]) {
            infraestructurasAgregadasArco[index][infraField.dataset.field] = infraField.value;
          }
          return;
        }

        const select = e.target.closest(".infra-material-select");
        if (!select) return;

        const index = Number(select.dataset.index);
        const materialIndex = Number(select.dataset.materialIndex);
        if (Number.isNaN(index) || Number.isNaN(materialIndex)) return;

        const infra = infraestructurasAgregadasArco[index];
        if (!infra?.materiales?.[materialIndex]) return;

        const material = buscarMaterialInfraestructura(select.value);
        infra.materiales[materialIndex].id = select.value;
        infra.materiales[materialIndex].medida = material?.medida || "";
        if (material?.medida === "pz") {
          infra.materiales[materialIndex].cantidad = "1";
        }
        renderInfraestructurasArco();
      });

      listaInfraestructurasArco?.addEventListener("click", e => {
        const deleteInfra = e.target.closest(".infra-delete");
        if (deleteInfra) {
          const index = Number(deleteInfra.dataset.index);
          if (!Number.isNaN(index)) {
            infraestructurasAgregadasArco.splice(index, 1);
            renderInfraestructurasArco();
          }
          return;
        }

        const addMaterial = e.target.closest(".infra-add-material");
        if (addMaterial) {
          const index = Number(addMaterial.dataset.index);
          if (!Number.isNaN(index) && infraestructurasAgregadasArco[index]) {
            infraestructurasAgregadasArco[index].materiales.push({
              id: "",
              medida: "",
              cantidad: "1",
              serie: ""
            });
            renderInfraestructurasArco();
          }
          return;
        }

        const deleteMaterial = e.target.closest(".infra-delete-material");
        if (deleteMaterial) {
          const index = Number(deleteMaterial.dataset.index);
          const materialIndex = Number(deleteMaterial.dataset.materialIndex);
          if (!Number.isNaN(index) && !Number.isNaN(materialIndex) && infraestructurasAgregadasArco[index]) {
            infraestructurasAgregadasArco[index].materiales.splice(materialIndex, 1);
            renderInfraestructurasArco();
          }
        }
      });

  modalAgregarMaterial?.addEventListener("show.bs.modal", resetModalAgregarMaterial);
  modalAgregarMaterial?.addEventListener("shown.bs.modal", () => {
    marcarBackdropMaterialSobreArco();
    if (materialContextoActivo === "editar" && materialOperacionActiva === "actualizar") {
      setTimeout(() => {
        seleccionarMaterialEnModal(materialesEditandoArco[materialEditarIndex]);
      }, 50);
    } else if (materialContextoActivo === "infra" && materialOperacionActiva === "actualizar") {
      setTimeout(() => {
        seleccionarMaterialEnModal(materialesEditandoInfraestructura[materialEditarIndex]);
      }, 50);
    }
  });
  modalAgregarMaterial?.addEventListener("hidden.bs.modal", () => {
    limpiarModalMaterialSobreArco();
    if (materialContextoActivo === "editar" && seleccionandoMaterialParaEditar) {
      setTimeout(() => {
        ModalManager.show("modalEditarArco");
        document.body.classList.add("modal-open");
        seleccionandoMaterialParaEditar = false;
      }, 150);
    } else if (materialContextoActivo === "agregar" && seleccionandoMaterialParaEditar) {
      setTimeout(() => {
        limpiarModalMaterialSobreArco();
        if (document.getElementById("modalAgregarArco")?.classList.contains("show")) {
          document.body.classList.add("modal-open");
        }
        seleccionandoMaterialParaEditar = false;
      }, 150);
    } else if (materialContextoActivo === "infra" && seleccionandoMaterialParaEditar) {
      setTimeout(() => {
        limpiarModalMaterialSobreArco();
        if (document.getElementById("modalEditarInfraestructura")?.classList.contains("show")) {
          document.body.classList.add("modal-open");
        }
        seleccionandoMaterialParaEditar = false;
      }, 150);
    }
  });

  buscarMaterial?.addEventListener("input", function () {
    const valor = this.value.trim().toLowerCase();

    document.querySelectorAll("#modalAgregarMaterial .material-item").forEach(item => {
      const nombre = item.dataset.nombre || "";
      item.style.display = nombre.includes(valor) ? "" : "none";
    });
  });

  document.querySelectorAll("#modalAgregarMaterial .material-item").forEach(item => {
    item.addEventListener("click", function (e) {
      if (e.target.closest("input, textarea, button")) return;

      document.querySelectorAll("#modalAgregarMaterial .material-item").forEach(limpiarClasesSeleccionMaterial);
      marcarMaterialSeleccionado(this);

      materialSeleccionadoArco = {
        id: this.dataset.id,
        nombre: this.dataset.label || this.dataset.nombre,
        medida: this.dataset.medida,
        foto: this.dataset.foto
      };

      const materialSeleccionado = document.getElementById("materialSeleccionado");
      if (materialSeleccionado) {
        materialSeleccionado.innerHTML = `<i class="bi bi-box-seam me-2"></i>${escapeHtml(materialSeleccionadoArco.nombre)}`;
      }

      const esPieza = materialSeleccionadoArco.medida === "pz";

      const checkSerie = document.getElementById("checkSerie");
      const serieInput = document.getElementById("serieInput");
      const serieContainer = document.getElementById("serieContainer");
      if (checkSerie) checkSerie.checked = false;
      if (serieInput) serieInput.value = "";
      serieContainer?.classList.add("d-none");

      const checkIp = document.getElementById("checkIp");
      const ipInput = document.getElementById("ipInput");
      const ipContainer = document.getElementById("ipContainer");
      if (checkIp) checkIp.checked = false;
      if (ipInput) ipInput.value = "";
      ipContainer?.classList.add("d-none");

      const checkMac = document.getElementById("checkMac");
      const macInput = document.getElementById("macInput");
      const macContainer = document.getElementById("macContainer");
      if (checkMac) checkMac.checked = false;
      if (macInput) macInput.value = "";
      macContainer?.classList.add("d-none");

      document.getElementById("serieConfigGroup")?.classList.toggle("d-none", !esPieza);
      document.getElementById("ipConfigGroup")?.classList.toggle("d-none", !esPieza);
      document.getElementById("macConfigGroup")?.classList.toggle("d-none", !esPieza);

      const cantidadContainer = document.getElementById("cantidadContainer");
      const cantidadInput = document.getElementById("cantidadInput");
      const unidadMedida = document.getElementById("unidadMedida");

      if (esPieza) {
        cantidadContainer?.classList.add("d-none");
      } else {
        cantidadContainer?.classList.remove("d-none");
      }

      if (unidadMedida) {
        unidadMedida.textContent = materialSeleccionadoArco.medida;
      }
      if (cantidadInput) {
        cantidadInput.min = esPieza ? "1" : "0.1";
        cantidadInput.step = esPieza ? "1" : "0.1";
        cantidadInput.value = esPieza ? "1" : "";
      }
    });
  });

  checkSerie?.addEventListener("change", function () {
    const serieContainer = document.getElementById("serieContainer");
    const serieInput = document.getElementById("serieInput");

    if (this.checked) {
      serieContainer?.classList.remove("d-none");
      setTimeout(() => serieInput?.focus(), 100);
    } else {
      serieContainer?.classList.add("d-none");
      if (serieInput) serieInput.value = "";
    }
  });

  guardarMaterialModal?.addEventListener("click", () => {
    if (!materialSeleccionadoArco) {
      alert("Seleccione un material");
      return;
    }

    const esPieza = materialSeleccionadoArco.medida === "pz";
    const tieneSerie = document.getElementById("checkSerie")?.checked;
    const serie = document.getElementById("serieInput")?.value.trim() || "";

    const tieneIp = document.getElementById('checkIp').checked;
    const ip = document.getElementById('ipInput').value.trim();

    const tieneMac = document.getElementById('checkMac').checked;
    const mac = document.getElementById('macInput').value.trim();

    const cantidad = esPieza ? "1" : (document.getElementById("cantidadInput")?.value || "");
    const cantidadNumero = parseFloat(cantidad);

    if (tieneSerie && !serie) {
      alert("Ingrese la serie");
      return;
    }

    if (tieneIp && ip === '') {
      alert('Ingrese la dirección IP');
      return;
    }
    if (tieneMac && mac === '') {
      alert('Ingrese la dirección MAC');
      return;
    }

    if (!esPieza && (cantidad === "" || Number.isNaN(cantidadNumero) || cantidadNumero <= 0)) {
      alert("Ingrese una cantidad válida");
      return;
    }

    const cantidadNormalizada = esPieza
      ? "1"
      : String(cantidadNumero);

    const nuevoMaterial = {
      id: materialSeleccionadoArco.id,
      nombre: materialSeleccionadoArco.nombre,
      medida: materialSeleccionadoArco.medida,
      serie: tieneSerie ? serie : "",
      ip: tieneIp ? ip : "",
      mac: tieneMac ? mac : "",
      cantidad: cantidadNormalizada,
      foto: materialSeleccionadoArco.foto,
      relacion_id: materialContextoActivo === "editar" && materialOperacionActiva === "actualizar" && materialEditarIndex !== null
        ? (materialesEditandoArco[materialEditarIndex]?.relacion_id || "")
        : ""
    };

    if (materialContextoActivo === "infra") {
      if (materialOperacionActiva === "actualizar" && materialEditarIndex !== null) {
        materialesEditandoInfraestructura[materialEditarIndex] = nuevoMaterial;
      } else {
        materialesEditandoInfraestructura.push(nuevoMaterial);
      }
      renderEditarInfraMateriales();
    } else if (materialContextoActivo === "editar") {
      if (materialOperacionActiva === "actualizar" && materialEditarIndex !== null) {
        materialesEditandoArco[materialEditarIndex] = nuevoMaterial;
        materialSeleccionadoEditarIndex = materialEditarIndex;
      } else {
        materialesEditandoArco.push(nuevoMaterial);
        materialSeleccionadoEditarIndex = materialesEditandoArco.length - 1;
      }
      renderMaterialesEditarArco();
    } else {
      materialesAgregadosArco.push(nuevoMaterial);
      renderMaterialesAgregadosArco();
    }

    ModalManager.hide("modalAgregarMaterial");
    if (materialContextoActivo === "editar") {
      setTimeout(() => {
        if (!document.getElementById("modalEditarArco")?.classList.contains("show")) {
          ModalManager.show("modalEditarArco");
        }
        seleccionandoMaterialParaEditar = false;
        materialOperacionActiva = "agregar";
        materialEditarIndex = null;
      }, 250);
    } else if (materialContextoActivo === "infra") {
      setTimeout(() => {
        if (document.getElementById("modalEditarInfraestructura")?.classList.contains("show")) {
          document.body.classList.add("modal-open");
        }
        seleccionandoMaterialParaEditar = false;
        materialOperacionActiva = "agregar";
        materialEditarIndex = null;
      }, 250);
    }
  });

  listaMateriales?.addEventListener("click", e => {
    const btn = e.target.closest(".material-delete");
    if (!btn) return;

    const index = Number(btn.dataset.index);
    if (!Number.isNaN(index)) {
      materialesAgregadosArco.splice(index, 1);
      renderMaterialesAgregadosArco();
    }
  });

  listaMaterialesEditar?.addEventListener("click", e => {
    const btnEditar = e.target.closest(".material-edit");
    if (btnEditar) {
      const index = Number(btnEditar.dataset.index);
      if (!Number.isNaN(index)) {
        materialSeleccionadoEditarIndex = index;
        materialContextoActivo = "editar";
        materialOperacionActiva = "actualizar";
        materialEditarIndex = index;
        seleccionandoMaterialParaEditar = true;
        configurarModoModalMaterial();
        prepararModalMaterialSobreArco();
        ModalManager.show("modalAgregarMaterial");
        setTimeout(() => {
          seleccionarMaterialEnModal(materialesEditandoArco[materialEditarIndex]);
        }, 250);
      }
      return;
    }

    const btn = e.target.closest(".material-delete");
    if (btn) {
      const index = Number(btn.dataset.index);
      if (!Number.isNaN(index)) {
        materialesEditandoArco.splice(index, 1);
        materialSeleccionadoEditarIndex = null;
        renderMaterialesEditarArco();
      }
      return;
    }
  });

  renderMaterialesAgregadosArco();
  renderMaterialesEditarArco();
  renderInfraestructurasArco();
  actualizarModoPuenteSitio();

  // Inicialización de SmartCardPicker (Buscador Predictivo con Tarjeta) en modales
  initSmartCardPicker("ubicacionPrincipalSelect", {
    placeholder: "Escribe para buscar municipio o ubicación...",
    icon: "bi-geo-alt-fill",
    isOptional: false
  });

  initSmartCardPicker("agregar_infra_vinculada", {
    placeholder: "Buscar puente o sitio de enlace (opcional)...",
    icon: "bi-broadcast-pin",
    isOptional: true
  });

  initSmartCardPicker("editar_ubicacion", {
    placeholder: "Escribe para buscar municipio o ubicación...",
    icon: "bi-geo-alt-fill",
    isOptional: false
  });

  initSmartCardPicker("editar_infra_vinculada", {
    placeholder: "Buscar puente o sitio de enlace (opcional)...",
    icon: "bi-broadcast-pin",
    isOptional: true
  });

  initSmartCardPicker("editarInfraUbicacion", {
    placeholder: "Escribe para buscar municipio o ubicación...",
    icon: "bi-geo-alt-fill",
    isOptional: false
  });
});

// Delegación global para mostrar/ocultar detalles de materiales (Serie, IP, MAC)
document.addEventListener("click", e => {
  const toggleBtn = e.target.closest(".material-details-toggle");
  if (toggleBtn) {
    e.preventDefault();
    const card = toggleBtn.closest(".material-card-added");
    const dataContainer = card?.querySelector(".material-data");
    if (dataContainer) {
      const isHidden = dataContainer.classList.toggle("is-hidden");
      toggleBtn.classList.toggle("is-active", !isHidden);
      const icon = toggleBtn.querySelector("i");
      if (icon) {
        icon.className = isHidden ? "bi bi-eye" : "bi bi-eye-slash";
      }
    }
    return;
  }

  const toggleAllBtn = e.target.closest(".btn-toggle-all-data");
  if (toggleAllBtn) {
    e.preventDefault();
    const modal = toggleAllBtn.closest(".modal");
    if (!modal) return;
    const allDataContainers = modal.querySelectorAll(".material-data");
    if (!allDataContainers.length) return;

    const hasHidden = Array.from(allDataContainers).some(el => el.classList.contains("is-hidden"));
    allDataContainers.forEach(el => {
      el.classList.toggle("is-hidden", !hasHidden);
    });

    modal.querySelectorAll(".material-details-toggle").forEach(btn => {
      btn.classList.toggle("is-active", hasHidden);
      const icon = btn.querySelector("i");
      if (icon) {
        icon.className = hasHidden ? "bi bi-eye-slash" : "bi bi-eye";
      }
    });

    const label = toggleAllBtn.querySelector(".btn-toggle-all-text");
    const iconAll = toggleAllBtn.querySelector("i");
    if (label) {
      label.textContent = hasHidden ? "Ocultar datos" : "Ver datos";
    }
    if (iconAll) {
      iconAll.className = hasHidden ? "bi bi-eye-slash" : "bi bi-eye";
    }
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const contenedorMateriales = document.getElementById("contenedorMateriales");
  const editarInfraUbicacion = document.getElementById("editarInfraUbicacion");
  const buscarEditarInfraArcos = document.getElementById("buscarEditarInfraArcos");
  const editarInfraArcosLista = document.getElementById("editarInfraArcosLista");
  const editarInfraMateriales = document.getElementById("editarInfraMateriales");

  document.querySelectorAll(".verInfraComponentesBtn").forEach(btn => {
    btn.addEventListener("click", function () {
      const infraestructuras = JSON.parse(this.dataset.infraestructura || "[]");
      if (contenedorMateriales) {
        contenedorMateriales.innerHTML = renderInfraestructurasComponentes(infraestructuras) || `
          <div class="text-center p-3">
            <span class="badge bg-warning text-dark">
              <i class="bi bi-exclamation-circle"></i> Sin componentes
            </span>
          </div>`;
      }
    });
  });

  document.querySelectorAll(".editarInfraBtn").forEach(btn => {
    btn.addEventListener("click", async function () {
      const id = this.dataset.id;
      const form = document.getElementById("formEditarInfraestructura");
      form?.reset();
      materialesEditandoInfraestructura = [];
      renderEditarInfraMateriales();

      try {
        const res = await fetch(`../controllers/arcos_controller.php?action=get_infra&id=${encodeURIComponent(id)}`);
        const data = await res.json();
        if (data.error) {
          alert(data.error);
          return;
        }

        document.getElementById("editarInfraId").value = data.id || "";
        document.getElementById("editarInfraNombre").value = data.nombre || "";
        document.getElementById("editarInfraTipo").value = data.tipo || "Puente/Poste";
        document.getElementById("editarInfraUbicacion").value = data.ubicacion_id || "";
        document.getElementById("editarInfraLat").value = data.lat || "";
        document.getElementById("editarInfraLng").value = data.lng || "";

        syncSearchableSelect("editarInfraUbicacion");

        const badgeTipo = document.getElementById("editarInfraBadgeTipo");
        if (badgeTipo) {
          badgeTipo.textContent = data.tipo || "Nodo";
          badgeTipo.className = "badge " + (data.tipo === 'Sitio/Torre' ? 'bg-light text-primary' : 'bg-warning text-dark') + " fw-semibold";
        }

        // Marcar arcos vinculados
        const arcos = (data.arcos || []).map(String);
        document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check").forEach(check => {
          check.checked = arcos.includes(String(check.value));
        });

        // Marcar sitios vinculados
        const sitios = (data.sitios_vinculados || []).map(String);
        document.querySelectorAll("#editarInfraSitiosLista .editar-infra-sitio-check").forEach(check => {
          check.checked = sitios.includes(String(check.value));
        });

        // Marcar postes vinculados
        const postes = (data.postes_vinculados || []).map(String);
        document.querySelectorAll("#editarInfraPostesLista .editar-infra-poste-check").forEach(check => {
          check.checked = postes.includes(String(check.value));
        });

        // Marcar enlaces con enlaces vinculados (saltos y relevos)
        const enlacesConEnlaces = (data.enlaces_con_enlaces || []).map(String);
        document.querySelectorAll("#editarInfraEnlacesEnlacesLista .editar-infra-enlace-enlace-check").forEach(check => {
          check.checked = enlacesConEnlaces.includes(String(check.value));
        });

        // Limpiar búsquedas
        const buscarArcos = document.getElementById("buscarEditarInfraArcos");
        if (buscarArcos) buscarArcos.value = "";
        const buscarSitios = document.getElementById("buscarEditarInfraSitios");
        if (buscarSitios) buscarSitios.value = "";
        const buscarPostes = document.getElementById("buscarEditarInfraPostes");
        if (buscarPostes) buscarPostes.value = "";
        const buscarEnlacesEnlaces = document.getElementById("buscarEditarInfraEnlacesEnlaces");
        if (buscarEnlacesEnlaces) buscarEnlacesEnlaces.value = "";

        // Ejecutar filtros y renderizado de chips / contadores
        filtrarEditarInfraArcos();
        filtrarEditarInfraSitios(id);
        filtrarEditarInfraPostes(id);
        filtrarEditarInfraEnlacesEnlaces(id);
        renderChipsEditarInfra();
        actualizarContadoresEditarInfra();

        // Activar la primera pestaña de enlaces
        const firstSubTab = document.getElementById("tab-arcos-btn");
        if (firstSubTab && typeof bootstrap !== "undefined") {
          const bsTab = bootstrap.Tab.getOrCreateInstance(firstSubTab);
          bsTab.show();
        }

        materialesEditandoInfraestructura = (data.materiales || []).map(material => ({
          id: material.material_id,
          nombre: material.nombre,
          medida: material.medida,
          cantidad: material.cantidad || "1",
          serie: material.serie || "",
          ip: material.ip || "",
          mac: material.mac || "",
          foto: material.foto || ""
        }));
        renderEditarInfraMateriales();
      } catch (error) {
        alert("No se pudo cargar el puente/sitio.");
      }
    });
  });

  // Filtro estricto por ubicación del nodo
  document.getElementById("editarInfraUbicacion")?.addEventListener("change", () => {
    const ubicacionId = String(document.getElementById("editarInfraUbicacion")?.value || "");
    // Desmarcar elementos que no pertenezcan a la nueva ubicación seleccionada
    document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check:checked").forEach(chk => {
      const card = chk.closest(".arco-node-item");
      if (card && String(card.dataset.ubicacionId || "") !== ubicacionId) {
        chk.checked = false;
      }
    });
    document.querySelectorAll("#editarInfraSitiosLista .editar-infra-sitio-check:checked").forEach(chk => {
      const card = chk.closest(".sitio-node-item");
      if (card && String(card.dataset.ubicacionId || "") !== ubicacionId) {
        chk.checked = false;
      }
    });
    document.querySelectorAll("#editarInfraPostesLista .editar-infra-poste-check:checked").forEach(chk => {
      const card = chk.closest(".poste-node-item");
      if (card && String(card.dataset.ubicacionId || "") !== ubicacionId) {
        chk.checked = false;
      }
    });
    document.querySelectorAll("#editarInfraEnlacesEnlacesLista .editar-infra-enlace-enlace-check:checked").forEach(chk => {
      const card = chk.closest(".enlace-node-item");
      if (card && String(card.dataset.ubicacionId || "") !== ubicacionId) {
        chk.checked = false;
      }
    });

    filtrarEditarInfraArcos();
    filtrarEditarInfraSitios();
    filtrarEditarInfraPostes();
    filtrarEditarInfraEnlacesEnlaces();
    renderChipsEditarInfra();
    actualizarContadoresEditarInfra();
  });

  // Búsquedas de arcos y enlaces por texto
  document.getElementById("buscarEditarInfraArcos")?.addEventListener("input", filtrarEditarInfraArcos);
  document.getElementById("buscarEditarInfraSitios")?.addEventListener("input", () => filtrarEditarInfraSitios());
  document.getElementById("buscarEditarInfraPostes")?.addEventListener("input", () => filtrarEditarInfraPostes());
  document.getElementById("buscarEditarInfraEnlacesEnlaces")?.addEventListener("input", () => filtrarEditarInfraEnlacesEnlaces());

  // Botones de selección masiva y limpieza
  document.getElementById("btnSelectAllEditarInfraArcos")?.addEventListener("click", () => {
    document.querySelectorAll("#editarInfraArcosLista .arco-node-item").forEach(item => {
      if (item.style.display !== "none") {
        const chk = item.querySelector(".editar-infra-arco-check");
        if (chk) chk.checked = true;
      }
    });
    renderChipsEditarInfra('arcos');
    actualizarContadoresEditarInfra();
  });

  document.getElementById("btnSelectAllEditarInfraEnlacesEnlaces")?.addEventListener("click", () => {
    document.querySelectorAll("#editarInfraEnlacesEnlacesLista .enlace-node-item").forEach(item => {
      if (item.style.display !== "none") {
        const chk = item.querySelector(".editar-infra-enlace-enlace-check");
        if (chk) chk.checked = true;
      }
    });
    renderChipsEditarInfra('enlaces_enlaces');
    actualizarContadoresEditarInfra();
  });

  document.getElementById("btnClearEditarInfraArcos")?.addEventListener("click", () => {
    document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check").forEach(chk => chk.checked = false);
    renderChipsEditarInfra('arcos');
    actualizarContadoresEditarInfra();
  });

  document.getElementById("btnClearEditarInfraSitios")?.addEventListener("click", () => {
    document.querySelectorAll("#editarInfraSitiosLista .editar-infra-sitio-check").forEach(chk => chk.checked = false);
    renderChipsEditarInfra('sitios');
    actualizarContadoresEditarInfra();
  });

  document.getElementById("btnClearEditarInfraPostes")?.addEventListener("click", () => {
    document.querySelectorAll("#editarInfraPostesLista .editar-infra-poste-check").forEach(chk => chk.checked = false);
    renderChipsEditarInfra('postes');
    actualizarContadoresEditarInfra();
  });

  document.getElementById("btnClearEditarInfraEnlacesEnlaces")?.addEventListener("click", () => {
    document.querySelectorAll("#editarInfraEnlacesEnlacesLista .editar-infra-enlace-enlace-check").forEach(chk => chk.checked = false);
    renderChipsEditarInfra('enlaces_enlaces');
    actualizarContadoresEditarInfra();
  });

  // Eventos de cambios en checkboxes y clics en tarjetas dentro del modal
  document.getElementById("modalEditarInfraestructura")?.addEventListener("change", e => {
    if (e.target.classList.contains("editar-infra-arco-check")) {
      renderChipsEditarInfra('arcos');
      actualizarContadoresEditarInfra();
    } else if (e.target.classList.contains("editar-infra-sitio-check")) {
      renderChipsEditarInfra('sitios');
      actualizarContadoresEditarInfra();
    } else if (e.target.classList.contains("editar-infra-poste-check")) {
      renderChipsEditarInfra('postes');
      actualizarContadoresEditarInfra();
    } else if (e.target.classList.contains("editar-infra-enlace-enlace-check")) {
      renderChipsEditarInfra('enlaces_enlaces');
      actualizarContadoresEditarInfra();
    }
  });

  document.getElementById("modalEditarInfraestructura")?.addEventListener("click", e => {
    // Manejar clic en tarjeta de nodo para activar/desactivar checkbox
    const card = e.target.closest(".infra-node-card");
    if (card && e.target.tagName !== "INPUT" && !e.target.closest(".chip-remove-btn")) {
      const chk = card.querySelector('input[type="checkbox"]');
      if (chk) {
        chk.checked = !chk.checked;
        chk.dispatchEvent(new Event("change", { bubbles: true }));
      }
      return;
    }

    // Manejar clic en botón de remover chip
    const removeBtn = e.target.closest(".chip-remove-btn");
    if (removeBtn) {
      const tipo = removeBtn.dataset.targetTipo;
      const targetId = removeBtn.dataset.id;
      let selector = "";
      if (tipo === 'arcos') selector = `#editarInfraArcosLista .editar-infra-arco-check[value="${targetId}"]`;
      else if (tipo === 'sitios') selector = `#editarInfraSitiosLista .editar-infra-sitio-check[value="${targetId}"]`;
      else if (tipo === 'postes') selector = `#editarInfraPostesLista .editar-infra-poste-check[value="${targetId}"]`;
      else if (tipo === 'enlaces_enlaces') selector = `#editarInfraEnlacesEnlacesLista .editar-infra-enlace-enlace-check[value="${targetId}"]`;

      if (selector) {
        const chk = document.querySelector(selector);
        if (chk) {
          chk.checked = false;
          chk.dispatchEvent(new Event("change", { bubbles: true }));
        }
      }
    }
  });

  document.getElementById("btnEditarInfraAddMaterial")?.addEventListener("click", e => {
    e.preventDefault();
    materialContextoActivo = "infra";
    materialOperacionActiva = "agregar";
    materialEditarIndex = null;
    seleccionandoMaterialParaEditar = true;
    configurarModoModalMaterial();
    prepararModalMaterialSobreArco();
    ModalManager.show("modalAgregarMaterial");
  });

  editarInfraMateriales?.addEventListener("change", e => {
    const select = e.target.closest(".editar-infra-material-select");
    if (!select) return;

    const index = Number(select.dataset.index);
    if (Number.isNaN(index) || !materialesEditandoInfraestructura[index]) return;

    const material = buscarMaterialInfraestructura(select.value);
    materialesEditandoInfraestructura[index].id = select.value;
    materialesEditandoInfraestructura[index].medida = material?.medida || "";
    if (material?.medida === "pz") {
      materialesEditandoInfraestructura[index].cantidad = "1";
    }
    renderEditarInfraMateriales();
  });

  editarInfraMateriales?.addEventListener("input", e => {
    const cantidad = e.target.closest(".editar-infra-material-cantidad");
    const serie = e.target.closest(".editar-infra-material-serie");
    const input = cantidad || serie;
    if (!input) return;

    const index = Number(input.dataset.index);
    if (Number.isNaN(index) || !materialesEditandoInfraestructura[index]) return;

    if (cantidad) {
      materialesEditandoInfraestructura[index].cantidad = cantidad.value;
    }
    if (serie) {
      materialesEditandoInfraestructura[index].serie = serie.value;
    }
  });

  editarInfraMateriales?.addEventListener("click", e => {
    const btnEditar = e.target.closest(".editar-infra-material-edit");
    if (btnEditar) {
      const index = Number(btnEditar.dataset.index);
      if (!Number.isNaN(index)) {
        materialContextoActivo = "infra";
        materialOperacionActiva = "actualizar";
        materialEditarIndex = index;
        seleccionandoMaterialParaEditar = true;
        configurarModoModalMaterial();
        prepararModalMaterialSobreArco();
        ModalManager.show("modalAgregarMaterial");
        setTimeout(() => {
          seleccionarMaterialEnModal(materialesEditandoInfraestructura[materialEditarIndex]);
        }, 250);
      }
      return;
    }

    const btn = e.target.closest(".editar-infra-material-delete");
    if (!btn) return;

    const index = Number(btn.dataset.index);
    if (!Number.isNaN(index)) {
      materialesEditandoInfraestructura.splice(index, 1);
      renderEditarInfraMateriales();
    }
  });
});

  document.addEventListener('click', function (e) {
    const btnArco = e.target.closest('.generarBitacoraBtn');
    if (btnArco) {
      const bitArco = document.getElementById('bitacoraArcoId');
      const bitInfra = document.getElementById('bitacoraInfraId');
      if (bitArco) bitArco.value = btnArco.dataset.id || '';
      if (bitInfra) bitInfra.value = '';
      const title = document.querySelector('#modalBitacora .modal-title');
      if (title) title.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i>Generar Bitácora de Instalación (Arco)';
    }

    const btnInfra = e.target.closest('.generarBitacoraInfraBtn');
    if (btnInfra) {
      const bitArco = document.getElementById('bitacoraArcoId');
      const bitInfra = document.getElementById('bitacoraInfraId');
      if (bitArco) bitArco.value = '';
      if (bitInfra) bitInfra.value = btnInfra.dataset.id || '';
      const title = document.querySelector('#modalBitacora .modal-title');
      if (title) title.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i>Generar Bitácora de Instalación (' + (btnInfra.dataset.nombre || 'Puente / Sitio') + ')';
    }

    const btnModalInfra = e.target.closest('#btnModalAgregarInfra');
    if (btnModalInfra) {
      const chk = document.getElementById('checkPuenteSitio');
      if (chk) {
        chk.checked = true;
        chk.dispatchEvent(new Event('change'));
      }
    }

    const btnModalArco = e.target.closest('#btnModalAgregarArco');
    if (btnModalArco) {
      const chk = document.getElementById('checkPuenteSitio');
      if (chk && chk.checked) {
        chk.checked = false;
        chk.dispatchEvent(new Event('change'));
      }
    }
  });


// OBTENER UBICACIÓN ACTUAL CON PERMISO  JS MAPA

  let map;
  let mapInitialized = false;
  let selectedMarker = null;
  let lastSearchController = null;


  const modalMapaArcos = document.getElementById('modalMapaArcos');

  modalMapaArcos?.addEventListener('show.bs.modal', function (event) {

    const trigger = event.relatedTarget;
    if (!trigger) return;

    if (typeof L === "undefined") {
      const mapEl = document.getElementById("map");
      if (mapEl) {
        mapEl.innerHTML = `
          <div class="alert alert-warning m-3">
            No se pudo cargar Leaflet. Revisa la conexión para mostrar el mapa.
          </div>
        `;
      }
      return;
    }

    const lat = parseFloat(trigger.getAttribute('data-lat'));
    const lng = parseFloat(trigger.getAttribute('data-lng'));
    const nombre = trigger.getAttribute('data-nombre');
    const ubic = trigger.getAttribute('data-ubic');
    const fallas = trigger.getAttribute('data-fallas');

    // Inicializar mapa una sola vez
    if (!mapInitialized) {
      map = L.map('map').setView([lat || 19.432608, lng || -99.133209], 14);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
      }).addTo(map);

      mapInitialized = true;
    }

    // Recalcular tamaño
    setTimeout(() => {
      map.invalidateSize();
    }, 200);

    const popupContent = `
            <strong>${nombre}</strong><br>
            📍 ${ubic || 'Sin ubicación'}
            <br>⚠️ Fallas: ${fallas}  <br>
        `;

    // Limpiar marcador anterior
    if (selectedMarker) {
      map.removeLayer(selectedMarker);
      selectedMarker = null;
    }

    // Colocar marcador SOLO del arco seleccionado
    if (!isNaN(lat) && !isNaN(lng)) {
      selectedMarker = L.marker([lat, lng]).addTo(map);
      selectedMarker.bindPopup(popupContent).openPopup();
      map.setView([lat, lng], 16);
    }
  });



  function obtenerUbicacionActual(callback) {
    if (!navigator.geolocation) {
      console.warn("Geolocalización no soportada");
      callback(17.550826, -99.501462);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      pos => {
        callback(pos.coords.latitude, pos.coords.longitude);
      },
      err => {
        console.warn("Permiso denegado o error:", err.message);
        callback(17.550826, -99.501462); // fallback
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  }

  // modal seleccionar ubicacion en mapa
  let mapSelector, markerSelector;
  let selectedLat = null;
  let selectedLng = null;
  let selectorInitialized = false;

  // Destinos dinámicos para lat/lng (soporta agregar o editar)
  let inputLatDestino = null;
  let inputLngDestino = null;

  const modalMapa = ModalManager.get("modalSeleccionarMapa");

  // Abrir modal mapa (Agregar)
  document.querySelectorAll('#btnAbrirMapa').forEach(btn => {
    btn.addEventListener('click', () => {
      console.log('abrirMapa click, data-lat:', btn.dataset.lat, 'data-lng:', btn.dataset.lng);
      inputLatDestino = btn.dataset.lat;
      inputLngDestino = btn.dataset.lng;

      // Si los inputs ya tienen valores (editar rápido), pre-seleccionarlos
      const latVal = document.getElementById('latInput')?.value;
      const lngVal = document.getElementById('lngInput')?.value;
      if (latVal && lngVal) {
        selectedLat = latVal;
        selectedLng = lngVal;
      } else {
        selectedLat = null;
        selectedLng = null;
      }

      const parentCustom = btn.closest('.custom-modal');
      if (parentCustom) {
        parentCustom.style.display = 'none';
        // Guardamos referencia para restaurarla cuando se cierre el selector
          modalMapa._parentCustomModal = parentCustom;

      }
      // Definir modo: editar o agregar (para personalizar el texto y el comportamiento)
      const mode = parentCustom && parentCustom.id === 'modalEditarUbicacion' ? 'editar' : 'agregar';
      modalMapa._mode = mode;

      // Cambiar estilo del header y texto según modo
      const headerEl = document.querySelector('#modalSeleccionarMapa .modal-header');
      const titleEl = document.querySelector('#modalSeleccionarMapa .modal-title');
      const acceptBtn = document.getElementById('btnAceptarUbicacion');
      const helpEl = document.getElementById('mapHelp');
      if (headerEl && titleEl && acceptBtn && helpEl) {
        if (mode === 'editar') {
          headerEl.classList.remove('bg-success', 'text-white');
          headerEl.classList.add('bg-warning', 'text-dark');
          titleEl.textContent = 'Seleccionar ubicación (Editar)';
          helpEl.textContent = 'Editar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';
          acceptBtn.classList.remove('btn-success');
          acceptBtn.classList.add('btn-warning');
        } else {
          headerEl.classList.remove('bg-warning', 'text-dark');
          headerEl.classList.add('bg-success', 'text-white');
          titleEl.textContent = 'Seleccionar ubicación (Agregar)';
          helpEl.textContent = 'Agregar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';
          acceptBtn.classList.remove('btn-warning');
          acceptBtn.classList.add('btn-success');
        }
      }

      ModalManager.show("modalSeleccionarMapa");
    });
  });

  // Abrir modal mapa (Editar)
  document.getElementById('btnAbrirMapaEditar')?.addEventListener('click', () => {
    inputLatDestino = 'editar_lat';
    inputLngDestino = 'editar_lng';

    const latVal = document.getElementById('editar_lat')?.value;
    const lngVal = document.getElementById('editar_lng')?.value;
    if (latVal && lngVal) {
      selectedLat = latVal;
      selectedLng = lngVal;
    } else {
      selectedLat = null;
      selectedLng = null;
    }

    modalMapa._mode = 'editar';

    const headerElE = document.querySelector('#modalSeleccionarMapa .modal-header');
    const titleElE = document.querySelector('#modalSeleccionarMapa .modal-title');
    const acceptBtnE = document.getElementById('btnAceptarUbicacion');
    const helpElE = document.getElementById('mapHelp');
    if (headerElE && titleElE && acceptBtnE && helpElE) {
      headerElE.classList.remove('bg-success', 'text-white');
      headerElE.classList.add('bg-warning', 'text-dark');
      titleElE.textContent = 'Seleccionar ubicación (Editar)';
      helpElE.textContent = 'Editar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';
      acceptBtnE.classList.remove('btn-success');
      acceptBtnE.classList.add('btn-warning');
    }

    ModalManager.show("modalSeleccionarMapa");
  });

  document.getElementById('modalSeleccionarMapa')
    ?.addEventListener('shown.bs.modal', () => {
      if (typeof L === "undefined") {
        const mapEl = document.getElementById("mapSelector");
        if (mapEl) {
          mapEl.innerHTML = `
            <div class="alert alert-warning m-3">
              No se pudo cargar Leaflet. Revisa la conexión para seleccionar ubicación en el mapa.
            </div>
          `;
        }
        return;
      }

      // Resetear marcadores al abrir el selector para evitar problemas al colocar nuevos marcadores
      if (markerSelector && mapSelector) {
        try { mapSelector.removeLayer(markerSelector); } catch (e) { console.warn('Error al remover marcador al abrir selector:', e); }
      }
      markerSelector = null;

      if (!selectorInitialized) {

        obtenerUbicacionActual((lat, lng, error) => {

          // Si el selector ya tiene un valor preseleccionado (editar), centrar ahí
          const preLat = selectedLat || lat;
          const preLng = selectedLng || lng;

          mapSelector = L.map('mapSelector').setView([preLat, preLng], 13);

          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
          }).addTo(mapSelector);

          // Mostrar mensaje si hubo error al obtener la ubicación
          const statusEl = document.getElementById('mapStatus');
          if (error) {
            statusEl.textContent = 'Estado: ' + error;
            statusEl.classList.remove('text-muted');
            statusEl.classList.add('text-danger');
          } else {
            statusEl.textContent = 'Estado: ubicación obtenida.';
            statusEl.classList.remove('text-danger');
            statusEl.classList.add('text-success');
          }

          // Si ya había coordenadas seleccionadas (p.ej. por editar), colocar marcador
          if (selectedLat && selectedLng) {
            try { colocarMarcador(parseFloat(selectedLat), parseFloat(selectedLng)); } catch (e) { console.warn('pre-seed marker failed', e); }
          }

          // Click en mapa
          mapSelector.on('click', e => {
            colocarMarcador(e.latlng.lat, e.latlng.lng);
          });

        });

        selectorInitialized = true;
      } else {
        // Mapa ya inicializado: si hay valores preseleccionados, colocar marcador
        if (selectedLat && selectedLng) {
          try { colocarMarcador(parseFloat(selectedLat), parseFloat(selectedLng)); } catch (e) { console.warn('pre-seed marker failed', e); }
        }
      }

      setTimeout(() => {
        if (mapSelector) {
          mapSelector.invalidateSize();
        }
      }, 200);

    });

  function colocarMarcador(lat, lng) {
    selectedLat = Number(lat).toFixed(6);
    selectedLng = Number(lng).toFixed(6);

    const popupContent = `
      📍 <strong>Latitud:</strong> ${selectedLat}<br>
      📍 <strong>Longitud:</strong> ${selectedLng}
    `;

    if (!markerSelector) {
      markerSelector = L.marker([selectedLat, selectedLng], {
        draggable: true,
        autoPan: true
      }).addTo(mapSelector);

      markerSelector.on('dragend', () => {
        const pos = markerSelector.getLatLng();
        colocarMarcador(pos.lat, pos.lng);
      });
    } else {
      markerSelector.setLatLng([selectedLat, selectedLng]);
    }

    markerSelector.bindPopup(popupContent).openPopup();

    // Actualizar campos preview y destino (si existen)
    const lp = document.getElementById('latPreview');
    const lg = document.getElementById('lngPreview');
    if (lp) lp.value = selectedLat;
    if (lg) lg.value = selectedLng;

    if (inputLatDestino && document.getElementById(inputLatDestino)) document.getElementById(inputLatDestino).value = selectedLat;
    if (inputLngDestino && document.getElementById(inputLngDestino)) document.getElementById(inputLngDestino).value = selectedLng;
  }

  // ✅ Aceptar ubicación
  document.getElementById('btnAceptarUbicacion')?.addEventListener('click', () => {
    if (!selectedLat || !selectedLng) {
      alert('Selecciona una ubicación en el mapa');
      return;
    }

    const targetLatId = inputLatDestino || 'latInput';
    const targetLngId = inputLngDestino || 'lngInput';

    const latEl = document.getElementById(targetLatId);
    const lngEl = document.getElementById(targetLngId);
    if (latEl) latEl.value = selectedLat;
    if (lngEl) lngEl.value = selectedLng;

    // limpiar destino
    inputLatDestino = null;
    inputLngDestino = null;

    ModalManager.hide("modalSeleccionarMapa");
  });


  function solicitarPermisoDirecto() {
    const fallback = { lat: 17.550826, lng: -99.501462 };
    return new Promise(resolve => {
      if (!navigator.geolocation) {
        resolve({ lat: fallback.lat, lng: fallback.lng, error: 'Geolocalización no soportada' });
        return;
      }
      navigator.geolocation.getCurrentPosition(
        pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude, error: null }),
        err => {
          let msg = err.message || 'Error de geolocalización';
          try {
            switch (err.code) {
              case err.PERMISSION_DENIED:
                msg = 'Permiso denegado por el usuario.';
                break;
              case err.POSITION_UNAVAILABLE:
                msg = 'Posición no disponible.';
                break;
              case err.TIMEOUT:
                msg = 'Tiempo de espera agotado (timeout).';
                break;
            }
          } catch (e) { }
          resolve({ lat: fallback.lat, lng: fallback.lng, error: msg });
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  document.getElementById('btnUsarMiUbicacion')?.addEventListener('click', () => {
    const statusEl = document.getElementById('mapStatus');
    const helpEl = document.getElementById('mapHelp');
    statusEl.textContent = 'Estado: solicitando ubicación...';
    statusEl.classList.remove('text-danger', 'text-success');
    statusEl.classList.add('text-muted');
    helpEl.classList.add('d-none');

    const permissionQuery = (navigator.permissions && navigator.permissions.query)
      ? navigator.permissions.query({ name: 'geolocation' }).catch(() => ({ state: 'prompt' }))
      : Promise.resolve({ state: 'prompt' });

    permissionQuery.then(status => {
      console.log('permission.state =', status && status.state);
      return solicitarPermisoDirecto();
    }).then(({ lat, lng, error }) => {
      if (error) {
        statusEl.textContent = 'Estado: ' + error;
        statusEl.classList.remove('text-muted');
        statusEl.classList.add('text-danger');
        if (error.toLowerCase().includes('permiso denegado')) {
          helpEl.classList.remove('d-none');
          helpEl.textContent = 'Permiso denegado. Habilita “Ubicación” para este sitio desde la configuración del navegador (haz clic en el icono de candado en la barra de direcciones).';
        }
      } else {
        // Ubicación obtenida: inicializar o centrar mapa y colocar marcador
        statusEl.textContent = 'Estado: ubicación obtenida.';
        statusEl.classList.remove('text-danger');
        statusEl.classList.add('text-success');

        if (!selectorInitialized) {
          try {
            mapSelector = L.map('mapSelector').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '© OpenStreetMap'
            }).addTo(mapSelector);
            mapSelector.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));
            selectorInitialized = true;
          } catch (err) {
            console.warn('Error al inicializar mapa desde Usar mi ubicación:', err);
          }
        } else {
          try { mapSelector.setView([lat, lng], 13); } catch (e) { console.warn('Error al centrar mapa:', e); }
        }

        try { colocarMarcador(lat, lng); } catch (e) { console.warn('Error al colocar marcador:', e); }
      }
    }).catch(err => console.warn('Error al solicitar ubicación:', err));

    // Sincronizar selects de ubicaciones con los inputs de lat/lng
    (function setupUbicacionSync() {
      // Al cambiar la ubicación en el modal Agregar, copiar coordenadas si existen
      const addSel = document.querySelector('#modalAgregarArco select[name="ubicacion_id"]');
      if (addSel) {
        addSel.addEventListener('change', function () {
          const opt = this.selectedOptions[0];
          if (!opt) return;
          const lat = opt.dataset.lat;
          const lng = opt.dataset.lng;
          if (lat !== undefined) document.getElementById('latInput').value = lat || '';
          if (lng !== undefined) document.getElementById('lngInput').value = lng || '';
        });

        // Al abrir el modal, pre-seleccionar coords si ya hay opción seleccionada
        document.getElementById('modalAgregarArco')?.addEventListener('shown.bs.modal', () => {
          const opt = addSel.selectedOptions[0];
          if (!opt) return;
          if (opt.dataset.lat) document.getElementById('latInput').value = opt.dataset.lat;
          if (opt.dataset.lng) document.getElementById('lngInput').value = opt.dataset.lng;
        });
      }

      // Para el modal Editar
      const editSel = document.querySelector('#formEditarArco select[name="ubicacion_id"]');
      if (editSel) {
        editSel.addEventListener('change', function () {
          const opt = this.selectedOptions[0];
          if (!opt) return;
          const lat = opt.dataset.lat;
          const lng = opt.dataset.lng;
          if (lat !== undefined) document.getElementById('editar_lat').value = lat || '';
          if (lng !== undefined) document.getElementById('editar_lng').value = lng || '';
        });

        document.getElementById('modalEditarArco')?.addEventListener('shown.bs.modal', () => {
          const opt = editSel.selectedOptions[0];
          if (!opt) return;
          if (opt.dataset.lat) document.getElementById('editar_lat').value = opt.dataset.lat;
          if (opt.dataset.lng) document.getElementById('editar_lng').value = opt.dataset.lng;
        });
      }
    })();
  });

  // Cerrar modal
  document.querySelectorAll('.cerrarMapa').forEach(btn => {
    btn.addEventListener('click', () =>  ModalManager.hide("modalSeleccionarMapa"));
  });


  // SCRIPT PARA MODAL AGREGAR MATERIAL DEL ARCO

  // document.addEventListener('DOMContentLoaded', function () {
  //   const contenedor = document.getElementById('contenedorMateriales');
  //   const btnAdd = document.getElementById('btnAddRow');

  //   // 1. Manejar Switch de Serie
  //   contenedor.addEventListener('change', function (e) {
  //     if (e.target.classList.contains('toggle-serie')) {
  //       const row = e.target.closest('.material-row');
  //       const inputContainer = row.querySelector('.serie-input-container');
  //       const infoText = row.querySelector('.no-serie-text');
  //       const inputField = row.querySelector('input[name="serie[]"]');

  //       if (e.target.checked) {
  //         inputContainer.classList.remove('d-none');
  //         infoText.classList.add('d-none');
  //         inputField.focus();
  //       } else {
  //         inputContainer.classList.add('d-none');
  //         infoText.classList.remove('d-none');
  //         inputField.value = '';
  //       }
  //     }
  //   });

  //   // 2. Eliminar Fila
  //   contenedor.addEventListener('click', function (e) {
  //     if (e.target.closest('.remove-material')) {
  //       const filas = contenedor.querySelectorAll('.material-row');
  //       if (filas.length > 1) {
  //         e.target.closest('.material-row').remove();
  //       } else {
  //         alert("Al menos debes dejar un material.");
  //       }
  //     }
  //   });

  //   // 3. Clonar Fila (Para agregar múltiples)
  //   btnAdd.addEventListener('click', function () {
  //     const firstRow = document.querySelector('.material-row');
  //     const newRow = firstRow.cloneNode(true);

  //     // Limpiar valores del clon
  //     newRow.querySelector('select').value = '';
  //     newRow.querySelector('input[type="text"]').value = '';
  //     newRow.querySelector('.toggle-serie').checked = false;
  //     newRow.querySelector('.serie-input-container').classList.add('d-none');
  //     newRow.querySelector('.no-serie-text').classList.remove('d-none');

  //     // ID único para el switch del clon
  //     const uniqueId = 'sw_' + Date.now();
  //     newRow.querySelector('.toggle-serie').id = uniqueId;
  //     newRow.querySelector('.form-check-label').setAttribute('for', uniqueId);

  //     contenedor.appendChild(newRow);
  //   });
  // });

// El modal de mapa se abre con data-bs-toggle desde la celda del arco.
// El modal de formatos se abre desde la tabla de arcos o de sitios.
document.addEventListener("click", async (event) => {
  const button = event.target.closest(".verFormatosArcoBtn, .verFormatosInfraBtn");
  if (!button) return;

  const isInfra = button.classList.contains("verFormatosInfraBtn") || Boolean(button.dataset.infraId);
  const targetId = isInfra ? button.dataset.infraId : button.dataset.id;
  const container = document.getElementById("formatosArcoContenido");
  const title = document.getElementById("formatosArcoNombre");
  const createLink = document.getElementById("crearFormatoArco");
  const downloadAllButton = document.getElementById("descargarFormatosArco");
  const labels = {
    checklist: "Check List de Diagnóstico Inicial",
    quality: "Formato de Pruebas de Calidad",
    tools: "Formato de Herramientas"
  };

  title.textContent = button.dataset.nombre || "";
  createLink.href = isInfra 
    ? `formatos.php?infraestructura_id=${encodeURIComponent(targetId)}` 
    : `formatos.php?arco_id=${encodeURIComponent(targetId)}`;
  downloadAllButton.disabled = true;
  downloadAllButton.dataset.urls = "[]";
  container.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando formatos...</div>';

  try {
    const url = isInfra 
      ? `../controllers/formatos_ajax.php?infraestructura_id=${encodeURIComponent(targetId)}`
      : `../controllers/formatos_ajax.php?arco_id=${encodeURIComponent(targetId)}`;
    const response = await fetch(url);
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.message || "No se pudieron cargar los formatos.");

    const entityObj = isInfra ? (data.infra || data.arco || {}) : (data.arco || {});
    const cards = [];
    const bitacoraPdfUrl = isInfra 
      ? `../views/pdf/bitacora_arco.php?tipo=infra&id=${targetId}` 
      : `../views/pdf/bitacora_arco.php?id=${targetId}`;

    if (entityObj.tiene_bitacora) {
      cards.push(`
        <a class="formato-arco-card formato-arco-card--primary" href="${bitacoraPdfUrl}" target="_blank">
          <i class="bi bi-file-earmark-check"></i>
          <span><strong>Diagnóstico / Bitácora de instalación</strong><small>Documento base del ${isInfra ? "sitio" : "arco"}</small></span>
          <i class="bi bi-box-arrow-up-right"></i>
        </a>
      `);
    } else {
      cards.push(`
        <div class="formato-arco-card formato-arco-card--muted">
          <i class="bi bi-file-earmark-x"></i>
          <span><strong>Diagnóstico / Bitácora</strong><small>Aún no ha sido generado</small></span>
        </div>
      `);
    }

    data.formatos.forEach((format) => {
      const rawDate = format.fecha_servicio || format.created_at;
      const date = new Date(String(rawDate).replace(" ", "T"));
      const formattedDate = Number.isNaN(date.getTime())
        ? rawDate
        : date.toLocaleDateString("es-MX", { year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit" });
      const editParams = isInfra 
        ? `type=${encodeURIComponent(format.tipo)}&infraestructura_id=${targetId}&formato_id=${format.id}`
        : `type=${encodeURIComponent(format.tipo)}&arco_id=${targetId}&formato_id=${format.id}`;
      cards.push(`
        <div class="formato-arco-card">
          <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
          <span><strong>${labels[format.tipo] || "Formato de servicio"}</strong><small><i class="bi bi-calendar-event me-1"></i>${formattedDate}</small></span>
          <div class="formato-arco-card__actions">
            <a class="btn btn-outline-primary btn-sm" href="../controllers/formato_servicio_pdf.php?id=${format.id}" target="_blank" title="Ver PDF"><i class="bi bi-eye"></i></a>
            <a class="btn btn-outline-warning btn-sm" href="formato_llenar.php?${editParams}" title="Editar"><i class="bi bi-pencil"></i></a>
          </div>
        </div>
      `);
    });

    container.innerHTML = cards.join("");
    const pdfUrls = data.formatos.map((format) => `../controllers/formato_servicio_pdf.php?id=${format.id}`);
    downloadAllButton.dataset.urls = JSON.stringify(pdfUrls);
    downloadAllButton.disabled = pdfUrls.length === 0;
  } catch (error) {
    container.innerHTML = `<div class="alert alert-danger mb-0">${error.message}</div>`;
  }
});

document.addEventListener("click", (event) => {
  const button = event.target.closest("#descargarFormatosArco");
  if (!button || button.disabled) return;
  const urls = JSON.parse(button.dataset.urls || "[]");
  urls.forEach((url, index) => {
    window.setTimeout(() => {
      const link = document.createElement("a");
      link.href = url;
      link.download = "";
      document.body.appendChild(link);
      link.click();
      link.remove();
    }, index * 180);
  });
});

document.addEventListener('DOMContentLoaded', () => {
  // Elementos de Serie
  const checkSerie = document.getElementById('checkSerie');
  const serieContainer = document.getElementById('serieContainer');
  const serieInput = document.getElementById('serieInput');

  // Elementos de IP
  const checkIp = document.getElementById('checkIp');
  const ipContainer = document.getElementById('ipContainer');
  const ipInput = document.getElementById('ipInput');

  // Elementos de MAC
  const checkMac = document.getElementById('checkMac');
  const macContainer = document.getElementById('macContainer');
  const macInput = document.getElementById('macInput');

  // Evento Switch Serie
  if (checkSerie) {
    checkSerie.addEventListener('change', function () {
      if (this.checked) {
        serieContainer.classList.remove('d-none');
        setTimeout(() => serieInput.focus(), 100);
      } else {
        serieContainer.classList.add('d-none');
        serieInput.value = '';
      }
    });
  }

  // Evento Switch IP
  if (checkIp) {
    checkIp.addEventListener('change', function () {
      if (this.checked) {
        ipContainer.classList.remove('d-none');
        setTimeout(() => ipInput.focus(), 100);
      } else {
        ipContainer.classList.add('d-none');
        ipInput.value = '';
      }
    });
  }

  // Evento Switch MAC
  if (checkMac) {
    checkMac.addEventListener('change', function () {
      if (this.checked) {
        macContainer.classList.remove('d-none');
        setTimeout(() => macInput.focus(), 100);
      } else {
        macContainer.classList.add('d-none');
        macInput.value = '';
      }
    });
  }

  // Autoformato para la dirección MAC (convierte a mayúsculas y coloca dos puntos automáticamente)
  if (macInput) {
    macInput.addEventListener('input', function () {
      let val = this.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
      let formatted = val.match(/.{1,2}/g)?.join(':') || '';
      this.value = formatted.substring(0, 17);
    });
  }
});