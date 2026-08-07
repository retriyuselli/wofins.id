@extends('profile.layout')

@section('profile-page-title', 'Kompensasi & Cuti')
@section('profile-page-subtitle', 'Ringkasan gaji, saldo cuti, dan statistik cuti')

@section('profile-content')
@include('profile.partials.pro-preview-banner')

<div class="{{ ($proFeatureLocked ?? \App\Support\ProFeatures::locked(\App\Support\PricingPlans::FEATURE_EMPLOYEE_PORTAL)) ? 'wf-pro-readonly' : '' }}">
    @include('profile.sections.hr-salary-leave')
</div>
@endsection
