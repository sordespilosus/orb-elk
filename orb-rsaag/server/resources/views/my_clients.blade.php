<?php
$data = [
    'clientsServer' => isset($clients) ? $clients : null,
    'clientScope' => isset($scope) ? $scope : null,
    'esiaScopes' => isset($esiaScopes) ? $esiaScopes : null,
];
?>
@extends('layouts.app')

@section('content')
    <page-my-clients v-bind="{{json_encode($data)}}"/>
@endsection