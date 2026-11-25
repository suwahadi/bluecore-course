<x-error-screen code="403" title="Akses ditolak" :message="filled($exception?->getMessage()) ? $exception->getMessage() : 'Akun Anda tidak punya hak untuk membuka halaman ini.'" />
