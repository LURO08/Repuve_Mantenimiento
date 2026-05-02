
const config = {
  revisionesTable: {
    page: 1,
    limit: 3
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

  const pag = document.getElementById("pagination-revisiones");
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
  document.querySelectorAll("#revisionesTable tbody tr").forEach(r => r.dataset.visible = "1");
  renderPagination("revisionesTable");
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
  cont.innerHTML = '<div class="text-center"><div class="spinner-border text-success"></div></div>';

  fetch('../controllers/revisiones_controller.php?action=get_materiales&arco_id=' + arcoId)
    .then(res => res.json())
    .then(data => {
      if (!data.length) {
        cont.innerHTML = '<p class="text-danger">Este arco no tiene materiales registrados.</p>';
        return;
      }

      let html = '<div>  </div> ';



       html += `<table class="table table-sm table-materiales">
        
        <thead>
        <tr>
          <th class="material-name-col">Material</th>
          <th class="material-serie-col" col="2">Serie</th>
          <th class="material-qty-col text-center">Cantidad</th>
          <th class="material-qty-col text-center">Cambiado</th>
          <th class="material-edit-col text-center">Editar</th>
        </tr></thead><tbody>`;

      data.forEach(m => {

        const medidaLabel = m.medida === 'm' ? 'metros' : (m.medida === 'pz' ? 'piezas' : m.medida);

        // 🔥 SI ES POR PIEZAS → SEPARAR EN FILAS
        if (m.medida === 'pz') {

          for (let i = 0; i < m.cantidad; i++) {

            html += `
        <tr class="material-row">

          <td class="material-name d-flex align-items-center gap-2">
            <img src="../uploads/materiales/${m.foto || 'default.png'}"
                class="material-img" width="40" height="40"
                style="object-fit:cover; border-radius:6px;"
                onerror="this.src='../uploads/materiales/default.png'">

            <span class="small">${m.material} #${i + 1}</span>
          </td>

          <td class="small text-muted">
            <span class="text-secondary">Sin serie</span>
          </td>

          <td class="text-center small">
            1 <span class="text-muted">pieza</span>
          </td>

          <td class="text-center small">
            <input type="checkbox" class="material-cambiado" data-id="${m.id}">
          </td>

          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary btn-edit-serie"
                    data-id="${m.id}"
                    data-material-id="${m.materialId}"
                    data-material="${m.material}"
                    data-medida="piezas"
                    data-serie=""
                    data-cantidad="1">
              <i class="bi bi-pencil-square"></i>
            </button>
          </td>

        </tr>
      `;
          }

        } else {

          html += `
      <tr class="material-row">

        <td class="material-name d-flex align-items-center gap-2">
          <img src="../uploads/materiales/${m.foto || 'default.png'}"
              class="material-img" width="40" height="40"
              style="object-fit:cover; border-radius:6px;"
              onerror="this.src='../uploads/materiales/default.png'">

          <span class="small">${m.material}</span>
        </td>

        <td class="small text-muted">
          ${m.serie || '<span class="text-secondary">Sin serie</span>'}
        </td>

        <td class="text-center small">
          ${m.cantidad} <span class="text-muted">${medidaLabel}</span>
        </td>

        <td class="text-center small">
          <input type="checkbox" class="material-cambiado" data-id="${m.id}">
        </td>

        <td class="text-center">
          <button class="btn btn-sm btn-outline-primary btn-edit-serie"
                  data-id="${m.id}"
                  data-material-id="${m.materialId}"
                  data-material="${m.material}"
                  data-medida="${medidaLabel}"
                  data-serie="${m.serie || ''}"
                  data-cantidad="${m.cantidad}">
            <i class="bi bi-pencil-square"></i>
          </button>
        </td>

      </tr>
    `;
        }

      });


      cont.innerHTML = html + '</tbody></table>';

      cont.querySelectorAll('.material-cambiado').forEach(cb => {
        cb.addEventListener('change', function () {

          const id = this.dataset.id;
          const fila = this.closest('tr');
          const btnEditar = fila.querySelector('.btn-edit-serie');

          // Activar edición solo si está marcado
          if (this.checked) {
            btnEditar.classList.remove('btn-outline-secondary');
            btnEditar.classList.add('btn-outline-primary');
          } else {
            btnEditar.classList.remove('btn-outline-primary');
            btnEditar.classList.add('btn-outline-secondary');
          }

          // Guardar en hidden
          const container = document.getElementById('materialesHidden');

          let existing = container.querySelector(`input[name="materiales[${id}][cambiado]"]`);

          const materialId = fila.querySelector('.btn-edit-serie').dataset.materialId;

          if (!existing) {
            container.innerHTML += `
        <input type="hidden" name="materiales[${id}][material_id]" value="${materialId}">
        <input type="hidden" name="materiales[${id}][cambiado]" value="${this.checked ? 1 : 0}">
      `;
          } else {
            existing.value = this.checked ? 1 : 0;
          }

        });
      });
      const modal = new bootstrap.Modal(document.getElementById('modalSerie'));

      document.querySelectorAll('.btn-edit-serie').forEach(btn => {
        btn.addEventListener('click', () => {

          const id = btn.dataset.id;
          const material = btn.dataset.material;
          const serie = btn.dataset.serie;
          const cantidad = btn.dataset.cantidad;
          const medida = btn.dataset.medida;

          document.getElementById('modalCurrentSerie').innerHTML = '';
          document.getElementById('modalCurrentCantidad').innerHTML = '';

          const DatosCantidad = document.getElementById('DatosCantidad');
          const DatosSerie = document.getElementById('DatosSeries');

          if (medida === 'metros') {
            DatosCantidad.classList.remove('d-none');
            DatosSerie.classList.add('d-none');
          } else {
            DatosCantidad.classList.add('d-none');
            DatosSerie.classList.remove('d-none');
          }

          document.getElementById('modalMaterialId').value = id;
          const selectMaterial = document.getElementById('modalSelectMaterial');

          selectMaterial.innerHTML = `<option>Cargando...</option>`;

          fetch('../controllers/revisiones_controller.php?action=get_all_materiales')
            .then(r => r.json())
            .then(materiales => {
              console.log('Materiales cargados:', materiales);
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

          document.getElementById('modalCurrentSerie').innerHTML = serie ? `<strong>${serie}</strong>` : '<span class="text-secondary">Sin serie</span>';
          document.getElementById('modalCurrentCantidad').innerHTML = cantidad ? `<strong>${cantidad} ${medida}</strong>` : '<span class="text-secondary">Sin cantidad</span>';
          document.getElementById('medida-label').innerHTML = medida;

          // document.getElementById('modalCantidadInput').max = cantidad;


          modal.show();

          document.getElementById('modalSerieInput').value = '';
        });
      });
      
      function guardarCambios() {

          const uid = document.getElementById('modalMaterialId').value;

          const fila = document.querySelector(`button[data-id="${id}"]`).closest('tr');
           const rowId = this.dataset.id; 

          const materialId = fila.querySelector('.btn-edit-serie').dataset.materialId;

          const nuevaSerie = document.getElementById('modalSerieInput').value;
          const nuevaCantidad = document.getElementById('modalCantidadInput').value;

          const checkbox = fila.querySelector('.material-cambiado');

          // 🔥 SI EDITA → AUTOMÁTICAMENTE CAMBIADO
          if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
          }

          const select = document.getElementById('modalSelectMaterial');
          const opt = select.selectedOptions[0];

          const container = document.getElementById('materialesHidden');
          console.log(container);

          const nuevoMaterial = opt.text;
          const nuevaFoto = opt.dataset.foto;
          const nuevaMedida = opt.dataset.medida === 'm' ? 'metros' : 'piezas';

          // UI update
          fila.querySelector('.material-name span').innerText = nuevoMaterial;
          fila.querySelector('.material-name img').src =
            `../uploads/materiales/${nuevaFoto}`;

          fila.querySelector('td:nth-child(2)').innerHTML =
            nuevaSerie || '<span class="text-secondary">Sin serie</span>';

          if (nuevaCantidad) {
            fila.querySelector('td:nth-child(3)').innerHTML =
              `${nuevaCantidad} <span class="text-muted">${nuevaMedida}</span>`;
          }

          // 🔥 BLOQUE ÚNICO POR UID
          let bloque = container.querySelector(`.material-${rowId}`);

          if (!bloque) {
            bloque = document.createElement('div');
            bloque.classList.add(`material-${rowId}`);
            container.appendChild(bloque);
          }

          bloque.innerHTML = `
            <input type="hidden" name="materiales[${rowId}][material_id]" value="${materialId}">
            <input type="hidden" name="materiales[${rowId}][cantidad]" value="${nuevaCantidad || 1}">
            <input type="hidden" name="materiales[${rowId}][serie]" value="${nuevaSerie}">
            <input type="hidden" name="materiales[${rowId}][cambiado]" value="1">
          `;

          modal.hide();
        }

        document.getElementById('btnGuardarSerie').addEventListener('click', guardarCambios);
      });

  (function syncSerieChanged() {

    const cbs = cont.querySelectorAll('input[name="material_cambiado[]"]');

    cbs.forEach(cb => {
      cb.addEventListener('change', () => {
        const id = cb.value;
        const row = cb.closest('tr');
        const inp = row.querySelector(`input[name="serie_cambiada[${id}]"]`);

        if (cb.checked) {
          inp.classList.remove('d-none');
          inp.focus();
        } else {
          inp.classList.add('d-none');
          inp.value = '';
        }
      });
    });

  })();
});

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

// Manejar botón de ver evidencias
document.querySelectorAll('.verEvidenciasBtn').forEach(btn => {
  btn.addEventListener('click', function () {
    const revisionId = this.getAttribute('data-id');
    const container = document.getElementById('evidenciasContainer');
    container.innerHTML = '<div class="w-100 text-center p-3"><div class="spinner-border text-primary"></div></div>';

    fetch(`../controllers/revisiones_controller.php?action=get_evidencias&revision_id=${encodeURIComponent(revisionId)}`)
      .then(r => r.json())
      .then(items => {
        if (!items || items.length === 0) {
          container.innerHTML = '<p class="text-muted">No hay evidencias para esta revisión.</p>';
          return;
        }

        let html = '';
        items.forEach(it => {
          if (it.mimetype && it.mimetype.indexOf('image/') === 0) {
            html += `<div style="width:200px"><img src="../uploads/revisiones/${it.filename}" style="max-width:100%; border-radius:6px;" alt="evidencia"></div>`;
          } else {
            html += `<div style="width:200px"><a href="../uploads/revisiones/${it.filename}" target="_blank" class="d-block">📄 ${it.filename}</a></div>`;
          }
        });

        container.innerHTML = html;
      })
      .catch(err => {
        console.error('Error cargando evidencias:', err);
        container.innerHTML = '<p class="text-danger">Error al cargar evidencias.</p>';
      });
  });
});
