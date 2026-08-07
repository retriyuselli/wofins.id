@extends('profile.layout')

@section('profile-page-title', 'Jadwal & Riwayat')
@section('profile-page-subtitle', 'Acara mendatang dan riwayat cuti Anda')

@section('profile-content')
@include('profile.partials.pro-preview-banner')

<div class="{{ ($proFeatureLocked ?? \App\Support\ProFeatures::locked(\App\Support\PricingPlans::FEATURE_EMPLOYEE_PORTAL)) ? 'wf-pro-readonly' : '' }}">
    @include('profile.sections.upcoming-events')
</div>
@endsection
