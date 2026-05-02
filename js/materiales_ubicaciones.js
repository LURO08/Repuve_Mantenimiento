// CONFIG GLOBAL
const config = {
  materialesTable: { page: 1, limit: 3 },
  ubicacionesTable: { page: 1, limit: 4 }
};

/* --- PAGINACIÓN GENÉRICA --- */
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
  if (!config[tableId]) return;
  config[tableId].page = page;
  renderPagination(tableId);
}

// Inicialización: sólo afectar tablas manejadas aquí
document.addEventListener("DOMContentLoaded", () => {
  ['materialesTable','ubicacionesTable'].forEach(id => {
    const rows = document.querySelectorAll(`#${id} tbody tr`);
    rows.forEach(r => r.dataset.visible = "1");
    renderPagination(id);
  });
});

/* --- MODALES Y UTILIDADES --- */
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = "flex";
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = "none";
}

function previewImage(previewId, event) {
  const img = document.getElementById(previewId);
  if (!img) return;
  const file = event?.target?.files?.[0];
  if (!file) return img.style.display = "none";

  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
  reader.readAsDataURL(file);
}

function openEditMaterial(btn) {
  const tr = btn.closest("tr");
  if (!tr) return;
  document.getElementById("edit-material-id").value = tr.dataset.id || '';
  document.getElementById("edit-material-nombre").value = tr.dataset.nombre || '';
  document.getElementById("edit-material-medida").value = tr.dataset.medida || '';

  const preview = document.getElementById("previewEditar");
  if (preview) {
    if (tr.dataset.foto) { preview.src = "../uploads/materiales/" + tr.dataset.foto; preview.style.display = "block"; }
    else { preview.style.display = "none"; }
  }

  openModal('modalEditarMaterial');
}

function openEditUbicacion(btn, lat, lng) {
  const tr = btn.closest("tr");
  if (!tr) return;
  const ubicacionId = tr.dataset.id;
  document.getElementById("edit-ubicacion-id").value = ubicacionId || '';
  document.getElementById("edit-ubicacion-nombre").value = tr.dataset.nombre || '';

  const latitud = tr.dataset.lat;
  const longitud = lng;
  // Si no hay coordenadas en dataset, obtenerlas por AJAX desde la BD
  if (!latitud || !longitud) {
    fetch(`../controllers/ubicaciones_controller.php?action=get_coords&id=${encodeURIComponent(ubicacionId)}`)
      .then(r => r.json())
      .then(data => {
       
        document.getElementById('edit-material-latitud').value = data.lat || '';
        document.getElementById('edit-material-longitud').value = data.lng || '';
      })
      .catch(err => {
        console.warn('Error al obtener coordenadas:', err);
        document.getElementById('edit-material-latitud').value = '';
        document.getElementById('edit-material-longitud').value = '';
      });
  } else {
    document.getElementById('edit-material-latitud').value = latitud || '';
    document.getElementById('edit-material-longitud').value = longitud || '';
  }
  
  openModal('modalEditarUbicacion');
}

/* --- HELPERS --- */
function debounce(fn, wait = 150) {
  let t;
  return function(...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), wait);
  };
}

