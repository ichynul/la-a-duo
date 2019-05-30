@if($lines)
    <pre style="line-height:25px;font-size:14px;margin:0;">
    @foreach($lines as $line)
    {!! $line !!}
    @endforeach
    </pre>
@endif