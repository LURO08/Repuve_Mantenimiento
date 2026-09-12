
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
const paginationLimitOptions = [3, 10, 20, 30, 40, 50, 60, 100];


function renderPagination(tableId) {
    const state = config[tableId];
    const allRows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));

    // SOLO filas visibles por búsqueda
    const visibleRows = allRows.filter(row => row.dataset.visible !== "0");

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

    const options = getPaginationLimitOptions(total, state.limit);

    let html = `
      <div class="pagination-toolbar">
        <div class="pagination-summary">
          ${shownEnd - shownStart + (total > 0 ? 1 : 0)} de ${total} registros &middot; Pagina ${state.page} de ${totalPages}
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
  const btnAgregar = document.getElementById('btnAgregarMaterialMantenimiento');

  if (select) {
    select.name = esInfra ? 'infraestructura_id' : 'arco_id';
  }
  if (label) {
    label.textContent = esInfra ? 'Puente/Sitio' : 'Arco';
  }
  if (titulo) {
    titulo.textContent = esInfra ? 'Material(es) del Puente/Sitio (Cambiados / Agregados)' : 'Material(es) del Arco (Cambiados / Agregados)';
  }
  btnAgregar?.classList.add('d-none');
  if (hidden) {
    hidden.innerHTML = '';
  }
  if (cont) {
    cont.innerHTML = esInfra
      ? 'Seleccione una ubicación para mostrar puentes/sitios...'
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
    const btnAgregar = document.getElementById('btnAgregarMaterialMantenimiento');

    hidden.innerHTML = '';
    btnAgregar?.classList.toggle('d-none', !objetivoId);

    if (!objetivoId) {
      cont.innerHTML = esInfra
        ? 'Seleccione un puente/sitio para mostrar sus materiales...'
        : 'Seleccione un arco para mostrar sus materiales...';
      return;
    }

    cont.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-success"></div>
            <p class="text-muted small mt-2">Cargando materiales...</p>
        </div>
    `;

    const url = esInfra
      ? `../controllers/revisiones_controller.php?action=get_infra_materiales&infraestructura_id=${objetivoId}`
      : `../controllers/revisiones_controller.php?action=get_materiales&arco_id=${objetivoId}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const botonAgregarHtml = `
              <div class="materiales-maintenance-toolbar">
                <span class="text-muted small">${esInfra ? 'Selecciona, cambia, retira o agrega material al puente/sitio.' : 'Selecciona, cambia, retira o agrega material al arco.'}</span>
              </div>
            `;

            if (!data.length) {
                cont.innerHTML = `
                    ${botonAgregarHtml}
                    <div class="alert alert-warning py-2 mb-3">
                      ${esInfra ? 'Este puente/sitio' : 'Este arco'} no tiene materiales registrados.
                    </div>
                    <div class="contenedor-materiales"></div>
                `;
                bindAddMaterialButton();
                return;
            }

            let html = `${botonAgregarHtml}<div class="contenedor-materiales">`;

            data.forEach((m,index) => {
                const medidaLabel = m.medida === 'm' ? 'metros' : (m.medida === 'pz' ? 'piezas' : m.medida);
                const relacionId = m.relacion_id || m.arco_material_id || '';
                const ip = m.ip || '';
                const mac = m.mac || '';
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
                          data-ip="${ip}"
                          data-original-ip="${ip}"
                          data-mac="${mac}"
                          data-original-mac="${mac}"
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

                            ${ip ? `
                              <small class="text-muted d-block mb-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-hdd-network text-primary me-1"></i>IP: ${escapeHtmlRevision(ip)}</span>
                              </small>
                            ` : ''}

                            ${mac ? `
                              <small class="text-muted d-block mb-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-ethernet text-success me-1"></i>MAC: ${escapeHtmlRevision(mac)}</span>
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
                                          data-ip="${ip}"
                                          data-mac="${mac}"
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
            bindAddMaterialButton();
        });
});

