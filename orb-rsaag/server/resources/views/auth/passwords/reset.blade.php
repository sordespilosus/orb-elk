@extends('layouts.app')

@section('content')
<?php 
$props = [
    'serverData' => [ 'email' => $email ?? old('email') ],
    'serverErrors' => [
        'email' => isset($errorsEmail) ? [$errorsEmail['email']] : ($errors->has('email') ? [$errors->first('email')] : null),
        'password' => $errors->has('password') ? [$errors->first('password')] : null, 
    ],
    'token' => $token,
    'email' => $email,
    'isEmailSent' => true
];
?>
<page-password-reset v-bind="{{json_encode($props)}}"/>
@endsection
