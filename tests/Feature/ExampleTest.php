<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Memastikan rute beranda (/) mengarahkan pengguna tamu (guest)
     * ke halaman login dengan kode status redirect HTTP 302.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Mengirimkan request GET ke root '/' sebagai pengguna yang belum login
        $response = $this->get('/');

        // Memastikan aplikasi merespons dengan HTTP 302 (Redirect)
        $response->assertStatus(302);

        // Memastikan tujuan redirect adalah ke rute login web resmi (/login)
        $response->assertRedirect('/login');
    }
}