function bindMaterialCards() {
    const hidden = document.getElementById('materialesHidden');

    document.querySelectorAll('#materialesContainer .material-card').forEach(card => {
        if (card.dataset.cardBound === '1') return;
        card.dataset.cardBound = '1';
        card.addEventListener('click', function (e) {
            if (e.target.closest('.btn-edit-material, .btn-retire-material')) return;
            if (this.classList.contains('material-retirado')) return;
            if (this.classList.contains('material-agregado')) return;

            const uid = this.dataset.uid;
            const materialId = this.dataset.materialId || this.dataset.material_id;
            const arcoMaterialId = obtenerRelacionOriginalMaterial(this);
            const serie = this.dataset.serie || '';
            const ip = this.dataset.ip || '';
            const mac = this.dataset.mac || '';
            const cantidad = this.dataset.cantidad || 1;
            if (!arcoMaterialId) {
                alert('Este material no tiene una relacion valida con el arco o sitio. Recarga la pagina antes de registrarlo como cambio.');
                return;
            }

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
                    <input type="hidden" name="materiales[${uid}][ip]" value="${ip}">
                    <input type="hidden" name="materiales[${uid}][mac]" value="${mac}">
                    <input type="hidden" name="materiales[${uid}][accion]" value="cambio">
                    <input type="hidden" name="materiales[${uid}][cambiado]" value="1">
                `;

                hidden.appendChild(bloque);
            }
        });
    });
}

let materialesRevisionModalCache = null;
let contadorMaterialAgregadoRevision = 0;

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

function aplicarEstadoIpModal(tieneIp, enfocar = false) {
  const checkIp = document.getElementById('modalCheckIp');
  const ipField = document.getElementById('modalIpField');
  const ipInput = document.getElementById('modalIpInput');

  if (checkIp) checkIp.checked = Boolean(tieneIp);
  ipField?.classList.toggle('d-none', !tieneIp);
  if (!tieneIp && ipInput) ipInput.value = '';
  if (tieneIp && enfocar) setTimeout(() => ipInput?.focus(), 100);
}

function aplicarEstadoMacModal(tieneMac, enfocar = false) {
  const checkMac = document.getElementById('modalCheckMac');
  const macField = document.getElementById('modalMacField');
  const macInput = document.getElementById('modalMacInput');

  if (checkMac) checkMac.checked = Boolean(tieneMac);
  macField?.classList.toggle('d-none', !tieneMac);
  if (!tieneMac && macInput) macInput.value = '';
  if (tieneMac && enfocar) setTimeout(() => macInput?.focus(), 100);
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
    aplicarEstadoIpModal(false);
    aplicarEstadoMacModal(false);
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
        if (btn.dataset.editBound === '1') return;
        btn.dataset.editBound = '1';
        btn.addEventListener('click', function (e) {
            e.stopPropagation();

            const id = btn.dataset.materialId || btn.dataset.material_id;
            const medida = btn.dataset.medida;

            const card = this.closest('.material-card');
            document.getElementById('modalSerie').dataset.mode = card?.classList.contains('material-agregado') ? 'agregado' : 'cambio';
            document.querySelector('#modalSerie .modal-title').innerHTML = card?.classList.contains('material-agregado')
              ? '<i class="bi bi-plus-circle"></i> Editar material agregado'
              : '<i class="bi bi-pencil"></i> Editar material cambiado';

            actualizarCamposModalSeriePorMedida(medida);

            document.getElementById('modalMaterialId').value = btn.dataset.uid;
            const serieActual = normalizarSerieRevision(card.dataset.serie);
            const ipActual = String(card.dataset.ip || '').trim();
            const macActual = String(card.dataset.mac || '').trim();

            document.getElementById('modalSerieInput').value = serieActual;
            aplicarEstadoSerieModal(Boolean(serieActual));

            document.getElementById('modalIpInput').value = ipActual;
            aplicarEstadoIpModal(Boolean(ipActual));

            document.getElementById('modalMacInput').value = macActual;
            aplicarEstadoMacModal(Boolean(macActual));

            document.getElementById('modalCantidadInput').value = card.dataset.cantidad || '1';

            cargarMaterialesModalMantenimiento(id).then(() => {
              const serieActualizada = normalizarSerieRevision(card.dataset.serie);
              const ipActualizada = String(card.dataset.ip || '').trim();
              const macActualizada = String(card.dataset.mac || '').trim();

              document.getElementById('modalSerieInput').value = serieActualizada;
              aplicarEstadoSerieModal(Boolean(serieActualizada));

              document.getElementById('modalIpInput').value = ipActualizada;
              aplicarEstadoIpModal(Boolean(ipActualizada));

              document.getElementById('modalMacInput').value = macActualizada;
              aplicarEstadoMacModal(Boolean(macActualizada));
            });
            
            modal.show();
        });
    });
}

function bindAddMaterialButton() {
  const btn = document.getElementById('btnAgregarMaterialMantenimiento');
  if (!btn || btn.dataset.addBound === '1') return;
  btn.dataset.addBound = '1';

  btn.addEventListener('click', () => {
    const esInfra = esMantenimientoInfraestructura();
    const objetivoId = document.getElementById('arcoSelect')?.value || '';
    if (!objetivoId) {
      alert(`Seleccione un ${esInfra ? 'puente/sitio' : 'arco'} antes de agregar material.`);
      return;
    }

    const modalEl = document.getElementById('modalSerie');
    modalEl.dataset.mode = 'agregado';
    document.querySelector('#modalSerie .modal-title').innerHTML = `<i class="bi bi-plus-circle"></i> Agregar material al ${esInfra ? 'puente/sitio' : 'arco'}`;

    const uid = `agregado_${Date.now()}_${++contadorMaterialAgregadoRevision}`;
    document.getElementById('modalMaterialId').value = uid;
    document.getElementById('modalSerieInput').value = '';
    document.getElementById('modalIpInput').value = '';
    document.getElementById('modalMacInput').value = '';
    document.getElementById('modalCantidadInput').value = '1';
    aplicarEstadoSerieModal(false);
    aplicarEstadoIpModal(false);
    aplicarEstadoMacModal(false);

    cargarMaterialesModalMantenimiento('').then(() => {
      document.getElementById('modalSerieInput').value = '';
      document.getElementById('modalIpInput').value = '';
      document.getElementById('modalMacInput').value = '';
      document.getElementById('modalCantidadInput').value = '1';
      aplicarEstadoSerieModal(false);
      aplicarEstadoIpModal(false);
      aplicarEstadoMacModal(false);
    });

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
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
    serie: card?.dataset?.originalSerie || '',
    ip: card?.dataset?.originalIp || '',
    mac: card?.dataset?.originalMac || ''
  };
}

function actualizarCamposModalSeriePorMedida(medida) {
  const datosCantidad = document.getElementById('DatosCantidad');
  const datosSerie = document.getElementById('DatosSeries');
  const medidaLabel = document.getElementById('medida-label');
  const esMetro = medidaEsMetro(medida);

  datosCantidad?.classList.toggle('d-none', !esMetro);
  datosSerie?.classList.toggle('d-none', esMetro);
  if (esMetro) {
    aplicarEstadoSerieModal(false);
    aplicarEstadoIpModal(false);
    aplicarEstadoMacModal(false);
  }
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

function sincronizarMaterialHidden(uid, materialId, cantidad, serie, ip = '', mac = '', arcoMaterialId = '', accion = 'cambio') {
  const hidden = document.getElementById('materialesHidden');
  if (!hidden) return false;

  if (accion !== 'agregado' && !arcoMaterialId) {
    alert('Este material no tiene una relacion valida con el arco o sitio. Recarga la pagina antes de registrar el mantenimiento.');
    return false;
  }

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
  asegurarInputMaterial(bloque, uid, 'ip', ip);
  asegurarInputMaterial(bloque, uid, 'mac', mac);
  asegurarInputMaterial(bloque, uid, 'accion', accion);
  asegurarInputMaterial(bloque, uid, 'cambiado', '1');
  return true;
}

function quitarMaterialHidden(uid) {
  document.getElementById('materialesHidden')?.querySelector(`.material-${uid}`)?.remove();
}

function marcarMaterialRetirado(card) {
  const uid = card?.dataset?.uid || '';
  if (!uid || !card) return;

  const arcoMaterialId = obtenerRelacionOriginalMaterial(card);
  const original = obtenerMaterialOriginal(card);
  if (!arcoMaterialId) {
    alert('Este material no tiene una relacion valida con el arco o sitio. Recarga la pagina antes de retirarlo.');
    return;
  }

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

  sincronizarMaterialHidden(uid, original.materialId, original.cantidad, original.serie, original.ip, original.mac, arcoMaterialId, 'retiro');
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
    if (btn.dataset.retireBound === '1') return;
    btn.dataset.retireBound = '1';
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const card = this.closest('.material-card');
      if (!card) return;

      if (card.classList.contains('material-agregado')) {
        const nombre = card.querySelector('.fw-semibold')?.textContent?.trim() || 'este material agregado';
        const confirmar = window.confirm(`Seguro que quieres quitar ${nombre} de este mantenimiento?`);
        if (!confirmar) return;
        const uid = card.dataset.uid || '';
        quitarMaterialHidden(uid);
        card.closest('.contenedor-material')?.remove();
        return;
      }

      if (card.classList.contains('material-retirado')) {
        cancelarRetiroMaterial(card);
      } else {
        const nombre = card.querySelector('.fw-semibold')?.textContent?.trim() || 'este material';
        const objetivo = esMantenimientoInfraestructura() ? 'sitio' : 'arco';
        const confirmar = window.confirm(`Seguro que quieres retirar ${nombre} de este ${objetivo}?`);
        if (!confirmar) return;

        marcarMaterialRetirado(card);
      }
    });
  });
}

function asegurarGridMaterialesMantenimiento() {
  const cont = document.getElementById('materialesContainer');
  if (!cont) return null;

  let grid = cont.querySelector('.contenedor-materiales');
  if (!grid) {
    grid = document.createElement('div');
    grid.className = 'contenedor-materiales';
    cont.appendChild(grid);
  }

  return grid;
}

function crearTarjetaMaterialAgregado(uid, materialId, nombre, medida, foto, cantidad, serie, ip = '', mac = '') {
  const grid = asegurarGridMaterialesMantenimiento();
  if (!grid) return null;

  const medidaLabel = etiquetaMedidaModal(medida);
  const fotoFinal = foto && foto !== 'null' ? foto : 'default.png';
  const wrapper = document.createElement('div');
  wrapper.className = 'contenedor-material material-agregado-wrapper';
  wrapper.innerHTML = `
    <div class="card material-card material-agregado shadow-sm border-success border-3"
      data-uid="${escapeHtmlRevision(uid)}"
      data-material_id="${escapeHtmlRevision(materialId)}"
      data-material-id="${escapeHtmlRevision(materialId)}"
      data-original-material-id="${escapeHtmlRevision(materialId)}"
      data-arco-material-id=""
      data-relacion_id=""
      data-serie="${escapeHtmlRevision(serie || '')}"
      data-original-serie="${escapeHtmlRevision(serie || '')}"
      data-ip="${escapeHtmlRevision(ip || '')}"
      data-original-ip="${escapeHtmlRevision(ip || '')}"
      data-mac="${escapeHtmlRevision(mac || '')}"
      data-original-mac="${escapeHtmlRevision(mac || '')}"
      data-cantidad="${escapeHtmlRevision(cantidad || '1')}"
      data-original-cantidad="${escapeHtmlRevision(cantidad || '1')}"
      title="${escapeHtmlRevision(nombre || '')}">

      <button type="button"
              class="btn btn-sm btn-danger btn-retire-material material-retire-x"
              data-uid="${escapeHtmlRevision(uid)}"
              title="Quitar material agregado">
        &times;
      </button>

      <span class="badge bg-success material-added-badge">Agregado</span>

      <img src="../uploads/materiales/${escapeHtmlRevision(fotoFinal)}"
        class="card-img-top"
        style="height:80px; width:100%; object-fit:contain; padding:5px;"
        onerror="this.src='../uploads/materiales/default.png'">

      <div class="card-body text-center">
        <div class="fw-semibold mb-1">${escapeHtmlRevision(nombre || 'Material')}</div>
        <small class="text-muted d-block mb-1">${escapeHtmlRevision(medidaLabel)}</small>

        ${medidaEsMetro(medida) ? `
          <small class="text-muted d-block">${escapeHtmlRevision(cantidad)} metros</small>
        ` : ''}

        ${serie ? `
          <span class="serie-label">Serie:</span>
          <small class="text-muted d-block serie-container">
            <span class="serie-value">${escapeHtmlRevision(serie)}</span>
          </small>
        ` : ''}

        ${ip ? `
          <small class="text-muted d-block mb-1 ip-container">
            <span class="badge bg-light text-dark border"><i class="bi bi-hdd-network text-primary me-1"></i>IP: ${escapeHtmlRevision(ip)}</span>
          </small>
        ` : ''}

        ${mac ? `
          <small class="text-muted d-block mb-1 mac-container">
            <span class="badge bg-light text-dark border"><i class="bi bi-ethernet text-success me-1"></i>MAC: ${escapeHtmlRevision(mac)}</span>
          </small>
        ` : ''}

        <div class="material-actions mt-auto">
          <button type="button"
                  class="btn btn-sm btn-outline-primary btn-edit-material"
                  data-uid="${escapeHtmlRevision(uid)}"
                  data-material_id="${escapeHtmlRevision(materialId)}"
                  data-material-id="${escapeHtmlRevision(materialId)}"
                  data-arco-material-id=""
                  data-relacion_id=""
                  data-material="${escapeHtmlRevision(nombre || '')}"
                  data-medida="${escapeHtmlRevision(medidaLabel)}"
                  data-serie="${escapeHtmlRevision(serie || '')}"
                  data-ip="${escapeHtmlRevision(ip || '')}"
                  data-mac="${escapeHtmlRevision(mac || '')}"
                  data-cantidad="${escapeHtmlRevision(cantidad || '1')}">
               Editar
          </button>
        </div>
      </div>
    </div>
  `;

  grid.prepend(wrapper);
  const card = wrapper.querySelector('.material-card');
  bindEditButtons();
  bindRetireButtons();
  return card;
}

function guardarCambiosMaterialMantenimiento() {
  const uid = document.getElementById('modalMaterialId')?.value || '';
  const inputMaterial = document.getElementById('modalSelectMaterial');
  const materialId = inputMaterial?.value || '';
  const medida = inputMaterial?.dataset?.medida || '';
  const nombre = inputMaterial?.dataset?.nombre || '';
  const foto = inputMaterial?.dataset?.foto || '';
  const modo = document.getElementById('modalSerie')?.dataset?.mode || 'cambio';
  const esMetro = medidaEsMetro(medida);
  const cantidad = esMetro ? (document.getElementById('modalCantidadInput')?.value || '1') : '1';
  const tieneSerie = !esMetro && Boolean(document.getElementById('modalCheckSerie')?.checked);
  const serie = tieneSerie ? normalizarSerieRevision(document.getElementById('modalSerieInput')?.value) : '';
  const tieneIp = !esMetro && Boolean(document.getElementById('modalCheckIp')?.checked);
  const ip = tieneIp ? String(document.getElementById('modalIpInput')?.value || '').trim() : '';
  const tieneMac = !esMetro && Boolean(document.getElementById('modalCheckMac')?.checked);
  const mac = tieneMac ? String(document.getElementById('modalMacInput')?.value || '').trim() : '';
  const card = Array.from(document.querySelectorAll('#materialesContainer .material-card'))
    .find(item => item.dataset.uid === uid);

  if (!uid || !materialId) return;
  if (tieneSerie && !serie) {
    alert('Ingrese la serie');
    return;
  }
  if (tieneIp && !ip) {
    alert('Ingrese la dirección IP');
    return;
  }
  if (tieneMac && !mac) {
    alert('Ingrese la dirección MAC');
    return;
  }

  if (modo === 'agregado' && !card) {
    crearTarjetaMaterialAgregado(uid, materialId, nombre, medida, foto, cantidad, serie, ip, mac);
    sincronizarMaterialHidden(uid, materialId, cantidad, serie, ip, mac, '', 'agregado');
    bootstrap.Modal.getInstance(document.getElementById('modalSerie'))?.hide();
    return;
  }

  if (!card) return;

  const arcoMaterialId = obtenerRelacionOriginalMaterial(card);
  const accionMaterial = card.classList.contains('material-agregado') ? 'agregado' : 'cambio';
  if (accionMaterial !== 'agregado' && !arcoMaterialId) {
    alert('Este material no tiene una relacion valida con el arco o sitio. Recarga la pagina antes de editarlo.');
    return;
  }

  card.dataset.material_id = materialId;
  card.dataset.materialId = materialId;
  card.setAttribute('data-material_id', materialId);
  card.dataset.cantidad = cantidad;
  card.dataset.serie = serie;
  card.dataset.ip = ip;
  card.dataset.mac = mac;
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
    btn.dataset.ip = ip;
    btn.dataset.mac = mac;
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

  let ipContainer = card.querySelector('.ip-container');
  if (ip) {
    if (!ipContainer) {
      ipContainer = document.createElement('small');
      ipContainer.className = 'text-muted d-block mb-1 ip-container';
      card.querySelector('.card-body')?.insertBefore(ipContainer, actions);
    }
    ipContainer.innerHTML = `<span class="badge bg-light text-dark border"><i class="bi bi-hdd-network text-primary me-1"></i>IP: ${escapeHtmlRevision(ip)}</span>`;
  } else {
    ipContainer?.remove();
  }

  let macContainer = card.querySelector('.mac-container');
  if (mac) {
    if (!macContainer) {
      macContainer = document.createElement('small');
      macContainer.className = 'text-muted d-block mb-1 mac-container';
      card.querySelector('.card-body')?.insertBefore(macContainer, actions);
    }
    macContainer.innerHTML = `<span class="badge bg-light text-dark border"><i class="bi bi-ethernet text-success me-1"></i>MAC: ${escapeHtmlRevision(mac)}</span>`;
  } else {
    macContainer?.remove();
  }

  if (!sincronizarMaterialHidden(uid, materialId, cantidad, serie, ip, mac, arcoMaterialId, accionMaterial)) {
    return;
  }
  bootstrap.Modal.getInstance(document.getElementById('modalSerie'))?.hide();
}

document.getElementById('btnGuardarSerie')?.addEventListener('click', function (e) {
  e.preventDefault();
  guardarCambiosMaterialMantenimiento();
});

document.getElementById('modalCheckSerie')?.addEventListener('change', function () {
  aplicarEstadoSerieModal(this.checked, this.checked);
});

document.getElementById('modalCheckIp')?.addEventListener('change', function () {
  aplicarEstadoIpModal(this.checked, this.checked);
});

document.getElementById('modalCheckMac')?.addEventListener('change', function () {
  aplicarEstadoMacModal(this.checked, this.checked);
});

const modalMacInput = document.getElementById('modalMacInput');
if (modalMacInput) {
  modalMacInput.addEventListener('input', function () {
    let val = this.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
    let formatted = val.match(/.{1,2}/g)?.join(':') || '';
    this.value = formatted.substring(0, 17);
  });
}

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
      ? `${fechaKey}|${accion}|${material.material}|${material.medida}|${rowId}|${material.serie || ""}|${material.ip || ""}|${material.mac || ""}|${index}`
      : `${fechaKey}|${accion}|${material.material}|${material.medida}`;

    if (!grupos.has(key)) {
      grupos.set(key, {
        material: material.material,
        medida: material.medida,
        cantidad: 0,
        series: [],
        ip: material.ip || "",
        mac: material.mac || "",
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
          Fecha de mantenimiento: ${escapeHtmlRevision(fechaTexto)}
        </div>
        <div class="material-grid revision-material-grid">
          ${items.map((m, index) => {
            const globalIndex = `${fechaKey}_${index}`.replace(/[^a-zA-Z0-9_-]/g, "_");
            const foto = String(m.foto || "").trim();
            const imagenHtml = (!foto || foto === "null")
              ? `<div class="d-flex align-items-center justify-content-center bg-secondary text-white material-img">Sin foto</div>`
              : `<img src="../uploads/materiales/${escapeHtmlRevision(foto)}" class="material-img" alt="${escapeHtmlRevision(m.material)}">`;
            const medidaTexto = textoMedidaRevision(m.medida, m.cantidad);
            const accion = String(m.accion || "").toLowerCase();
            const esRetiro = accion === "retiro";
            const esAgregado = accion === "agregado";
            const accionIcon = esRetiro ? 'bi-box-arrow-up' : (esAgregado ? 'bi-plus-circle' : 'bi-tools');
            const accionTexto = esRetiro
              ? 'Retirado por mantenimiento'
              : (esAgregado ? 'Agregado por mantenimiento' : 'Cambiado por mantenimiento');
            const badgeClass = esRetiro ? 'bg-danger' : (esAgregado ? 'bg-primary' : 'bg-success');

            const seriesList = Array.isArray(m.series) ? m.series.filter(Boolean) : [];
            const totalSeries = seriesList.length;
            const ipVal = String(m.ip || "").trim();
            const macVal = String(m.mac || "").trim();
            const hasTechData = totalSeries > 0 || ipVal !== "" || macVal !== "";

            let techDataHtml = "";
            if (hasTechData) {
              const techCollapseId = `tech_rev_${globalIndex}`;
              const summaryParts = [];
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
                            ${seriesList.map(s => `<span class="series-chip">${escapeHtmlRevision(s)}</span>`).join("")}
                          </div>
                        </div>
                      ` : ""}
                      ${ipVal ? `
                        <div class="mt-1">
                          <span class="tech-badge"><i class="bi bi-hdd-network text-primary me-1"></i>IP: <strong class="ms-1">${escapeHtmlRevision(ipVal)}</strong></span>
                        </div>
                      ` : ""}
                      ${macVal ? `
                        <div class="mt-1">
                          <span class="tech-badge"><i class="bi bi-ethernet text-success me-1"></i>MAC: <strong class="ms-1">${escapeHtmlRevision(macVal)}</strong></span>
                        </div>
                      ` : ""}
                    </div>
                  </div>
                </div>
              `;
            }

            return `
              <div class="revision-material-card material-card">
                <div class="d-flex align-items-start gap-2 justify-content-between">
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    ${imagenHtml}
                    <div class="min-w-0">
                      <div class="fw-bold text-capitalize revision-material-name">${escapeHtmlRevision(m.material)}</div>
                      <div class="text-muted small">
                        <i class="bi ${accionIcon}"></i>
                        ${accionTexto}
                      </div>
                    </div>
                  </div>
                  <div class="revision-material-qty text-end">
                    <span class="badge ${badgeClass} fs-6 px-3 py-2">${escapeHtmlRevision(m.cantidad)}</span>
                    <div class="text-muted small mt-1">${escapeHtmlRevision(medidaTexto)}</div>
                  </div>
                </div>
                <div class="text-muted small mt-2">
                  <i class="bi bi-clock"></i>
                  ${escapeHtmlRevision(fechaTexto)}
                </div>
                ${techDataHtml}
              </div>
            `;
          }).join("")}
        </div>
      </div>
    `;
  }).join("");

  contenedor.querySelectorAll('.collapse').forEach(collapseEl => {
    collapseEl.addEventListener('show.bs.collapse', function () {
      let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
      if (btn && btn.classList.contains('tech-toggle-btn')) {
        btn.innerHTML = 'Ocultar <i class="bi bi-chevron-up"></i>';
      } else if (btn) {
        btn.innerHTML = 'Ocultar series <i class="bi bi-chevron-up"></i>';
      }
    });

    collapseEl.addEventListener('hide.bs.collapse', function () {
      let btn = contenedor.querySelector(`[data-bs-target="#${this.id}"]`);
      if (btn && btn.classList.contains('tech-toggle-btn')) {
        btn.innerHTML = 'Ver detalles <i class="bi bi-chevron-down"></i>';
      } else if (btn) {
        btn.innerHTML = 'Ver series <i class="bi bi-chevron-down"></i>';
      }
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

async function cargarFormatosDetalle(detalle) {
  const grid = document.getElementById("detalleFormatosGrid");
  if (!grid) return;

  const isInfra = detalle.origen !== 'Arco';
  let url = `../controllers/formatos_ajax.php?action=get_revision_formatos`;
  if (isInfra) {
    url += `&infraestructura_revision_id=${encodeURIComponent(detalle.id || '')}`;
  } else {
    url += `&revision_id=${encodeURIComponent(detalle.id || '')}`;
  }

  try {
    const res = await fetch(url);
    const data = await res.json();
    const formatos = (data && data.ok && Array.isArray(data.formatos)) ? data.formatos : [];

    const items = ['checklist', 'quality', 'tools'].map(typeKey => {
      const cfg = FORMATOS_CONFIG[typeKey];
      const existing = formatos.find(f => f.tipo === typeKey);
      let fillUrl = `../views/formato_llenar.php?type=${typeKey}`;
      if (existing) {
        fillUrl += `&formato_id=${existing.id}`;
      }
      if (isInfra) {
        fillUrl += `&infraestructura_revision_id=${encodeURIComponent(detalle.id || '')}`;
      } else {
        fillUrl += `&revision_id=${encodeURIComponent(detalle.id || '')}`;
      }

      return `
        <div class="detalle-formato-item ${existing ? 'is-active' : ''}">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold small text-dark"><i class="bi ${cfg.icon} me-1" style="color: ${cfg.color}"></i> ${escapeHtmlRevision(cfg.shortTitle)}</span>
            <span class="badge ${existing ? 'bg-success' : 'bg-secondary'} small">${existing ? 'Generado' : 'Pendiente'}</span>
          </div>
          <div class="d-flex gap-1 mt-1">
            ${existing ? `
              <a href="../controllers/formato_servicio_pdf.php?id=${existing.id}" target="_blank" class="btn btn-sm btn-danger py-0 px-2 flex-fill" style="font-size: 0.75rem;">
                <i class="bi bi-file-earmark-pdf"></i> PDF
              </a>
              <a href="${escapeHtmlRevision(fillUrl)}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 flex-fill" style="font-size: 0.75rem;">
                <i class="bi bi-pencil"></i> Editar
              </a>
            ` : `
              <a href="${escapeHtmlRevision(fillUrl)}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2 flex-fill" style="font-size: 0.75rem;">
                <i class="bi bi-plus-circle"></i> Llenar
              </a>
            `}
          </div>
        </div>
      `;
    }).join('');

    grid.innerHTML = items;
  } catch (e) {
    grid.innerHTML = `<div class="text-muted small py-1">No se pudieron cargar los formatos.</div>`;
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

  const isInfra = detalle.origen !== "Arco";
  const tipoClase = detalle.tipo === "Correctivo" ? "bg-warning text-dark" : "bg-success";
  const downloadUrl = detalle.pdf_download || (detalle.id ? `../controllers/pdf_controller.php?action=mantenimiento&id=${detalle.id}&download=1` : "");
  const pdfHtml = detalle.pdf
    ? `<a href="${escapeHtmlRevision(detalle.pdf)}" target="_blank" class="btn btn-sm btn-danger" title="Ver / Imprimir formato">
         <i class="bi bi-file-earmark-pdf"></i> Ver formato
       </a>
       ${downloadUrl ? `
       <a href="${escapeHtmlRevision(downloadUrl)}" class="btn btn-sm btn-outline-danger" title="Descargar PDF">
         <i class="bi bi-download"></i> Descargar
       </a>` : ""}`
    : "";

  const formatosBtnModal = `
    <button type="button" class="btn btn-sm btn-outline-success verFormatosRevisionBtn"
      data-revision-id="${!isInfra ? escapeHtmlRevision(detalle.id || '') : ''}"
      data-infra-revision-id="${isInfra ? escapeHtmlRevision(detalle.id || '') : ''}"
      data-arco-id="${escapeHtmlRevision(detalle.arco_id || '')}"
      data-infraestructura-id="${escapeHtmlRevision(detalle.infraestructura_id || '')}"
      data-objetivo="${escapeHtmlRevision(detalle.objetivo || '')}"
      data-tipo-objetivo="${escapeHtmlRevision(detalle.origen || 'Mantenimiento')}"
      data-ubicacion="${escapeHtmlRevision(detalle.ubicacion || '')}"
      data-fecha="${escapeHtmlRevision(detalle.fecha || '')}"
      data-tipo-mant="${escapeHtmlRevision(detalle.tipo || 'Correctivo')}"
      data-tecnico-id="${escapeHtmlRevision(detalle.tecnico_id || '')}"
      data-bs-toggle="modal" data-bs-target="#modalFormatosMantenimiento"
      title="Formatos de Servicio">
      <i class="bi bi-file-earmark-check"></i> Formatos de Servicio
    </button>
  `;

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
          ${formatosBtnModal}
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
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-check text-success"></i> Formatos de Servicio</h6>
        </div>
        <div class="detalle-formatos-grid" id="detalleFormatosGrid">
          <div class="text-muted small py-2"><div class="spinner-border spinner-border-sm text-success me-1"></div> Consultando formatos...</div>
        </div>
      </div>

      <div class="mt-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-camera"></i> Evidencias</h6>
        <div class="detalle-evidencias-grid" id="detalleEvidenciasGrid"></div>
      </div>
    </div>
  `;

  window.requestAnimationFrame(() => {
    cargarEvidenciasDetalle(detalle);
    cargarFormatosDetalle(detalle);
  });
}

// ==================== FORMATOS DE MANTENIMIENTO ====================

// ==================== FORMATOS DE MANTENIMIENTO ====================

const FORMATOS_CONFIG = {
  checklist: {
    key: 'checklist',
    title: 'Check List de Diagnóstico Inicial',
    shortTitle: 'Check List Diagnóstico',
    icon: 'bi-card-checklist',
    badgeClass: 'bg-success',
    color: '#198754',
    lightBg: '#e9f7ef',
    description: 'Diagnóstico inicial de componentes, serie, IP, MAC y estado operativo.',
    blankFile: 'CHECK_LIST_DIAGNOSTICO_INICIAL.docx'
  },
  quality: {
    key: 'quality',
    title: 'Formato de Pruebas de Calidad',
    shortTitle: 'Pruebas de Calidad',
    icon: 'bi-patch-check',
    badgeClass: 'bg-primary',
    color: '#0d6efd',
    lightBg: '#e7f1ff',
    description: 'Verificación de lectura por carril, alimentación eléctrica y enlace.',
    blankFile: 'FORMATO_PRUEBAS_CALIDAD.docx'
  },
  tools: {
    key: 'tools',
    title: 'Formato de Herramientas y EPP',
    shortTitle: 'Herramientas y EPP',
    icon: 'bi-tools',
    badgeClass: 'bg-warning',
    color: '#d97706',
    lightBg: '#fef3c7',
    description: 'Control de herramientas, consumibles y equipo de protección personal.',
    blankFile: 'FORMATO_HERRAMIENTAS.docx'
  }
};

async function abrirModalFormatosMantenimiento(btn) {
  const headerContainer = document.getElementById("formatosModalHeader");
  const gridContainer = document.getElementById("formatosMantenimientoGrid");
  if (!headerContainer || !gridContainer) return;

  const dataset = btn.dataset || {};
  const revisionId = parseInt(dataset.revisionId || "0", 10);
  const infraRevisionId = parseInt(dataset.infraRevisionId || "0", 10);
  const arcoId = parseInt(dataset.arcoId || "0", 10);
  const infraId = parseInt(dataset.infraestructuraId || "0", 10);
  const objetivo = dataset.objetivo || "Mantenimiento";
  const tipoObjetivo = dataset.tipoObjetivo || (infraRevisionId > 0 || infraId > 0 ? "Puente/Sitio" : "Arco");
  const ubicacion = dataset.ubicacion || "";
  const fecha = dataset.fecha || "";
  const tipoMant = dataset.tipoMant || "Correctivo";
  const tecnicoId = dataset.tecnicoId || "";
  let tecnicoNombre = dataset.tecnicoNombre || "";

  // Render initial loading state
  headerContainer.innerHTML = `
    <div class="formatos-banner-card p-3 p-md-4 rounded-3 bg-white border shadow-sm">
      <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2 pb-2 border-bottom">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="badge ${tipoObjetivo === 'Arco' ? 'bg-success' : 'bg-primary'} px-2.5 py-1.5"><i class="bi ${tipoObjetivo === 'Arco' ? 'bi-bounding-box-circles' : 'bi-broadcast-pin'} me-1"></i>${escapeHtmlRevision(tipoObjetivo)}</span>
            <span class="badge ${tipoMant === 'Correctivo' ? 'bg-warning text-dark' : 'bg-info text-dark'} px-2.5 py-1.5">${escapeHtmlRevision(tipoMant)}</span>
            ${revisionId > 0 ? `<span class="badge bg-secondary px-2.5 py-1.5">Folio #${revisionId}</span>` : ''}
            ${infraRevisionId > 0 ? `<span class="badge bg-secondary px-2.5 py-1.5">Folio #${infraRevisionId}</span>` : ''}
          </div>
          <h4 class="fw-bold mb-1 text-dark">${escapeHtmlRevision(objetivo)}</h4>
          ${ubicacion ? `<div class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${escapeHtmlRevision(ubicacion)}</div>` : ''}
        </div>
        <div class="text-md-end">
          <div class="formatos-progress-pill px-3 py-1.5 rounded-pill border bg-light text-secondary">
            <div class="spinner-border spinner-border-sm text-success me-1"></div> Consultando formatos...
          </div>
        </div>
      </div>
    </div>
  `;

  gridContainer.innerHTML = `
    <div class="text-center text-muted py-5 w-100 bg-white rounded-3 border">
      <div class="spinner-border text-success spinner-border-sm me-2"></div> Consultando formatos vinculados y datos del servicio...
    </div>
  `;

  let linkedFormats = [];
  try {
    let url = `../controllers/formatos_ajax.php?action=get_revision_formatos`;
    if (revisionId > 0) {
      url += `&revision_id=${revisionId}`;
    } else if (infraRevisionId > 0) {
      url += `&infraestructura_revision_id=${infraRevisionId}`;
    } else if (arcoId > 0) {
      url += `&arco_id=${arcoId}`;
    } else if (infraId > 0) {
      url += `&infraestructura_id=${infraId}`;
    }

    const response = await fetch(url);
    if (response.ok) {
      const data = await response.json();
      if (data.ok && Array.isArray(data.formatos)) {
        linkedFormats = data.formatos;
      }
      if (data.ok && data.revision && data.revision.tecnico) {
        tecnicoNombre = data.revision.tecnico;
      }
    }
  } catch (err) {
    console.error("Error al obtener formatos de revisión:", err);
  }

  const countGenerated = linkedFormats.filter(f => ['checklist', 'quality', 'tools'].includes(f.tipo)).length;

  // Render finalized header banner
  headerContainer.innerHTML = `
    <div class="formatos-banner-card p-3 p-md-4 rounded-3 bg-white border shadow-sm">
      <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2 pb-2 border-bottom">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="badge ${tipoObjetivo === 'Arco' ? 'bg-success' : 'bg-primary'} px-2.5 py-1.5"><i class="bi ${tipoObjetivo === 'Arco' ? 'bi-bounding-box-circles' : 'bi-broadcast-pin'} me-1"></i>${escapeHtmlRevision(tipoObjetivo)}</span>
            <span class="badge ${tipoMant === 'Correctivo' ? 'bg-warning text-dark' : 'bg-info text-dark'} px-2.5 py-1.5">${escapeHtmlRevision(tipoMant)}</span>
            ${revisionId > 0 ? `<span class="badge bg-secondary px-2.5 py-1.5">Folio #${revisionId}</span>` : ''}
            ${infraRevisionId > 0 ? `<span class="badge bg-secondary px-2.5 py-1.5">Folio #${infraRevisionId}</span>` : ''}
          </div>
          <h4 class="fw-bold mb-1 text-dark">${escapeHtmlRevision(objetivo)}</h4>
          ${ubicacion ? `<div class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i>${escapeHtmlRevision(ubicacion)}</div>` : ''}
        </div>
        <div class="text-md-end">
          <div class="formatos-progress-pill px-3 py-1.5 rounded-pill border ${countGenerated === 3 ? 'bg-success-subtle text-success border-success' : 'bg-light text-secondary'}">
            <i class="bi ${countGenerated === 3 ? 'bi-check-all text-success' : 'bi-hourglass-split'} me-1"></i><strong>${countGenerated} de 3</strong> formatos completados
          </div>
        </div>
      </div>
      <div class="row g-2 pt-1 text-secondary small align-items-center">
        <div class="col-12 col-md-5">
          <i class="bi bi-person-badge text-primary me-1"></i><strong>Técnico encargado:</strong> ${escapeHtmlRevision(tecnicoNombre || 'Asignado')}
        </div>
        <div class="col-12 col-md-4">
          <i class="bi bi-calendar-check text-success me-1"></i><strong>Fecha servicio:</strong> ${fecha ? escapeHtmlRevision(formatearFechaHoraRevision(fecha)) : 'No especificada'}
        </div>
        <div class="col-12 col-md-3 text-md-end text-muted">
          <i class="bi bi-arrow-repeat text-info me-1"></i>Reutilización activa
        </div>
      </div>
    </div>
  `;

  // Render cards for all 3 formats
  let cardsHtml = '';
  ['checklist', 'quality', 'tools'].forEach(typeKey => {
    const formatCfg = FORMATOS_CONFIG[typeKey];
    const existingFormat = linkedFormats.find(f => f.tipo === typeKey);

    // Build URL for creating or editing
    let fillUrl = `../views/formato_llenar.php?type=${typeKey}`;
    if (existingFormat) {
      fillUrl += `&formato_id=${existingFormat.id}`;
    }
    if (revisionId > 0) fillUrl += `&revision_id=${revisionId}`;
    if (infraRevisionId > 0) fillUrl += `&infraestructura_revision_id=${infraRevisionId}`;
    if (arcoId > 0) fillUrl += `&arco_id=${arcoId}`;
    if (infraId > 0) fillUrl += `&infraestructura_id=${infraId}`;
    if (tecnicoId) fillUrl += `&tecnico_id=${encodeURIComponent(tecnicoId)}`;

    const blankDownloadUrl = `../controllers/formatos_controller.php?action=download_blank&type=${typeKey}`;

    cardsHtml += `
      <div class="formato-mantenimiento-card ${existingFormat ? 'is-created' : 'is-pending'} p-3 p-md-4 bg-white d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
          <div class="d-flex align-items-center gap-3">
            <span class="formato-card-icon shadow-xs" style="color: ${formatCfg.color}; background: ${formatCfg.lightBg};">
              <i class="bi ${formatCfg.icon} fs-4"></i>
            </span>
            <div>
              <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.05rem;">${escapeHtmlRevision(formatCfg.title)}</h5>
              <small class="text-muted d-block mt-1" style="font-size: 0.8rem; line-height: 1.35;">${escapeHtmlRevision(formatCfg.description)}</small>
            </div>
          </div>
          <div>
            ${existingFormat ? `
              <span class="badge bg-success text-white px-2.5 py-1.5 shadow-xs">
                <i class="bi bi-check-circle-fill me-1"></i> Generado
              </span>
            ` : `
              <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1.5">
                <i class="bi bi-hourglass-split me-1"></i> Pendiente
              </span>
            `}
          </div>
        </div>

        <div class="formato-card-meta mb-3 p-2.5 rounded-2 ${existingFormat ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-light text-muted border'} small">
          ${existingFormat ? `
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
              <span><i class="bi bi-person-fill me-1 text-success"></i>${escapeHtmlRevision(existingFormat.creado_por || 'Sistema')}</span>
              <span><i class="bi bi-clock-history me-1 text-success"></i>${escapeHtmlRevision(existingFormat.fecha_servicio ? formatearFechaHoraRevision(existingFormat.fecha_servicio) : (existingFormat.created_at ? formatearFechaHoraRevision(existingFormat.created_at) : ''))}</span>
            </div>
          ` : `
            <div class="d-flex align-items-center gap-1.5 text-secondary">
              <i class="bi bi-magic text-success"></i> Precarga técnico, fecha y componentes del mantenimiento.
            </div>
          `}
        </div>

        <div class="d-flex flex-wrap gap-2 mt-auto pt-3 border-top">
          ${existingFormat ? `
            <a href="../controllers/formato_servicio_pdf.php?id=${existingFormat.id}" target="_blank" class="btn btn-danger flex-fill shadow-xs fw-semibold py-2">
              <i class="bi bi-file-earmark-pdf me-1"></i> Ver PDF
            </a>
            <a href="${escapeHtmlRevision(fillUrl)}" target="_blank" class="btn btn-outline-primary flex-fill shadow-xs py-2">
              <i class="bi bi-pencil-square me-1"></i> Editar
            </a>
          ` : `
            <a href="${escapeHtmlRevision(fillUrl)}" target="_blank" class="btn btn-success flex-fill shadow-xs fw-bold py-2">
              <i class="bi bi-pencil-fill me-1"></i> Llenar Formato
            </a>
          `}
        </div>
      </div>
    `;
  });

  gridContainer.innerHTML = cardsHtml;
}


document.addEventListener('click', function (e) {
  const btnMaterial = e.target.closest('.verMaterialesBtn, .verInfraMaterialesBtn');
  if (btnMaterial) {
    renderComponentesMantenimiento(btnMaterial);
  }

  const btnDetalle = e.target.closest('.verDetalleMantenimientoBtn');
  if (btnDetalle) {
    renderDetalleMantenimiento(btnDetalle);
  }

  const btnFormatos = e.target.closest('.verFormatosRevisionBtn');
  if (btnFormatos) {
    abrirModalFormatosMantenimiento(btnFormatos);
  }
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

  rows.addEventListener("input", e => {
    if (e.target.name === "infra_mac[]") {
      let val = e.target.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
      let formatted = val.match(/.{1,2}/g)?.join(':') || '';
      e.target.value = formatted.substring(0, 17);
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


