<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('api/contacts');

        $response->assertStatus(200);
    }

    public function test_the_application_index_returns_contacts()
    {
        $response = $this->get('api/contacts');

        $response->assertJsonStructure([
            'status',
            'message',
            'contacts',
        ]);
    }

    public function test_the_application_uploads_data()
    {
        $response = $this->post('api/contacts', [
            'name' => 'John Doe',
            'email' => Str::slug('John Doe') . '@mail.com',
            'message' => 'This is a test message',
            'attachment' => UploadedFile::fake()->image('avatar.png'),
        ]);
//        dd($response->getContent());

        $response->assertJsonStructure([
            'status',
            'message',
            'contact',
        ]);
    }
}
