
const config = {
  ArcosTable: {
    page: 1,
    limit: 3
  }
};

// RENDER PAGINACIÓN
function renderPagination(tableId) {
  const state = config[tableId];
  const allRows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));

  // SOLO filas visibles por búsqueda
  const visibleRows = allRows.filter(row => row.dataset.visible !== "0");

  const total = visibleRows.length;
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

  if (totalPages <= 1) return;

  let html = `
    <div class="w-100 text-center small text-muted mb-1">
      Mostrando ${shownStart}-${shownEnd} de ${total}
    </div>
    <nav><ul class="pagination pagination-sm mb-0">
  `;

  html += `
        <li class="page-item ${state.page === 1 ? 'disabled' : ''}">
            <button type="button" class="page-link" onclick="changePage('${tableId}', ${state.page - 1})">
                Anterior
            </button>
        </li>
    `;

  for (let i = 1; i <= totalPages; i++) {
    html += `
            <li class="page-item ${i === state.page ? 'active' : ''}">
                <button type="button" class="page-link" onclick="changePage('${tableId}', ${i})">
                    ${i}
                </button>
            </li>
        `;
  }

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

window.changePage = changePage;
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
  document.querySelectorAll("#ArcosTable tbody tr").forEach(r => r.dataset.visible = "1");
  renderPagination("ArcosTable");
});

window.addEventListener("resize", () => {
  renderPagination("ArcosTable");
});

