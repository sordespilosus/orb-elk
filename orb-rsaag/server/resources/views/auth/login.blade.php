<?php
function get_server_errors($fields, $errors) {
    $result = [];

    foreach($fields as $field => $validators) {
      $errorMessages = [];

      foreach($validators as $validator) {
        if($errors->has($validator)) $errorMessages[] = $errors->first($validator);
      }

      $result[$field] = count($errorMessages) ? $errorMessages : null;
    }

    return $result;
  }

  $errorFields = [
    'login' => ['login', 'not_activate', 'throttle'],
    'password' => ['password'],
  ];

  $props = [
    'serverErrors' => get_server_errors($errorFields, $errors),
    'serverData' => [
      'login' => old('login') ?? null,
    ],
    'clients' => isset($clients) ? $clients : null,
    'clientName' => isset($clientName) ? $clientName : null,
    'isProfileVerified' => !$errors->has('not_activate'),
    'cities' => isset($cities) ? $cities : null,
    'registerData' => isset($registerData) ? $registerData : null,
    'sectionsDisabled' => isset($sectionsDisabled) ? $sectionsDisabled : null,
    'authMethodsDisabled' => isset($authMethodsDisabled) ? $authMethodsDisabled : null,
      'withoutMethod' => $withoutMethod ?? null,
      'withoutRegistration' => $withoutRegistration ?? null,
    'onlyLogin' => isset($onlyLogin) ? $onlyLogin : null,
    'authMethods' => isset($authMethods) ? $authMethods : null,
      'clientId' => isset($clientId) ? $clientId : null,
  ];
?>

@extends('layouts.app')

@section('content')
  <page-auth v-bind="{{json_encode($props)}}"/>
@endsection
