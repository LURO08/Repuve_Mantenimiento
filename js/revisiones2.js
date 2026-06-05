
const config = {
  revisionesTable: {
    page: 1,
    limit: 3
  },
  infraRevisionesTable: {
    page: 1,
    limit: 3
  }
};


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
    const scrollBox = table?.closest(".revision-tabla-scroll");
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

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#revisionesTable tbody tr").forEach(r => r.dataset.visible = "1");
  document.querySelectorAll("#infraRevisionesTable tbody tr").forEach(r => r.dataset.visible = "1");
  renderPagination("revisionesTable");
  renderPagination("infraRevisionesTable");
});

window.addEventListener("resize", () => {
  renderPagination("revisionesTable");
  renderPagination("infraRevisionesTable");
});

document.addEventListener("DOMContentLoaded", () => {
  const botones = document.querySelectorAll(".revision-tabla-toggle-btn");
  const vistas = document.querySelectorAll(".revisiones-table-view");

  function cambiarTabla(targetId) {
    vistas.forEach(vista => {
      vista.classList.toggle("d-none", vista.id !== targetId);
    });

    botones.forEach(btn => {
      const activo = btn.dataset.tableViewTarget === targetId;
      const esInfra = btn.dataset.tableViewTarget === "revisionViewInfra";
      btn.classList.toggle("active", activo);
      btn.classList.toggle("btn-success", activo && !esInfra);
      btn.classList.toggle("btn-primary", activo && esInfra);
      btn.classList.toggle("btn-outline-success", !activo && !esInfra);
      btn.classList.toggle("btn-outline-primary", !activo && esInfra);
      btn.classList.remove(activo ? (esInfra ? "btn-outline-primary" : "btn-outline-success") : (esInfra ? "btn-primary" : "btn-success"));
    });

    document.getElementById(targetId)?.scrollIntoView({ behavior: "smooth", block: "start" });
    if (targetId === "revisionViewArcos") {
      renderPagination("revisionesTable");
    } else if (targetId === "revisionViewInfra") {
      renderPagination("infraRevisionesTable");
    }
  }

  botones.forEach(btn => {
    btn.addEventListener("click", () => cambiarTabla(btn.dataset.tableViewTarget));
  });
});



document.querySelectorAll('.eliminar-form').forEach(form => {
  form.addEventListener('submit', e => {
    if (!confirm('¿Eliminar esta revisión?')) e.preventDefault();
  });
});

function esMantenimientoInfraestructura() {
  return Boolean(document.getElementById('checkMantenimientoInfra')?.checked);
}

function actualizarModoMantenimiento() {
  const esInfra = esMantenimientoInfraestructura();
  const select = document.getElementById('arcoSelect');
  const label = document.getElementById('objetivoMantenimientoLabel');
  const titulo = document.getElementById('tituloMaterialesMantenimiento');
  const cont = document.getElementById('materialesContainer');
  const hidden = document.getElementById('materialesHidden');

  if (select) {
    select.name = esInfra ? 'infraestructura_id' : 'arco_id';
  }
  if (label) {
    label.textContent = esInfra ? 'Puente/Sitio' : 'Arco';
  }
  if (titulo) {
    titulo.textContent = esInfra ? 'Material(es) cambiados del Puente/Sitio' : 'Material(es) cambiados';
  }
  if (hidden) {
    hidden.innerHTML = '';
  }
  if (cont) {
    cont.innerHTML = esInfra
      ? 'Seleccione una ubicacion para mostrar puentes/sitios...'
      : 'Seleccione un arco para mostrar sus materiales...';
  }

  cargarObjetivosMantenimiento();
}

function cargarObjetivosMantenimiento() {
  const id = document.getElementById('ubicacionSelect')?.value || '';
  const select = document.getElementById('arcoSelect');
  const esInfra = esMantenimientoInfraestructura();

  if (!select) return;

  if (!id) {
    select.innerHTML = `<option value="">Seleccione una ubicacion primero...</option>`;
    return;
  }

  select.innerHTML = '<option>Cargando...</option>';

  const action = esInfra ? 'get_infraestructuras' : 'get_arcos';
  fetch(`../controllers/revisiones_controller.php?action=${action}&ubicacion_id=${encodeURIComponent(id)}`)
    .then(r => r.json())
    .then(data => {
      select.innerHTML = `<option value="">Seleccione ${esInfra ? 'un puente/sitio' : 'un arco'}...</option>`;
      data.forEach(item => {
        const label = esInfra ? `${item.tipo} - ${item.nombre}` : item.nombre;
        select.innerHTML += `<option value="${item.id}">${label}</option>`;
      });
    });
}

document.getElementById('checkMantenimientoInfra')?.addEventListener('change', actualizarModoMantenimiento);
document.getElementById('ubicacionSelect')?.addEventListener('change', cargarObjetivosMantenimiento);
document.addEventListener('DOMContentLoaded', actualizarModoMantenimiento);

