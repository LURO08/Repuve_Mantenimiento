<?php include('../views/header.php'); ?>

<h3 class="text-center my-3">🗺️ Mapa de Arcos (OpenStreetMap)</h3>

<div id="map" style="height:600px; border-radius:10px;"></div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
const map = L.map('map').setView([19.432608, -99.133209], 10);

// Mapa base (OpenStreetMap)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Cargar arcos
fetch("../controllers/mapa_arcos_controller.php")
.then(res => res.json())
.then(data => {
    data.forEach(arco => {
        if (!arco.lat || !arco.lng) return;

        const marker = L.marker([arco.lat, arco.lng]).addTo(map);

        marker.bindPopup(`
            <strong>${arco.arco}</strong><br>
            📍 ${arco.ubicacion}<br>
            ⚠️ Fallas: ${arco.fallas}
        `);
    });
});
</script>

<?php include('../views/footer.php'); ?>
