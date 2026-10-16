@extends('layouts.app')
<?php
$sidebarLinks = [
    [
        'id' => 1,
        'to' => ['name' => 'adminUsers'],
        'name' => 'Пользователи'
    ],
    [
        'id' => 2,
        'to' => ['name' => 'adminRoles'],
        'name' => 'Роли'
    ],
    [
        'id' => 3,
        'to' => ['name' => 'adminClients'],
        'name' => 'Доступные системы'
    ],
    [
        'id' => 7,
        'to' => ['name' => 'adminClientsModeration'],
        'name' => 'Системы на модерации'
    ],
    [
        'id' => 4,
        'to' => ['name' => 'adminLogs'],
        'name' => 'Лог запросов к сервису'
    ],
    [
        'id' => 5,
        'to' => ['name' => 'adminAgreement'],
        'name' => 'Пользовательское соглашение'
    ],
    [
        'id' => 6,
        'to' => ['name' => 'adminDescription'],
        'name' => 'Описание системы'
    ],
	[
		'id' => 8,
		'to' => ['name' => 'mailingModule'],
		'name' => 'Рассылка сообщений'
	],
]
?>
@section('content')
	<page-admin :sidebar-links="{{json_encode($sidebarLinks)}}"></page-admin>
@endsection