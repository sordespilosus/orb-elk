@extends('layouts.app')

@section('content')

	<?php

	$props = [
		'cities' => $cities,
		'serverData' => $old,
		'serverSocial' => $social,
		'isUserOrganization' => $isOrgUser,
		'userOrganizations' => $userOrgs,
		'isUserActivation' => $isUserActivation ?? null,
		'esiaInfo' => $esiaInfo ?? null
	];
	?>
	
	<page-profile v-bind="{{json_encode($props)}}"></page-profile>

@endsection
