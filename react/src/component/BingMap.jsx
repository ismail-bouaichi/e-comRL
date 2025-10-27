import { ComboboxDemo } from '../components/ui/combobox';
import React, { useState, useEffect, useRef } from 'react';

const BingMap = ({ onLocationSelect }) => {
  const [searchManager, setSearchManager] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [error, setError] = useState(null);
  const mapRef = useRef(null);
  const [options, setOptions] = useState([]);
  const mapInstance = useRef(null);

  const Apikey = 'AueuxOEDw6oIclMWjZNYdq9zuWasrwsotuBsv7qqb4O8X7WWkEycsU2EzSQu_H3x';

  useEffect(() => {
    const loadMap = () => {
      if (window.Microsoft && window.Microsoft.Maps) {
        const mapOptions = {
          credentials: Apikey,
          center: new window.Microsoft.Maps.Location(47.60357, -122.35565),
          mapTypeId: window.Microsoft.Maps.MapTypeId.road,
          zoom: 12
        };

        mapInstance.current = new window.Microsoft.Maps.Map(mapRef.current, mapOptions);

        window.Microsoft.Maps.Events.addHandler(mapInstance.current, 'click', handleMapClick);

        window.Microsoft.Maps.loadModule('Microsoft.Maps.Search', () => {
          setSearchManager(new window.Microsoft.Maps.Search.SearchManager(mapInstance.current));
        });
      }
    };

    if (!window.Microsoft) {
      const script = document.createElement('script');
      script.src = `https://www.bing.com/api/maps/mapcontrol?callback=loadMap`;
      script.async = true;
      script.defer = true;
      script.onerror = () => console.error('Failed to load Bing Maps script');
      window.loadMap = loadMap;
      document.body.appendChild(script);
    } else {
      loadMap();
    }
  }, []);

  const handleMapClick = (e) => {
    const latitude = e.location.latitude;
    const longitude = e.location.longitude;

    if (mapInstance.current && mapInstance.current.entities) {
      mapInstance.current.entities.clear();
      const pushpin = new window.Microsoft.Maps.Pushpin(new window.Microsoft.Maps.Location(latitude, longitude), null);
      mapInstance.current.entities.push(pushpin);
    }

    const url = `http://dev.virtualearth.net/REST/v1/Locations/${latitude},${longitude}?o=json&key=${Apikey}`;

    fetch(url)
      .then(response => response.json())
      .then(data => {
        const address = data.resourceSets[0].resources[0].address;
        onLocationSelect(address.formattedAddress, address.postalCode, address.adminDistrict2, address.countryRegion, latitude, longitude);
      



      })
      .catch(error => console.error('Error:', error));
  };

  const handleSearch = (searchQuery) => {
    if (searchManager && mapInstance.current) {
      const searchRequest = {
        where: searchQuery,
        callback: function (r) {
          if (r && r.results && r.results.length > 0) {
            const firstResult = r.results[0];
            mapInstance.current.setView({ bounds: firstResult.bestView });

            mapInstance.current.entities.clear();
            const pushpin = new window.Microsoft.Maps.Pushpin(firstResult.location);
            mapInstance.current.entities.push(pushpin);

            const location = firstResult.location;
            const url = `https://dev.virtualearth.net/REST/v1/Locations/${location.latitude},${location.longitude}?o=json&key=${Apikey}`;

            fetch(url)
              .then(response => response.json())
              .then(data => {
                const address = data.resourceSets[0].resources[0].address;
                onLocationSelect(address.formattedAddress, address.postalCode, address.adminDistrict2, location.latitude, location.longitude);
              })
              .catch(error => {
                setError(error);
              });
          }
        },
        errorCallback: function (e) {
          setError(e);
        }
      };
      searchManager.geocode(searchRequest);
    }
  };

  useEffect(() => {
    if (searchQuery) {
      const fetchOptions = async () => {
        try {
          const url = `https://dev.virtualearth.net/REST/v1/Locations?q=${encodeURIComponent(searchQuery)}&key=${Apikey}`;
          const response = await fetch(url);
          const data = await response.json();
          
          // Check if the response has the expected structure
          if (data.resourceSets && 
              data.resourceSets.length > 0 && 
              data.resourceSets[0].resources) {
            const results = data.resourceSets[0].resources;
            const options = results.map((result) => ({
              value: result.address.formattedAddress,
              label: result.address.formattedAddress,
              small: result.address.adminDistrict2
            }));
            
            const uniqueOptions = options.filter((option, index, self) =>
              index === self.findIndex((t) => t.label === option.label)
            );
            
            setOptions(uniqueOptions);
          } else {
            console.log("Empty or unexpected API response:", data);
            setOptions([]);
          }
        } catch (error) {
          console.error("Error fetching location suggestions:", error);
          setOptions([]);
        }
      };
      
      // Only search if query is long enough (prevents API errors for very short queries)
      if (searchQuery.length > 2) {
        fetchOptions();
      } else {
        setOptions([]);
      }
    }
  }, [searchQuery]);

  return (
    <div>
      <div style={{ marginBottom: '10px' }}>
        <ComboboxDemo
          value={searchQuery}
          onChange={setSearchQuery}
          onSearch={handleSearch}
          options={options}
        />
      </div>
      {error && <div style={{ color: 'red' }}>{error.message}</div>}
      <div ref={mapRef} style={{ width: '100%', height: '500px' }} />
    </div>
  );
};

export default BingMap;