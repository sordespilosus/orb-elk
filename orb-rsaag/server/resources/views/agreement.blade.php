<?php
$attrs =[
    'description' => $description,
    'agreement' => $agreement
];
?>

@extends('layouts.app')

@section('content')
    <page-agreement v-bind="{{json_encode($attrs)}}"/>
@endsection