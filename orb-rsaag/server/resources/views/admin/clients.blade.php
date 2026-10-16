
@extends('layouts.app')

@section('content')
<rsaag-admin-clients v-bind:clients_prop="{{json_encode((object)$clients)}}"></rsaag-admin-clients>
@endsection
