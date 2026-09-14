/**
 * SMART CARD PICKER JS - REPUVE MANTENIMIENTO
 * Componente global de selector predictivo con opciones en 2 columnas y tarjeta de selección
 */

(function (window, document) {
  "use strict";

  function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function normalizeStr(str) {
    return (str || "")
      .toString()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  function deduceIcon(select) {
    const id = (select.id || "").toLowerCase();
    const name = (select.name || "").toLowerCase();
    const combined = `${id} ${name}`;

    if (combined.includes("ubicacion") || combined.includes("municipio") || combined.includes("ciudad")) {
      return "bi-geo-alt-fill";
    }
    if (combined.includes("arco") || combined.includes("objetivo")) {
      return "bi-bounding-box-circles";
    }
    if (combined.includes("infra") || combined.includes("sitio") || combined.includes("nodo") || combined.includes("poste") || combined.includes("enlace") || combined.includes("torre")) {
      return "bi-broadcast-pin";
    }
    if (combined.includes("tecnico")) {
      return "bi-person-badge-fill";
    }
    if (combined.includes("mantenimiento") || combined.includes("tipo_mantenimiento")) {
      return "bi-tools";
    }
    if (combined.includes("motivo") || combined.includes("baja")) {
      return "bi-exclamation-triangle-fill";
    }
    if (combined.includes("role") || combined.includes("rol")) {
      return "bi-shield-lock-fill";
    }
    if (combined.includes("medida") || combined.includes("unidad")) {
      return "bi-rulers";
    }
    if (combined.includes("formato") || combined.includes("reporte")) {
      return "bi-file-earmark-text";
    }
    return "bi-list-ul";
  }

  function deducePlaceholder(select) {
    if (select.dataset.placeholder) return select.dataset.placeholder;

    // Si tiene un primer option con value vacio ("Seleccione...")
    const firstOpt = select.querySelector("option[value='']");
    if (firstOpt && firstOpt.text && firstOpt.text.trim().length > 0 && !firstOpt.text.toLowerCase().includes("cargando")) {
      return firstOpt.text.trim();
    }

    const id = (select.id || "").toLowerCase();
    const name = (select.name || "").toLowerCase();
    const combined = `${id} ${name}`;

    if (combined.includes("ubicacion") || combined.includes("municipio")) {
      return "Buscar ubicación o municipio...";
    }
    if (combined.includes("arco")) {
      return "Buscar arco...";
    }
    if (combined.includes("infra") || combined.includes("sitio") || combined.includes("poste") || combined.includes("enlace")) {
      return "Buscar puente, sitio o enlace...";
    }
    if (combined.includes("tecnico")) {
      return "Buscar técnico responsable...";
    }
    if (combined.includes("mantenimiento")) {
      return "Seleccionar tipo de mantenimiento...";
    }
    if (combined.includes("motivo")) {
      return "Seleccionar motivo...";
    }
    if (combined.includes("role") || combined.includes("rol")) {
      return "Seleccionar rol de usuario...";
    }
    if (combined.includes("medida")) {
      return "Seleccionar unidad de medida...";
    }
    return "Escribe para buscar...";
  }

  class SmartCardPicker {
    constructor(selectElement, options = {}) {
      this.select = typeof selectElement === "string" ? document.getElementById(selectElement) : selectElement;
      if (!this.select || this.select.tagName !== "SELECT") return null;

      if (this.select._cardPickerInstance) {
        this.select._cardPickerInstance.sync();
        return this.select._cardPickerInstance;
      }

      this.options = Object.assign({
        placeholder: deducePlaceholder(this.select),
        icon: this.select.getAttribute("data-icon") || deduceIcon(this.select),
        isOptional: options.isOptional !== undefined ? options.isOptional : (!this.select.required && !this.select.hasAttribute("required"))
      }, options);

      this.select._cardPickerInstance = this;
      this.select._searchableInstance = this; // Compatibilidad

      this._isSyncing = false;
      this.init();
    }

    init() {
      // 1. Ocultar de forma accesible el select nativo
      this.select.classList.add("searchable-select-native");
      this.select.tabIndex = -1;
      this.select.setAttribute("aria-hidden", "true");

      // 2. Crear Wrapper
      this.wrapper = document.createElement("div");
      this.wrapper.className = "smart-picker-wrapper";
      if (this.select.id) {
        this.wrapper.setAttribute("data-for-select", this.select.id);
      }

      // 3. Contenedor de Búsqueda
      this.searchContainer = document.createElement("div");
      this.searchContainer.className = "smart-picker-search-container";
      this.searchContainer.innerHTML = `
        <div class="smart-picker-search-box">
          <i class="bi ${this.options.icon} smart-picker-search-icon"></i>
          <input type="search" class="form-control smart-picker-input" placeholder="${escapeHtml(this.options.placeholder)}" autocomplete="off">
        </div>
        <div class="smart-picker-dropdown shadow-lg">
          <div class="smart-picker-options-list"></div>
        </div>
      `;

      // 4. Contenedor de Tarjeta Seleccionada
      this.selectedCard = document.createElement("div");
      this.selectedCard.className = "smart-picker-selected-card shadow-xs d-none";

      this.wrapper.appendChild(this.searchContainer);
      this.wrapper.appendChild(this.selectedCard);
      this.select.parentNode.insertBefore(this.wrapper, this.select.nextSibling);

      this.searchInput = this.searchContainer.querySelector(".smart-picker-input");
      this.dropdown = this.searchContainer.querySelector(".smart-picker-dropdown");
      this.optionsList = this.searchContainer.querySelector(".smart-picker-options-list");

      this.bindEvents();
      this.setupMutationObserver();
      this.sync();
    }

    bindEvents() {
      // Apertura de dropdown al enfocar input
      this.searchInput.addEventListener("focus", () => {
        this.openDropdown();
      });

      // Filtro en tiempo real
      this.searchInput.addEventListener("input", () => {
        this.filter(this.searchInput.value);
        this.openDropdown();
      });

      // Manejo de teclado
      this.searchInput.addEventListener("keydown", e => {
        if (e.key === "Escape") {
          this.closeDropdown();
          if (this.hasSelectedValue()) {
            this.renderSelectedMode();
          }
        } else if (e.key === "ArrowDown") {
          e.preventDefault();
          const first = this.optionsList.querySelector(".smart-picker-option:not([style*='display: none'])");
          first?.focus();
        }
      });

      // Selección de opción por clic
      this.optionsList.addEventListener("click", e => {
        const optionEl = e.target.closest(".smart-picker-option");
        if (optionEl && optionEl.dataset.value !== undefined) {
          this.selectValue(optionEl.dataset.value);
        }
      });

      // Navegación por teclado en las opciones
      this.optionsList.addEventListener("keydown", e => {
        const currentOption = e.target.closest(".smart-picker-option");
        if (!currentOption) return;

        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          this.selectValue(currentOption.dataset.value);
        } else if (e.key === "ArrowDown") {
          e.preventDefault();
          const next = currentOption.nextElementSibling?.classList.contains("smart-picker-option")
            ? currentOption.nextElementSibling
            : currentOption.closest(".smart-picker-group")?.nextElementSibling?.querySelector(".smart-picker-option");
          next?.focus();
        } else if (e.key === "ArrowUp") {
          e.preventDefault();
          const prev = currentOption.previousElementSibling?.classList.contains("smart-picker-option")
            ? currentOption.previousElementSibling
            : this.searchInput;
          prev?.focus();
        } else if (e.key === "Escape") {
          this.closeDropdown();
          this.searchInput.focus();
        }
      });

      // Clics en la tarjeta seleccionada (Cambiar / Limpiar)
      this.selectedCard.addEventListener("click", e => {
        if (e.target.closest(".smart-picker-change-btn")) {
          e.preventDefault();
          this.renderSearchMode();
          this.searchInput.value = "";
          this.filter("");
          this.openDropdown();
          this.searchInput.focus();
        } else if (e.target.closest(".smart-picker-clear-btn")) {
          e.preventDefault();
          this.selectValue("");
          this.renderSearchMode();
          this.searchInput.value = "";
          this.filter("");
          this.searchInput.focus();
        }
      });

      // Cambio en el select nativo (programático o externo)
      this.select.addEventListener("change", () => {
        if (!this._isSyncing) {
          this.sync();
        }
      });

      // Validación HTML5 nativa
      this.select.addEventListener("invalid", () => {
        this.wrapper.classList.add("is-invalid");
        this.searchInput.classList.add("is-invalid");
        this.searchInput.focus();
      });

      // Cierre al hacer clic fuera
      document.addEventListener("click", e => {
        if (!this.wrapper.contains(e.target)) {
          this.closeDropdown();
          if (this.hasSelectedValue()) {
            this.renderSelectedMode();
          }
        }
      });
    }

    setupMutationObserver() {
      // Observador para cambios dinámicos de opciones o valores en el <select>
      const observer = new MutationObserver(() => {
        if (!this._isSyncing) {
          this.sync();
        }
      });

      observer.observe(this.select, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["value", "selected", "disabled"]
      });
    }

    hasSelectedValue() {
      return Boolean(this.select.value && this.select.value !== "");
    }

    sync() {
      this._isSyncing = true;
      try {
        this.options.icon = this.select.getAttribute("data-icon") || deduceIcon(this.select);
        this.options.placeholder = deducePlaceholder(this.select);
        this.searchInput.placeholder = this.options.placeholder;

        const searchIcon = this.searchContainer.querySelector(".smart-picker-search-icon");
        if (searchIcon) {
          searchIcon.className = `bi ${this.options.icon} smart-picker-search-icon`;
        }

        this.buildOptions();

        if (this.hasSelectedValue()) {
          this.renderSelectedMode();
        } else {
          this.renderSearchMode();
        }
      } finally {
        this._isSyncing = false;
      }
    }

    buildOptions() {
      this.optionsList.innerHTML = "";
      const currentValue = String(this.select.value || "");
      let count = 0;

      // 1. Grupos de opciones (<optgroup>)
      const optgroups = this.select.querySelectorAll("optgroup");
      if (optgroups.length > 0) {
        optgroups.forEach(og => {
          if (og.style.display === "none") return;
          const ogDiv = document.createElement("div");
          ogDiv.className = "smart-picker-group";

          const groupLabel = document.createElement("div");
          groupLabel.className = "smart-picker-group-label";
          groupLabel.textContent = og.label;
          ogDiv.appendChild(groupLabel);

          let groupCount = 0;
          og.querySelectorAll("option").forEach(opt => {
            if (opt.style.display === "none") return;
            const optEl = this.createOptionElement(opt, currentValue);
            ogDiv.appendChild(optEl);
            groupCount++;
            count++;
          });

          if (groupCount > 0) {
            this.optionsList.appendChild(ogDiv);
          }
        });
      }

      // 2. Opciones directas (hijas directas de <select>)
      const directOptions = Array.from(this.select.children).filter(c => c.tagName === "OPTION");
      directOptions.forEach(opt => {
        if (opt.style.display === "none") return;
        // Omitir option placeholder vacio en la lista desplegable si no tiene contenido útil
        if (opt.value === "" && directOptions.length > 1 && (!opt.text || opt.text.toLowerCase().includes("seleccione") || opt.text.toLowerCase().includes("cargando"))) {
          return;
        }
        const optEl = this.createOptionElement(opt, currentValue);
        this.optionsList.appendChild(optEl);
        count++;
      });

      if (count === 0) {
        this.optionsList.innerHTML = '<div class="smart-picker-empty"><i class="bi bi-info-circle me-1"></i> No hay opciones disponibles</div>';
      }
    }

    createOptionElement(opt, currentValue) {
      const isSelected = String(opt.value || "") === currentValue && currentValue !== "";
      const optDiv = document.createElement("div");
      optDiv.className = `smart-picker-option${isSelected ? " is-selected" : ""}`;
      optDiv.tabIndex = 0;
      optDiv.dataset.value = opt.value;
      optDiv.dataset.text = opt.text;

      const lat = opt.dataset.lat;
      const lng = opt.dataset.lng;
      let subtitle = "";

      if (lat && lng) {
        subtitle = `Coordenadas: ${lat}, ${lng}`;
      } else if (opt.dataset.ubicacionNombre) {
        subtitle = opt.dataset.ubicacionNombre;
      } else if (opt.dataset.ubic) {
        subtitle = opt.dataset.ubic;
      } else if (opt.dataset.tipo) {
        subtitle = opt.dataset.tipo;
      }

      optDiv.innerHTML = `
        <div class="smart-picker-option-icon">
          <i class="bi ${this.options.icon}"></i>
        </div>
        <div class="smart-picker-option-body">
          <span class="smart-picker-option-title">${escapeHtml(opt.text)}</span>
          ${subtitle ? `<span class="smart-picker-option-sub">${escapeHtml(subtitle)}</span>` : ""}
        </div>
        ${isSelected ? '<i class="bi bi-check-circle-fill text-primary ms-auto flex-shrink-0"></i>' : ""}
      `;
      return optDiv;
    }

    filter(query) {
      const cleanQuery = normalizeStr(query);
      let matchCount = 0;

      this.optionsList.querySelectorAll(".smart-picker-option").forEach(opt => {
        const match = normalizeStr(opt.dataset.text || "").includes(cleanQuery);
        opt.style.display = match ? "" : "none";
        if (match) matchCount++;
      });

      this.optionsList.querySelectorAll(".smart-picker-group").forEach(og => {
        const hasVisible = Array.from(og.querySelectorAll(".smart-picker-option")).some(o => o.style.display !== "none");
        og.style.display = hasVisible ? "" : "none";
      });

      let emptyMsg = this.optionsList.querySelector(".smart-picker-empty");
      if (matchCount === 0) {
        if (!emptyMsg) {
          emptyMsg = document.createElement("div");
          emptyMsg.className = "smart-picker-empty";
          emptyMsg.innerHTML = '<i class="bi bi-search me-1"></i> No se encontraron coincidencias';
          this.optionsList.appendChild(emptyMsg);
        }
        emptyMsg.style.display = "";
      } else if (emptyMsg) {
        emptyMsg.style.display = "none";
      }
    }

    selectValue(val) {
      this._isSyncing = true;
      try {
        this.select.value = val;
      } finally {
        this._isSyncing = false;
      }

      this.closeDropdown();
      this.sync();

      this.wrapper.classList.remove("is-invalid");
      this.searchInput.classList.remove("is-invalid");

      // Disparar eventos nativos para que los listeners de la página reaccionen
      this.select.dispatchEvent(new Event("change", { bubbles: true }));
      this.select.dispatchEvent(new Event("input", { bubbles: true }));
    }

    renderSelectedMode() {
      const selectedOption = this.select.selectedOptions?.[0] ||
        this.select.querySelector(`option[value="${CSS.escape(String(this.select.value))}"]`);

      if (!selectedOption || selectedOption.value === "") {
        this.renderSearchMode();
        return;
      }

      const title = selectedOption.text;
      const lat = selectedOption.dataset.lat;
      const lng = selectedOption.dataset.lng;

      let subHtml = "";
      if (lat && lng) {
        subHtml = `<span class="badge bg-white text-dark border"><i class="bi bi-geo text-primary me-1"></i>${lat}, ${lng}</span>`;
      } else if (selectedOption.dataset.ubicacionNombre) {
        subHtml = `<span class="badge bg-white text-dark border"><i class="bi bi-geo-alt text-primary me-1"></i>${escapeHtml(selectedOption.dataset.ubicacionNombre)}</span>`;
      } else if (selectedOption.dataset.ubic) {
        subHtml = `<span class="badge bg-white text-dark border"><i class="bi bi-geo-alt text-primary me-1"></i>${escapeHtml(selectedOption.dataset.ubic)}</span>`;
      } else if (selectedOption.dataset.tipo) {
        subHtml = `<span class="badge bg-white text-dark border">${escapeHtml(selectedOption.dataset.tipo)}</span>`;
      } else {
        subHtml = `<span class="badge bg-white text-secondary border">Seleccionado</span>`;
      }

      this.selectedCard.innerHTML = `
        <div class="smart-picker-card-left">
          <div class="smart-picker-card-icon-badge">
            <i class="bi ${this.options.icon}"></i>
          </div>
          <div class="smart-picker-card-info">
            <div class="smart-picker-card-title">${escapeHtml(title)}</div>
            <div class="smart-picker-card-subtitle">${subHtml}</div>
          </div>
        </div>
        <div class="smart-picker-card-actions">
          <button type="button" class="btn btn-outline-primary btn-sm smart-picker-change-btn" title="Cambiar selección">
            <i class="bi bi-arrow-repeat me-1"></i> Cambiar
          </button>
          ${this.options.isOptional ? `
            <button type="button" class="btn btn-outline-secondary btn-sm smart-picker-clear-btn" title="Quitar selección">
              <i class="bi bi-x-lg"></i>
            </button>
          ` : ""}
        </div>
      `;

      this.searchContainer.classList.add("d-none");
      this.selectedCard.classList.remove("d-none");
      this.closeDropdown();
    }

    renderSearchMode() {
      this.selectedCard.classList.add("d-none");
      this.searchContainer.classList.remove("d-none");
    }

    openDropdown() {
      document.querySelectorAll(".smart-picker-wrapper.is-searching").forEach(w => {
        if (w !== this.wrapper) w.classList.remove("is-searching");
      });
      this.buildOptions();
      this.wrapper.classList.add("is-searching");
    }

    closeDropdown() {
      this.wrapper.classList.remove("is-searching");
    }

    static autoInit(root = document) {
      if (!root) return;
      const selects = root.querySelectorAll(
        "select.form-select, select.form-control, select[data-smart-picker], form select"
      );

      selects.forEach(select => {
        // Exclusiones: selects marcados explícitamente, de paginación, o inline pequeños de tablas
        if (select.getAttribute("data-no-smart-picker") === "true" ||
            select.classList.contains("pagination-select") ||
            select.classList.contains("dataTables_length") ||
            select.classList.contains("lane-reading") ||
            select.classList.contains("lane-monitor") ||
            select.closest(".dataTables_length") ||
            select.closest(".dataTables_wrapper .row:first-child")) {
          return;
        }

        if (!select._cardPickerInstance) {
          new SmartCardPicker(select);
        }
      });
    }
  }

  function initSmartCardPicker(selectOrId, options = {}) {
    const el = typeof selectOrId === "string" ? document.getElementById(selectOrId) : selectOrId;
    if (!el) return null;
    return new SmartCardPicker(el, options);
  }

  function syncSmartCardPicker(selectOrId) {
    const el = typeof selectOrId === "string" ? document.getElementById(selectOrId) : selectOrId;
    el?._cardPickerInstance?.sync();
  }

  // Exportar a nivel global
  window.SmartCardPicker = SmartCardPicker;
  window.initSmartCardPicker = initSmartCardPicker;
  window.syncSmartCardPicker = syncSmartCardPicker;

  // Aliases de compatibilidad
  window.SearchableSelect = SmartCardPicker;
  window.initSearchableSelect = initSmartCardPicker;
  window.syncSearchableSelect = syncSmartCardPicker;

  // Inicialización automática al cargar el DOM
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      SmartCardPicker.autoInit(document);
    });
  } else {
    SmartCardPicker.autoInit(document);
  }

  // Auto-init al abrir modales Bootstrap dinámicamente
  document.addEventListener("shown.bs.modal", e => {
    if (e.target) {
      SmartCardPicker.autoInit(e.target);
    }
  });

})(window, document);