document.getElementById('arcoSelect').addEventListener('change', function () {
    const objetivoId = this.value;
    const cont = document.getElementById('materialesContainer');
    const hidden = document.getElementById('materialesHidden');
    const esInfra = esMantenimientoInfraestructura();

    hidden.innerHTML = '';

    if (!objetivoId) {
      cont.innerHTML = esInfra
        ? 'Seleccione un puente/sitio para mostrar sus materiales...'
        : 'Seleccione un arco para mostrar sus materiales...';
      return;
    }

    cont.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-success"></div>
        </div>
    `;

    const url = esInfra
      ? `../controllers/revisiones_controller.php?action=get_infra_materiales&infraestructura_id=${objetivoId}`
      : `../controllers/revisiones_controller.php?action=get_materiales&arco_id=${objetivoId}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.length) {
                cont.innerHTML = `
                    <p class="text-danger">
                        ${esInfra ? 'Este puente/sitio' : 'Este arco'} no tiene materiales registrados.
                    </p>
                `;
                return;
            }

            let html = `<div class="contenedor-materiales">`;

            data.forEach((m,index) => {
                const medidaLabel = m.medida === 'm' ? 'metros' : (m.medida === 'pz' ? 'piezas' : m.medida);
                const relacionId = m.relacion_id || m.arco_material_id || '';
                html += `
                    <div class="contenedor-material" >
                        <div class="card material-card shadow-sm"
                          data-id="${objetivoId}"
                          data-uid="${index}"
                          data-material_id="${m.id}"
                          data-original-material-id="${m.id}"
                          data-arco-material-id="${relacionId}"
                          data-relacion_id="${relacionId}"
                          data-serie="${m.serie || ''}"
                          data-original-serie="${m.serie || ''}"
                          data-cantidad="${m.cantidad || 1}"
                          data-original-cantidad="${m.cantidad || 1}"
                          title="${m.material}"
                          style="cursor:pointer;">

                          <button type="button"
                                  class="btn btn-sm btn-danger btn-retire-material material-retire-x"
                                  data-uid="${index}"
                                  title="Retirar material">
                            &times;
                          </button>

                          <img src="../uploads/materiales/${m.foto || 'default.png'}"
                            class="card-img-top"
                            style="height:80px; width:100%; object-fit:contain; padding:5px;"
                            onerror="this.src='../uploads/materiales/default.png'">

                          <div class="card-body text-center">
                            <div class="fw-semibold mb-1">${m.material}</div>
                            <small class="text-muted d-block mb-1">${medidaLabel}</small>

                            ${m.medida === 'm' ? `
                            <small class="text-muted d-block">
                              ${m.cantidad} metros
                            </small> ` : ''}

                            ${m.serie && m.serie !== 'null' && m.serie.trim() !== '' ? `
                                    <span class="serie-label">Serie:</span>
                                    <small class="text-muted d-block serie-container">
                                        <span class="serie-value">${m.serie}</span>
                                    </small>
                                ` : ''}

                                <div class="material-actions mt-auto">
                                  <button type="button"
                                          class="btn btn-sm btn-outline-primary btn-edit-material"
                                          data-id="${objetivoId}"
                                          data-uid="${index}"
                                          data-material_id="${m.id}"
                                          data-arco-material-id="${relacionId}"
                                          data-relacion_id="${relacionId}"
                                          data-material="${m.material}"
                                          data-medida="${medidaLabel}"
                                          data-serie="${m.serie || ''}"
                                          data-cantidad="${m.cantidad}">
                                       Editar
                                  </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            cont.innerHTML = html;

            bindMaterialCards();
            bindEditButtons();
            bindRetireButtons();
        });
});

function bindMaterialCards() {
    const hidden = document.getElementById('materialesHidden');

    document.querySelectorAll('#materialesContainer .material-card').forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.closest('.btn-edit-material, .btn-retire-material')) return;
            if (this.classList.contains('material-retirado')) return;

            const uid = this.dataset.uid;
            const materialId = this.dataset.materialId || this.dataset.material_id;
            const arcoMaterialId = obtenerRelacionOriginalMaterial(this);
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
                    <input type="hidden" name="materiales[${uid}][arco_material_id]" value="${arcoMaterialId}">
                    <input type="hidden" name="materiales[${uid}][material_id]" value="${materialId}">
                    <input type="hidden" name="materiales[${uid}][cantidad]" value="${cantidad}">
                    <input type="hidden" name="materiales[${uid}][serie]" value="${serie}">
                    <input type="hidden" name="materiales[${uid}][accion]" value="cambio">
                    <input type="hidden" name="materiales[${uid}][cambiado]" value="1">
                `;

                hidden.appendChild(bloque);
            }
        });
    });
}

let materialesRevisionModalCache = null;

function normalizarSerieRevision(value) {
  const serie = String(value ?? '').trim();
  return serie.toLowerCase() === 'null' ? '' : serie;
}

function aplicarEstadoSerieModal(tieneSerie, enfocar = false) {
  const checkSerie = document.getElementById('modalCheckSerie');
  const serieField = document.getElementById('modalSerieField');
  const serieInput = document.getElementById('modalSerieInput');

  if (checkSerie) checkSerie.checked = Boolean(tieneSerie);
  serieField?.classList.toggle('d-none', !tieneSerie);
  if (!tieneSerie && serieInput) serieInput.value = '';
  if (tieneSerie && enfocar) setTimeout(() => serieInput?.focus(), 100);
}

function setMaterialSeleccionadoModal(material, options = {}) {
  const input = document.getElementById('modalSelectMaterial');
  const resumen = document.getElementById('modalMaterialSeleccionado');
  if (!input || !material) return;

  input.value = material.id || '';
  input.dataset.medida = material.medida || '';
  input.dataset.foto = material.foto || '';
  input.dataset.nombre = material.nombre || '';

  document.querySelectorAll('#modalMaterialGrid .modal-material-option').forEach(card => {
    card.classList.toggle('is-selected', String(card.dataset.id) === String(material.id));
  });

  if (resumen) {
    resumen.innerHTML = `
      <span class="modal-material-selected-name">${escapeHtmlRevision(material.nombre || 'Material seleccionado')}</span>
      <span class="modal-material-selected-measure">${escapeHtmlRevision(etiquetaMedidaModal(material.medida || 'pz'))}</span>
    `;
  }

  actualizarCamposModalSeriePorMedida(material.medida || '');

  if (options.resetSerie) {
    aplicarEstadoSerieModal(false);
  }
}