document.addEventListener("DOMContentLoaded", () => {
  const botones = document.querySelectorAll(".tabla-toggle-btn");
  const vistas = document.querySelectorAll(".arcos-table-view");

  function cambiarTabla(targetId) {
    vistas.forEach(vista => {
      vista.classList.toggle("d-none", vista.id !== targetId);
    });

    botones.forEach(btn => {
      const activo = btn.dataset.tableViewTarget === targetId;
      const esInfra = btn.dataset.tableViewTarget === "tableViewInfra";
      btn.classList.toggle("active", activo);
      btn.classList.toggle("btn-success", activo && !esInfra);
      btn.classList.toggle("btn-primary", activo && esInfra);
      btn.classList.toggle("btn-outline-success", !activo && !esInfra);
      btn.classList.toggle("btn-outline-primary", !activo && esInfra);
      btn.classList.remove(activo ? (esInfra ? "btn-outline-primary" : "btn-outline-success") : (esInfra ? "btn-primary" : "btn-success"));
    });

    document.getElementById(targetId)?.scrollIntoView({ behavior: "smooth", block: "start" });
    if (targetId === "tableViewArcos") {
      renderPagination("ArcosTable");
    }
  }

  botones.forEach(btn => {
    btn.addEventListener("click", () => cambiarTabla(btn.dataset.tableViewTarget));
  });
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

document.querySelectorAll('.verMaterialesBtn').forEach(btn => {
  btn.addEventListener('click', function () {

    let nuevos = JSON.parse(this.dataset.nuevos || "[]");       // 🔴 PRINCIPALES
    let anteriores = JSON.parse(this.dataset.anteriores || "[]"); // 🔽 HISTORIAL

    let infraestructuras = JSON.parse(this.dataset.infraestructura || "[]");
    let contenedor = document.getElementById("contenedorMateriales");

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

      let seriesId = "series_" + index;
      let totalSeries = (m.series || []).length;

      let seriesHtml = "";

      if (totalSeries > 0) {

        let chips = m.series.map(s => `<span class="series-chip">${s}</span>`).join("");

        seriesHtml = `
          <div class="mt-2">

            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">
                Series: ${totalSeries}
              </small>

              <button class="btn btn-sm btn-outline-primary series-btn"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#${seriesId}">
                Ver series <i class="bi bi-chevron-down"></i>
              </button>
            </div>

            <div class="collapse" id="${seriesId}">
              <div class="series-panel">
                ${chips}
              </div>
            </div>

          </div>
        `;
      }

      let fechaActualHtml = textoFechaMaterial(m);

      html += `
         ${esNuevo 
                  ? `<div class="material-card border border-danger">` 
                  : `<div class="material-card border">`
                }
          <div class="d-flex align-items-center gap-2 mb-2 justify-content-between">
            <div class="d-flex align-items-center gap-2">
              ${imagenHtml}
              <div>
                <div class="fw-bold">${m.material}</div>
              </div>
              ${esNuevo 
                  ? `<span class="badge bg-danger">NUEVO</span>` 
                  : `<span class="badge bg-secondary"></span>`
                }
            </div>

            <div class="d-flex  align-items-center">
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

          ${seriesHtml}
          ${ esNuevo && anterior ? `
            
            <div class="mt-2">
              <button class="btn btn-sm btn-outline-secondary ver-anterior-btn"
              data-anterior='${JSON.stringify(historial)}'>
              Ver material anterior
            </button>

            <div class="collapse material mt-2" id="${histId}">
              <div class="material-card border border-secondary mt-2 p-2 rounded">
                <div class="d-flex align-items-center gap-2 justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    
                    ${
                      (!anterior.foto || anterior.foto === "null")
                      ? `<div class="material-img bg-secondary text-white d-flex align-items-center justify-content-center">Sin foto</div>`
                      : `<img src="../uploads/materiales/${anterior.foto}" class="material-img">`
                    }

                    <div>
                      <div class="fw-bold">${anterior.material}</div>
                      <small class="text-muted">Material anterior</small>
                    </div>
                  </div>

                  <div>
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                      ${anterior.cantidad}
                    </span>
                  </div>
                </div>

                ${
                  obtenerSeriesMaterial(anterior).length > 0
                  ? `<div class="mt-2">
                      <small class="text-muted">Series:</small><br>
                      ${obtenerSeriesMaterial(anterior).map(s => `<span class="series-chip">${s}</span>`).join("")}
                    </div>`
                  : `<div class="text-muted small mt-2">Sin series</div>`
                }
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
  // ✅ ACTIVAR COLLAPSE DE SERIES (CORRECTO)
  contenedor.querySelectorAll('.collapse').forEach(collapseEl => {

    collapseEl.addEventListener('show.bs.collapse', function () {
      let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
      if (btn) {
        btn.innerHTML = 'Ocultar serie <i class="bi bi-chevron-up"></i>';
      }
    });

    collapseEl.addEventListener('hide.bs.collapse', function () {
      let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
      if (btn) {
        btn.innerHTML = 'Ver serie <i class="bi bi-chevron-down"></i>';
      }
    });
  });

    // ✅ ACTIVAR TOOLTIP BOOTSTRAP
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

  });
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
                             <div>
                                 <strong>Serie:</strong>
                                 ${obtenerSeriesMaterial(anterior).join(", ") || "Sin serie"}
                             </div>
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

      materialesContainer.innerHTML = `
        <div class="text-center text-muted py-4">
          <div class="spinner-border text-warning" role="status"></div>
          <p class="mt-2 mb-0">Cargando datos...</p>
        </div>`;
      try {
        const res = await fetch(`../controllers/arcos_controller.php?action=get&id=${id}`);
        const data = await res.json();

        nombre.value = data.nombre;
        ubicacion.value = data.ubicacion_id;
        fecha.value = data.fecha_instalacion;


        // lat/lng (para editar)
        const latEl = document.getElementById('editar_lat');
        const lngEl = document.getElementById('editar_lng');
        if (latEl) latEl.value = data.lat ?? '';
        if (lngEl) lngEl.value = data.lng ?? '';

        materialesEditandoArco = (data.materiales || []).map(mat => ({
          id: mat.material_id,
          nombre: mat.nombre,
          medida: mat.medida,
          serie: mat.serie || "",
          cantidad: (mat.medida === "pz" ? "1" : (mat.cantidad || "1")),
          foto: mat.foto || "",
          relacion_id: mat.relacion_id || ""
        }));
        materialSeleccionadoEditarIndex = null;
        renderMaterialesEditarArco();
      } catch (error) {
        document.getElementById("listaMaterialesEditar").innerHTML = `<div class="alert alert-danger text-center">Error al cargar los datos.</div>`;
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
            const medida = m.medida === "m" ? "metros" : (m.medida === "pz" ? "piezas" : (m.medida || ""));
            const serie = m.serie ? `<span class="series-chip">${escapeHtml(m.serie)}</span>` : `<span class="text-muted small">Sin serie</span>`;
            const fecha = m.fecha_instalacion ? `<div class="text-muted small mt-2"><i class="bi bi-calendar-event"></i> Instalado: ${escapeHtml(formatearFechaHoraMaterial(m.fecha_instalacion))}</div>` : "";

            return `
              <div class="material-card border infra-component-card" data-infra-material="${matIndex}">
                <div class="d-flex align-items-center gap-2 justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    ${foto}
                    <div>
                      <div class="fw-bold">${escapeHtml(m.material)}</div>
                      <small class="text-muted">${escapeHtml(medida)}</small>
                    </div>
                  </div>
                  <span class="badge bg-primary fs-6 px-3 py-2">${escapeHtml(m.cantidad || 1)}</span>
                </div>
                <div class="mt-2">${serie}</div>
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
    const foto = escapeHtml(material.foto || catalogo?.foto || "");
    const imagen = foto
      ? `<img src="../uploads/materiales/${foto}" class="material-image" alt="${nombre}">`
      : `<div class="material-placeholder"><i class="bi bi-box-seam"></i></div>`;

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
          <div class="flex-grow-1">
            <div class="material-title text-capitalize">${nombre}</div>
            <div class="material-subtitle">
              ${medida === "pz" ? "Por pieza" : `${cantidad} ${escapeHtml(medida)}`}
            </div>
          </div>
        </div>

        <div class="material-data">
          ${serie ? `
            <div class="material-chip">
              <i class="bi bi-upc-scan text-primary"></i>
              <span>${serie}</span>
            </div>
          ` : ""}
        </div>

        <input type="hidden" name="material_id[]" value="${escapeHtml(material.id)}">
        <input type="hidden" name="relacion_id[]" value="${escapeHtml(material.relacion_id || "")}">
        <input type="hidden" name="cantidad[]" value="${cantidad}">
        <input type="hidden" name="serie[]" value="${serie}">
      </div>
    `;
  }).join("");
}

function actualizarContadorEditarInfraArcos() {
  const total = document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check:checked").length;
  const badge = document.getElementById("editarInfraArcosCount");
  if (badge) {
    badge.textContent = `${total} seleccionado${total === 1 ? "" : "s"}`;
  }
}

function filtrarEditarInfraArcos() {
  const ubicacionId = document.getElementById("editarInfraUbicacion")?.value || "";
  const filtro = (document.getElementById("buscarEditarInfraArcos")?.value || "").trim().toLowerCase();
  let visibles = 0;

  document.querySelectorAll("#editarInfraArcosLista .infra-arco-option").forEach(option => {
    const coincideUbicacion = ubicacionId !== "" && option.dataset.ubicacionId === ubicacionId;
    const coincideBusqueda = option.textContent.toLowerCase().includes(filtro);
    const visible = coincideUbicacion && coincideBusqueda;
    option.style.display = visible ? "" : "none";

    const check = option.querySelector(".editar-infra-arco-check");
    if (!coincideUbicacion && check) {
      check.checked = false;
    }

    if (visible) visibles++;
  });

  document.getElementById("editarInfraArcosEmpty")?.classList.toggle("d-none", visibles > 0);
  actualizarContadorEditarInfraArcos();
}

function esEdicionDeMaterial() {
  return materialOperacionActiva === "actualizar";
}

function clasesColorMaterialSeleccionado() {
  return esEdicionDeMaterial() ? ["border-warning"] : ["border-success"];
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
  modal.classList.toggle("modal-material-agregar", !editando);
  modal.classList.toggle("modal-material-editar", editando);

  const header = modal.querySelector(".modal-header");
  header?.classList.remove("bg-success", "bg-warning", "text-white", "text-dark");
  header?.classList.add(editando ? "bg-warning" : "bg-success", editando ? "text-dark" : "text-white");

  const closeBtn = header?.querySelector(".btn-close");
  closeBtn?.classList.toggle("btn-close-white", !editando);

  const title = header?.querySelector(".modal-title");
  if (title) {
    title.innerHTML = editando
      ? `<i class="bi bi-pencil-square me-2"></i>Editar Material del ${objetivoMaterial}`
      : `<i class="bi bi-box-seam me-2"></i>Agregar Material al ${objetivoMaterial}`;
  }

  const guardarBtn = document.getElementById("guardarMaterialModal");
  guardarBtn?.classList.remove("btn-success", "btn-warning");
  guardarBtn?.classList.add(editando ? "btn-warning" : "btn-success");
  if (guardarBtn) {
    guardarBtn.innerHTML = editando
      ? '<i class="bi bi-pencil-square me-2"></i>Actualizar Material'
      : '<i class="bi bi-check-circle me-2"></i>Agregar Material';
  }

  const configIconBox = document.querySelector("#camposDinamicos .rounded-circle");
  configIconBox?.classList.remove("bg-success", "bg-warning");
  configIconBox?.classList.add(editando ? "bg-warning" : "bg-success");

  const configIcon = document.querySelector("#camposDinamicos .bi-sliders");
  configIcon?.classList.remove("text-success", "text-warning");
  configIcon?.classList.add(editando ? "text-warning" : "text-success");

  const configTitle = document.querySelector("#camposDinamicos h5");
  configTitle?.classList.remove("text-success", "text-warning");
  configTitle?.classList.add(editando ? "text-warning" : "text-success");

  const materialSeleccionado = document.getElementById("materialSeleccionado");
  materialSeleccionado?.classList.remove("alert-success", "alert-warning");
  materialSeleccionado?.classList.add(editando ? "alert-warning" : "alert-success");
}

function resetModalAgregarMaterial() {
  materialSeleccionadoArco = null;
  configurarModoModalMaterial();

  document.getElementById("buscarMaterial") && (document.getElementById("buscarMaterial").value = "");
  document.getElementById("checkSerie") && (document.getElementById("checkSerie").checked = false);
  document.getElementById("serieInput") && (document.getElementById("serieInput").value = "");
  document.getElementById("cantidadInput") && (document.getElementById("cantidadInput").value = "");
  document.getElementById("materialSeleccionado") && (document.getElementById("materialSeleccionado").innerHTML = "Ningún material seleccionado");
  document.getElementById("serieContainer")?.classList.add("d-none");
  document.getElementById("cantidadContainer")?.classList.add("d-none");
  document.getElementById("camposDinamicos")?.classList.add("d-none");
  document.getElementById("configuracionColumna")?.classList.add("d-none");

  const materialesColumna = document.getElementById("materialesColumna");
  materialesColumna?.classList.remove("col-lg-8", "col-lg-7");
  materialesColumna?.classList.add("col-12");

  document.querySelectorAll("#modalAgregarMaterial .material-item").forEach(item => {
    item.style.display = "";
  });

  document.querySelectorAll("#modalAgregarMaterial .material-card").forEach(limpiarClasesSeleccionMaterial);
}

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
    const foto = escapeHtml(material.foto);
    const imagen = foto
      ? `<img src="../uploads/materiales/${foto}" class="material-image" alt="${nombre}">`
      : `<div class="material-placeholder"><i class="bi bi-box-seam"></i></div>`;

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
          <div class="flex-grow-1">
            <div class="material-title text-capitalize">${nombre}</div>
            <div class="material-subtitle">
              ${material.medida === "pz" ? "Por pieza" : `${cantidad} ${medida}`}
            </div>
          </div>
        </div>

        <div class="material-data">
          ${serie ? `
            <div class="material-chip">
              <i class="bi bi-upc-scan text-primary"></i>
              <span>${serie}</span>
            </div>
          ` : ""}
        </div>

        <input type="hidden" name="material_id[]" value="${escapeHtml(material.id)}">
        <input type="hidden" name="cantidad[]" value="${cantidad}">
        <input type="hidden" name="serie[]" value="${serie}">
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

  document.querySelectorAll("#modalAgregarMaterial .material-card").forEach(limpiarClasesSeleccionMaterial);

  const item = document.querySelector(`#modalAgregarMaterial .material-item[data-id="${CSS.escape(String(material.id))}"]`);
  marcarMaterialSeleccionado(item?.querySelector(".material-card"));

  document.getElementById("configuracionColumna")?.classList.remove("d-none");
  document.getElementById("camposDinamicos")?.classList.remove("d-none");

  const materialesColumna = document.getElementById("materialesColumna");
  materialesColumna?.classList.remove("col-12");
  materialesColumna?.classList.add("col-lg-8");

  const materialSeleccionado = document.getElementById("materialSeleccionado");
  if (materialSeleccionado) {
    materialSeleccionado.innerHTML = `<i class="bi bi-box-seam me-2"></i>${escapeHtml(material.nombre)}`;
  }

  const checkSerie = document.getElementById("checkSerie");
  const serieInput = document.getElementById("serieInput");
  const serieContainer = document.getElementById("serieContainer");
  const cantidadContainer = document.getElementById("cantidadContainer");
  const cantidadInput = document.getElementById("cantidadInput");
  const unidadMedida = document.getElementById("unidadMedida");
  const esPieza = material.medida === "pz";

  if (checkSerie) checkSerie.checked = Boolean(material.serie);
  if (serieInput) serieInput.value = material.serie || "";
  serieContainer?.classList.toggle("d-none", !material.serie);
  cantidadContainer?.classList.toggle("d-none", esPieza);

  if (unidadMedida) unidadMedida.textContent = material.medida;
  if (cantidadInput) {
    cantidadInput.min = esPieza ? "1" : "0.1";
    cantidadInput.step = esPieza ? "1" : "0.1";
    cantidadInput.value = esPieza ? "1" : (material.cantidad || "");
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
      ubicacionPrincipalSelect?.addEventListener("change", filtrarInfraArcos);
      infraArcosVinculados?.addEventListener("change", e => {
        if (e.target.classList.contains("infra-arco-check")) {
          actualizarContadorInfraArcos();
        }
      });

      function actualizarModoPuenteSitio() {
        const activo = Boolean(checkPuenteSitio?.checked);
        camposPuenteSitio?.classList.toggle("d-none", !activo);
        tipoPuenteSitioGroup?.classList.toggle("d-none", !activo);
        ubicacionPrincipalGroup?.classList.remove("d-none");

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

  modalAgregarMaterial.addEventListener("show.bs.modal", resetModalAgregarMaterial);
  modalAgregarMaterial.addEventListener("shown.bs.modal", () => {
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
  modalAgregarMaterial.addEventListener("hidden.bs.modal", () => {
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

      document.querySelectorAll("#modalAgregarMaterial .material-card").forEach(limpiarClasesSeleccionMaterial);

      marcarMaterialSeleccionado(this.querySelector(".material-card"));

      materialSeleccionadoArco = {
        id: this.dataset.id,
        nombre: this.dataset.label || this.dataset.nombre,
        medida: this.dataset.medida,
        foto: this.dataset.foto
      };

      document.getElementById("configuracionColumna")?.classList.remove("d-none");
      document.getElementById("camposDinamicos")?.classList.remove("d-none");

      const materialesColumna = document.getElementById("materialesColumna");
      materialesColumna?.classList.remove("col-12");
      materialesColumna?.classList.add("col-lg-8");

      const materialSeleccionado = document.getElementById("materialSeleccionado");
      if (materialSeleccionado) {
        materialSeleccionado.innerHTML = `<i class="bi bi-box-seam me-2"></i>${escapeHtml(materialSeleccionadoArco.nombre)}`;
      }

      checkSerie && (checkSerie.checked = false);
      document.getElementById("serieInput") && (document.getElementById("serieInput").value = "");
      document.getElementById("serieContainer")?.classList.add("d-none");

      const cantidadContainer = document.getElementById("cantidadContainer");
      const cantidadInput = document.getElementById("cantidadInput");
      const unidadMedida = document.getElementById("unidadMedida");

      const esPieza = materialSeleccionadoArco.medida === "pz";

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
    const cantidad = esPieza ? "1" : (document.getElementById("cantidadInput")?.value || "");
    const cantidadNumero = parseFloat(cantidad);

    if (tieneSerie && !serie) {
      alert("Ingrese la serie");
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

        const arcos = (data.arcos || []).map(String);
        document.querySelectorAll("#editarInfraArcosLista .editar-infra-arco-check").forEach(check => {
          check.checked = arcos.includes(String(check.value));
        });
        filtrarEditarInfraArcos();

        materialesEditandoInfraestructura = (data.materiales || []).map(material => ({
          id: material.material_id,
          nombre: material.nombre,
          medida: material.medida,
          cantidad: material.cantidad || "1",
          serie: material.serie || "",
          foto: material.foto || ""
        }));
        renderEditarInfraMateriales();
      } catch (error) {
        alert("No se pudo cargar el puente/sitio.");
      }
    });
  });

  editarInfraUbicacion?.addEventListener("change", filtrarEditarInfraArcos);
  buscarEditarInfraArcos?.addEventListener("input", filtrarEditarInfraArcos);
  editarInfraArcosLista?.addEventListener("change", e => {
    if (e.target.classList.contains("editar-infra-arco-check")) {
      actualizarContadorEditarInfraArcos();
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

  document.querySelectorAll('.generarBitacoraBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('bitacoraArcoId').value = this.dataset.id;
    });
  });


// OBTENER UBICACIÓN ACTUAL CON PERMISO  JS MAPA

  let map;
  let mapInitialized = false;
  let selectedMarker = null;
  let lastSearchController = null;


  const modalMapaArcos = document.getElementById('modalMapaArcos');

  modalMapaArcos.addEventListener('show.bs.modal', function (event) {

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
    .addEventListener('shown.bs.modal', () => {
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
  document.getElementById('btnAceptarUbicacion').addEventListener('click', () => {
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

  document.getElementById('btnUsarMiUbicacion').addEventListener('click', () => {
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
