<h1>Analytics health check failed</h1>

<p>The automated GA4 check found a problem for {{ $date }}.</p>

<ul>
    @foreach($issues as $issue)
        <li>{{ $issue }}</li>
    @endforeach
</ul>

@if(!empty($metrics))
    <p>Sessions: {{ $metrics['sessions'] }}<br>Page views: {{ $metrics['screenPageViews'] }}</p>
@endif
