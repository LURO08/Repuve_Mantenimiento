
const config = {
  revisionesTable: {
    page: 1,
    limit: 3
  }
};


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
  document.querySelectorAll("#revisionesTable tbody tr").forEach(r => r.dataset.visible = "1");
  renderPagination("revisionesTable");
});



document.querySelectorAll('.eliminar-form').forEach(form => {
  form.addEventListener('submit', e => {
    if (!confirm('¿Eliminar esta revisión?')) e.preventDefault();
  });
});

document.getElementById('ubicacionSelect').addEventListener('change', function () {
  const id = this.value;
  const arcoSel = document.getElementById('arcoSelect');
  arcoSel.innerHTML = '<option>Cargando...</option>';

  fetch('../controllers/revisiones_controller.php?action=get_arcos&ubicacion_id=' + id)
    .then(r => r.json())
    .then(data => {
      arcoSel.innerHTML = '<option value="">Seleccione un arco...</option>';
      data.forEach(a => {
        arcoSel.innerHTML += `<option value="${a.id}">${a.nombre}</option>`;
      });
    });
});

document.getElementById('arcoSelect').addEventListener('change', function () {
    const arcoId = this.value;
    const cont = document.getElementById('materialesContainer');
    const hidden = document.getElementById('materialesHidden');

    cont.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-success"></div>
        </div>
    `;

    fetch(`../controllers/revisiones_controller.php?action=get_materiales&arco_id=${arcoId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.length) {
                cont.innerHTML = `
                    <p class="text-danger">
                        Este arco no tiene materiales registrados.
                    </p>
                `;
                return;
            }

            let html = `<div class="row g-3 contenedor-materiales">`;

            data.forEach((m,index) => {
                const medidaLabel = m.medida === 'm' ? 'metros' : (m.medida === 'pz' ? 'piezas' : m.medida);
                html += `
                    <div class="-md-4 contenedor-material" >
                        <div class="card material-card shadow-sm h-100 mx-auto"
                          data-id="${arcoId}"
                          data-uid="${index}"
                          data-material_id="${m.id}"
                          data-serie="${m.serie || ''}"
                          data-cantidad="${m.cantidad || 1}"
                          title="${m.material}"
                          style="cursor:pointer;">

                          <img src="../uploads/materiales/${m.foto || 'default.png'}"
                            class="card-img-top"
                            style="height:80px; width:100%; object-fit:contain; padding:5px;"
                            onerror="this.src='../uploads/materiales/default.png'">

                          <div class="card-body text-center">
                            ${m.medida === 'm' ? `
                            <small class="text-muted d-block">
                              ${m.cantidad} metros
                            </small> ` : ''}

                            ${m.serie && m.serie !== 'null' && m.serie.trim() !== '' ? `
                                    <span>Serie:</span>
                                    <small class="text-muted d-block serie-container">
                                        <span class="serie-scroll">
                                            ${m.serie}
                                        </span>
                                    </small>
                                ` : ''}

                                <button type="button"
                                        class="btn btn-sm btn-outline-primary mt-2 btn-edit-material"
                                        data-id="${arcoId}"
                                        data-uid="${index}"
                                        data-material_id="${m.id}"
                                        data-material="${m.material}"
                                        data-medida="${medidaLabel}"
                                        data-serie="${m.serie || ''}"
                                        data-cantidad="${m.cantidad}">
                                    Editar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            cont.innerHTML = html;

            bindMaterialCards();
            bindEditButtons();
        });
});

function bindMaterialCards() {
    const hidden = document.getElementById('materialesHidden');

    document.querySelectorAll('.material-card').forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-edit-material')) return;

            const uid = this.dataset.uid;
            const materialId = this.dataset.materialId || this.dataset.material_id;
            const serie = this.dataset.serie || '';
            const cantidad = this.dataset.cantidad || 1;

            let bloque = hidden.querySelector(`.material-${uid}`);

            this.classList.toggle('border-success');
            this.classList.toggle('border-3');

            if (bloque) {
                bloque.remove();
            } else {
                bloque = document.createElement('div');
                bloque.className = `material-${uid}`;

                bloque.innerHTML = `
                    <input type="hidden" name="materiales[${uid}][uid]" value="${uid}">
                    <input type="hidden" name="materiales[${uid}][material_id]" value="${materialId}">
                    <input type="hidden" name="materiales[${uid}][cantidad]" value="${cantidad}">
                    <input type="hidden" name="materiales[${uid}][serie]" value="${serie}">
                    <input type="hidden" name="materiales[${uid}][cambiado]" value="1">
                `;

                hidden.appendChild(bloque);
            }
        });
    });
}

function bindEditButtons() {
    const modal = new bootstrap.Modal(document.getElementById('modalSerie'));

    document.querySelectorAll('.btn-edit-material').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();

            const id = btn.dataset.material_id;
            const medida = btn.dataset.medida;

            const card = this.closest('.material-card');
            const selectMaterial = document.getElementById('modalSelectMaterial');

            const DatosCantidad = document.getElementById('DatosCantidad');
            const DatosSerie = document.getElementById('DatosSeries');

            if (medida === 'metros') {
              DatosCantidad.classList.remove('d-none');
              DatosSerie.classList.add('d-none');
            } else {
              DatosCantidad.classList.add('d-none');
              DatosSerie.classList.remove('d-none');
            }

            fetch('../controllers/revisiones_controller.php?action=get_all_materiales')
            .then(r => r.json())
            .then(materiales => {
              selectMaterial.innerHTML = `<option value="">Seleccione material</option>`;
              
              materiales.forEach(mat => {
                const selected = mat.id == id ? 'selected' : '';
                selectMaterial.innerHTML += `
                  <option value="${mat.id}"
                           data-foto="${mat.foto}" 
                           data-medida="${mat.medida}" 
                           ${selected}>
                    ${mat.nombre}
                  </option>
                `;
              });
          });

            document.getElementById('modalMaterialId').value = card.dataset.id;
            document.getElementById('modalSerieInput').value = card.dataset.serie;
            document.getElementById('modalCantidadInput').value = card.dataset.cantidad;
            
            modal.show();
        });
    });
}
      
  // function guardarCambios() {
  //   const rowId = document.getElementById('modalMaterialId').value;
  //   const serie = document.getElementById('modalSerieInput').value;
  //   const cantidad = document.getElementById('modalCantidadInput').value;

  //   const card = document.querySelector(`.material-card[data-id="${rowId}"]`);
  //   const hidden = document.getElementById('materialesHidden');

  //   card.dataset.serie = serie;
  //   card.dataset.cantidad = cantidad;

  //   let bloque = hidden.querySelector(`.material-${rowId}`);

  //   if (bloque) {
  //       bloque.querySelector(`[name="materiales[${rowId}][serie]"]`).value = serie;
  //       bloque.querySelector(`[name="materiales[${rowId}][cantidad]"]`).value = cantidad;
  //   }
  // }


document.querySelectorAll('.verMaterialesBtn').forEach(btn => {
  btn.addEventListener('click', function () {

    let materialesOriginal = JSON.parse(this.dataset.materiales);
    let contenedor = document.getElementById("contenedorMateriales");

    if (!materialesOriginal || materialesOriginal.length === 0) {
      contenedor.innerHTML = `
        <div class="text-center p-3">
          <span class="badge bg-warning text-dark">
            <i class="bi bi-exclamation-circle"></i> Sin materiales
          </span>
        </div>`;
      return;
    }

    // ✅ AGRUPAR MATERIALES
    let agrupados = {};

    materialesOriginal.forEach(m => {
      let key = m.material + "_" + m.medida;

      if (!agrupados[key]) {
        agrupados[key] = {
          material: m.material,
          medida: m.medida,
          cantidad: 0,
          series: [],
          foto: m.foto
        };
      }

      agrupados[key].cantidad += parseFloat(m.cantidad);

      if (m.serie && m.serie.trim() !== "") {
        agrupados[key].series.push(m.serie);
      }
    });

    let materiales = Object.values(agrupados);

    let html = `<div class="material-grid">`;

    materiales.forEach((m, index) => {

      let imagenHtml = (!m.foto || m.foto === "null" || m.foto.trim() === "")
        ? `<div class="d-flex align-items-center justify-content-center bg-secondary text-white material-img">Sin foto</div>`
        : `<img src="../uploads/materiales/${m.foto}" class="material-img">`;

      let medida2 = m.medida === 'm' ? 'metros' : m.medida === 'pz' ? 'piezas' : m.medida;
      if (medida2 === 'piezas' && m.cantidad === 1) medida2 = "pieza";

      let seriesId = "series_" + index;
      let totalSeries = m.series.length;

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
        <div class="material-card">

          <div class="d-flex align-items-center gap-2 mb-2 justify-content-between">
            <div class="d-flex align-items-center gap-2">
              ${imagenHtml}
              <div>
                <div class="fw-bold">${m.material}</div>
              </div>
            </div>

            <div class="d-flex  align-items-center">
              <span class="badge bg-success fs-6 px-3 py-2 me-2">
                ${m.cantidad}
              </span>
              <span class="text-muted small">${medida2}</span>
            </div>
          </div>

          ${seriesHtml}

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
          btn.innerHTML = 'Ocultar series <i class="bi bi-chevron-up"></i>';
        }
      });

      collapseEl.addEventListener('hide.bs.collapse', function () {
        let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
        if (btn) {
          btn.innerHTML = 'Ver series <i class="bi bi-chevron-down"></i>';
        }
      });

    });


    // ✅ ACTIVAR TOOLTIP BOOTSTRAP
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

  });


});

// Nuevo método para cargar evidencias al hacer clic en el botón (CORRECTO, MÁS COMPLETO Y CON MEJOR DISEÑO)
document.querySelectorAll(".verEvidenciasBtn").forEach(btn => {
  btn.addEventListener("click", async function () {
    const id = this.getAttribute('data-id');
    const container = document.getElementById("evidenciasContainer");

    container.innerHTML = `
      <div class="text-center p-3" style="grid-column: 1 / -1;">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">Cargando evidencias...</p>
      </div>
    `;

    try {
      const res = await fetch(`../controllers/revisiones_controller.php?action=get_evidencias&revision_id=${encodeURIComponent(id)}`)
      const data = await res.json();


      if (!data.length) {
        container.innerHTML = `
          <div class="alert alert-warning w-100 text-center">
            No hay evidencias registradas.
          </div>
        `;
        return;
      }

      let html = "";
      data.forEach(ev => {
        if (!ev || !ev.filename) return;
        // MOSTRAR PDF CON ÍCONO Y ENLACE
        if (ev.mimetype && ev.mimetype.includes("pdf")) {
          html += `
            <div class="card shadow-sm border-0 evidencia-card pdf-card"
                  data-src="../uploads/revisiones/${ev.filename}"
                data-type="pdf"
                style="cursor:pointer;">

                <div class="pdf-preview">
                    <i class="bi bi-file-earmark-pdf-fill pdf-icon"></i>
                </div>

                <div class="pdf-info">
                    <p class="pdf-name" title="${ev.filename}">
                        ${ev.filename}
                    </p>
                </div>
            </div>
          `;

        } else {
          // MOSTRAR IMAGEN CON CLASE PARA ABRIR EN MODAL
          html += `
            <div class="card shadow-sm border-0 evidencia-card">
              <img src="../uploads/revisiones/${ev.filename}" 
                  class="img-fluid rounded evidencia-img abrir-imagen" 
                  data-img="../uploads/revisiones/${ev.filename}"
                  alt="Evidencia"
                  style="
                      width:100%;
                      height:180px;
                      object-fit:cover;
                      cursor:pointer;
                  ">
            </div>
          `;

        }
      });

      document.getElementsByClassName("tituloEvidencias")[0].textContent = `Evidencias (${data.length})`;

      container.innerHTML = html;

      // AGREGAR FUNCIONALIDAD DE VISOR DE PDF EN MODAL (CORRECTO, CON MEJOR DISEÑO Y USABILIDAD)
      document.querySelectorAll(".pdf-card").forEach(card => {
        card.addEventListener("click", function () {
            const src = this.dataset.src;

            const modalBody = document.querySelector("#modalPdf .modal-body");

            modalBody.innerHTML = `
                <button type="button" 
                        class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                        data-bs-dismiss="modal">
                </button>

                <iframe src="${src}" 
                        style="width:100%; height:80vh; border:none; border-radius:10px;">
                </iframe>
            `;

            const modal = new bootstrap.Modal(document.getElementById("modalPdf"));
            modal.show();
        });
    });

      // AGREGAR FUNCIONALIDAD DE VISOR DE IMÁGENES (CORRECTO, CON NAVEGACIÓN ENTRE IMÁGENES)
      let imagenes = [];
      let imagenActual = 0;

      document.querySelectorAll(".abrir-imagen").forEach((img, index) => {
        imagenes.push(img.dataset.img);

        img.addEventListener("click", function () {
          imagenActual = index;
          mostrarImagen();
          const modal = new bootstrap.Modal(document.getElementById("modalImagen"));
          modal.show();
        });
      });

      function mostrarImagen() {
        document.getElementById("imagenAmpliada").src = imagenes[imagenActual];
      }

      document.getElementById("btnPrevImg").onclick = function () {
        imagenActual--;
        if (imagenActual < 0) {
          imagenActual = imagenes.length - 1;
        }
        mostrarImagen();
      };

      document.getElementById("btnNextImg").onclick = function () {
        imagenActual++;
        if (imagenActual >= imagenes.length) {
          imagenActual = 0;
        }
        mostrarImagen();
      };

    } catch (error) {
      container.innerHTML = `
        <div class="alert alert-danger w-100 text-center">
          Error al cargar evidencias
        </div>
      `;
      console.error(error);
    }
  });
});


// METOOD PARA EVIDENCIAS EN EL FORMULARIO DE EDICIÓN (PREVIEW ANTES DE SUBIR)
document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("evidenciasInput");
    const preview = document.getElementById("previewEvidencias");

    if (!input) return;

    input.addEventListener("change", function () {
        preview.innerHTML = "";

        Array.from(this.files).forEach((file, index) => {
            const col = document.createElement("div");
            col.className = "col-md-4";

            const card = document.createElement("div");
            card.className = "card shadow-sm";

            if (file.type.startsWith("image/")) {
                const img = document.createElement("img");
                img.src = URL.createObjectURL(file);
                img.className = "card-img-top";
                img.style.height = "60px";
                img.style.objectFit = "cover";

                card.appendChild(img);
            } else if (file.type === "application/pdf") {
                card.innerHTML = `
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
                        <p class="small mt-2 mb-0">${file.name}</p>
                    </div>
                `;
            }

            col.appendChild(card);
            preview.appendChild(col);
        });
    });
});


function renderEvidencias(files) {
    const container = document.getElementById("evidenciasContainer");
    container.innerHTML = "";

    files.forEach(file => {
        let html = "";

        if (file.tipo === "pdf") {
            html = `
                <div class="col-md-4">
                    <a href="${file.ruta}" target="_blank" class="text-decoration-none">
                        <div class="card shadow-sm text-center p-3">
                            <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
                            <small>${file.nombre}</small>
                        </div>
                    </a>
                </div>
            `;
        } else {
            html = `
                <div class="col-md-4">
                    <img src="${file.ruta}" 
                         class="img-fluid rounded shadow-sm evidencia-img"
                         style="cursor:pointer">
                </div>
            `;
        }

        container.innerHTML += html;
    });
}


document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("evidenciasInput");
    const preview = document.getElementById("previewEvidencias");

    let archivosSeleccionados = [];

    input.addEventListener("change", function () {
        const nuevos = Array.from(this.files);

        // evitar duplicados opcional
        archivosSeleccionados = [...archivosSeleccionados, ...nuevos];

        actualizarInput();
        renderPreview();
    });

    function actualizarInput() {
        const dt = new DataTransfer();
        archivosSeleccionados.forEach(file => dt.items.add(file));
        input.files = dt.files;
    }

    function renderPreview() {
        preview.innerHTML = "";

        archivosSeleccionados.forEach((file, index) => {

            let contenido = "";

            if (file.type.startsWith("image/")) {
                contenido = `<img src="${URL.createObjectURL(file)}">`;
            } else {
                contenido = `
                    <div class="preview-pdf">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                `;
            }

            const div = document.createElement("div");
            div.className = "preview-item";

            div.innerHTML = `
                ${contenido}

                <button class="preview-remove" data-index="${index}">
                    ✕
                </button>

                <div class="preview-name">${file.name}</div>
            `;

            preview.appendChild(div);
        });

        // EVENTO ELIMINAR
        document.querySelectorAll(".preview-remove").forEach(btn => {
            btn.addEventListener("click", function () {
                const index = this.dataset.index;

                archivosSeleccionados.splice(index, 1);

                actualizarInput();
                renderPreview();
            });
        });
    }
});

