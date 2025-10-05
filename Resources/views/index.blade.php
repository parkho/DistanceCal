@extends('distancecal::layouts.frontend')

@section('title', 'DistanceCal')

@section('content')
<div class="row mt-5">
    <div class="form-group col-md-6">
      <label>Departure ICAO</label>
      <select style="width: 100%;" class="form-control airport_search" name="depicao" id="depicao"></select>
    </div>
    <div class="form-group col-md-6">
      <label>Arrival ICAO</label>
      <select style="width: 100%;" class="form-control airport_search" name="arricao" id="arricao"></select>
    </div>
	<div class="form-group col-md-12">
		<label>&nbsp;</label>
		 <button type="button" class="form-control btn btn-primary" onclick="calcDistance()">Calculate Distance</button>
	</div>
    <div class="form-group col-md-12 mt-5">
       
        <input type="text" id="distance" class="form-control mt-2" disabled style="display: none; font-size: 40px; height: 120px; font-weight: Bold;">
        <span id="flight_time" class="badge badge-info ml-2"></span>
    </div>
	<div class="form-group col-md-12 mt-4">
        <div id="map" style="height: 500px;"></div>
    </div>
</div>
@endsection
@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.geodesic"></script>
<script>
// Make sure map is initialized globally
let map, depMarker, arrMarker, line;

function initMap() {
    map = L.map("map").setView([20, 0], 2); // initial world view
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
}

function calcDistance() {
    const dep = $("#depicao").val().trim();
    const arr = $("#arricao").val().trim();
    const $distance = $("#distance");
    const $flightTime = $("#flight_time");

    $distance.removeClass('btn-danger btn-success btn-warning').val('');
    $flightTime.text('');

    if (dep === "" || arr === "") {
        $distance.val("Please enter both ICAO codes")
                 .prop("disabled", false)
                 .addClass('btn btn-danger');
        return;
    }

    $distance.val("Calculating...").prop("disabled", true)
             .removeClass('btn-danger').addClass('btn-warning');

    $.ajax({
        url: "{{ route('distancecal.calculate') }}",
        method: "GET",
        dataType: "json",
        data: { depicao: dep, arricao: arr },
        success: function(data) {
            if (data.error) {
                $distance.val(data.error).prop("disabled", false)
                         .removeClass('btn-warning').addClass('btn-danger');
                $flightTime.text('');
                return;
            }

            const nm = Number(data.distance).toFixed(1);
            const ft = data.flight_time || '';

            $distance.val("Distance: " + nm + " NM | Estimated Flight Time: " + ft + " HRS")
                     .removeClass('btn-warning').addClass('btn btn-success')
                     .css('display', '');
            $flightTime.text(ft ? ` ${ft}` : '');

            // --- MAP SECTION ---
            const depLatLng = [data.dep.lat, data.dep.lon];
            const arrLatLng = [data.arr.lat, data.arr.lon];

            if (!map) initMap();

            // Clear old markers/lines
            if (depMarker) map.removeLayer(depMarker);
            if (arrMarker) map.removeLayer(arrMarker);
            if (line) map.removeLayer(line);

            depMarker = L.marker(depLatLng).addTo(map)
    .bindPopup(`<b>Departure:</b> ${data.dep.icao} - ${data.dep.name}`).openPopup();

arrMarker = L.marker(arrLatLng).addTo(map)
    .bindPopup(`<b>Arrival:</b> ${data.arr.icao} - ${data.arr.name}`);

            // Use geodesic arc instead of straight line
            line = L.geodesic([[depLatLng, arrLatLng]], {
                weight: 3,
                opacity: 0.7,
                color: "blue",
                steps: 50
            }).addTo(map);

            map.fitBounds([depLatLng, arrLatLng]);
        },
        error: function(xhr) {
            let err = "Error calculating";
            if (xhr.responseJSON && xhr.responseJSON.error) err = xhr.responseJSON.error;
            $distance.val(err).prop("disabled", false)
                     .removeClass('btn-warning').addClass('btn btn-danger');
            $flightTime.text('');
        }
    });
}

// Initialize map at page load
$(document).ready(function () {
    initMap();
    $('.airport-select').select2({
        placeholder: "Search airport ICAO or name",
        allowClear: true
    });
});

</script>
@include('scripts.airport_search')
@endsection
