<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportController extends Controller
{
    private const CHUNK = 500;

    private const HEADERS = ['Nama', 'Email', 'Telepon', 'Peran', 'Terverifikasi', 'Sumber daftar', 'Tanggal daftar', 'Jumlah kursus', 'Total belanja lunas'];

    public function __invoke(Request $request): StreamedResponse
    {
        $query = User::query()->filteredForAdmin($request->only(['q', 'role']))->withAdminTotals();
        $admin = $request->user();

        return response()->streamDownload(function () use ($query, $admin) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::HEADERS, ';', '"', '');
            $rows = 0;

            $query->chunkById(self::CHUNK, function ($users) use ($output, &$rows) {
                foreach ($users as $user) {
                    fputcsv($output, array_map(self::neutralize(...), [
                        $user->name,
                        $user->email,
                        (string) $user->phone,
                        $user->role->label(),
                        $user->email_verified_at ? 'Ya' : 'Tidak',
                        $user->signupSourceLabel(),
                        $user->created_at->format('Y-m-d H:i'),
                        (string) $user->enrollments_count,
                        (string) (int) $user->paid_total,
                    ]), ';', '"', '');
                    $rows++;
                }
            });

            fclose($output);
            Log::info('Admin exported users', ['admin_id' => $admin->id, 'rows' => $rows, 'at' => now()->toIso8601String()]);
        }, 'pengguna-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function neutralize(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
