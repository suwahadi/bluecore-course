<?php

it('keeps brand values in one place instead of repeating them in the Tailwind theme', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    preg_match('/@theme[^{]*\{(.*?)\}/s', $css, $theme);

    expect($theme[1] ?? '')->not->toMatch('/#[0-9a-fA-F]{3,8}\b/')
        ->not->toContain("'Poppins'");
});
