const config = {
  arcosMaterialTable: {
    page: 1,
    limit: 5
  },
  reportesTable: {
    page: 1,
    limit: 6
  },
  ubicacionesTable: {
    page: 1,
    limit: 6
  }
};

function renderPagination(tableId) {
  const state = config[tableId];
  if (!state) return;

  const table = document.getElementById(tableId);
  if (!table) return;

  const allRows = getReportItems(table);
  const visibleRows = allRows.filter(row => row.dataset.visible !== "0");
  const total = visibleRows.length;
  const totalPages = Math.ceil(total / state.limit) || 1;

  if (state.page > totalPages) state.page = totalPages;
  if (state.page < 1) state.page = 1;

  const start = (state.page - 1) * state.limit;
  const end = start + state.limit;
  const shownStart = total === 0 ? 0 : start + 1;
  const shownEnd = Math.min(end, total);

  allRows.forEach(row => row.style.display = "none");
  visibleRows.slice(start, end).forEach(row => row.style.display = "");

  renderPaginationButtons(tableId, totalPages, shownStart, shownEnd, total);
  ajustarScrollReporte(tableId);
}

function renderPaginationButtons(tableId, totalPages, shownStart, shownEnd, total) {
  const name = tableId.replace("Table", "");
  const state = config[tableId];
  const pag = document.getElementById(`pagination-${name}`);
  if (!pag) return;

  pag.innerHTML = "";

  let html = `
    <div class="w-100 text-center small text-muted mb-1">
      Mostrando ${shownStart}-${shownEnd} de ${total}
    </div>
  `;

  if (totalPages > 1) {
    html += `<nav><ul class="pagination pagination-sm mb-0">`;
    html += `
      <li class="page-item ${state.page === 1 ? 'disabled' : ''}">
        <button type="button" class="page-link" onclick="changePage('${tableId}', ${state.page - 1})">Anterior</button>
      </li>
    `;

    for (let i = 1; i <= totalPages; i++) {
      html += `
        <li class="page-item ${i === state.page ? 'active' : ''}">
          <button type="button" class="page-link" onclick="changePage('${tableId}', ${i})">${i}</button>
        </li>
      `;
    }

    html += `
      <li class="page-item ${state.page === totalPages ? 'disabled' : ''}">
        <button type="button" class="page-link" onclick="changePage('${tableId}', ${state.page + 1})">Siguiente</button>
      </li>
    `;
    html += `</ul></nav>`;
  }

  pag.innerHTML = html;
}

function changePage(tableId, page) {
  if (!config[tableId]) return;
  config[tableId].page = Math.max(1, page);
  renderPagination(tableId);
}

function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table || !config[tableId]) return;

  const q = input.value.trim().toLowerCase();
  getReportItems(table).forEach(row => {
    row.dataset.visible = !q || row.textContent.toLowerCase().includes(q) ? "1" : "0";
  });

  config[tableId].page = 1;
  renderPagination(tableId);
}

function getReportItems(container) {
  const tableRows = Array.from(container.querySelectorAll("tbody tr"));
  if (tableRows.length) return tableRows;
  return Array.from(container.querySelectorAll(".report-page-item"));
}

function ajustarScrollReporte(tableId) {
  const table = document.getElementById(tableId);
  const box = table?.closest(".report-table-scroll, .report-card-scroll");
  if (!box) return;

  box.classList.remove("is-scroll-limited");

  window.requestAnimationFrame(() => {
    const pageWouldScroll = document.documentElement.scrollHeight > window.innerHeight + 8;
    box.classList.toggle("is-scroll-limited", pageWouldScroll);
  });
}

function escapeReporte(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function fechaReporteTexto(value) {
  if (!value) return "N/A";
  const date = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(date.getTime())) return String(value).slice(0, 10);
  return date.toLocaleDateString("es-MX", { day: "2-digit", month: "2-digit", year: "numeric" });
}

function fechaReporteDate(value) {
  if (!value) return null;
  const date = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? null : date;
}

function bimestreReporte(value) {
  const date = fechaReporteDate(value);
  if (!date) return "";
  return `${date.getFullYear()}-b${Math.ceil((date.getMonth() + 1) / 2)}`;
}

