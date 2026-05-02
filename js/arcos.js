
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
  const visibleRows = allRows.filter(row => row.dataset.visible === "1");

  const total = visibleRows.length;
  const totalPages = Math.ceil(total / state.limit) || 1;

  // corregir página si se pasa
  if (state.page > totalPages) {
    state.page = totalPages;
  }

  const start = (state.page - 1) * state.limit;
  const end = start + state.limit;

  // ocultar TODAS primero
  allRows.forEach(row => row.style.display = "none");

  // mostrar SOLO las de la página actual
  visibleRows.slice(start, end).forEach(row => {
    row.style.display = "";
  });

  renderPaginationButtons(tableId, totalPages);
}

function renderPaginationButtons(tableId, totalPages) {
  const state = config[tableId];
  const name = tableId.replace("Table", "");
  const pag = document.getElementById(`pagination-${name}`);

  pag.innerHTML = "";

  if (totalPages <= 1) return;

  let html = `<nav><ul class="pagination pagination-sm">`;

  html += `
        <li class="page-item ${state.page === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="changePage('${tableId}', ${state.page - 1})">
                Anterior
            </button>
        </li>
    `;

  for (let i = 1; i <= totalPages; i++) {
    html += `
            <li class="page-item ${i === state.page ? 'active' : ''}">
                <button class="page-link" onclick="changePage('${tableId}', ${i})">
                    ${i}
                </button>
            </li>
        `;
  }

  html += `
        <li class="page-item ${state.page === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="changePage('${tableId}', ${state.page + 1})">
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
  config[tableId].page = page;
  renderPagination(tableId);
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("tbody tr").forEach(r => r.dataset.visible = "1");
  renderPagination("ArcosTable");
});

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

    let contenedor = document.getElementById("contenedorMateriales");

    if ((!anteriores || anteriores.length === 0) && (!nuevos || nuevos.length === 0)){
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

    let key = m.material + "_" + m.medida;

    // FECHA MÁS RECIENTE
    let fecha =
        m.fecha_mantenimiento ||
        m.fecha_instalacion ||
        "1900-01-01";

    if (!materialesAgrupados[key]) {
        materialesAgrupados[key] = [];
    }

    materialesAgrupados[key].push({
        ...m,
        fecha_real: fecha
    });

});

// RECORRER CADA MATERIAL
Object.keys(materialesAgrupados).forEach(key => {

    let lista = materialesAgrupados[key];

    // ORDENAR DEL MÁS NUEVO AL MÁS VIEJO
    lista.sort((a, b) =>
        new Date(b.fecha_real) - new Date(a.fecha_real)
    );

    // EL PRIMERO ES EL ACTUAL
    let actual = lista[0];

    // LOS DEMÁS SON HISTORIAL
    let historial = lista.slice(1);

    materiales.push({
        tipo: historial.length ? "cambiado" : "existente",
        nuevo: {
            material: actual.material,
            medida: actual.medida,
            cantidad: actual.cantidad,
            series: actual.serie ? [actual.serie] : [],
            foto: actual.foto,
            fecha_mantenimiento: actual.fecha_mantenimiento,
            fecha_instalacion: actual.fecha_instalacion
        },
        historial: historial
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

          <div class="text-muted small">
            ${
              esNuevo && m.fecha_mantenimiento
              ? `Mantenimiento el: ${new Date(m.fecha_mantenimiento).toLocaleDateString()}`
              : anterior && anterior.fecha_instalacion
                ? `Instalado el: ${new Date(anterior.fecha_instalacion).toLocaleDateString()}`
                : ''
            }
          </div>

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
                  (anterior.series || []).length > 0
                  ? `<div class="mt-2">
                      <small class="text-muted">Series:</small><br>
                      ${(anterior.series || []).map(s => `<span class="series-chip">${s}</span>`).join("")}
                    </div>`
                  : `<div class="text-muted small mt-2">Sin series</div>`
                }
                ${anterior.fecha_instalacion ? `<div class="text-muted small mt-2">Instalado el: ${new Date(anterior.fecha_instalacion).toLocaleDateString()}</div>` : ""}

              </div>
            </div>
      </div>
    ` : ``}
        </div>
      `;
    });

    html += `</div>`;
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
          mat.fecha_instalacion ||
          mat.fecha_mantenimiento ||
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
                Mantenimiento:
                ${
                    fecha !== "Sin fecha"
                    ? new Date(fecha).toLocaleDateString()
                    : "Sin fecha"
                }
            </div>

            <div class="material-grid">

            ${materiales.map(anterior => `

                <div class="material-card border border-secondary p-3 rounded">

                    <div class="d-flex gap-3 align-items-center">

                        ${
                            (!anterior.foto || anterior.foto === "null")
                            ? `
                                <div class="material-img bg-secondary text-white d-flex align-items-center justify-content-center">
                                    Sin foto
                                </div>
                              `
                            : `
                                <img src="../uploads/materiales/${anterior.foto}"
                                     class="material-img">
                              `
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
                                ${
                                    (anterior.series || []).join(", ")
                                    || "Sin serie"
                                }
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

        new bootstrap.Modal(document.getElementById("modalAnterior")).show();
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
  addMaterialBtn.addEventListener("click", () => {
    const newRow = materialContainer.firstElementChild.cloneNode(true);
    newRow.querySelectorAll("input, select").forEach(el => el.value = "");
    newRow.querySelectorAll("input[type='checkbox']").forEach(cb => cb.checked = false);
    newRow.querySelector(".serie-input").classList.add("d-none");
    newRow.querySelector(".cantidad-input").value = 1;

    // newRow.querySelector(".sinserie_input").checked = false;
    materialContainer.appendChild(newRow);
  });

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
        cantidad.value = 0;
      } else if (medida === "pz") {
        medida2 = "piezas";
        cantidad.classList.add("d-none");
        cantidad.value = "1";

      } else {
        medida2 = medida;

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
      const materialesContainer = document.getElementById("editarMaterialesContainer");
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

        materialesContainer.innerHTML = "";
        if (data.materiales.length) {
          data.materiales.forEach(mat => {
            materialesContainer.appendChild(crearFilaMaterial(data.todos_materiales, mat.material_id, mat.cantidad, mat.medida, mat.serie));
          });
        } else {
          materialesContainer.innerHTML = `<div class="alert alert-warning text-center">Sin materiales registrados.</div>`;
        }

        configurarEventosMateriales(materialesContainer, "editarAddMaterial");
      } catch (error) {
        materialesContainer.innerHTML = `<div class="alert alert-danger text-center">Error al cargar los datos.</div>`;
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
          
          <div style="min-width: 140px;" class="flex-grow-1 ${medida === "m" ? '' : 'd-none'} cantidadform">
            <label class="form-label fw-semibold">Cantidad</label>
            <div class="d-flex align-items-center gap-2">
              <input type="number" name="cantidad[]" class="form-control cantidad-input"
                min="1" step="0.5" value="${cantidad > 1 ? cantidad : '1.0'}">
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

  function configurarEventosMateriales(container, botonID) {
    const btnAdd = document.getElementById(botonID);
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

        if (medida === "m") {
          e.target.closest(".material-row")
            .querySelector(".cantidadform").classList.remove("d-none");
        } else if (medida === "pz") {
          e.target.closest(".material-row")
            .querySelector(".cantidadform").classList.add("d-none");
          e.target.closest(".material-row")
            .querySelector("input[name='cantidad[]']").value = "1";
        } else {
          e.target.closest(".material-row")
            .querySelector(".cantidadform").classList.remove("d-none");
        }
      }
    });
  }
});



