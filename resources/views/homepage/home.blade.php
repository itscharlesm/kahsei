@extends('homepage.layouts.master')

@section('title', 'Home')

@section('content')
    @include('homepage.partials.vision_mission')
    @include('homepage.partials.goals_objectives')
    @include('homepage.partials.latest_questions')
    @include('homepage.partials.board')
    @include('homepage.partials.partners')
@endsection