// Simple Google Maps helper.  Requires an element with id="map" on the page.
function initMap() {
	var map = new google.maps.Map(document.getElementById('map'), {
		center: {lat: 53.795, lng: -1.759}, // roughly Bradford center
		zoom: 12
	});
	// additional markers or layers can be added here
}

// load the library asynchronously by including script with callback=initMap
