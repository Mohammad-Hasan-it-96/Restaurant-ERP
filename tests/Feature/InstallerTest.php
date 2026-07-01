<?php

namespace Tests\Feature;

use App\Support\Installer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_is_locked_once_installed(): void
    {
        // RefreshDatabase migrates (users table exists) and Installer::lock()
        // makes the installed state explicit.
        Installer::lock();

        $this->get('/install')->assertRedirect('/');
    }

    public function test_installed_app_serves_normally(): void
    {
        Installer::lock();

        // A normal route is not hijacked by the install gate.
        $this->get('/')->assertOk();
    }
}
