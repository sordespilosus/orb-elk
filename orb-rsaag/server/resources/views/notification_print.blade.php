@extends('layouts.app')

@section('content')
    <?php

    $props = [
        'content' => $content,
        'info' => $info
    ];
    ?>
    <page-notification-print v-bind="{{json_encode($props)}}"/>
@endsection