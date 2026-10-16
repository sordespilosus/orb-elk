@extends('layouts.app')

@section('content')
<?php
    $props = [
        'client' => $client,
        'scopes' => $scopes,
        'state' => $request->state,
        'agreement' => $agreement,
        'organizations' => $organizations,
        'needUpdateEsia' => $needUpdateEsia,
        'hasRequiredScopes' => $hasRequiredScopes,
        'hasTrustedLevel' => $hasTrustedLevel,
        'allScopes' => $allScopes,
    ];
?>

<page-o-auth v-bind="{{json_encode($props)}}"/>
@endsection
