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
    'email' => ['email', 'user_id'],
];

$props = [
    'serverErrors' => get_server_errors($errorFields, $errors),
    'oldEmail' => isset($email) ? $email : null,
];
?>

@extends('layouts.app')

@section('content')
    <page-add-email v-bind="{{json_encode($props)}}"/>
@endsection
