@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <p>Name : {!! Auth::user()->name !!}</p>
                    <p>Email : {!! Auth::user()->email !!}</p>

                    <a href="{{ route('allProducts') }}" class="btn btn-primary">Main Website</a>
                    @if($userData->isAdmin())
                    <a href="{{ route('adminDisplayProducts') }}" class="btn btn-primary">Admin Dashboard</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
