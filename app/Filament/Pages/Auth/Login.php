<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable
    {
        return 'SInventory Demo Portal';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Masuk untuk menjelajahi dashboard sistem inventaris';
    }

    public function mount(): void
    {
        parent::mount();

        // Otomatis isi kredensial demo agar pengunjung bisa langsung klik sign in
        $this->form->fill([
            'email' => 'admin@sinventory.test',
            'password' => 'password',
            'remember' => true,
        ]);
    }
}
