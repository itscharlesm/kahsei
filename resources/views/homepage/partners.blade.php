@extends('homepage.layouts.master')

@section('title', 'Partners')
@section('crumb', 'About Us')
@section('page_title', 'Partners')

@section('content')
    @include('homepage.partials.partners', ['showHeading' => false])
@endsection