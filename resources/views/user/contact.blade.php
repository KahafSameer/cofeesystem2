@extends('user.layouts.master')
@section('content')
<div class="container my-5">
    <div class="card shadow-lg p-4 bg-light mx-auto" style="max-width: 700px; border-radius: 1rem;">
        <h1 class="text-center text-dark mb-4">Contact Us</h1>
        <form action="{{ route('addContact') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label text-dark">Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter your name" required>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label text-dark">Phone</label>
                <input type="tel" name="phone" id="phone" class="form-control" placeholder="Enter your phone number" required>
            </div>

            <div class="mb-3 text-dark">
                <label class="form-label d-block">Inquiry Type</label>
                <div class="form-check form-check-inline">
                    <input type="radio" name="inquiry_type" id="issue" value="issue" class="form-check-input" required>
                    <label for="issue" class="form-check-label">Issue</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="inquiry_type" id="feedback" value="feedback" class="form-check-input">
                    <label for="feedback" class="form-check-label">Feedback</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="inquiry_type" id="inquiry" value="other" class="form-check-input">
                    <label for="inquiry" class="form-check-label">Other Inquiry</label>
                </div>
            </div>

            <div class="mb-3">
                <label for="message" class="form-label text-dark">Message</label>
                <textarea name="message" id="message" class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary me-2 px-4">Send</button>
                <button type="reset" class="btn btn-danger px-4">Reset</button>
            </div>
        </form>
    </div>
</div>


@endsection
