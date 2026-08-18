@extends('profile.layout')

@section('profile-page-title', 'Kompensasi')
@section('profile-page-subtitle', 'Ringkasan gaji dan payroll')

@section('profile-content')
@include('profile.partials.pro-preview-banner')

<div class="{{ ($proFeatureLocked ?? \App\Support\ProFeatures::locked(\App\Support\PricingPlans::FEATURE_PAYROLL)) ? 'wf-pro-readonly' : '' }}">
    @include('profile.sections.hr-salary-leave')
</div>
@endsection
