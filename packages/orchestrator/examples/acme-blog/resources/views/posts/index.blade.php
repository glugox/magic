@extends('layouts.app')

@section('content')
    <section>
        <h1>{{ $title }}</h1>
        <ul>
            @foreach ($posts as $post)
                <li>
                    <strong>{{ $post['title'] }}</strong>
                    <p>{{ $post['excerpt'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
