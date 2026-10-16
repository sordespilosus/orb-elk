
@extends('layouts.app')

@section('content')
    <rsaag-admin-users v-bind:clients_prop="{{json_encode((object)$users)}}"></rsaag-admin-users>
@endsection
