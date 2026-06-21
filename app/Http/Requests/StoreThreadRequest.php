<?php

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
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ((int) $value === $this->user()->id) {
                        $fail('You cannot create a thread with yourself.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) {
                    $client = User::find($value);
                    if ($client && $client->role !== 'client') {
                        $fail('The selected user is not a client.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) {
                    $exists = MessageThread::where('coach_id', $this->user()->id)
                        ->where('client_id', $value)
                        ->exists();
                    if ($exists) {
                        $fail('A thread with this client already exists.');
                    }
                },
            ],
            'subject' => ['required', 'string', 'max:150'],
        ];
    }
}
