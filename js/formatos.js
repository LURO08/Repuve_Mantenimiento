document.addEventListener('DOMContentLoaded', () => {
  const editNode = document.getElementById('formatoEditData');
  const editData = editNode ? JSON.parse(editNode.textContent || '{}') : {};
  const arcSelect = document.getElementById('formato_arco');
  const locationSelect = document.getElementById('formato_ubicacion');
  const arcOptions = arcSelect
    ? [...arcSelect.querySelectorAll('option[data-location-id]')].map((option) => option.cloneNode(true))
    : [];

  const filterArcsByLocation = () => {
    if (!locationSelect || !arcSelect) return;
    const locationId = locationSelect.value;
    const selectedArcId = arcSelect.value;
    arcSelect.innerHTML = `<option value="">${locationId ? 'Selecciona un arco o sitio...' : 'Selecciona una ubicación...'}</option>`;
    arcOptions
      .filter((option) => option.dataset.locationId === locationId)
      .forEach((option) => arcSelect.appendChild(option.cloneNode(true)));
    if ([...arcSelect.options].some((option) => option.value === selectedArcId)) {
      arcSelect.value = selectedArcId;
    }
    updateHiddenIds();
  };

  const updateHiddenIds = () => {
    const hiddenArco = document.getElementById('hidden_arco_id');
    const hiddenInfra = document.getElementById('hidden_infra_id');
    if (!arcSelect || !hiddenArco || !hiddenInfra) return;

    const val = arcSelect.value || '';
    if (!val) return;

    const opt = arcSelect.options[arcSelect.selectedIndex];
    const tipo = opt?.dataset?.tipo;
    const id = opt?.dataset?.id || '';

    if (tipo === 'infra') {
      hiddenInfra.value = id;
      hiddenArco.value = '';
    } else if (tipo === 'arco') {
      hiddenArco.value = id;
      hiddenInfra.value = '';
    } else {
      if (val.startsWith('infra_')) {
        hiddenInfra.value = val.replace('infra_', '');
        hiddenArco.value = '';
      } else if (val.startsWith('arco_')) {
        hiddenArco.value = val.replace('arco_', '');
        hiddenInfra.value = '';
      } else if (val) {
        hiddenArco.value = val;
        hiddenInfra.value = '';
      }
    }
  };

  if (locationSelect) {
    locationSelect.addEventListener('change', () => {
      filterArcsByLocation();
      loadChecklistMaterials();
    });
    filterArcsByLocation();
  }

  updateHiddenIds();

  document.querySelectorAll('.js-toggle-group').forEach((button) => {
    button.addEventListener('click', () => {
      const checkboxes = [...button.closest('.form-section').querySelectorAll('.selection-item input[type="checkbox"]')];
      const selectAll = checkboxes.some((checkbox) => !checkbox.checked);
      checkboxes.forEach((checkbox) => {
        checkbox.checked = selectAll;
        checkbox.dispatchEvent(new Event('change'));
      });
      button.textContent = selectAll ? 'Quitar selección' : 'Seleccionar todo';
    });
  });

  const checklistSelector = document.getElementById('materialSelectorChecklist');
  const checklistRows = document.getElementById('checklistRows');
  const checklistTemplate = document.getElementById('checklistRowTemplate');
  let currentChecklistMaterials = [];

  const normalizeText = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

  const getSavedComponent = (material) => {
    const components = editData.componentes || [];
    return components.find((component) => (
      Number(component.relacion_id) > 0
      && Number(component.relacion_id) === Number(material.relacion_id)
    )) || components.find((component) => (
      normalizeText(component.nombre) === normalizeText(material.material)
    ));
  };

  const renumberChecklist = () => {
    if (!checklistRows) return;
    [...checklistRows.querySelectorAll('.checklist-row')].forEach((row, index) => {
      const rel = row.querySelector('.checklist-relation');
      const name = row.querySelector('.checklist-name');
      const ser = row.querySelector('.checklist-series');
      const ip = row.querySelector('.checklist-ip');
      const mac = row.querySelector('.checklist-mac');
      const qty = row.querySelector('.checklist-quantity');
      const med = row.querySelector('.checklist-measure');
      const good = row.querySelector('.status-good');
      const bad = row.querySelector('.status-bad');
      const obs = row.querySelector('.checklist-observation');
      const chg = row.querySelector('.checklist-changed');

      if (rel) rel.name = `componente[${index}][relacion_id]`;
      if (name) name.name = `componente[${index}][nombre]`;
      if (ser) ser.name = `componente[${index}][serie]`;
      if (ip) ip.name = `componente[${index}][ip]`;
      if (mac) mac.name = `componente[${index}][mac]`;
      if (qty) qty.name = `componente[${index}][cantidad]`;
      if (med) med.name = `componente[${index}][medida]`;
      if (good) good.name = `componente[${index}][estado]`;
      if (bad) bad.name = `componente[${index}][estado]`;
      if (obs) obs.name = `componente[${index}][observacion]`;
      if (chg) chg.name = `componente[${index}][cambiado]`;
    });
  };

  const addChecklistRow = (material, saved = {}) => {
    if (!checklistRows || !checklistTemplate) return;
    const row = checklistTemplate.content.firstElementChild?.cloneNode(true);
    if (!row) return;
    const documentedSeries = saved.serie ?? material.serie ?? '';
    const documentedIp = saved.ip ?? material.ip ?? '';
    const documentedMac = saved.mac ?? material.mac ?? '';

    row.dataset.relationId = material.relacion_id;
    const nameEl = row.querySelector('.checklist-material-name');
    if (nameEl) nameEl.textContent = material.material;

    let subDetails = [];
    if (documentedSeries) subDetails.push(`<span class="badge bg-light text-dark border"><i class="bi bi-upc-scan text-primary me-1"></i>${documentedSeries}</span>`);
    if (documentedIp) subDetails.push(`<span class="badge bg-light text-dark border"><i class="bi bi-hdd-network text-info me-1"></i>${documentedIp}</span>`);
    if (documentedMac) subDetails.push(`<span class="badge bg-light text-dark border"><i class="bi bi-ethernet text-success me-1"></i>${documentedMac}</span>`);

    const seriesEl = row.querySelector('.checklist-material-series');
    if (seriesEl) {
      seriesEl.innerHTML = subDetails.join(' ');
      seriesEl.style.display = subDetails.length ? 'flex' : 'none';
    }

    const rel = row.querySelector('.checklist-relation');
    const name = row.querySelector('.checklist-name');
    const ser = row.querySelector('.checklist-series');
    const ip = row.querySelector('.checklist-ip');
    const mac = row.querySelector('.checklist-mac');
    const qty = row.querySelector('.checklist-quantity');
    const med = row.querySelector('.checklist-measure');
    const good = row.querySelector('.status-good');
    const bad = row.querySelector('.status-bad');
    const obs = row.querySelector('.checklist-observation');
    const chg = row.querySelector('.checklist-changed');

    if (rel) rel.value = material.relacion_id;
    if (name) name.value = material.material;
    if (ser) ser.value = documentedSeries;
    if (ip) ip.value = documentedIp;
    if (mac) mac.value = documentedMac;
    if (qty) qty.value = material.cantidad || 1;
    if (med) med.value = material.medida || 'pz';
    if (good) good.checked = saved.estado !== 'Malo';
    if (bad) bad.checked = saved.estado === 'Malo';
    if (obs) obs.value = saved.observacion || '';
    if (chg) chg.checked = Boolean(saved.cambiado);

    checklistRows.appendChild(row);
    renumberChecklist();
  };

  const virtualMaterial = (key, material, medida = 'pz') => ({
    relacion_id: `virtual-${key}`,
    material,
    serie: '',
    ip: '',
    mac: '',
    cantidad: 1,
    medida
  });

  const renderChecklistMaterials = () => {
    if (!checklistRows) return;
    const currentRows = new Map();
    [...checklistRows.querySelectorAll('.checklist-row')].forEach((row) => {
      if (!row.dataset.relationId) return;
      currentRows.set(row.dataset.relationId, {
        estado: row.querySelector('.status-bad')?.checked ? 'Malo' : 'Bueno',
        observacion: row.querySelector('.checklist-observation')?.value || '',
        cambiado: Boolean(row.querySelector('.checklist-changed')?.checked),
        serie: row.querySelector('.checklist-series')?.value || '',
        ip: row.querySelector('.checklist-ip')?.value || '',
        mac: row.querySelector('.checklist-mac')?.value || ''
      });
    });
    checklistRows.innerHTML = '';

    const selected = [...currentChecklistMaterials];
    const hasStructure = selected.some((material) => normalizeText(material.material).includes('estructura'));
    if (!hasStructure) selected.push(virtualMaterial('structure', 'Estructura metálica'));

    const countBadge = document.getElementById('checklistCount');
    if (countBadge) {
      countBadge.textContent = `${selected.length} componentes activos`;
    }

    selected.forEach((material) => {
      const saved = currentRows.get(String(material.relacion_id)) || getSavedComponent(material) || {};
      addChecklistRow(material, saved);
    });
  };

  async function loadChecklistMaterials() {
    if (!checklistRows) return;
    updateHiddenIds();
    const hiddenArco = document.getElementById('hidden_arco_id');
    const hiddenInfra = document.getElementById('hidden_infra_id');
    const hiddenRev = document.getElementById('hidden_revision_id');
    const hiddenInfraRev = document.getElementById('hidden_infra_revision_id');
    const countBadge = document.getElementById('checklistCount');

    const arcId = hiddenArco?.value || '';
    const infraId = hiddenInfra?.value || '';
    const revId = hiddenRev?.value || '';
    const infraRevId = hiddenInfraRev?.value || '';

    currentChecklistMaterials = [];
    if (!arcId && !infraId && !revId && !infraRevId) {
      checklistRows.innerHTML = '<div class="text-muted text-center py-3">Selecciona un arco o sitio para cargar sus materiales.</div>';
      if (countBadge) countBadge.textContent = 'Sin objetivo';
      return;
    }

    if (countBadge) countBadge.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cargando...';
    checklistRows.innerHTML = '<div class="text-muted text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando componentes...</div>';
    try {
      let url = '../controllers/formatos_ajax.php?action=materials';
      if (revId) {
        url += `&revision_id=${encodeURIComponent(revId)}`;
      } else if (infraRevId) {
        url += `&infraestructura_revision_id=${encodeURIComponent(infraRevId)}`;
      } else if (infraId) {
        url += `&infraestructura_id=${encodeURIComponent(infraId)}`;
      } else if (arcId) {
        url += `&arco_id=${encodeURIComponent(arcId)}`;
      }

      const response = await fetch(url);
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudieron cargar los materiales.');
      currentChecklistMaterials = data.materials || [];
      renderChecklistMaterials();
    } catch (error) {
      if (countBadge) countBadge.textContent = 'Error';
      checklistRows.innerHTML = `<div class="alert alert-danger py-2 m-2">${error.message}</div>`;
    }
  }

  arcSelect?.addEventListener('change', () => {
    updateHiddenIds();
    loadChecklistMaterials();
  });

  const lanesContainer = document.getElementById('carrilesContainer');
  const laneTemplate = document.getElementById('carrilTemplate');
  const addLaneButton = document.getElementById('agregarCarril');

  const renumberLanes = () => {
    if (!lanesContainer) return;
    [...lanesContainer.querySelectorAll('.lane-card')].forEach((lane, index) => {
      const num = lane.querySelector('.lane-number');
      const name = lane.querySelector('.lane-name');
      const read = lane.querySelector('.lane-reading');
      const mon = lane.querySelector('.lane-monitor');
      const obs = lane.querySelector('.lane-observation');
      const rem = lane.querySelector('.remove-lane');

      if (num) num.textContent = index + 1;
      if (name) name.name = `carril[${index}][nombre]`;
      if (read) read.name = `carril[${index}][lectura]`;
      if (mon) mon.name = `carril[${index}][monitoreo]`;
      if (obs) obs.name = `carril[${index}][observacion]`;
      if (rem) rem.disabled = lanesContainer.children.length <= 2;
    });
  };

  const addLane = (saved = {}) => {
    if (!lanesContainer || !laneTemplate || lanesContainer.children.length >= 8) return;
    const lane = laneTemplate.content.firstElementChild?.cloneNode(true);
    if (!lane) return;

    const name = lane.querySelector('.lane-name');
    const read = lane.querySelector('.lane-reading');
    const mon = lane.querySelector('.lane-monitor');
    const obs = lane.querySelector('.lane-observation');
    const rem = lane.querySelector('.remove-lane');

    if (name) name.value = saved.nombre || '';
    if (read) read.value = saved.lectura || '';
    if (mon) mon.value = saved.monitoreo || '';
    if (obs) obs.value = saved.observacion || '';
    if (rem) {
      rem.addEventListener('click', () => {
        lane.remove();
        renumberLanes();
      });
    }
    lanesContainer.appendChild(lane);
    renumberLanes();
  };

  addLaneButton?.addEventListener('click', () => addLane());
  if (lanesContainer) {
    const savedLanes = editData.carriles || [];
    const laneCount = Math.max(2, savedLanes.length);
    for (let index = 0; index < laneCount; index++) addLane(savedLanes[index] || {});
  }

  const setChecked = (name, value) => {
    const input = document.querySelector(`[name="${name}"][value="${value}"]`);
    if (input) input.checked = true;
  };
  const qualityEnergy = editData.energia_fuente
    || (editData.energia_solar ? 'solar' : (editData.energia_luz ? 'luz' : ''));
  setChecked('energia_fuente', qualityEnergy);
  setChecked('enlace', editData.enlace);
  setChecked('sistema_monitoreo', editData.sistema_monitoreo);
  setChecked('resultado', editData.resultado);
  const actions = document.querySelector('[name="acciones_correctivas"]');
  if (actions) actions.value = editData.acciones_correctivas || '';

  const selectedGroups = [
    ...(editData.herramientas || []),
    ...(editData.consumibles || []),
    ...(editData.epp || [])
  ];
  const selectedGroupNames = selectedGroups.map((item) => typeof item === 'string' ? item : item.nombre);
  document.querySelectorAll('.selection-item').forEach((label) => {
    const text = label.querySelector('span')?.textContent.trim();
    const checkbox = label.querySelector('input[type="checkbox"]');
    const wrapper = label.closest('.selection-item-wrap');
    const quantityInput = wrapper?.querySelector('.selection-quantity input');
    const savedItem = selectedGroups.find((item) => typeof item === 'object' && item.nombre === text);
    checkbox.checked = selectedGroupNames.includes(text);
    if (quantityInput) {
      quantityInput.disabled = !checkbox.checked;
      quantityInput.value = savedItem?.cantidad || 1;
    }
    checkbox.addEventListener('change', () => {
      if (!quantityInput) return;
      quantityInput.disabled = !checkbox.checked;
      if (checkbox.checked && Number(quantityInput.value) < 1) quantityInput.value = 1;
    });
  });

  if (checklistSelector) {
    const hiddenArco = document.getElementById('hidden_arco_id');
    const hiddenInfra = document.getElementById('hidden_infra_id');
    const hiddenRev = document.getElementById('hidden_revision_id');
    const hiddenInfraRev = document.getElementById('hidden_infra_revision_id');
    if (arcSelect?.value || hiddenArco?.value || hiddenInfra?.value || hiddenRev?.value || hiddenInfraRev?.value) {
      loadChecklistMaterials();
    }
  }

  const form = document.querySelector('.js-stepped-form');
  if (!form) return;
  const sections = [...form.querySelectorAll('.js-form-section')];
  const navigation = form.querySelector('.format-step-nav');
  const previousButton = form.querySelector('.js-prev-step');
  const nextButton = form.querySelector('.js-next-step');
  const submitButton = form.querySelector('.js-submit-format');
  let activeStep = 0;

  const showStep = (index) => {
    activeStep = Math.max(0, Math.min(index, sections.length - 1));
    sections.forEach((section, sectionIndex) => section.classList.toggle('is-active', sectionIndex === activeStep));
    [...navigation.children].forEach((button, buttonIndex) => button.classList.toggle('is-active', buttonIndex === activeStep));
    previousButton.disabled = activeStep === 0;
    nextButton.classList.toggle('d-none', activeStep === sections.length - 1);
    submitButton.classList.toggle('d-none', activeStep !== sections.length - 1);

    if (sections.length <= 1) {
      previousButton.classList.add('d-none');
      nextButton.classList.add('d-none');
      submitButton.classList.remove('d-none');
      if (navigation) navigation.classList.add('d-none');
    } else {
      if (navigation) navigation.classList.remove('d-none');
    }
  };

  sections.forEach((section, index) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'format-step-nav__button';
    button.innerHTML = `<span>${index + 1}</span>${section.dataset.stepTitle}`;
    button.addEventListener('click', () => showStep(index));
    navigation.appendChild(button);
  });

  previousButton?.addEventListener('click', () => showStep(activeStep - 1));
  nextButton?.addEventListener('click', () => showStep(activeStep + 1));
  form.addEventListener('invalid', (event) => {
    const collapse = document.getElementById('collapseGeneralData');
    if (collapse && collapse.contains(event.target)) {
      if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
        bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
      } else {
        collapse.classList.add('show');
      }
    }
    const index = sections.findIndex((section) => section.contains(event.target));
    if (index >= 0) showStep(index);
  }, true);
  showStep(0);
});
