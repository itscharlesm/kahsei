@extends('homepage.layouts.master')

@section('title', 'Board of Members')
@section('crumb', 'About Us')
@section('page_title', 'Board of Members')

@section('content')
    @include('homepage.partials.board', ['showHeading' => false])
@endsection