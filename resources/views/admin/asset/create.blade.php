@extends('admin.layouts.master')
@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Add Asset</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('assets.store') }}" method="POST">
                            @csrf

                            @include('admin.asset.partials.form', ['asset' => null])

                            <div class="d-flex justify-content-between mt-3">
                                <button type="submit" class="btn btn-primary w-100 me-2">Save</button>
                                <a href="{{ route('assets.index') }}" class="btn btn-secondary w-100">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
