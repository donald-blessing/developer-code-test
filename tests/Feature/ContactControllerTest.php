<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use WithFaker;

//    public function __construct()
//    {
//        parent::__construct();
//        $this->setUpFaker();
//    }

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

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'contacts',
            ]);
    }

    public function test_the_application_uploads_data()
    {
        $mimeTypes = [
            'PNG' => 'image/png',
            'SVG' => 'image/svg+xml',
            'SVG2' => 'application/svg+xml',
            'CSV' => 'text/csv',
        ];

        $extension = array_rand($mimeTypes);

        $filename = Str::random(10) . "." . strtolower(substr($extension, 0, 3));


        $response = $this->post('api/contacts', [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'message' => $this->faker->text,
            'attachment' => UploadedFile::fake()->create(
                $filename,
                100,
                $mimeTypes[$extension]
            ),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'contact',
            ])->assertJson([
                'status' => 'success',
                'message' => 'Contact created successfully',
            ]);
    }

    protected function duplicateUpload(): TestResponse
    {
        return $this->post('api/contacts', [
            'name' => 'John Doe',
            'email' => Str::slug('John Doe') . '@mail.com',
            'message' => 'This is a test message',
            'attachment' => UploadedFile::fake()->image('avatar.png'),
        ]);
    }

    public function test_the_application_for_duplicate_uploads_data()
    {
        $response = $this->duplicateUpload();

        if ($response->status() === 200) {
            $response = $this->duplicateUpload();
        }

        $response->assertStatus(409)
            ->assertJsonStructure([
                'status',
                'message',
            ])
            ->assertJson([
                'status' => 'error',
                'message' => 'Duplicate upload',
            ]);
    }

    public function test_application_invalid_file_upload()
    {
        $response = $this->post('api/contacts', [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'message' => $this->faker->text,
            'attachment' => UploadedFile::fake()->create(
                'document.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
            ])
            ->assertJson([
                'status' => 'error',
                'message' => 'The attachment must be a file of type: csv, png, svg.',
            ]);
    }

    public function test_application_show_contact()
    {
        $response = $this->get('api/contacts/1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'contact',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Contact retrieved successfully',
            ]);
    }

    public function test_application_contact_not_found()
    {
        $response = $this->get('api/contacts/0');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status',
                'message',
            ])
            ->assertJson([
                'status' => 'error',
                'message' => 'Contact not found',
            ]);
    }

    public function test_application_update_contact()
    {
        $mimeTypes = [
            'PNG' => 'image/png',
            'SVG' => 'image/svg+xml',
            'SVG2' => 'application/svg+xml',
            'CSV' => 'text/csv',
        ];

        $extension = array_rand($mimeTypes);
        $filename = Str::random(10) . "." . strtolower(substr($extension, 0, 3));

        $response = $this->put('api/contacts/1', [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'message' => $this->faker->text,
            'attachment' => UploadedFile::fake()->create(
                $filename,
                100
            ),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'contact',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Contact updated successfully',
            ]);
    }
}
