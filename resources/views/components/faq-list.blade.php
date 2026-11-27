@props(['items'])
<div class="faq__list">
    @foreach ($items as $item)
        <details class="faq__item">
            <summary>{{ $item['question'] }}</summary>
            <div class="prose faq__answer">{!! $item['answer'] !!}</div>
        </details>
    @endforeach
</div>
