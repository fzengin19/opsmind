<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Data\ContactData;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class UpdateContactAction
{
    public function execute(Contact $contact, ContactData $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            $contact->update([
                'type' => $data->type,
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'email' => $data->email,
                'phone' => $data->phone,
                'company_name' => $data->company_name,
                'job_title' => $data->job_title,
                'notes' => $data->notes,
                'tags' => $data->tags,
            ]);

            return $contact->fresh();
        });
    }
}
