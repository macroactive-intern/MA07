<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $client = User::find($value);
                    if (! $client) {
                        $fail('The selected client does not exist.');
                        return;
                    }
                    if ((int) $value === $this->user()->id) {
                        $fail('You cannot create a thread with yourself.');
                        return;
                    }
                    if ($client->role !== 'client') {
                        $fail('The selected user is not a client.');
                        return;
                    }
                    if (MessageThread::where('coach_id', $this->user()->id)->where('client_id', $value)->exists()) {
                        $fail('A thread with this client already exists.');
                    }
                },
            ],
            'subject' => ['required', 'string', 'max:' . config('messaging.subject_max_length')],
        ];
    }
}
