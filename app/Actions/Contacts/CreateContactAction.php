<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Data\ContactData;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateContactAction
{
    public function execute(ContactData $data, User $user): Contact
    {
        return DB::transaction(function () use ($data, $user) {
            return Contact::create([
                'company_id' => $data->company_id,
                'type' => $data->type,
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'email' => $data->email,
                'phone' => $data->phone,
                'company_name' => $data->company_name,
                'job_title' => $data->job_title,
                'notes' => $data->notes,
                'tags' => $data->tags,
                'created_by' => $user->id,
            ]);
        });
    }
}