const modalContainer = document.getElementById("materialesContainerModal");
const realContainer = document.getElementById("materialesContainer");
const template = document.getElementById("materialRowTemplate");

document.getElementById("addMaterialModal").onclick = () => {
  const clone = template.content.cloneNode(true);

  clone.querySelector(".remove-material").onclick = e => {
    e.target.closest(".material-row").remove();
  };

  modalContainer.appendChild(clone);
};

document.getElementById("guardarMateriales").onclick = () => {

  console.log("Guardando materiales...");
  console.log(modalContainer);

  realContainer.innerHTML = "";

  modalContainer.querySelectorAll(".material-row").forEach(row => {

    const material = row.querySelector('select[name="material_id[]"]').value;
    const cantidad = row.querySelector('input[name="cantidad[]"]').value;
    const serie = row.querySelector('input[name="serie[]"]').value;

    const medida = row.querySelector('select[name="material_id[]"]')
      .selectedOptions[0].dataset.medida;

    const div = document.createElement("div");
    div.className = "material-row d-flex align-items-center gap-2 mb-2 bg-light p-2 rounded flex-wrap";

    div.innerHTML = `
      <input type="hidden" name="material_id[]" value="${material}">
      <input type="hidden" name="cantidad[]" value="${cantidad}">
      <input type="hidden" name="serie[]" value="${serie}">
      <input type="hidden" name="medida[]" value="${medida}">

      <span class="fw-bold">Material ID: ${material}</span>
      <span class="text-success">${cantidad} ${medida}</span>
    `;

    realContainer.appendChild(div);
  });

  document.getElementById("resumenMateriales").innerHTML =
    `<i class="bi bi-check-circle text-success"></i>
     ${realContainer.children.length} materiales agregados`;

  bootstrap.Modal.getInstance(
    document.getElementById("modalMateriales")
  ).hide();
};




