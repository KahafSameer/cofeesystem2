@extends('user.layouts.master')
@section('content')

    <section id="review" class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="text-white text-center mb-4">Leave a Review</h2>

                <div class="card">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form action="{{ route('addReview') }}" method="POST">
                            @csrf
                            <!-- Name Field -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <!-- Rating Field-->
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="d-flex gap-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <input type="radio" class="btn-check" name="rating" id="star{{ $i }}" value="{{ $i }}" required>
                                        <label class="btn btn-light rating-label" for="star{{ $i }}" data-value="{{ $i }}">
                                            <i class="fa fa-star"></i> {{ $i }}
                                        </label>
                                    @endfor
                                </div>
                            </div>

                            <!-- Review Message Field -->
                            <div class="mb-3">
                                <label for="message" class="form-label">Your Review</label>
                                <textarea class="form-control" id="message" name="subject" rows="4" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <button type="submit" class="btn btn-primary w-100">Submit Review</button>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('userDashboard') }}" class="btn btn-dark w-100 text-center"
                                        >Back</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

