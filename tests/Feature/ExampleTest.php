<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_home(): void
    {
        $this->get('/')->assertRedirect('home');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }
}
