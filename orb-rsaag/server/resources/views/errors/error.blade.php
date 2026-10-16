<?php
    $type = isset($type) ? $type : 'error';
    $title = $type === 'error' ? 'Ошибка' : 'Уведомление';

?>

@extends('layouts.app')

@section('content')
    @for($i = 0; $i < count($errors); $i++)
        <notification type="{{$type}}">
            <template #title>{{$title}} {{$errors[$i]['code']}}!</template>
            <template>
                <p>{{$errors[$i]['message']}}</p>
                    @if(isset($errors[$i]['scnd_message']))
                        <p>{!!$errors[$i]['scnd_message']!!}</p>
                    @endif
            </template>
        </notification>
    @endfor
@endsection