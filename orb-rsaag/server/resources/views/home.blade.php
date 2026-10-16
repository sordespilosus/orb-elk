<?php
  $props = [
    'clientsData' => $clients,
    'userId' => $userId,
    'isUserESIA' => $isUserEsia === 'true',
    'hasUserESIAFields' => $needEsiaFields === 'false',
    'hasUserOrgFields' => $isOrgUser === 'true' /*TODO: с бека передавать boolean, не строку! */
  ];
?>

@extends('layouts.app')

@section('content')
<page-home v-bind="{{json_encode($props)}}"/>
@endsection
