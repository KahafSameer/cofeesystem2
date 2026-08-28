@extends('user.layouts.master')

@section('content')
    <section class="container-fluid py-4">
    <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="col-md-12">
            <form action="{{ route('updateProfile', Auth::user()->id) }}" method="post" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="city" id="city">
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8">
                        <div class="card shadow-lg rounded-4">
                            <div class="card-body p-4">
                                <h2 class="text-center text-dark fw-bold mb-4">Update Your Account</h2>

                                <div class="row g-4">
                                    <!-- Profile Picture -->
                                    <div class="col-md-4 text-center">
                                        <label class="form-label text-muted mb-2">Profile</label>
                                        <input type="hidden" name="oldImage" value="{{ auth()->user()->profile }}">
                                        <div class="mb-3">
                                            <img id="output" class="img-profile img-thumbnail rounded-circle"
                                                 src="{{ auth()->user()->profile ? asset('customerProfile/' . auth()->user()->profile) : asset('admin/images/undraw_profile.svg') }}"
                                                 style="width: 150px;">
                                        </div>
                                        <input type="file" name="image"
                                               class="form-control @error('image') is-invalid @enderror"
                                               onchange="loadFile(event)">
                                        @error('image')
                                            <small class="invalid-feedback">{{ $message }}</small>
                                        @enderror

                                         <a href="{{ route('climenu') }}"
                                           class="btn btn-outline-danger w-100 rounded-pill mt-4">Back</a>
                                    </div>

                                    <!-- Name and Phone -->
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" name="email" id="email"
                                                   class="form-control @error('email') is-invalid @enderror"
                                                   value="{{ auth()->user()->email }}" disabled>
                                            @error('email')
                                                <small class="invalid-feedback">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" name="name"
                                                   @if (auth()->user()->provider !== 'simple') disabled @endif
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   id="name" placeholder="Enter your name"
                                                   value="{{ old('name', auth()->user()->name ?? auth()->user()->nickname) }}">
                                            @error('name')
                                                <small class="invalid-feedback">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" name="phone"
                                                   class="form-control @error('phone') is-invalid @enderror"
                                                   id="phone" placeholder="09xxxxxxxx"
                                                   value="{{ old('phone', auth()->user()->phone) }}">
                                            @error('phone')
                                                <small class="invalid-feedback">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <input type="submit" value="Update"
                                               class="btn btn-primary w-100 mt-4 mb-3 rounded-pill">

                                    </div>

                                    <!-- Email, Address, Map -->
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" name="address" id="address"
                                                   class="form-control @error('address') is-invalid @enderror"
                                                   placeholder="Search and select your address"
                                                   value="{{ old('address', auth()->user()->address) }}">
                                            @error('address')
                                                <small class="invalid-feedback">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div id="map" class="map-container rounded mb-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@endsection

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

@section('scripts')
<script>
    function loadFile(event) {
            const output = document.getElementById('output');
            output.src = URL.createObjectURL(event.target.files[0]);
            output.onload = function () {
                URL.revokeObjectURL(output.src);
            }
        }

    const map = L.map('map').setView([16.8409, 96.1735], 13); // Default Yangon
    const marker = L.marker([16.8409, 96.1735], { draggable: true }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    marker.on('dragend', function (e) {
        const latlng = marker.getLatLng();
        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latlng.lat}&lon=${latlng.lng}`)
            .then(response => response.json())
            .then(data => {
                const address = data.display_name || '';
                document.getElementById('address').value = address;

                const city = data.address.city || data.address.town || data.address.village || data.address.county || '';
                document.getElementById('city').value = city;
            });
    });

    // Search input handler
    document.getElementById('addressSearch').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = e.target.value;
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        const lat = data[0].lat;
                        const lon = data[0].lon;
                        marker.setLatLng([lat, lon]);
                        map.setView([lat, lon], 15);

                        // Trigger reverse geocode to autofill
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`)
                            .then(res => res.json())
                            .then(data => {
                                document.getElementById('address').value = data.display_name || '';
                                const city = data.address.city || data.address.town || data.address.village || data.address.county || '';
                                document.getElementById('city').value = city;
                            });
                    }
                });
        }
    });
</script>


@endsection