function bimestreActualReporte() {
  const date = new Date();
  return `${date.getFullYear()}-b${Math.ceil((date.getMonth() + 1) / 2)}`;
}

function aniosMantenimientosReporte(rows) {
  const years = new Set();
  rows.forEach(row => {
    const fecha = fechaReporteDate(row.fecha_mantenimiento);
    if (fecha) years.add(fecha.getFullYear());
  });
  years.add(new Date().getFullYear());
  return Array.from(years).sort((a, b) => b - a);
}

function anioDesdePeriodoReporte(periodo, rows) {
  if (periodo === "actual") return new Date().getFullYear();
  const match = String(periodo || "").match(/^(\d{4})-/);
  if (match) return Number(match[1]);
  return aniosMantenimientosReporte(rows)[0] || new Date().getFullYear();
}

function periodoOpcionesReporte(rows, periodo) {
  const etiquetas = {
    b1: "Ene-Feb",
    b2: "Mar-Abr",
    b3: "May-Jun",
    b4: "Jul-Ago",
    b5: "Sep-Oct",
    b6: "Nov-Dic"
  };
  const years = aniosMantenimientosReporte(rows);
  const selectedYear = anioDesdePeriodoReporte(periodo, rows);
  const bimestres = Array.from({ length: 6 }, (_, index) => {
    const bimestre = `b${index + 1}`;
    return {
      id: `${selectedYear}-${bimestre}`,
      label: etiquetas[bimestre]
    };
  });

  return {
    quick: [
      { id: `${selectedYear}-todos`, label: "Todos" }
    ],
    years,
    selectedYear,
    bimestres
  };
}

function filtrarMantenimientosPorPeriodo(rows, periodo) {
  if (periodo === "actual") {
    return rows.filter(row => bimestreReporte(row.fecha_mantenimiento) === bimestreActualReporte());
  }

  if (periodo === "todos") {
    const currentYear = new Date().getFullYear();
    return rows.filter(row => {
      const fecha = fechaReporteDate(row.fecha_mantenimiento);
      return fecha && fecha.getFullYear() === currentYear;
    });
  }

  if (periodo === "60") {
    const limite = new Date();
    limite.setDate(limite.getDate() - 60);
    return rows.filter(row => {
      const fecha = fechaReporteDate(row.fecha_mantenimiento);
      return fecha && fecha >= limite;
    });
  }

  const yearMatch = String(periodo || "").match(/^(\d{4})-todos$/);
  if (yearMatch) {
    const year = Number(yearMatch[1]);
    return rows.filter(row => {
      const fecha = fechaReporteDate(row.fecha_mantenimiento);
      return fecha && fecha.getFullYear() === year;
    });
  }

  return rows.filter(row => bimestreReporte(row.fecha_mantenimiento) === periodo);
}

function bimestresAnioReporte(year) {
  return [
    { id: `${year}-b1`, label: "Ene-Feb" },
    { id: `${year}-b2`, label: "Mar-Abr" },
    { id: `${year}-b3`, label: "May-Jun" },
    { id: `${year}-b4`, label: "Jul-Ago" },
    { id: `${year}-b5`, label: "Sep-Oct" },
    { id: `${year}-b6`, label: "Nov-Dic" }
  ];
}

function contarMantenimientosPorBimestre(rows, bimestres) {
  return bimestres.map(periodo => rows.filter(row => bimestreReporte(row.fecha_mantenimiento) === periodo.id).length);
}

