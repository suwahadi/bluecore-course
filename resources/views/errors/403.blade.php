<x-error-screen code="403" title="Akses ditolak" :message="filled($exception?->getMessage()) ? $exception->getMessage() : 'Akunmu tidak punya hak untuk membuka halaman ini.'" />
