<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::transaction(function () {
            Contact::factory()->count(1000)->create();
            $contacts = Contact::all()->filter(function ($contact) {
                return $contact->getMedia(Contact::MEDIA_COLLECTION)->isEmpty();
            });
            foreach ($contacts as $contact) {
                $contact->copyMedia(
                    public_path('Marvel-Transparent.png')
                )->toMediaCollection(
                    Contact::MEDIA_COLLECTION
                );
            }
        });
    }
}