function renderGraficaMantenimientosReporte(year = null) {
  const cont = document.getElementById("graficaMantenimientosReporte");
  if (!cont) return;

  const data = window.reporteMantenimientos || {};
  const years = aniosMantenimientosReporte([...(data.correctivos || []), ...(data.preventivos || [])]);
  const selectedYear = Number(year || years[0] || new Date().getFullYear());
  const bimestres = bimestresAnioReporte(selectedYear);
  const correctivos = contarMantenimientosPorBimestre(data.correctivos || [], bimestres);
  const preventivos = contarMantenimientosPorBimestre(data.preventivos || [], bimestres);
  const max = Math.max(1, ...correctivos, ...preventivos);
  const totalCorrectivos = correctivos.reduce((sum, value) => sum + value, 0);
  const totalPreventivos = preventivos.reduce((sum, value) => sum + value, 0);
  const totalArcos = Math.max(1, Number(window.reporteTotalArcos || 0));

  cont.innerHTML = `
    <div class="report-chart-years mb-2">
      <label class="report-year-control">
        <span>Año</span>
        <select class="form-select form-select-sm report-chart-year-select">
          ${years.map(anio => `
            <option value="${anio}" ${anio === selectedYear ? "selected" : ""}>${anio}</option>
          `).join("")}
        </select>
      </label>
    </div>
    <div class="report-chart-summary">
      <div>
        <span>Correctivos ${selectedYear}</span>
        <strong>${totalCorrectivos}</strong>
      </div>
      <div>
        <span>Preventivos ${selectedYear}</span>
        <strong>${totalPreventivos}</strong>
      </div>
      <div>
        <span>Total</span>
        <strong>${totalCorrectivos + totalPreventivos}</strong>
      </div>
    </div>
    <div class="report-chart-grid" aria-label="Grafica de mantenimientos por bimestre ${selectedYear}">
      ${bimestres.map((periodo, index) => {
        const correctivo = correctivos[index];
        const preventivo = preventivos[index];
        const altoCorrectivo = correctivo ? Math.max(12, Math.round((correctivo / max) * 100)) : 3;
        const altoPreventivo = preventivo ? Math.max(12, Math.round((preventivo / max) * 100)) : 3;
        const porcentajeCorrectivo = ((correctivo / totalArcos) * 100).toFixed(1);
        const porcentajePreventivo = ((preventivo / totalArcos) * 100).toFixed(1);
        return `
          <div class="report-chart-group">
            <div class="report-chart-bars">
              <button type="button"
                      class="report-chart-bar report-chart-correctivo ${correctivo ? "" : "is-empty"}"
                      style="height:${altoCorrectivo}%"
                      data-tipo="correctivos"
                      data-periodo="${escapeReporte(periodo.id)}"
                      ${correctivo ? "" : "disabled"}
                      title="Correctivos: ${correctivo} (${porcentajeCorrectivo}%)">
                <span><strong>${correctivo}</strong><small>${porcentajeCorrectivo}%</small></span>
              </button>
              <button type="button"
                      class="report-chart-bar report-chart-preventivo ${preventivo ? "" : "is-empty"}"
                      style="height:${altoPreventivo}%"
                      data-tipo="preventivos"
                      data-periodo="${escapeReporte(periodo.id)}"
                      ${preventivo ? "" : "disabled"}
                      title="Preventivos: ${preventivo} (${porcentajePreventivo}%)">
                <span><strong>${preventivo}</strong><small>${porcentajePreventivo}%</small></span>
              </button>
            </div>
            <div class="report-chart-label">${escapeReporte(periodo.label)}</div>
          </div>
        `;
      }).join("")}
    </div>
  `;

  cont.querySelectorAll(".report-chart-year-select").forEach(select => {
    select.addEventListener("change", () => renderGraficaMantenimientosReporte(Number(select.value)));
  });

  cont.querySelectorAll(".report-chart-bar:not(.is-empty)").forEach(bar => {
    bar.addEventListener("click", () => {
      renderMantenimientosReporte(bar.dataset.tipo || "correctivos", bar.dataset.periodo || "actual");
      const modal = document.getElementById("modalMantenimientosReporte");
      if (modal && window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
      }
    });
  });
}

