<?php

use App\View\Components\Icon;
use Illuminate\Support\Facades\Blade;

it('draws every solid icon that a view asks for', function (string $name) {
    expect(Blade::render('<x-icon name="'.$name.'" :solid="true" />'))->toContain('<path');
})->with(['play', 'mail', 'whatsapp', 'instagram', 'youtube', 'tiktok']);

it('draws every stroke icon', function () {
    foreach (array_keys(Icon::STROKE) as $name) {
        expect(Blade::render('<x-icon name="'.$name.'" />'))->toMatch('/<(path|circle|rect)/');
    }
});