function obtenerUbicacionActual(callback) {
  const hasCallback = typeof callback === 'function';
  const fallback = { lat: 17.550826, lng: -99.501462 };

  const fix = (n) => Number(Number(n).toFixed(6));

  if (!navigator.geolocation) {
    console.warn("Geolocalización no soportada");
    if (hasCallback) callback(fallback.lat, fallback.lng);
    return Promise.resolve({
      lat: fix(fallback.lat),
      lng: fix(fallback.lng),
      error: 'Geolocalización no soportada'
    });
  }

  return new Promise(resolve => {
    navigator.geolocation.getCurrentPosition(
      pos => {
        const lat = fix(pos.coords.latitude);
        const lng = fix(pos.coords.longitude);

        if (hasCallback) callback(lat, lng);
        resolve({ lat, lng, error: null });
      },
      err => {
        const msg = (err && err.message) ? err.message : 'Error de geolocalización';
        console.warn("Permiso denegado o error:", msg);

        const lat = fix(fallback.lat);
        const lng = fix(fallback.lng);

        if (hasCallback) callback(lat, lng);
        resolve({ lat, lng, error: msg });
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  });
}

  
  let map, marker;
  let inputLatDestino = null;
  let inputLngDestino = null;
  let mapInicializado = false;

  const modalMapa = new bootstrap.Modal(
    document.getElementById('modalSeleccionarMapa'),
    {
      backdrop: 'static',
      keyboard: false,
      focus: false
    }
  );

  // 🔓 Abrir mapa desde cualquier botón (oculta modal padre para evitar solapamiento)
  document.querySelectorAll('.abrirMapa').forEach(btn => {
    btn.addEventListener('click', () => {
      
      inputLatDestino = btn.dataset.lat;
      inputLngDestino = btn.dataset.lng;

      const latInput = document.getElementById(inputLatDestino);
      const lngInput = document.getElementById(inputLngDestino);

      const lat = latInput ? parseFloat(latInput.value) : NaN;
      const lng = lngInput ? parseFloat(lngInput.value) : NaN;

    console.log('abrirMapa click, data-lat:', lat, 'data-lng:', lng);

      // Si el botón está dentro de un modal custom, lo ocultamos temporalmente
      const parentCustom = btn.closest('.custom-modal');
      if (parentCustom) {
        parentCustom.style.display = 'none';
        // Guardamos referencia para restaurarla cuando se cierre el selector
        modalMapa._parentCustomModal = parentCustom;
      }

      // Definir modo: editar o agregar (para personalizar el texto y el comportamiento)
      const mode = parentCustom && parentCustom.id === 'modalEditarUbicacion' ? 'editar' : 'agregar';
      modalMapa._mode = mode;

      // Actualizar título y ayuda contextual antes de abrir
      const titulo = modalMapa._mode === 'editar' ? 'Seleccionar ubicación (Editar)' : 'Seleccionar ubicación (Agregar)';
      const titleEl = document.querySelector('#modalSeleccionarMapa .modal-title');
      if (titleEl) titleEl.textContent = titulo;
      const helpEl = document.getElementById('mapHelp');
      if (helpEl) helpEl.textContent = modalMapa._mode === 'editar'
        ? 'Editar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.'
        : 'Agregar ubicación: haz clic en el mapa para colocar o arrastra el marcador para ajustar.';

      // Cambiar estilo del header y botón Aceptar según el modo
      const headerEl = document.querySelector('#modalSeleccionarMapa .modal-header');
      const acceptBtn = document.getElementById('btnAceptarUbicacion');
      if (headerEl) {
        if (modalMapa._mode === 'editar') {
          headerEl.classList.remove('bg-success', 'text-white');
          headerEl.classList.add('bg-warning', 'text-dark');
        } else {
          headerEl.classList.remove('bg-warning', 'text-dark');
          headerEl.classList.add('bg-success', 'text-white');
        }
      }
      if (acceptBtn) {
        if (modalMapa._mode === 'editar') {
          acceptBtn.classList.remove('btn-success');
          acceptBtn.classList.add('btn-warning');
        } else {
          acceptBtn.classList.remove('btn-warning');
          acceptBtn.classList.add('btn-success');
        }
      }

      mapInicializado = false;
      if (marker && map) {
        try { 
          map.removeLayer(marker);
          map.off();
          map.remove();
          map = null;
        } catch(e) { console.warn('Error al remover marcador previo:', e); }
      }

      marker = null;

    modalMapa._initialCoords = (!isNaN(lat) && !isNaN(lng))
      ? { lat, lng }
      : null;
      setTimeout(() => modalMapa.show(), 80);
    });
  });

  document.getElementById('modalSeleccionarMapa').addEventListener('hidden.bs.modal', () => {
    if (modalMapa._parentCustomModal) {
      modalMapa._parentCustomModal.style.display = 'flex';
      modalMapa._parentCustomModal = null;
    }
  });

  document.getElementById('modalSeleccionarMapa')
    .addEventListener('shown.bs.modal', () => {

      if (marker && map) {
        try { map.removeLayer(marker); mapInicializado = false;  } catch (e) { console.warn('Error al remover marcador al abrir selector:', e); }
      }
      marker = null;

      if (!mapInicializado) {

        if (modalMapa._mode === 'agregar') {
          obtenerUbicacionActual().then(({lat, lng, error}) => {

            console.log('Ubicación actual obtenida:', lat, lng, 'Error:', error);
            let centerLat = lat;
            let centerLng = lng;

            if (map) {
              map.off();
              map.remove();
              map = null;
            }

            map = L.map('mapSelector').setView([centerLat, centerLng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

            colocarMarcador(centerLat, centerLng);
           

          setTimeout(() => map.invalidateSize(), 200);
            // Mostrar mensaje si hubo error al obtener la ubicación
            const statusEl = document.getElementById('mapStatus');
            if (statusEl) {
              if (error) {
                statusEl.textContent = 'Estado: ' + error;
                statusEl.classList.remove('text-muted');
                statusEl.classList.add('text-danger');
              } else {
                statusEl.textContent = 'Estado: ubicación obtenida.';
                statusEl.classList.remove('text-danger');
                statusEl.classList.add('text-success');
              }
            }

            map.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));
            mapInicializado = true;
          });
        } else {

        let centerLat = 19.432608;
        let centerLng = -99.133209;

        if (modalMapa._initialCoords) {
          centerLat = modalMapa._initialCoords.lat;
          centerLng = modalMapa._initialCoords.lng;
        }

          map = L.map('mapSelector').setView([centerLat, centerLng], 13);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
          map.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));
          
          if (modalMapa._initialCoords) {
            colocarMarcador(centerLat, centerLng);
          }

          setTimeout(() => map.invalidateSize(), 200);

          mapInicializado = true;
        }

      } else {
        // Si el mapa ya estaba inicializado y abrimos para un elemento concreto, centramos en sus coordenadas
        if (inputLatDestino) {
          const maybeLat = parseFloat(inputLatDestino);
          const maybeLng = parseFloat(inputLngDestino);
          if (!isNaN(maybeLat) && !isNaN(maybeLng)) {
            if (marker) marker.setLatLng([maybeLat, maybeLng]); else colocarMarcador(maybeLat, maybeLng);
            try { map.setView([maybeLat, maybeLng], 15); } catch(e) { console.warn('Error al centrar mapa:', e); }
          } else if (document.getElementById(inputLatDestino) && document.getElementById(inputLatDestino).value) {
            const vlat = parseFloat(document.getElementById(inputLatDestino).value);
            const vlng = parseFloat(document.getElementById(inputLngDestino).value);
            if (!isNaN(vlat) && !isNaN(vlng)) {
              if (marker) marker.setLatLng([vlat, vlng]); else colocarMarcador(vlat, vlng);
              try { map.setView([vlat, vlng], 13); } catch(e) { console.warn('Error al centrar mapa:', e); }
            }
          }
        }
      }
    });

  // ❗ Limpiar marcadores existentes
  function resetMarcadores() {
    try {
      if (marker && map) {
        map.removeLayer(marker);
      }
    } catch (e) {
      console.warn('Error reseteando marcador:', e);
    } finally {
      marker = null;
    }
  }

  // 📌 Colocar marcador

  function colocarMarcador(lat, lng) {
    const selectedLat = Number(lat).toFixed(6);
    const selectedLng = Number(lng).toFixed(6);

    const popupContent = `
      📍 <strong>Latitud:</strong> ${selectedLat}<br>
      📍 <strong>Longitud:</strong> ${selectedLng}
    `;

    if (!marker) {
      marker = L.marker([selectedLat, selectedLng], {
        draggable: true,
        autoPan: true
      }).addTo(map);

      marker.on('dragend', () => {
        const pos = marker.getLatLng();
        colocarMarcador(pos.lat, pos.lng);
      });
    } else {
      marker.setLatLng([selectedLat, selectedLng]);
    }

     marker.bindPopup(popupContent).openPopup();


    if (inputLatDestino && document.getElementById(inputLatDestino)) {
      document.getElementById(inputLatDestino).value = selectedLat;
    }
    if (inputLngDestino && document.getElementById(inputLngDestino)) {
      document.getElementById(inputLngDestino).value = selectedLng;
    }
  }

  // ✅ Aceptar
  document.getElementById('btnAceptarUbicacion').addEventListener('click', () => {
    document.getElementById('btnAceptarUbicacion').blur();
    modalMapa.hide();
  });

  // Solicitud directa de permiso (reintento) y ayuda si está bloqueado
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
          } catch (e) {}
          resolve({ lat: fallback.lat, lng: fallback.lng, error: msg });
        },
        { enableHighAccuracy: true, timeout: 100, maximumAge: 0 }
      );
    });
  }

  document.getElementById('btnUsarMiUbicacion').addEventListener('click', () => {
    const statusEl = document.getElementById('mapStatus');
    const helpEl = document.getElementById('mapHelp');
    statusEl.textContent = 'Estado: solicitando ubicación...';
    statusEl.classList.remove('text-danger','text-success');
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
        statusEl.textContent = 'Estado: ubicación obtenida.';
        statusEl.classList.remove('text-danger');
        statusEl.classList.add('text-success');
      }

      // Si el mapa aún no está inicializado (usuario hizo clic rápido), inicializarlo aquí
      if (!mapInicializado) {
        try {
          map = L.map('mapSelector').setView([lat, lng], 13);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
          }).addTo(map);
          map.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));
          mapInicializado = true;
        } catch (err) {
          console.warn('Error al inicializar mapa desde Usar mi ubicación:', err);
        }
      } else {
        try { map.setView([lat, lng], 13); } catch(e) { console.warn('Error al centrar mapa:', e); }
      }

      try { colocarMarcador(lat, lng); } catch(e) { console.warn('Error al colocar marcador:', e); }
    });
  });

  // ❌ Cancelar
  document.querySelectorAll('.cerrarMapa').forEach(btn => {
    btn.addEventListener('click', () => modalMapa.hide());
  });

  let mapInitialized = false;
  let selectedMarker = null;
  let lastSearchController = null;


  const modalMapaArcos = document.getElementById('modalMapaArcos');
  let markersGroup = null; // will hold arco markers
  let markerById = {};      // map arco id -> marker

  modalMapaArcos.addEventListener('show.bs.modal', function (event) {

    const trigger = event.relatedTarget;
    if (!trigger) return;

    const lat = parseFloat(trigger.getAttribute('data-lat'));
    const lng = parseFloat(trigger.getAttribute('data-lng'));
    const nombre = trigger.getAttribute('data-nombre');
    const ubic = trigger.getAttribute('data-ubic');
    const fallas = trigger.getAttribute('data-fallas');

    // Obtener id de la fila (ubicación)
    let ubicId = trigger.getAttribute('data-id');
    if (!ubicId) {
      const tr = trigger.closest('tr');
      ubicId = tr ? tr.getAttribute('data-id') : null;
    }

    // Inicializar mapa una sola vez
    if (!mapInitialized) {
      map = L.map('map').setView([lat || 19.432608, lng || -99.133209], 15);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
      }).addTo(map);

      mapInitialized = true;
    }

    // Recalcular tamaño
    setTimeout(() => {
      map.invalidateSize();
    }, 200);

    // Limpiar marcador anterior del centro (si existe)
    if (selectedMarker) {
      map.removeLayer(selectedMarker);
      selectedMarker = null;
    }

    // Limpiar grupo de marcadores de arcos
    if (!markersGroup) {
      markersGroup = L.layerGroup().addTo(map);
    }
    markersGroup.clearLayers();
    markerById = {};

    // Elementos UI de la lista
    const listaEl = document.getElementById('listaArcos');
    const listaMsg = document.getElementById('listaMsg');
    if (listaEl) listaEl.innerHTML = '';
    if (listaMsg) listaMsg.textContent = '';

    // Mostrar marcador de la ubicación (ciudad) solo como referencia
    if (!isNaN(lat) && !isNaN(lng)) {
      selectedMarker = L.marker([lat, lng], {opacity: 0.9}).addTo(map);
      const hasUbic = ubic !== null && ubic !== undefined && String(ubic).trim() !== '';
      const hasFallas = fallas !== null && fallas !== undefined && String(fallas).trim() !== '';
      const coordHtml = hasUbic ? '<small class="text-success">📍 </small>' : '';
      const fallasHtml = hasFallas ? `\n<br>⚠️ <small class="text-muted">Fallas: ${fallas}</small>` : '';

      const popupContent = (hasUbic || hasFallas)
        ? `<strong>${nombre}</strong><br>${coordHtml}${hasFallas ? fallasHtml : ''}`
        : `<strong>${nombre}</strong>`;

      selectedMarker.bindPopup(popupContent);
    }

    // Si tenemos un id de ubicación, pedir los arcos vía AJAX
    if (ubicId) {
      fetch(`../controllers/arcos_controller.php?action=get_arcos&ubicacion_id=${encodeURIComponent(ubicId)}`)
        .then(r => r.json())
        .then(arcos => {
          const bounds = [];

          // Deduplicate by id (defensive) to avoid rendering duplicates in the UI
          const mapById = new Map();
          arcos.forEach(a => mapById.set(String(a.id), a));
          const uniqueArcos = Array.from(mapById.values());
          if (uniqueArcos.length !== arcos.length) console.warn('get_arcos: duplicates removed', arcos.length, '->', uniqueArcos.length);

          // Populate list (all arcos) and markers for those with coords
          if (listaEl) {
            uniqueArcos.forEach(a => {
              // skip if an element with same id already exists (extra defense)
              if (listaEl.querySelector(`[data-id="${a.id}"]`)) return;

              const item = document.createElement('div');
              item.className = 'arco-item';
              item.dataset.id = a.id;

              const hasCoords = !isNaN(parseFloat(a.lat)) && !isNaN(parseFloat(a.lng));

              const fallasText = (a.fallas === null || a.fallas === undefined || String(a.fallas).trim() === '') ? 'Sin fallas' : `Fallas: ${a.fallas}`;
              const coordHtml = hasCoords ? '<small class="text-success">📍</small>' : '<small class="text-muted">Sin ubicación</small>';

              item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong> Arco ${a.nombre}</strong><br>
                    <small class="text-muted">${fallasText}</small>
                  </div>
                  <div class="text-end">
                    ${coordHtml}
                  </div>
                </div>
              `;

              // Click behavior
              if (hasCoords) {
                item.style.cursor = 'pointer';
                item.addEventListener('click', () => {
                  const marker = markerById[a.id];
                  if (marker) {
                    map.setView(marker.getLatLng(), 16);
                    marker.openPopup();
                  }
                });
              } else {
                item.classList.add('disabled');
                item.title = 'Este arco no tiene coordenadas registradas';
              }

              listaEl.appendChild(item);
            });

            if (uniqueArcos.length === 0) {
              listaMsg.textContent = 'No hay arcos registrados en esta ubicación.';
            }
          }

          uniqueArcos.forEach(a => {
            const alat = parseFloat(a.lat);
            const alng = parseFloat(a.lng);
            if (!isNaN(alat) && !isNaN(alng)) {
              const m = L.marker([alat, alng]).addTo(markersGroup);
              const arcFallas = (a.fallas === null || a.fallas === undefined || String(a.fallas).trim() === '') ? 'Sin fallas' : `Fallas: ${a.fallas}`;
              m.bindPopup(`\n                <strong> Arco ${a.nombre}</strong><br>⚠️ <small class="text-muted">${arcFallas}</small>\n              `);
              markerById[a.id] = m;
              bounds.push([alat, alng]);
            }
          });

          // Ajustar vista
          if (bounds.length > 0) {
            try {
             setTimeout(() => {
              map.invalidateSize();
              map.fitBounds(bounds, { padding: [40, 40] });
              }, 250);
            } catch (e) {
              if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 14);
            }
          } else {
            if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 14);
            if (selectedMarker) selectedMarker.openPopup();
          }
        })
        .catch(err => {
          console.error('Error al obtener arcos:', err);
          if (listaMsg) listaMsg.textContent = 'Error al obtener arcos.';
          if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 14);
        });

    } else {
      // Sin id de ubicación, solo centramos en la ubicación
      if (!isNaN(lat) && !isNaN(lng)) map.setView([lat, lng], 14);
      if (selectedMarker) selectedMarker.openPopup();
    }

  });