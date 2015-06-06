@extends('laravel-console::layout')

@section('content')
<div id="console" class="console" data-action="{{ route('laravel-console') }}">
    <input id="token" type="hidden" name="_token" value="{{ csrf_token() }}"/>
    <ul id="response" class="response"></ul>

    <nav id="controlbar" class="controlbar">
        <ul id="controls" class="controls"></ul>

        <div id="execute" class="execute">Execute</div>
    </nav>

    <section id="editor" class="editor"></section>
</div>
@stop
