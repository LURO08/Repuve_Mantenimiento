
  const config = {
    ArcosTable: {
      page: 1,
      limit: 4
    }
  };

  // RENDER PAGINACIÓN
  function renderPagination(tableId) {
    const state = config[tableId];
    const rows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`))
      .filter(r => r.dataset.visible !== "0");

    const total = rows.length;
    const totalPages = Math.ceil(total / state.limit);
    const start = (state.page - 1) * state.limit;
    const end = start + state.limit;

    // Mostrar sólo la página activa
    rows.forEach((r, i) => {
      r.style.display = i >= start && i < end ? "" : "none";
    });

    // Contenedor correcto
    const name = tableId.replace("Table", "");
    const pagContainer = document.getElementById(`pagination-${name}`);
    pagContainer.innerHTML = "";

    if (totalPages <= 1) return;

    let html = `
        <nav>
            <ul class="pagination pagination-sm">
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
            </ul>
        </nav>
    `;

    pagContainer.innerHTML = html;
  }

  function changePage(tableId, page) {
    config[tableId].page = page;
    renderPagination(tableId);
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("tbody tr").forEach(r => r.dataset.visible = "1");
    renderPagination("ArcosTable");
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

  document.querySelectorAll('.verMaterialesBtn').forEach(btn => {
    btn.addEventListener('click', function() {

      let materiales = JSON.parse(this.dataset.materiales);
      let contenedor = document.getElementById("contenedorMateriales");

      if (!materiales || materiales.length === 0) {
        contenedor.innerHTML = `
        <div class="text-center p-3" >
          <span class="badge bg-warning text-dark">
            <i class="bi bi-exclamation-circle"></i> Sin materiales
          </span>
        </div>
      `;
        return;
      }

      let html = '<div class="d-flex flex-column gap-2" style=" overflow-x: auto; width: 100%; min-width: 30px; " >';

      materiales.forEach(m => {

        // Si NO hay foto, poner un recuadro "Sin foto"
        let imagenHtml = "";

        if (!m.foto || m.foto === "null" || m.foto.trim() === "") {
          imagenHtml = `
          <div class="d-flex align-items-center justify-content-center bg-secondary text-white"
               style="width:40px; height:40px; border-radius:6px; font-size:10px;">
            Sin<br>foto
          </div>
        `;
        } else {
          imagenHtml = `
          <img 
            src="../uploads/materiales/${m.foto}" 
            alt="${m.material}"
            style="width:40px; height:40px; object-fit:cover; border-radius:6px;"
          >
        `;
        }

        let medida2 = "";
        if (m.medida === 'm') {
          medida2 = 'metros';
        } else if (m.medida === 'pz') {
          medida2 = 'piezas';
        } else {
          medida2 = m.medida;
        }

        html += `
        <div class="d-flex align-items-center gap-2 p-2 rounded shadow-sm bg-light border " style="width: 100%;">

          <!-- Foto o "sin foto" -->
          <div class="d-flex align-items-center justify-content-center">
            ${imagenHtml}
          </div>

          <!-- Nombre -->
          <div class="flex-grow-1">
            <strong>${m.material}</strong>
          </div>

          <!-- Cantidad -->
          <div class="text-success fw-bold text-end  " >
            ${m.cantidad} ${medida2}
          </div>

        </div>
      `;
      });

      html += '</div>';
      contenedor.innerHTML = html;
    });
  });

  document.querySelectorAll(".material-select").forEach(select => {
    select.addEventListener("change", function() {

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

  document.addEventListener("DOMContentLoaded", () => {
    const materialContainer = document.getElementById("materialesContainer");
    const addMaterialBtn = document.getElementById("addMaterial");
    addMaterialBtn.addEventListener("click", () => {
      const newRow = materialContainer.firstElementChild.cloneNode(true);
      newRow.querySelectorAll("input, select").forEach(el => el.value = "");
      materialContainer.appendChild(newRow);
    });

    document.addEventListener("change", function(e) {
      if (e.target.classList.contains("material-select")) {
        let medida = e.target.selectedOptions[0].dataset.medida || "";
        let medida2 = "";
        if (medida === "m") {
          medida2 = "metros";
        } else if (medida === "pz") {
          medida2 = "piezas";
        } else {
          medida2 = medida;
        }
        e.target.closest(".material-row")
          .querySelector(".medida-input").value = medida2;
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

    function crearFilaMaterial(lista, seleccionado = "", cantidad = "", medida = "", serie ="") {
      const div = document.createElement("div");
      div.className = "material-row p-2 mb-2 border rounded shadow-sm bg-light";

      div.innerHTML = `
        <div class="row g-2 align-items-center">

          <!-- Selector de material -->
          <div class="col-md-5 col-15">
            <label class="form-label fw-semibold mb-1">Material</label>
            <select name="material_id[]" class="form-select material-select" required>
              <option value="">Seleccione material...</option>
              ${lista.map(m =>
                `<option value="${m.id}" data-medida="${m.medida}" ${m.id == seleccionado ? "selected" : ""}>
                  ${m.nombre}
                </option>`).join("")}
            </select>
          </div>


          <div class="col-md-3">
            <label class="form-label fw-semibold mb-1">Serie</label>
            <div class="d-flex">
              <input type="text" name="serie[]" class="form-control serie-input" 
                     value="${serie}" placeholder="Ingrese la serie">
            </div>
          </div>

          <!-- Cantidad + medida -->
          <div class="col-md-3">
            <label class="form-label fw-semibold mb-1">Cantidad</label>
            <div class="d-flex">
              <input type="number" name="cantidad[]" class="text-center form-control cantidad-input" 
                    min="1" value="${cantidad}" placeholder="0" required>

              <a class="text-center ms-2" style="align-content:center; color: gray; text-decoration: none;"
              > ${!medida ? '' : (medida === 'm' ? 'metros' : (medida === 'pz' ? 'piezas' : medida))}</a> 
            </div>
          </div>

          <!-- Botón eliminar -->
          <div class="col-md-1 d-grid">
            <label class="form-label opacity-0">.</label>
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

      container.addEventListener("change", function(e) {
        if (e.target.classList.contains("material-select")) {
          let medida = e.target.selectedOptions[0].dataset.medida || "";
          let medida2 = medida === "m" ? "metros" : medida === "pz" ? "piezas" : medida;
          e.target.closest(".material-row")
            .querySelector(".medida-input").value = medida2;
        }
      });
    }
  });


  // CODIGO PARA MOSTRAR ARCO EN MAPA
  let map;
  let mapInitialized = false;
  let selectedMarker = null;
  let lastSearchController = null;


  const modalMapaArcos = document.getElementById('modalMapaArcos');

  modalMapaArcos.addEventListener('show.bs.modal', function (event) {

    const trigger = event.relatedTarget;
    if (!trigger) return;

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
  

const modalContainer = document.getElementById("materialesContainerModal");
const realContainer  = document.getElementById("materialesContainer");

// document.getElementById("addMaterialModal").onclick = () => {
//   const clone = template.content.cloneNode(true);

//   clone.querySelector(".remove-material").onclick = e => {
//     e.target.closest(".material-row").remove();
//   };

//   modalContainer.appendChild(clone);
// };

document.getElementById("cerrarMaterial").addEventListener("click", () => {

   bootstrap.Modal.getInstance(
    document.getElementById("modalRegistrarMaterial")
  ).hide();
  // Volver a abrir modal Arco
    const modalArco = new bootstrap.Modal(
    document.getElementById("modalAgregarArco")
  );
  // Volver a abrir modal Arco
  setTimeout(() => {
     modalArco.show();
  }, 300);
});

document.addEventListener("change", e => {

  // Checkbox serie
  if (e.target.classList.contains("tiene-serie")) {
    const row = e.target.closest(".row");
    const wrapper = row.querySelector(".serie-wrapper");
    const input = wrapper.querySelector("input");

    if (e.target.checked) {
      wrapper.classList.remove("d-none");
      input.required = true;
    } else {
      wrapper.classList.add("d-none");
      input.value = "";
      input.required = false;
    }
  }

  // Medida
  if (e.target.classList.contains("material-select")) {
    const row = e.target.closest(".row");
    const medida = e.target.selectedOptions[0].dataset.medida || "";
    row.querySelector(".medida-input").innerHTML =
      medida === "m" ? "metros" : medida === "pz" ? "piezas" : medida;

      const wrapperCantidad = row.querySelector(".cantidad-wrapper");

      if (medida === "m") {
          wrapperCantidad.classList.remove("d-none");
      }
       if (medida === "pz") {
          wrapperCantidad.classList.add("d-none");
      }
  }
});



document.getElementById("guardarMateriales").addEventListener("click", () => {

  modalContainer.querySelectorAll(".row").forEach(row => {

    const select   = row.querySelector('select[name="material_id[]"]');
    const serieInp = row.querySelector('input[name="material_serie[]"]');
    const cantInp  = row.querySelector('input[name="material_cantidad[]"]');

    if (!select || !select.value || !cantInp.value) return;

    const materialId   = select.value;
    const materialText = select.selectedOptions[0].text;
    const medida       = select.selectedOptions[0].dataset.medida;
    const tieneSerie = row.querySelector(".tiene-serie").checked;
const serie = tieneSerie
  ? row.querySelector('input[name="material_serie[]"]').value
  : "";

    const cantidad     = cantInp.value;

    // 🧠 OPCIONAL: evitar duplicados
    const existe = [...realContainer.querySelectorAll('input[name="material_id[]"]')]
      .some(i => i.value === materialId && 
                 i.closest('.material-row')
                  .querySelector('input[name="serie[]"]').value === serie);

    if (existe) return;

    // ➕ CREAR FILA
    const div = document.createElement("div");
    div.className = "row align-items-center border-top py-2 px-2 material-row";

    div.innerHTML = `
      <div class="col-4 fw-semibold">
        ${materialText}
        <input type="hidden" name="material_id[]" value="${materialId}">
        <input type="hidden" name="medida[]" value="${medida}">
      </div>

      <div class="col-3 text-muted">
        ${serie || 'Sin serie'}
        <input type="hidden" name="serie[]" value="${serie}">
      </div>

      <div class="col-3 text-success fw-bold">
        ${cantidad} ${medida === 'm' ? 'metros' : 'piezas'}
        <input type="hidden" name="cantidad[]" value="${cantidad}">
      </div>

      <div class="col-2 text-center">
        <button type="button" class="btn btn-sm btn-danger remove-material">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    `;

    // ✅ AQUÍ está la clave
    realContainer.appendChild(div);
  });

  // Resumen
  document.getElementById("resumenMateriales").innerHTML =
    `✔ ${document.querySelectorAll(".material-row").length} materiales agregados`;

  // Cerrar modal Material
  bootstrap.Modal.getInstance(
    document.getElementById("modalRegistrarMaterial")
  ).hide();

  // Volver a abrir modal Arco
   const modalArco = new bootstrap.Modal(
    document.getElementById("modalAgregarArco")
  );

  // Volver a abrir modal Arco
  setTimeout(() => {
     modalArco.show();
  }, 300);
});


document.getElementById("materialesContainer")
  .addEventListener("click", e => {
    if (e.target.closest(".remove-material")) {
      e.target.closest(".material-row").remove();

      document.getElementById("resumenMateriales").innerHTML =
        `✔ ${document.querySelectorAll(".material-row").length} materiales agregados`;
    }
});






