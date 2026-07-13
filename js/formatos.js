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
    arcSelect.innerHTML = `<option value="">${locationId ? 'Selecciona un arco...' : 'Selecciona una ubicación...'}</option>`;
    arcOptions
      .filter((option) => option.dataset.locationId === locationId)
      .forEach((option) => arcSelect.appendChild(option.cloneNode(true)));
    if ([...arcSelect.options].some((option) => option.value === selectedArcId)) {
      arcSelect.value = selectedArcId;
    }
  };

  if (locationSelect) {
    locationSelect.addEventListener('change', () => {
      filterArcsByLocation();
      loadChecklistMaterials();
    });
    filterArcsByLocation();
  }

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
    [...checklistRows.children].forEach((row, index) => {
      row.querySelector('.checklist-relation').name = `componente[${index}][relacion_id]`;
      row.querySelector('.checklist-name').name = `componente[${index}][nombre]`;
      row.querySelector('.checklist-series').name = `componente[${index}][serie]`;
      row.querySelector('.checklist-quantity').name = `componente[${index}][cantidad]`;
      row.querySelector('.checklist-measure').name = `componente[${index}][medida]`;
      row.querySelector('.status-good').name = `componente[${index}][estado]`;
      row.querySelector('.status-bad').name = `componente[${index}][estado]`;
      row.querySelector('.checklist-observation').name = `componente[${index}][observacion]`;
      row.querySelector('.checklist-changed').name = `componente[${index}][cambiado]`;
    });
  };

  const addChecklistRow = (material, saved = {}) => {
    if (!checklistRows || !checklistTemplate) return;
    const row = checklistTemplate.content.firstElementChild.cloneNode(true);
    const documentedSeries = saved.serie ?? material.serie ?? '';
    row.dataset.relationId = material.relacion_id;
    row.querySelector('.checklist-material-name').textContent = material.material;
    row.querySelector('.checklist-material-series').textContent = documentedSeries ? `Serie: ${documentedSeries}` : '';
    row.querySelector('.checklist-relation').value = material.relacion_id;
    row.querySelector('.checklist-name').value = material.material;
    row.querySelector('.checklist-series').value = documentedSeries;
    row.querySelector('.checklist-quantity').value = material.cantidad || 1;
    row.querySelector('.checklist-measure').value = material.medida || 'pz';
    row.querySelector('.status-good').checked = saved.estado !== 'Malo';
    row.querySelector('.status-bad').checked = saved.estado === 'Malo';
    row.querySelector('.checklist-observation').value = saved.observacion || '';
    row.querySelector('.checklist-changed').checked = Boolean(saved.cambiado);
    checklistRows.appendChild(row);
    renumberChecklist();
  };

  const virtualMaterial = (key, material, medida = 'pz') => ({
    relacion_id: `virtual-${key}`,
    material,
    serie: '',
    cantidad: 1,
    medida
  });

  const renderChecklistMaterials = () => {
    if (!checklistSelector || !checklistRows) return;
    const currentRows = new Map();
    [...checklistRows.children].forEach((row) => {
      currentRows.set(row.dataset.relationId, {
        estado: row.querySelector('.status-bad').checked ? 'Malo' : 'Bueno',
        observacion: row.querySelector('.checklist-observation').value,
        cambiado: row.querySelector('.checklist-changed').checked,
        serie: row.querySelector('.checklist-series').value
      });
    });
    checklistRows.innerHTML = '';

    const selected = [...currentChecklistMaterials];
    const hasStructure = selected.some((material) => normalizeText(material.material).includes('estructura'));
    if (!hasStructure) selected.push(virtualMaterial('structure', 'Estructura metálica'));

    checklistSelector.innerHTML = '';
    selected.forEach((material) => {
      const card = document.createElement('div');
      card.className = 'checklist-material-option is-included';
      card.innerHTML = '<span><i class="bi bi-check2"></i><strong></strong><small></small></span>';
      card.querySelector('strong').textContent = material.material;
      card.querySelector('small').textContent = material.serie
        ? `Serie: ${material.serie}`
        : `${material.cantidad || 1} ${material.medida === 'm' ? 'm' : 'pz'}`;
      checklistSelector.appendChild(card);

      const saved = currentRows.get(String(material.relacion_id)) || getSavedComponent(material) || {};
      addChecklistRow(material, saved);
    });
  };

  async function loadChecklistMaterials() {
    if (!checklistSelector || !arcSelect) return;
    const arcId = arcSelect.value;
    currentChecklistMaterials = [];
    checklistRows.innerHTML = '';
    if (!arcId) {
      checklistSelector.innerHTML = '<div class="text-muted text-center py-3">Selecciona un arco para cargar sus materiales.</div>';
      return;
    }

    checklistSelector.innerHTML = '<div class="text-muted text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando materiales...</div>';
    try {
      const response = await fetch(`../controllers/formatos_ajax.php?action=materials&arco_id=${encodeURIComponent(arcId)}`);
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudieron cargar los materiales.');
      currentChecklistMaterials = data.materials || [];
      renderChecklistMaterials();
    } catch (error) {
      checklistSelector.innerHTML = `<div class="alert alert-danger py-2 mb-0">${error.message}</div>`;
    }
  }

  arcSelect?.addEventListener('change', loadChecklistMaterials);

  const lanesContainer = document.getElementById('carrilesContainer');
  const laneTemplate = document.getElementById('carrilTemplate');
  const addLaneButton = document.getElementById('agregarCarril');

  const renumberLanes = () => {
    if (!lanesContainer) return;
    [...lanesContainer.children].forEach((lane, index) => {
      lane.querySelector('.lane-number').textContent = index + 1;
      lane.querySelector('.lane-name').name = `carril[${index}][nombre]`;
      lane.querySelector('.lane-reading').name = `carril[${index}][lectura]`;
      lane.querySelector('.lane-monitor').name = `carril[${index}][monitoreo]`;
      lane.querySelector('.lane-observation').name = `carril[${index}][observacion]`;
      lane.querySelector('.remove-lane').disabled = lanesContainer.children.length <= 2;
    });
  };

  const addLane = (saved = {}) => {
    if (!lanesContainer || !laneTemplate || lanesContainer.children.length >= 8) return;
    const lane = laneTemplate.content.firstElementChild.cloneNode(true);
    lane.querySelector('.lane-name').value = saved.nombre || '';
    lane.querySelector('.lane-reading').value = saved.lectura || '';
    lane.querySelector('.lane-monitor').value = saved.monitoreo || '';
    lane.querySelector('.lane-observation').value = saved.observacion || '';
    lane.querySelector('.remove-lane').addEventListener('click', () => {
      lane.remove();
      renumberLanes();
    });
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

  if (checklistSelector && arcSelect?.value) loadChecklistMaterials();

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
  };

  sections.forEach((section, index) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'format-step-nav__button';
    button.innerHTML = `<span>${index + 1}</span>${section.dataset.stepTitle}`;
    button.addEventListener('click', () => showStep(index));
    navigation.appendChild(button);
  });

  previousButton.addEventListener('click', () => showStep(activeStep - 1));
  nextButton.addEventListener('click', () => showStep(activeStep + 1));
  form.addEventListener('invalid', (event) => {
    const index = sections.findIndex((section) => section.contains(event.target));
    if (index >= 0) showStep(index);
  }, true);
  showStep(0);
});
