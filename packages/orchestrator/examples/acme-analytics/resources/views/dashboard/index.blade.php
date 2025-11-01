<section>
    <h1>{{ $headline }}</h1>
    <dl>
    @foreach ($metrics as $metric)
        <div>
            <dt>{{ $metric['label'] }}</dt>
            <dd>{{ $metric['value'] }}</dd>
        </div>
    @endforeach
    </dl>
</section>
