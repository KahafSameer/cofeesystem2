@extends('user.layouts.master')
@section('content')
<section id="about" class="container my-3">
    {{-- <h2 class="text-center text-white mb-3">About Us</h2> --}}
    <div class="row text-white">
        <div class="col-md-6">
            <h3 class="mb-4">Our Story</h3>
            <p>Welcome to MAY BROWN Coffee! We started our journey in 2020 with the mission to provide the best coffee experience in town. Located in the heart of the city, we offer a cozy atmosphere for coffee lovers to relax and enjoy their favorite beverages.</p>
            <p>Our address: 123 Coffee Street, Coffee City, CO 12345</p>
            <p>Phone: (123) 456-7890</p>
            <p>Email: contact@maybrowncoffee.com</p>
        </div>
        <div class="col-md-6">
            <h3 class="mb-4 text-center">Our Location</h3>
            <div id="map" style="height: 300px;"></div>
            <script>
                function initMap() {
                    var location = {lat: 40.712776, lng: -74.005974};
                    var map = new google.maps.Map(document.getElementById('map'), {
                        zoom: 14,
                        center: location
                    });
                    var marker = new google.maps.Marker({
                        position: location,
                        map: map
                    });
                }
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
        </div>
    </div>
</section>
@endsection
