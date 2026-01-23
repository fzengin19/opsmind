<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class DeleteContactAction
{
    public function execute(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            $contact->delete();
        });
    }
}
