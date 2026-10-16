@extends('layouts.app')

@section('content')
<?php
$props = [
    'serverData' => [ 'email' => $email ?? old('email') ],
    'serverErrors' => ['email' => $errors->has('email') ? [$errors->first('email')] : null],
    'successMessage' => session('status')
];
?>

<page-password-reset v-bind="{{json_encode($props)}}"/>
@endsection