function renderMantenimientosReporte(tipo, periodo = "actual") {
  const data = window.reporteMantenimientos || {};
  const rowsBase = data[tipo] || [];
  const periodoActivo = periodo === "actual" ? bimestreActualReporte() : periodo;
  const rows = filtrarMantenimientosPorPeriodo(rowsBase, periodo);
  const titulo = document.getElementById("modalMantenimientosTitulo");
  const cont = document.getElementById("contenidoMantenimientosReporte");
  const titulos = {
    preventivos: "Arcos con mantenimientos preventivos",
    correctivos: "Arcos con mantenimientos correctivos",
    todos: "Preventivos y correctivos"
  };

  if (titulo) titulo.textContent = titulos[tipo] || "Mantenimientos";
  if (!cont) return;

  const totalArcos = Math.max(1, Number(window.reporteTotalArcos || 0));
  const arcosUnicos = new Set(rows.map(row => row.arco_id || row.arco)).size;
  const porcentaje = ((rows.length / totalArcos) * 100).toFixed(1);
  const filtrosPeriodo = periodoOpcionesReporte(rowsBase, periodoActivo);
  const filtrosRapidos = filtrosPeriodo.quick.map(op => `
      <button type="button"
              class="btn btn-sm ${op.id === periodoActivo ? "btn-success" : "btn-outline-success"} reporte-periodo-btn"
              data-periodo="${escapeReporte(op.id)}"
              data-tipo="${escapeReporte(tipo)}">
        ${escapeReporte(op.label)}
      </button>
    `).join("");
  const filtrosAnios = `
    <label class="report-year-control">
      <span>Año</span>
      <select class="form-select form-select-sm reporte-year-select" data-tipo="${escapeReporte(tipo)}">
        ${filtrosPeriodo.years.map(anio => `
          <option value="${anio}" ${anio === filtrosPeriodo.selectedYear ? "selected" : ""}>${anio}</option>
        `).join("")}
      </select>
    </label>
  `;
  const filtrosBimestres = filtrosPeriodo.bimestres.map(op => `
      <button type="button"
              class="btn btn-sm ${op.id === periodoActivo ? "btn-success" : "btn-outline-success"} reporte-periodo-btn"
              data-periodo="${escapeReporte(op.id)}"
              data-tipo="${escapeReporte(tipo)}">
        ${escapeReporte(op.label)}
      </button>
    `).join("");

  cont.innerHTML = `
    <div class="reporte-periodo-panel mb-3">
      <div class="reporte-periodo-years">${filtrosAnios}</div>
      <div class="reporte-periodo-toolbar">${filtrosRapidos}</div>
      <div class="reporte-periodo-toolbar">${filtrosBimestres}</div>
    </div>
    <div class="report-detail-summary mb-3">
      <div>
        <span>Mantenimientos</span>
        <strong>${rows.length}</strong>
      </div>
      <div>
        <span>Arcos atendidos</span>
        <strong>${arcosUnicos}</strong>
      </div>
      <div>
        <span>Estadistica</span>
        <strong>${porcentaje}%</strong>
        <small class="text-muted">(${rows.length} / ${totalArcos}) * 100</small>
      </div>
      <div>
        <span>Componentes cambiados</span>
        <strong>${rows.reduce((sum, row) => sum + (Number(row.componentes) || 0), 0)}</strong>
      </div>
    </div>
    ${!rows.length ? `<div class="text-center text-muted py-4">No hay registros para este periodo.</div>` : `
    <div class="reporte-mantenimiento-list">
      ${rows.map(row => {
        const esCorrectivo = row.tipo_mantenimiento === "Correctivo";
        return `
          <div class="reporte-mantenimiento-item">
            <div class="reporte-mantenimiento-main">
              <div>
                <div class="fw-bold">${escapeReporte(row.arco || "Sin arco")}</div>
                <div class="text-muted small">${escapeReporte(row.ubicacion || "Sin ubicacion")}</div>
              </div>
              <span class="badge ${esCorrectivo ? "bg-warning text-dark" : "bg-success"}">${escapeReporte(row.tipo_mantenimiento || "N/A")}</span>
            </div>
            <div class="reporte-mantenimiento-meta">
              <span><i class="bi bi-calendar-event"></i> ${escapeReporte(fechaReporteTexto(row.fecha_mantenimiento))}</span>
              <span><i class="bi bi-person"></i> ${escapeReporte(row.tecnico_responsable || "Sin tecnico")}</span>
              <span><i class="bi bi-box-seam"></i> ${Number(row.componentes) || 0} componentes cambiados</span>
              <span><i class="bi bi-tools"></i> ${Number(row.piezas) || 0} piezas</span>
            </div>
            ${row.observaciones ? `<div class="reporte-mantenimiento-observacion">${escapeReporte(row.observaciones)}</div>` : ""}
          </div>
        `;
      }).join("")}
    </div>
    `}
  `;

  cont.querySelectorAll(".reporte-periodo-btn").forEach(btn => {
    btn.addEventListener("click", () => renderMantenimientosReporte(btn.dataset.tipo || tipo, btn.dataset.periodo || "actual"));
  });

  cont.querySelectorAll(".reporte-year-select").forEach(select => {
    select.addEventListener("change", () => {
      const selectedYear = Number(select.value);
      const currentYear = new Date().getFullYear();
      const periodoYear = selectedYear === currentYear ? bimestreActualReporte() : `${selectedYear}-b1`;
      renderMantenimientosReporte(select.dataset.tipo || tipo, periodoYear);
    });
  });
}

