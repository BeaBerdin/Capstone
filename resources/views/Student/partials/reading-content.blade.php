<article class="pw-reading-book" aria-labelledby="reading-material-title" data-reading-material>
    <header class="pw-reading-book__header">
        <p class="pw-reading-book__eyebrow">Explore · Understand · Learn</p>
        <h2 id="reading-material-title">Reading material</h2>
        <p class="pw-reading-book__intro">Take your time. Review each section, then mark the lesson complete when you are ready.</p>
    </header>

    <div class="pw-reading-book__pages">
        @if($lesson->sections->isNotEmpty())
            @foreach($lesson->sections as $section)
                <div class="pw-reading-book__section" data-reading-section="{{ $section->id }}">
                    @if($section->type === 'text')
                        @if(filled($section->caption))
                            <h3 class="pw-reading-book__heading">{{ $section->caption }}</h3>
                        @endif
                        @if(filled($section->body))
                            <div class="pw-reading-book__prose @if($section->body_format === 'html') pw-reading-rich @endif">
                                @if($section->body_format === 'html')
                                    {!! app(\App\Support\ReadingContent::class)->sanitize($section->body) !!}
                                @else
                                @foreach(preg_split('/\R[\t ]*\R/u', $section->body) ?: [$section->body] as $paragraph)
                                    <p>{{ $paragraph }}</p>
                                @endforeach
                                @endif
                            </div>
                        @endif
                    @elseif($section->type === 'image')
                        <figure class="pw-reading-book__figure">
                            <img src="{{ route('files.lesson-section', $section) }}"
                                 alt="{{ $section->caption ?: 'Lesson illustration' }}"
                                 loading="lazy" decoding="async" class="pw-reading-book__image">
                            @if(filled($section->caption))
                                <figcaption>{{ $section->caption }}</figcaption>
                            @endif
                        </figure>
                    @elseif($section->type === 'file')
                        @php($downloadLabel = $section->caption ?: $section->original_name ?: 'Learning material')
                        <a href="{{ route('files.lesson-section', $section) }}" download
                           class="pw-reading-book__attachment"
                           aria-label="{{ 'Download '.$downloadLabel }}">
                            <span class="pw-reading-book__file-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z M14 2v6h6 M12 11v7m-3-3 3 3 3-3"/>
                                </svg>
                            </span>
                            <span class="pw-reading-book__file-details">
                                <span class="pw-reading-book__file-label">{{ $downloadLabel }}</span>
                                <span class="pw-reading-book__file-meta">{{ $section->original_name ?: 'Private lesson attachment' }}</span>
                            </span>
                            <span class="pw-reading-book__download">Download <span aria-hidden="true">↓</span></span>
                        </a>
                    @endif
                </div>
            @endforeach
        @else
            <div class="pw-reading-book__section pw-reading-book__prose" data-reading-legacy>
                @foreach(preg_split('/\R[\t ]*\R/u', $lesson->content) ?: [$lesson->content] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif
    </div>
</article>