function renderMaterialesModalMantenimiento(materiales, selectedId = '') {
  const grid = document.getElementById('modalMaterialGrid');
  const buscador = document.getElementById('modalBuscarMaterial');
  if (!grid) return;

  grid.innerHTML = materiales.map(mat => {
    const foto = mat.foto && mat.foto !== 'null' ? mat.foto : 'default.png';
    const selected = String(mat.id) === String(selectedId) ? ' is-selected' : '';
    return `
      <button type="button"
              class="modal-material-option${selected}"
              data-id="${escapeHtmlRevision(mat.id)}"
              data-nombre="${escapeHtmlRevision(mat.nombre || '')}"
              data-medida="${escapeHtmlRevision(mat.medida || '')}"
              data-foto="${escapeHtmlRevision(foto)}">
        <img src="../uploads/materiales/${escapeHtmlRevision(foto)}"
             alt="${escapeHtmlRevision(mat.nombre || 'Material')}"
             onerror="this.src='../uploads/materiales/default.png'">
        <span>${escapeHtmlRevision(mat.nombre || 'Sin nombre')}</span>
        <small>${escapeHtmlRevision(etiquetaMedidaModal(mat.medida || 'pz'))}</small>
      </button>
    `;
  }).join('');

  grid.querySelectorAll('.modal-material-option').forEach(card => {
    card.addEventListener('click', () => {
      setMaterialSeleccionadoModal({
        id: card.dataset.id,
        nombre: card.dataset.nombre,
        medida: card.dataset.medida,
        foto: card.dataset.foto
      }, { resetSerie: true });
    });
  });

  if (buscador) {
    buscador.value = '';
    buscador.oninput = () => {
      const q = buscador.value.trim().toLowerCase();
      grid.querySelectorAll('.modal-material-option').forEach(card => {
        card.classList.toggle('d-none', q && !card.dataset.nombre.toLowerCase().includes(q));
      });
    };
  }

  const selectedMaterial = materiales.find(mat => String(mat.id) === String(selectedId)) || materiales[0];
  if (selectedMaterial) setMaterialSeleccionadoModal(selectedMaterial);
}

function cargarMaterialesModalMantenimiento(selectedId) {
  const grid = document.getElementById('modalMaterialGrid');
  if (grid) {
    grid.innerHTML = `
      <div class="text-center text-muted py-3" style="grid-column: 1 / -1;">
        <div class="spinner-border spinner-border-sm text-success me-2"></div>
        Cargando materiales...
      </div>
    `;
  }

  if (Array.isArray(materialesRevisionModalCache)) {
    renderMaterialesModalMantenimiento(materialesRevisionModalCache, selectedId);
    return Promise.resolve(materialesRevisionModalCache);
  }

  return fetch('../controllers/revisiones_controller.php?action=get_all_materiales')
    .then(r => r.json())
    .then(materiales => {
      materialesRevisionModalCache = Array.isArray(materiales) ? materiales : [];
      renderMaterialesModalMantenimiento(materialesRevisionModalCache, selectedId);
      return materialesRevisionModalCache;
    })
    .catch(() => {
      if (grid) {
        grid.innerHTML = `<div class="alert alert-danger mb-0" style="grid-column: 1 / -1;">No se pudieron cargar los materiales.</div>`;
      }
    });
}

function bindEditButtons() {
    const modal = new bootstrap.Modal(document.getElementById('modalSerie'));

    document.querySelectorAll('#materialesContainer .btn-edit-material').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();

            const id = btn.dataset.materialId || btn.dataset.material_id;
            const medida = btn.dataset.medida;

            const card = this.closest('.material-card');

            actualizarCamposModalSeriePorMedida(medida);

            document.getElementById('modalMaterialId').value = btn.dataset.uid;
            const serieActual = normalizarSerieRevision(card.dataset.serie);
            document.getElementById('modalSerieInput').value = serieActual;
            aplicarEstadoSerieModal(Boolean(serieActual));
            document.getElementById('modalCantidadInput').value = card.dataset.cantidad;

            cargarMaterialesModalMantenimiento(id).then(() => {
              const serieActualizada = normalizarSerieRevision(card.dataset.serie);
              document.getElementById('modalSerieInput').value = serieActualizada;
              aplicarEstadoSerieModal(Boolean(serieActualizada));
            });
            
            modal.show();
        });
    });
}

function medidaEsMetro(medida) {
  return medida === 'm' || medida === 'metros' || medida === 'metro';
}

function etiquetaMedidaModal(medida) {
  if (medida === 'm' || medida === 'metros') return 'metros';
  if (medida === 'pz' || medida === 'piezas') return 'piezas';
  return medida || 'pz';
}

function obtenerRelacionOriginalMaterial(elemento) {
  if (!elemento) return '';
  return elemento.dataset?.arcoMaterialId
    || elemento.dataset?.relacion_id
    || elemento.getAttribute?.('data-arco-material-id')
    || elemento.getAttribute?.('data-relacion_id')
    || '';
}

function obtenerMaterialOriginal(card) {
  return {
    materialId: card?.dataset?.originalMaterialId || card?.dataset?.materialId || card?.dataset?.material_id || '',
    cantidad: card?.dataset?.originalCantidad || card?.dataset?.cantidad || '1',
    serie: card?.dataset?.originalSerie || ''
  };
}

