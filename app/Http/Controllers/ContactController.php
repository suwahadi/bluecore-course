<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __invoke(): View
    {
        return view('contact', [
            'address' => Settings::get('address'),
            'phone' => Settings::get('phone'),
            'whatsapp' => Settings::get('whatsapp'),
            'email' => Settings::get('email'),
        ]);
    }
}
