<?php
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;

public function boot()
{
    // autre code éventuel...

    Password::broker()->getRepository()->setTable('password_resets');
}