function actualizarCamposModalSeriePorMedida(medida) {
  const datosCantidad = document.getElementById('DatosCantidad');
  const datosSerie = document.getElementById('DatosSeries');
  const medidaLabel = document.getElementById('medida-label');
  const esMetro = medidaEsMetro(medida);

  datosCantidad?.classList.toggle('d-none', !esMetro);
  datosSerie?.classList.toggle('d-none', esMetro);
  if (esMetro) aplicarEstadoSerieModal(false);
  if (medidaLabel) medidaLabel.textContent = etiquetaMedidaModal(medida);
}

function asegurarInputMaterial(bloque, uid, campo, valor) {
  let input = bloque.querySelector(`[data-field="${campo}"]`);
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.dataset.field = campo;
    input.name = `materiales[${uid}][${campo}]`;
    bloque.appendChild(input);
  }
  input.value = valor ?? '';
}

function sincronizarMaterialHidden(uid, materialId, cantidad, serie, arcoMaterialId = '', accion = 'cambio') {
  const hidden = document.getElementById('materialesHidden');
  if (!hidden) return;

  let bloque = hidden.querySelector(`.material-${uid}`);
  if (!bloque) {
    bloque = document.createElement('div');
    bloque.className = `material-${uid}`;
    hidden.appendChild(bloque);
  }

  asegurarInputMaterial(bloque, uid, 'uid', uid);
  asegurarInputMaterial(bloque, uid, 'arco_material_id', arcoMaterialId);
  asegurarInputMaterial(bloque, uid, 'material_id', materialId);
  asegurarInputMaterial(bloque, uid, 'cantidad', cantidad);
  asegurarInputMaterial(bloque, uid, 'serie', serie);
  asegurarInputMaterial(bloque, uid, 'accion', accion);
  asegurarInputMaterial(bloque, uid, 'cambiado', '1');
}

function quitarMaterialHidden(uid) {
  document.getElementById('materialesHidden')?.querySelector(`.material-${uid}`)?.remove();
}

function marcarMaterialRetirado(card) {
  const uid = card?.dataset?.uid || '';
  if (!uid || !card) return;

  const arcoMaterialId = obtenerRelacionOriginalMaterial(card);
  const original = obtenerMaterialOriginal(card);

  card.classList.remove('border-success');
  card.classList.add('border-danger', 'border-3', 'material-retirado');

  const editBtn = card.querySelector('.btn-edit-material');
  if (editBtn) editBtn.disabled = true;

  const retireBtn = card.querySelector('.btn-retire-material');
  if (retireBtn) {
    retireBtn.classList.remove('btn-danger');
    retireBtn.classList.add('btn-secondary');
    retireBtn.innerHTML = '&larr;';
    retireBtn.title = 'Cancelar retiro';
  }

  sincronizarMaterialHidden(uid, original.materialId, original.cantidad, original.serie, arcoMaterialId, 'retiro');
}

function cancelarRetiroMaterial(card) {
  const uid = card?.dataset?.uid || '';
  if (!uid || !card) return;

  card.classList.remove('border-danger', 'border-3', 'material-retirado');
  const editBtn = card.querySelector('.btn-edit-material');
  if (editBtn) editBtn.disabled = false;

  const retireBtn = card.querySelector('.btn-retire-material');
  if (retireBtn) {
    retireBtn.classList.remove('btn-secondary');
    retireBtn.classList.add('btn-danger');
    retireBtn.innerHTML = '&times;';
    retireBtn.title = 'Retirar material';
  }

  quitarMaterialHidden(uid);
}

function bindRetireButtons() {
  document.querySelectorAll('#materialesContainer .btn-retire-material').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const card = this.closest('.material-card');
      if (!card) return;

      if (card.classList.contains('material-retirado')) {
        cancelarRetiroMaterial(card);
      } else {
        const nombre = card.querySelector('.fw-semibold')?.textContent?.trim() || 'este material';
        const confirmar = window.confirm(`Seguro que quieres retirar ${nombre} de este arco?`);
        if (!confirmar) return;

        marcarMaterialRetirado(card);
      }
    });
  });
}

