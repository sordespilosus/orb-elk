@extends('layouts.app')

@section('content')

<?php

$props = [
    'method' => isset($method) ? $method : null,
];
?>

<page-question v-bind="{{json_encode($props)}}"/>

@endsection