function renderCriticosReporte() {
  const rows = window.reporteCriticosMantenimiento || [];
  const cont = document.getElementById("contenidoCriticosReporte");
  if (!cont) return;

  if (!rows.length) {
    cont.innerHTML = `<div class="text-center text-muted py-4">No hay arcos vencidos o sin mantenimiento.</div>`;
    return;
  }

  cont.innerHTML = `
    <div class="report-detail-summary mb-3">
      <div>
        <span>Total criticos</span>
        <strong>${rows.length}</strong>
      </div>
      <div>
        <span>Sin mantenimiento</span>
        <strong>${rows.filter(row => row.estado === "Sin mantenimiento vencido").length}</strong>
      </div>
      <div>
        <span>Vencidos</span>
        <strong>${rows.filter(row => row.estado === "Mantenimiento vencido").length}</strong>
      </div>
    </div>
    <div class="reporte-mantenimiento-list reporte-criticos-list">
      ${rows.map(row => {
        const sinMantenimiento = row.estado === "Sin mantenimiento vencido";
        return `
          <div class="reporte-mantenimiento-item reporte-critico-item">
            <div class="reporte-mantenimiento-main">
              <div>
                <div class="fw-bold">${escapeReporte(row.arco || "Sin arco")}</div>
                <div class="text-muted small">${escapeReporte(row.ubicacion || "Sin ubicacion")}</div>
              </div>
              <span class="badge ${sinMantenimiento ? "bg-danger" : "bg-warning text-dark"}">${escapeReporte(row.estado || "Critico")}</span>
            </div>
            <div class="reporte-mantenimiento-meta">
              <span><i class="bi bi-clock-history"></i> Ultimo: ${escapeReporte(fechaReporteTexto(row.ultima_mantenimiento))}</span>
              <span><i class="bi bi-calendar-x"></i> Requerido: ${escapeReporte(fechaReporteTexto(row.fecha_requerida))}</span>
            </div>
          </div>
        `;
      }).join("")}
    </div>
  `;
}

function initReportes() {
  renderGraficaMantenimientosReporte();

  Object.keys(config).forEach(tableId => {
    const container = document.getElementById(tableId);
    if (container) getReportItems(container).forEach(row => row.dataset.visible = "1");
    renderPagination(tableId);
  });

  document.querySelectorAll('.btnVerArcos').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const nombre = btn.dataset.nombre || "";
      const titulo = document.getElementById('modalMaterialNombre');
      const cont = document.getElementById('contenidoArcos');

      if (titulo) titulo.textContent = nombre;
      if (!cont) return;

      cont.innerHTML = `
        <div class="text-center py-4">
          <div class="spinner-border text-info" role="status"></div>
          <div class="small text-muted mt-2">Cargando detalle...</div>
        </div>
      `;

      fetch(`../controllers/reportes_materiales_ajax.php?material_id=${encodeURIComponent(id)}`)
        .then(res => res.text())
        .then(html => cont.innerHTML = html)
        .catch(() => cont.innerHTML = '<p class="text-danger text-center mb-0">Error al cargar datos.</p>');
    });
  });

  document.querySelectorAll(".btnReporteMantenimientos").forEach(btn => {
    btn.addEventListener("click", () => renderMantenimientosReporte(btn.dataset.tipo || "todos", btn.dataset.periodo || "actual"));
  });

  document.querySelectorAll(".btnReporteCriticos").forEach(btn => {
    btn.addEventListener("click", renderCriticosReporte);
  });
}

window.changePage = changePage;
window.filterTable = filterTable;

document.addEventListener("DOMContentLoaded", initReportes);
window.addEventListener("resize", () => Object.keys(config).forEach(renderPagination));