function guardarCambiosMaterialMantenimiento() {
  const uid = document.getElementById('modalMaterialId')?.value || '';
  const inputMaterial = document.getElementById('modalSelectMaterial');
  const materialId = inputMaterial?.value || '';
  const medida = inputMaterial?.dataset?.medida || '';
  const nombre = inputMaterial?.dataset?.nombre || '';
  const foto = inputMaterial?.dataset?.foto || '';
  const esMetro = medidaEsMetro(medida);
  const cantidad = esMetro ? (document.getElementById('modalCantidadInput')?.value || '1') : '1';
  const tieneSerie = !esMetro && Boolean(document.getElementById('modalCheckSerie')?.checked);
  const serie = tieneSerie ? normalizarSerieRevision(document.getElementById('modalSerieInput')?.value) : '';
  const card = Array.from(document.querySelectorAll('#materialesContainer .material-card'))
    .find(item => item.dataset.uid === uid);

  if (!uid || !materialId || !card) return;
  if (tieneSerie && !serie) {
    alert('Ingrese la serie');
    return;
  }

  const arcoMaterialId = obtenerRelacionOriginalMaterial(card);

  card.dataset.material_id = materialId;
  card.dataset.materialId = materialId;
  card.setAttribute('data-material_id', materialId);
  card.dataset.cantidad = cantidad;
  card.dataset.serie = serie;
  card.classList.remove('border-danger', 'material-retirado');
  card.classList.add('border-success', 'border-3');

  const btn = card.querySelector('.btn-edit-material');
  const actions = card.querySelector('.material-actions') || btn;
  if (btn) {
    btn.disabled = false;
    btn.dataset.material_id = materialId;
    btn.dataset.materialId = materialId;
    btn.setAttribute('data-material_id', materialId);
    btn.dataset.material = nombre;
    btn.dataset.medida = etiquetaMedidaModal(medida);
    btn.dataset.serie = serie;
    btn.dataset.cantidad = cantidad;
  }

  const retireBtn = card.querySelector('.btn-retire-material');
  if (retireBtn) {
    retireBtn.classList.remove('btn-secondary');
    retireBtn.classList.add('btn-danger');
    retireBtn.innerHTML = '&times;';
    retireBtn.title = 'Retirar material';
  }

  const nombreEl = card.querySelector('.card-body .fw-semibold');
  if (nombreEl && nombre) nombreEl.textContent = nombre;

  const medidaEl = card.querySelector('.card-body small.text-muted.d-block.mb-1');
  if (medidaEl) medidaEl.textContent = etiquetaMedidaModal(medida);

  const img = card.querySelector('img.card-img-top');
  if (img && foto) img.src = `../uploads/materiales/${foto}`;

  let serieContainer = card.querySelector('.serie-container');
  let serieLabel = card.querySelector('.serie-label');
  if (serie) {
    if (!serieLabel) {
      serieLabel = document.createElement('span');
      serieLabel.className = 'serie-label';
      serieLabel.textContent = 'Serie:';
      card.querySelector('.card-body')?.insertBefore(serieLabel, actions);
    }
    if (!serieContainer) {
      serieContainer = document.createElement('small');
      serieContainer.className = 'text-muted d-block serie-container';
      serieContainer.innerHTML = '<span class="serie-value"></span>';
      card.querySelector('.card-body')?.insertBefore(serieContainer, actions);
    }
    const serieScroll = serieContainer.querySelector('.serie-value');
    if (serieScroll) serieScroll.textContent = serie;
  } else {
    serieLabel?.remove();
    serieContainer?.remove();
  }

  sincronizarMaterialHidden(uid, materialId, cantidad, serie, arcoMaterialId, 'cambio');
  bootstrap.Modal.getInstance(document.getElementById('modalSerie'))?.hide();
}

document.getElementById('btnGuardarSerie')?.addEventListener('click', function (e) {
  e.preventDefault();
  guardarCambiosMaterialMantenimiento();
});

document.getElementById('modalCheckSerie')?.addEventListener('change', function () {
  aplicarEstadoSerieModal(this.checked, this.checked);
});
      
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
function escapeHtmlRevision(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function fechaCambioRevision(material) {
  return material?.fecha_mantenimiento || material?.fecha_cambio || material?.fecha_instalacion || "";
}

function fechaCambioKeyRevision(fecha) {
  return String(fecha || "Sin fecha").slice(0, 10);
}

function formatearFechaHoraRevision(fecha) {
  if (!fecha) return "Sin fecha";

  const texto = String(fecha).trim();
  const normalizada = texto.includes("T") ? texto : texto.replace(" ", "T");
  const date = new Date(normalizada);

  if (Number.isNaN(date.getTime())) return texto;

  const fechaFormateada = date.toLocaleDateString("es-MX", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  });

  const tieneHora = /[T\s]\d{2}:\d{2}/.test(texto);
  if (!tieneHora) return fechaFormateada;

  return `${fechaFormateada} ${date.toLocaleTimeString("es-MX", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false
  })}`;
}

function textoMedidaRevision(medida, cantidad) {
  if (medida === "m") return Number(cantidad) === 1 ? "metro" : "metros";
  if (medida === "pz") return Number(cantidad) === 1 ? "pieza" : "piezas";
  return medida || "";
}

function obtenerSeriesRevision(material) {
  if (Array.isArray(material?.series)) {
    return material.series.filter(Boolean);
  }

  if (material?.serie && String(material.serie).trim() !== "") {
    return [String(material.serie).trim()];
  }

  return [];
}

function expandirMaterialRevision(material, index) {
  const cantidad = Number.parseFloat(material.cantidad || "1") || 1;
  const series = obtenerSeriesRevision(material);

  if (material.medida !== "pz") {
    return [{ ...material, cantidad, series, instancia: index }];
  }

  if (series.length > 1) {
    return series.map((serie, serieIndex) => ({
      ...material,
      cantidad: 1,
      serie,
      series: [serie],
      instancia: `${index}-${serieIndex}`
    }));
  }

  if (!series.length && cantidad > 1) {
    return Array.from({ length: cantidad }, (_, piezaIndex) => ({
      ...material,
      cantidad: 1,
      series: [],
      instancia: `${index}-${piezaIndex}`
    }));
  }

  return [{ ...material, cantidad: 1, series, instancia: index }];
}

