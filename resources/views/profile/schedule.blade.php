@extends('profile.layout')

@section('profile-page-title', 'Jadwal & Riwayat')
@section('profile-page-subtitle', 'Ringkasan jadwal (modul cuti telah dihapus)')

@section('profile-content')
@include('profile.partials.pro-preview-banner')

<div class="{{ ($proFeatureLocked ?? \App\Support\ProFeatures::locked(\App\Support\PricingPlans::FEATURE_PAYROLL)) ? 'wf-pro-readonly' : '' }}">
    @include('profile.sections.upcoming-events')
</div>
@endsection
