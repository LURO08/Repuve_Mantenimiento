document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modalTecnico");
  const form = document.getElementById("formTecnico");
  const action = document.getElementById("tecnicoAction");
  const id = document.getElementById("tecnicoId");
  const nombre = document.getElementById("tecnicoNombre");
  const puesto = document.getElementById("tecnicoPuesto");
  const telefono = document.getElementById("tecnicoTelefono");
  const activo = document.getElementById("tecnicoActivo");
  const activoGroup = document.getElementById("tecnicoActivoGroup");
  const titulo = document.getElementById("modalTecnicoTitulo");
  const buscar = document.getElementById("buscarTecnico");
  const tabla = document.getElementById("tecnicosTable");
  const paginacion = document.getElementById("pagination-Tecnicos");
  const notif = document.getElementById("notifTecnicos");
  const estado = {
    page: 1,
    limit: 8
  };

  function filasFiltrables() {
    return Array.from(tabla?.querySelectorAll("tbody tr") || []).filter(row => !row.querySelector("td[colspan]"));
  }

  function renderTecnicos() {
    const rows = filasFiltrables();
    const visibles = rows.filter(row => row.dataset.visible !== "0");
    const total = visibles.length;
    const pages = Math.max(1, Math.ceil(total / estado.limit));

    if (estado.page > pages) estado.page = pages;
    if (estado.page < 1) estado.page = 1;

    rows.forEach(row => row.style.display = "none");

    const start = (estado.page - 1) * estado.limit;
    const end = start + estado.limit;
    visibles.slice(start, end).forEach(row => row.style.display = "");

    if (!paginacion) return;

    if (total <= estado.limit) {
      paginacion.innerHTML = total ? `<span class="small text-muted">Mostrando ${total} tecnico(s)</span>` : "";
      return;
    }

    const shownStart = start + 1;
    const shownEnd = Math.min(end, total);
    let buttons = `
      <span class="small text-muted me-2">Mostrando ${shownStart}-${shownEnd} de ${total}</span>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-success" data-page="${estado.page - 1}" ${estado.page === 1 ? "disabled" : ""}>Anterior</button>
    `;

    for (let i = 1; i <= pages; i++) {
      buttons += `<button type="button" class="btn ${i === estado.page ? "btn-success" : "btn-outline-success"}" data-page="${i}">${i}</button>`;
    }

    buttons += `
        <button type="button" class="btn btn-outline-success" data-page="${estado.page + 1}" ${estado.page === pages ? "disabled" : ""}>Siguiente</button>
      </div>
    `;

    paginacion.innerHTML = buttons;
  }

  if (notif) {
    setTimeout(() => {
      notif.style.opacity = "0";
      setTimeout(() => notif.remove(), 350);
    }, 3000);
  }

  // Signature Elements
  const canvas = document.getElementById("canvasFirma");
  const ctx = canvas?.getContext("2d");
  const btnLimpiar = document.getElementById("btnLimpiarCanvas");
  const firmaCanvasInput = document.getElementById("firmaCanvasInput");
  const eliminarFirmaInput = document.getElementById("eliminarFirmaInput");
  const firmaPreviewContainer = document.getElementById("firmaPreviewContainer");
  const firmaPreviewImg = document.getElementById("firmaPreviewImg");
  const btnEliminarFirma = document.getElementById("btnEliminarFirma");
  const firmaArchivo = document.getElementById("firmaArchivo");
  const tabCanvasBtn = document.getElementById("tab-canvas-btn");

  let isDrawing = false;
  let hasDrawn = false;

  function initCanvas() {
    if (!canvas || !ctx) return;
    ctx.lineWidth = 2.5;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
    ctx.strokeStyle = "#002855";
  }

  function clearCanvas() {
    if (!canvas || !ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasDrawn = false;
    if (firmaCanvasInput) firmaCanvasInput.value = "";
  }

  function getCanvasPos(e) {
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    let clientX = e.clientX;
    let clientY = e.clientY;

    if (e.touches && e.touches.length > 0) {
      clientX = e.touches[0].clientX;
      clientY = e.touches[0].clientY;
    }

    return {
      x: (clientX - rect.left) * scaleX,
      y: (clientY - rect.top) * scaleY
    };
  }

  function startDrawing(e) {
    isDrawing = true;
    const pos = getCanvasPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
    if (e.cancelable && e.type.startsWith("touch")) e.preventDefault();
  }

  function draw(e) {
    if (!isDrawing || !ctx) return;
    const pos = getCanvasPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    hasDrawn = true;
    if (e.cancelable && e.type.startsWith("touch")) e.preventDefault();
  }

  function stopDrawing() {
    if (!isDrawing) return;
    isDrawing = false;
    ctx.closePath();
  }

  if (canvas) {
    initCanvas();
    canvas.addEventListener("mousedown", startDrawing);
    canvas.addEventListener("mousemove", draw);
    canvas.addEventListener("mouseup", stopDrawing);
    canvas.addEventListener("mouseleave", stopDrawing);

    canvas.addEventListener("touchstart", startDrawing, { passive: false });
    canvas.addEventListener("touchmove", draw, { passive: false });
    canvas.addEventListener("touchend", stopDrawing);
  }

  btnLimpiar?.addEventListener("click", () => {
    clearCanvas();
  });

  btnEliminarFirma?.addEventListener("click", () => {
    if (eliminarFirmaInput) eliminarFirmaInput.value = "1";
    if (firmaPreviewContainer) firmaPreviewContainer.classList.add("d-none");
    if (firmaPreviewImg) firmaPreviewImg.src = "";
  });

  form?.addEventListener("submit", () => {
    if (hasDrawn && canvas && firmaCanvasInput) {
      firmaCanvasInput.value = canvas.toDataURL("image/png");
    }
  });

  modal?.addEventListener("show.bs.modal", event => {
    const btn = event.relatedTarget?.closest(".editarTecnicoBtn");

    form?.reset();
    clearCanvas();
    if (firmaCanvasInput) firmaCanvasInput.value = "";
    if (eliminarFirmaInput) eliminarFirmaInput.value = "0";
    if (firmaArchivo) firmaArchivo.value = "";
    if (firmaPreviewContainer) firmaPreviewContainer.classList.add("d-none");
    if (firmaPreviewImg) firmaPreviewImg.src = "";

    id.value = "";
    action.value = "add";
    activo.checked = true;
    activoGroup?.classList.add("d-none");
    titulo.innerHTML = '<i class="bi bi-person-badge"></i> Agregar tecnico';

    if (tabCanvasBtn && window.bootstrap?.Tab) {
      const tab = new bootstrap.Tab(tabCanvasBtn);
      tab.show();
    }

    if (!btn) return;

    action.value = "update";
    id.value = btn.dataset.id || "";
    nombre.value = btn.dataset.nombre || "";
    puesto.value = btn.dataset.puesto || "";
    telefono.value = btn.dataset.telefono || "";
    activo.checked = btn.dataset.activo === "1";
    activoGroup?.classList.remove("d-none");
    titulo.innerHTML = '<i class="bi bi-pencil-square"></i> Editar tecnico';

    const firmaPath = btn.dataset.firma || "";
    if (firmaPath && firmaPreviewContainer && firmaPreviewImg) {
      firmaPreviewImg.src = "../" + firmaPath;
      firmaPreviewContainer.classList.remove("d-none");
    }
  });

  buscar?.addEventListener("input", () => {
    const q = buscar.value.trim().toLowerCase();
    filasFiltrables().forEach(row => {
      row.dataset.visible = row.innerText.toLowerCase().includes(q) ? "1" : "0";
    });
    estado.page = 1;
    renderTecnicos();
  });

  paginacion?.addEventListener("click", event => {
    const btn = event.target.closest("[data-page]");
    if (!btn || btn.disabled) return;
    estado.page = Number(btn.dataset.page || 1);
    renderTecnicos();
  });

  filasFiltrables().forEach(row => {
    row.dataset.visible = "1";
  });
  renderTecnicos();

  if (new URLSearchParams(window.location.search).get("tab") === "tecnicos") {
    document.getElementById("seccionTecnicos")?.scrollIntoView({ behavior: "smooth", block: "start" });
  }
});