function agruparMaterialesRevision(materiales) {
  const grupos = new Map();

  materiales.flatMap(expandirMaterialRevision).forEach((material, index) => {
    const fecha = fechaCambioRevision(material);
    const fechaKey = fechaCambioKeyRevision(fecha);
    const esPieza = material.medida === "pz";
    const rowId = material.relacion_id || material.id || material.instancia || index;
    const accion = material.accion || "cambio";
    const key = esPieza
      ? `${fechaKey}|${accion}|${material.material}|${material.medida}|${rowId}|${material.serie || ""}|${index}`
      : `${fechaKey}|${accion}|${material.material}|${material.medida}`;

    if (!grupos.has(key)) {
      grupos.set(key, {
        material: material.material,
        medida: material.medida,
        cantidad: 0,
        series: [],
        foto: material.foto,
        fecha_cambio: fecha,
        fecha_key: fechaKey,
        relacion_id: rowId,
        accion
      });
    }

    const grupo = grupos.get(key);
    grupo.cantidad += Number.parseFloat(material.cantidad || "1") || 1;
    obtenerSeriesRevision(material).forEach(serie => grupo.series.push(serie));
  });

  return Array.from(grupos.values()).sort((a, b) => {
    const fechaA = new Date(a.fecha_key === "Sin fecha" ? "1900-01-01" : a.fecha_key);
    const fechaB = new Date(b.fecha_key === "Sin fecha" ? "1900-01-01" : b.fecha_key);
    if (fechaB - fechaA !== 0) return fechaB - fechaA;

    const nombreCompare = String(a.material || "").localeCompare(String(b.material || ""), "es");
    if (nombreCompare !== 0) return nombreCompare;

    return Number(a.relacion_id || 0) - Number(b.relacion_id || 0);
  });
}

function renderSeriesRevision(series, index) {
  if (!series.length) {
    return `<div class="text-muted small mt-2">Sin serie registrada</div>`;
  }

  const seriesId = `series_revision_${index}`;
  const chips = series.map(serie => `<span class="series-chip">${escapeHtmlRevision(serie)}</span>`).join("");

  return `
    <div class="mt-2">
      <div class="d-flex justify-content-between align-items-center gap-2">
        <small class="text-muted">Series: ${series.length}</small>
        <button class="btn btn-sm btn-outline-primary series-btn"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#${seriesId}">
          Ver series <i class="bi bi-chevron-down"></i>
        </button>
      </div>
      <div class="collapse" id="${seriesId}">
        <div class="series-panel">${chips}</div>
      </div>
    </div>
  `;
}

function renderComponentesMantenimiento(btn) {
  const contenedor = document.getElementById("contenedorMateriales");
  let materialesOriginal = [];

  try {
    materialesOriginal = JSON.parse(btn.dataset.materiales || "[]");
  } catch (error) {
    materialesOriginal = [];
  }

  if (!materialesOriginal.length) {
    contenedor.innerHTML = `
      <div class="text-center p-3">
        <span class="badge bg-warning text-dark">
          <i class="bi bi-exclamation-circle"></i> Sin materiales
        </span>
      </div>`;
    return;
  }

  const materiales = agruparMaterialesRevision(materialesOriginal);
  const materialesPorFecha = materiales.reduce((grupos, material) => {
    const key = material.fecha_key || "Sin fecha";
    if (!grupos[key]) grupos[key] = [];
    grupos[key].push(material);
    return grupos;
  }, {});

  const fechas = Object.keys(materialesPorFecha).sort((a, b) => {
    const fechaA = new Date(a === "Sin fecha" ? "1900-01-01" : a);
    const fechaB = new Date(b === "Sin fecha" ? "1900-01-01" : b);
    return fechaB - fechaA;
  });

  contenedor.innerHTML = fechas.map(fechaKey => {
    const items = materialesPorFecha[fechaKey];
    const fechaTexto = formatearFechaHoraRevision(items[0]?.fecha_cambio);

    return `
      <div class="revision-material-date-group">
        <div class="revision-material-date-title">
          <i class="bi bi-calendar-event"></i>
          Fecha de cambio: ${escapeHtmlRevision(fechaTexto)}
        </div>
        <div class="material-grid revision-material-grid">
          ${items.map((m, index) => {
            const globalIndex = `${fechaKey}_${index}`.replace(/[^a-zA-Z0-9_-]/g, "_");
            const foto = String(m.foto || "").trim();
            const imagenHtml = (!foto || foto === "null")
              ? `<div class="d-flex align-items-center justify-content-center bg-secondary text-white material-img">Sin foto</div>`
              : `<img src="../uploads/materiales/${escapeHtmlRevision(foto)}" class="material-img" alt="${escapeHtmlRevision(m.material)}">`;
            const medidaTexto = textoMedidaRevision(m.medida, m.cantidad);
            const esRetiro = String(m.accion || "").toLowerCase() === "retiro";

            return `
              <div class="revision-material-card material-card">
                <div class="d-flex align-items-start gap-2 justify-content-between">
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    ${imagenHtml}
                    <div class="min-w-0">
                      <div class="fw-bold text-capitalize revision-material-name">${escapeHtmlRevision(m.material)}</div>
                      <div class="text-muted small">
                        <i class="bi ${esRetiro ? 'bi-box-arrow-up' : 'bi-tools'}"></i>
                        ${esRetiro ? 'Retirado por mantenimiento' : 'Cambiado por mantenimiento'}
                      </div>
                    </div>
                  </div>
                  <div class="revision-material-qty text-end">
                    <span class="badge ${esRetiro ? 'bg-danger' : 'bg-success'} fs-6 px-3 py-2">${escapeHtmlRevision(m.cantidad)}</span>
                    <div class="text-muted small mt-1">${escapeHtmlRevision(medidaTexto)}</div>
                  </div>
                </div>
                <div class="text-muted small mt-2">
                  <i class="bi bi-clock"></i>
                  ${escapeHtmlRevision(fechaTexto)}
                </div>
                ${renderSeriesRevision(m.series || [], globalIndex)}
              </div>
            `;
          }).join("")}
        </div>
      </div>
    `;
  }).join("");

  contenedor.querySelectorAll('.collapse').forEach(collapseEl => {
    collapseEl.addEventListener('show.bs.collapse', function () {
      let btnSeries = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
      if (btnSeries) btnSeries.innerHTML = 'Ocultar series <i class="bi bi-chevron-up"></i>';
    });

    collapseEl.addEventListener('hide.bs.collapse', function () {
      let btnSeries = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
      if (btnSeries) btnSeries.innerHTML = 'Ver series <i class="bi bi-chevron-down"></i>';
    });
  });
}

