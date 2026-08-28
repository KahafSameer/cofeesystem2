@extends('user.layouts.master')
@section('content')
    <section id="hero" class="d-flex align-items-center justify-content-center"
        style="background-image: url('{{ asset('user/images/coffee.png') }}');
       background-size: cover; background-position: center; height: 60vh;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div>
                        @if (isset($discountPercentage->discount_percentage) && $discountPercentage->discount_percentage > 0)
                            <h2 class="text-white display-4 font-weight-bold">Exclusive Offer</h2>
                            <p class="text-white lead">Get {{ intval($discountPercentage->discount_percentage) }} % off!</p>
                        @else
                            <h2 class="text-white display-4 font-weight-bold">Welcome to our shop</h2>
                            <p class="text-white lead">What would you like to order today?</p>
                        @endif

                    </div>
                    <button class="btn btn-lg btn-light text-primary shadow-lg rounded-pill"
                        onclick="location.href='{{ route('climenu') }}'">ORDER NOW</button>
                </div>
            </div>
        </div>
    </section>

    <section id="hot-menu" class="py-3" style="background-color: #42280e;">
        <div class="container carousel-wrapper">
            <div class="carousel-container">
                @foreach ($showReviews as $item)
                    <div class="testimonial-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-2 text-muted">{{ $item->subject }}</p>

                            <div class="rating mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $item->rating)
                                        <i class="fas fa-star text-warning"></i>
                                    @else
                                        <i class="far fa-star text-warning"></i>
                                    @endif
                                @endfor
                            </div>

                            <p class="font-weight-bold text-primary mt-3">{{ $item->name }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>


@endsection
    @if($showAddressModal)
    <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST" action="{{ route('saveAddress') }}">
                    @csrf
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addressModalLabel">
                                <i class="fa-solid fa-location-dot" style="color: #FFD43B;"></i> Add Address
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" name="address" id="address" class="form-control mb-3" placeholder="">
                            <div id="map" class="map-container rounded mb-3"></div>
                            <input type="hidden" name="lat" id="lat">
                            <input type="hidden" name="lng" id="lng">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary rounded-pill">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    @endif

    <!-- Modal -->
@section('scripts')
    @if($showAddressModal)
        <script>
                document.addEventListener('DOMContentLoaded', function () {
                const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
                addressModal.show();

                const map = L.map('map').setView([16.8409, 96.1735], 13);
                const marker = L.marker([16.8409, 96.1735], { draggable: true }).addTo(map);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Fix the jumping issue
                document.getElementById('addressModal').addEventListener('shown.bs.modal', function () {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 200);
                });

                marker.on('dragend', function () {
                    const latlng = marker.getLatLng();
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latlng.lat}&lon=${latlng.lng}`)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('address').value = data.display_name || '';
                            document.getElementById('lat').value = latlng.lat;
                            document.getElementById('lng').value = latlng.lng;
                        });
                });
            });
        </script>
    @endif

@endsection
