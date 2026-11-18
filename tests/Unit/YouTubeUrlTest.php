<?php

use App\Support\YouTubeUrl;

dataset('youtube links', [
    'short link' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'short link with share param' => ['https://youtu.be/dQw4w9WgXcQ?si=abc123', 'dQw4w9WgXcQ'],
    'watch with time' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s', 'dQw4w9WgXcQ'],
    'mobile watch' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'privacy embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'live' => ['https://www.youtube.com/live/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'plain http' => ['http://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'lookalike host' => ['https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ', null],
    'id too short' => ['https://www.youtube.com/watch?v=short', null],
    'other site' => ['https://vimeo.com/123456', null],
    'other scheme' => ['ftp://youtu.be/dQw4w9WgXcQ', null],
    'not a url' => ['bukan url', null],
    'empty' => ['', null],
    'null' => [null, null],
]);

it('extracts the 11 character video id', function (?string $url, ?string $id) {
    expect(YouTubeUrl::parse($url))->toBe($id);
})->with('youtube links');