const evidenciasDetalleCache = new Map();

function renderDetalleEvidenciaCard(ev) {
  if (!ev || !ev.filename) return "";

  const filename = escapeHtmlRevision(ev.filename);
  const src = `../uploads/revisiones/${filename}`;
  const tipo = ev.tipo === "infra" ? "infra" : "arco";
  const thumbSrc = ev.id
    ? `../controllers/revisiones_controller.php?action=thumb_evidencia&id=${encodeURIComponent(ev.id)}&tipo=${encodeURIComponent(tipo)}&w=260`
    : src;
  const fecha = ev.uploaded_at ? formatearFechaHoraRevision(ev.uploaded_at) : "";

  if (ev.mimetype && ev.mimetype.includes("pdf")) {
    return `
      <div class="card shadow-sm border-0 evidencia-card pdf-card" data-src="${src}" data-type="pdf" style="cursor:pointer;">
        <div class="pdf-preview">
          <i class="bi bi-file-earmark-pdf-fill pdf-icon"></i>
        </div>
        <div class="pdf-info">
          <p class="pdf-name" title="${filename}">${filename}</p>
          ${fecha ? `<small class="text-muted">${escapeHtmlRevision(fecha)}</small>` : ""}
        </div>
      </div>
    `;
  }

  return `
    <div class="card shadow-sm border-0 evidencia-card detalle-evidencia-imagen abrir-imagen"
         data-img="${src}"
         style="cursor:pointer;">
      <div class="detalle-evidencia-preview">
        <img src="${thumbSrc}"
             class="detalle-evidencia-thumb"
             alt="Evidencia"
             loading="lazy"
             decoding="async"
             onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
        <i class="bi bi-image d-none"></i>
      </div>
      <div class="detalle-evidencia-info">
        <p class="pdf-name mb-1" title="${filename}">${filename}</p>
        ${fecha ? `<small class="text-muted">${escapeHtmlRevision(fecha)}</small>` : ""}
      </div>
    </div>
  `;
}

