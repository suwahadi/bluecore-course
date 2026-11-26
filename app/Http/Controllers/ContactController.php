<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Support\Settings;
use App\Support\SocialLinks;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        $address = Settings::get('address');
        $phone = Settings::get('phone');
        $whatsapp = Settings::get('whatsapp');
        $email = Settings::get('email');

        $rows = array_values(array_filter([
            filled($address) ? ['icon' => 'map-pin', 'label' => 'Alamat', 'text' => $address, 'url' => null] : null,
            filled($phone) ? ['icon' => 'phone', 'label' => 'Telepon', 'text' => $phone, 'url' => 'tel:'.preg_replace('/[^0-9+]/', '', $phone)] : null,
            filled($whatsapp) ? ['icon' => 'whatsapp', 'label' => 'WhatsApp', 'text' => WhatsApp::display($whatsapp), 'url' => WhatsApp::url($whatsapp)] : null,
            filled($email) ? ['icon' => 'mail', 'label' => 'Email', 'text' => $email, 'url' => 'mailto:'.$email] : null,
        ]));

        return view('contact', ['groups' => ['Info kontak' => $rows, 'Sosial Media' => SocialLinks::rows()], 'canMessage' => filled($whatsapp)]);
    }

    public function send(ContactMessageRequest $request): RedirectResponse
    {
        $whatsapp = Settings::get('whatsapp');

        abort_if(blank($whatsapp), 404);

        $text = WhatsApp::greeting().', saya '.$request->validated('name').".\n\n".$request->validated('message');

        return redirect()->away(WhatsApp::url($whatsapp, $text));
    }
}
