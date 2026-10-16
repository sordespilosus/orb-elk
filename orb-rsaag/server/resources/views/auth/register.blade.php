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
    'name' => ['name'],
    'surname' => ['surname'],
    'patroname' => ['patroname'],
    'email' => ['email', 'email_verified_at', 'blocked'],
    'phone' => ['phone', 'sms_hash'],
    'city' => ['city'],
    'birthday' => ['birthday'],
    'age_period' => ['age_period'],
    'gender' => ['gender'],
    'is_accepted' => ['is_accepted'],
    'password' => ['password'],
    'verify' => ['g-recaptcha-response'],
    'sms_hash' => ['sms_hash']
  ];

  $props = [
    'serverErrors' => get_server_errors($errorFields, $errors),

    'serverData' => [
      'name' => old('name') ?? null,
      'surname' => old('surname') ?? null,
      'patroname' => old('patroname') ?? null,
      'email' => old('email') ?? null,
      'phone' => old('phone') ?? null,
      'city' => old('city') ?? null,
      'birthday' => old('birthday') ?? null,
      'age_period' => old('age_period') ?? null,
      'gender' => old('gender') ?? null,
      'is_accepted' => old('is_accepted') ?? null,
      'vk_user_id' => old('vk_user_id') ?? null,
      'fb_user_id' => old('fb_user_id') ?? null,
      'esia_user_id' => old('esia_user_id') ?? null,
    ],

    'cities' => $cities
  ];
?>
@extends('layouts.app')

@section('content')
  <page-register v-bind="{{json_encode($props)}}"/>
@endsection
