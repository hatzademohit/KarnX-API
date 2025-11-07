<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    public function handle(Failed $event)
    {
        activity('auth')
            ->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'ip' => Request::ip(),
                'url' => Request::fullUrl(),
                'agent' => Request::header('User-Agent'),
            ])
            ->log('Failed login attempt');
    }
}

