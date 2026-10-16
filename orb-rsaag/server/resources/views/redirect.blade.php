@extends('layouts.app')

@section('content')

<?php

$props = [
    'client' => isset($client) ? $client : null,
];
?>

<page-redirect v-bind="{{json_encode($props)}}"/>

@endsection