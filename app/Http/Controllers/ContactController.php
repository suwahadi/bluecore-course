<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __invoke(): View
    {
        $address = Settings::get('address');
        $phone = Settings::get('phone');
        $whatsapp = Settings::get('whatsapp');
        $email = Settings::get('email');

        $rows = array_values(array_filter([
            filled($address) ? ['icon' => 'map-pin', 'label' => 'Alamat', 'text' => $address, 'url' => null] : null,
            filled($phone) ? ['icon' => 'phone', 'label' => 'Telepon', 'text' => $phone, 'url' => 'tel:'.preg_replace('/[^0-9+]/', '', $phone)] : null,
            filled($whatsapp) ? ['icon' => 'whatsapp', 'label' => 'WhatsApp', 'text' => '0'.substr($whatsapp, 2), 'url' => 'https://wa.me/'.$whatsapp] : null,
            filled($email) ? ['icon' => 'mail', 'label' => 'Email', 'text' => $email, 'url' => 'mailto:'.$email] : null,
        ]));

        return view('contact', ['rows' => $rows]);
    }
}
