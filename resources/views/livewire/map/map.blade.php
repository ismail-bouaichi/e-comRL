<div class="space-y-4" wire:ignore>
  <div class="flex items-center gap-2">
    <div class="flex-1 relative">
      <input id="map-search" type="text" placeholder="Search location..." class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm px-3 py-2" />
      <ul id="map-search-results" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded shadow text-sm hidden max-h-60 overflow-auto"></ul>
    </div>
    <button id="map-locate-btn" type="button" class="px-3 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-500">Locate Me</button>
  </div>
  <div id="map" class="rounded border" style="width: 650px; height: 480px;"></div>
  <div id="map-meta" class="text-xs text-gray-500 leading-relaxed"></div>

  <!-- Leaflet CSS/JS (CDN) without SRI (previous integrity mismatch blocked load) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

  <script>
    function initLeafletMap() {
      if (typeof L === 'undefined') {
        // Retry shortly if script not yet parsed
        return setTimeout(initLeafletMap, 100);
      }
      const mapEl = document.getElementById('map');
      const resultsEl = document.getElementById('map-search-results');
      const searchInput = document.getElementById('map-search');
      const metaEl = document.getElementById('map-meta');
      const locateBtn = document.getElementById('map-locate-btn');

      // Initialize Leaflet map
      const map = L.map(mapEl, { attributionControl: true }).setView([40.0, 0.0], 2);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      let marker = null;
      let searchTimeout = null;

      function setMarker(lat, lon) {
        if (marker) { marker.remove(); }
        marker = L.marker([lat, lon]).addTo(map);
      }

      function updateMeta(text) {
        metaEl.textContent = text || '';
      }

      function emitAddress(addrObj, lat, lon) {
        const addressLine = addrObj.road || addrObj.neighbourhood || addrObj.suburb || addrObj.village || addrObj.town || addrObj.city || '';
        const city = addrObj.city || addrObj.town || addrObj.village || addrObj.county || '';
        const zipCode = addrObj.postcode || '';
        
        const payload = {
          address: addressLine,
          city: city,
          zipCode: zipCode,
          longitude: lon,
          latitude: lat
        };
        
        console.log('Map emitting address:', payload);
        
        // Try Livewire dispatch with payload object
        Livewire.dispatch('updateAddress', [payload]);
        
        // Try custom Alpine.js event with object
        window.dispatchEvent(new CustomEvent('map-location-selected', {
          detail: payload
        }));
        
        updateMeta(`${addressLine ? addressLine + ', ' : ''}${city} ${zipCode}`);
      }

      async function reverseGeocode(lat, lon) {
        try {
          const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&addressdetails=1` , {
            headers: { 'Accept': 'application/json' }
          });
          const data = await res.json();
          if (data && data.address) {
            emitAddress(data.address, lat, lon);
          }
        } catch (e) { console.error(e); }
      }

      map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        setMarker(lat, lng);
        reverseGeocode(lat, lng);
      });

      locateBtn.addEventListener('click', () => {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], 14);
            setMarker(latitude, longitude);
            reverseGeocode(latitude, longitude);
          });
        }
      });

      async function searchPlaces(q) {
        if (!q || q.length < 3) { resultsEl.classList.add('hidden'); resultsEl.innerHTML=''; return; }
        try {
          const res = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=${encodeURIComponent(q)}`);
          const data = await res.json();
          if (!Array.isArray(data)) { resultsEl.classList.add('hidden'); return; }
          resultsEl.innerHTML = '';
          data.forEach(item => {
            const li = document.createElement('li');
            li.className = 'px-3 py-2 hover:bg-indigo-50 cursor-pointer';
            li.textContent = item.display_name;
            li.addEventListener('click', () => {
              const lat = parseFloat(item.lat); const lon = parseFloat(item.lon);
              map.setView([lat, lon], 15);
              setMarker(lat, lon);
              emitAddress(item.address || {}, lat, lon);
              resultsEl.classList.add('hidden');
              searchInput.value = item.display_name;
            });
            resultsEl.appendChild(li);
          });
          resultsEl.classList.toggle('hidden', data.length === 0);
        } catch (e) { console.error(e); }
      }

      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const value = e.target.value.trim();
        searchTimeout = setTimeout(() => searchPlaces(value), 500);
      });

      document.addEventListener('click', (e) => {
        if (!resultsEl.contains(e.target) && e.target !== searchInput) {
          resultsEl.classList.add('hidden');
        }
      });
    }

    document.addEventListener('livewire:initialized', initLeafletMap);
  </script>
</div>