function activarVisoresEvidenciasDetalle(contenedor) {
  contenedor.querySelectorAll(".pdf-card").forEach(card => {
    card.addEventListener("click", function () {
      const src = this.dataset.src;
      const modalBody = document.querySelector("#modalPdf .modal-body");
      if (!modalBody) return;

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

  const imagenes = [];
  let imagenActual = 0;

  contenedor.querySelectorAll(".abrir-imagen").forEach((img, index) => {
    imagenes.push(img.dataset.img);

    img.addEventListener("click", function () {
      imagenActual = index;
      mostrarImagenDetalle();
      const modal = new bootstrap.Modal(document.getElementById("modalImagen"));
      modal.show();
    });
  });

  function mostrarImagenDetalle() {
    const imagenAmpliada = document.getElementById("imagenAmpliada");
    if (imagenAmpliada) imagenAmpliada.src = imagenes[imagenActual];
  }

  const btnPrev = document.getElementById("btnPrevImg");
  const btnNext = document.getElementById("btnNextImg");

  if (btnPrev) {
    btnPrev.onclick = function () {
      imagenActual--;
      if (imagenActual < 0) imagenActual = imagenes.length - 1;
      mostrarImagenDetalle();
    };
  }

  if (btnNext) {
    btnNext.onclick = function () {
      imagenActual++;
      if (imagenActual >= imagenes.length) imagenActual = 0;
      mostrarImagenDetalle();
    };
  }
}

async function cargarEvidenciasDetalle(detalle) {
  const grid = document.getElementById("detalleEvidenciasGrid");
  if (!grid) return;

  if (!detalle.evidencias_ajax || !detalle.id) {
    grid.innerHTML = `<div class="alert alert-warning w-100 text-center mb-0">No hay evidencias registradas para este mantenimiento.</div>`;
    return;
  }

  if (Number(detalle.evidencias || 0) <= 0) {
    grid.innerHTML = `<div class="alert alert-warning w-100 text-center mb-0">No hay evidencias registradas.</div>`;
    return;
  }

  const evidenciaTipo = detalle.evidencias_tipo === "infra" ? "infra" : "arco";
  const cacheKey = `${evidenciaTipo}:${detalle.id}`;
  if (evidenciasDetalleCache.has(cacheKey)) {
    const data = evidenciasDetalleCache.get(cacheKey);
    grid.innerHTML = data.map(renderDetalleEvidenciaCard).join("");
    activarVisoresEvidenciasDetalle(grid);
    return;
  }

  grid.innerHTML = `
    <div class="detalle-evidencias-loading">
      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
      <span>Cargando lista de evidencias...</span>
    </div>
  `;

  try {
    const res = await fetch(`../controllers/revisiones_controller.php?action=get_evidencias&revision_id=${encodeURIComponent(detalle.id)}&tipo=${encodeURIComponent(evidenciaTipo)}`);
    const data = await res.json();

    if (!Array.isArray(data) || !data.length) {
      grid.innerHTML = `<div class="alert alert-warning w-100 text-center mb-0">No hay evidencias registradas.</div>`;
      return;
    }

    evidenciasDetalleCache.set(cacheKey, data);
    grid.innerHTML = data.map(renderDetalleEvidenciaCard).join("");
    activarVisoresEvidenciasDetalle(grid);
  } catch (error) {
    grid.innerHTML = `<div class="alert alert-danger w-100 text-center mb-0">Error al cargar evidencias.</div>`;
  }
}

function renderDetalleMantenimiento(btn) {
  const contenedor = document.getElementById("detalleMantenimientoContenido");
  if (!contenedor) return;

  let detalle = {};
  try {
    detalle = JSON.parse(btn.dataset.detalle || "{}");
  } catch (error) {
    detalle = {};
  }

  const tipoClase = detalle.tipo === "Correctivo" ? "bg-warning text-dark" : "bg-success";
  const pdfHtml = detalle.pdf
    ? `<a href="${escapeHtmlRevision(detalle.pdf)}" target="_blank" class="btn btn-sm btn-danger">
         <i class="bi bi-file-earmark-pdf"></i> Ver formato
       </a>`
    : "";

  contenedor.innerHTML = `
    <div class="detalle-mantenimiento">
      <div class="detalle-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
          <div class="text-muted small">${escapeHtmlRevision(detalle.origen || "Mantenimiento")}</div>
          <h5 class="fw-bold mb-1">${escapeHtmlRevision(detalle.objetivo || "Sin nombre")}</h5>
          <div class="text-muted">
            <i class="bi bi-geo-alt"></i> ${escapeHtmlRevision(detalle.ubicacion || "Sin ubicacion")}
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="badge ${tipoClase}">${escapeHtmlRevision(detalle.tipo || "Correctivo")}</span>
          ${pdfHtml}
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-3">
          <div class="detalle-info-box">
            <span>Folio</span>
            <strong>#${escapeHtmlRevision(detalle.id || "")}</strong>
          </div>
        </div>
        <div class="col-md-3">
          <div class="detalle-info-box">
            <span>Fecha</span>
            <strong>${escapeHtmlRevision(formatearFechaHoraRevision(detalle.fecha))}</strong>
          </div>
        </div>
        <div class="col-md-4">
          <div class="detalle-info-box">
            <span>Tecnico responsable</span>
            <strong>${escapeHtmlRevision(detalle.tecnico || "Sin tecnico")}</strong>
          </div>
        </div>
        <div class="col-md-2">
          <div class="detalle-info-box">
            <span>Evidencias</span>
            <strong>${escapeHtmlRevision(detalle.evidencias || 0)}</strong>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-card-text"></i> Observaciones</h6>
        <div class="detalle-observaciones">${escapeHtmlRevision(detalle.observaciones || "Sin observaciones").replace(/\n/g, "<br>")}</div>
      </div>

      <div class="mt-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-camera"></i> Evidencias</h6>
        <div class="detalle-evidencias-grid" id="detalleEvidenciasGrid"></div>
      </div>
    </div>
  `;

  window.requestAnimationFrame(() => cargarEvidenciasDetalle(detalle));
}

document.querySelectorAll('.verMaterialesBtn').forEach(btn => {
  btn.addEventListener('click', function () {
    renderComponentesMantenimiento(this);
  });
});

document.querySelectorAll('.verInfraMaterialesBtn').forEach(btn => {
  btn.addEventListener('click', function () {
    renderComponentesMantenimiento(this);
  });
});

document.querySelectorAll('.verDetalleMantenimientoBtn').forEach(btn => {
  btn.addEventListener('click', function () {
    renderDetalleMantenimiento(this);
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const rows = document.getElementById("infraRevisionMaterialRows");
  const addBtn = document.getElementById("btnAddInfraRevisionMaterial");

  if (!rows || !addBtn) return;

  function actualizarCantidadPorMedida(row) {
    const select = row?.querySelector(".infra-revision-material-select");
    const cantidad = row?.querySelector(".infra-revision-cantidad");
    const medida = select?.selectedOptions?.[0]?.dataset?.medida || "";

    if (!cantidad) return;

    if (medida === "pz") {
      cantidad.value = "1";
      cantidad.classList.add("d-none");
    } else {
      cantidad.classList.remove("d-none");
      cantidad.min = "0.1";
      cantidad.step = "0.1";
      if (!cantidad.value || Number(cantidad.value) <= 0) {
        cantidad.value = "1";
      }
    }
  }

  addBtn.addEventListener("click", () => {
    const first = rows.querySelector(".infra-revision-material-row");
    if (!first) return;

    const clone = first.cloneNode(true);
    clone.querySelectorAll("input").forEach(input => {
      input.value = input.type === "number" ? "1" : "";
      input.classList.remove("d-none");
    });
    clone.querySelectorAll("select").forEach(select => select.value = "");
    rows.appendChild(clone);
  });

  rows.addEventListener("click", e => {
    const btn = e.target.closest(".infra-revision-remove-material");
    if (!btn) return;

    const total = rows.querySelectorAll(".infra-revision-material-row").length;
    if (total > 1) {
      btn.closest(".infra-revision-material-row")?.remove();
    }
  });

  rows.addEventListener("change", e => {
    const select = e.target.closest(".infra-revision-material-select");
    if (!select) return;
    actualizarCantidadPorMedida(select.closest(".infra-revision-material-row"));
  });

  rows.querySelectorAll(".infra-revision-material-row").forEach(actualizarCantidadPorMedida);
});

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


