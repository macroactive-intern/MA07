# Audit — Coach-Client Messaging API

Rubric: `docs/rubric.md`
Date: 2026-06-22
Result: **3 / 10 pass**

---

## 1. Type Safety — FAIL

No file under `app/` contains `declare(strict_types=1)`. All 13 files are missing it:

```
app/Http/Controllers/Controller.php
app/Http/Controllers/ClientThreadController.php
app/Http/Controllers/CoachThreadController.php
app/Http/Controllers/MessageController.php
app/Http/Controllers/MessageReadController.php
app/Http/Controllers/ThreadController.php
app/Http/Requests/StoreMessageRequest.php
app/Http/Requests/StoreThreadRequest.php
app/Models/Message.php
app/Models/MessageThread.php
app/Models/User.php
app/Policies/MessageThreadPolicy.php
app/Providers/AppServiceProvider.php
```

Method signatures do have typed parameters and return types, which is good — but without `declare(strict_types=1)`, PHP will silently coerce mismatched scalar types at every function boundary.

**Fix:** Add `declare(strict_types=1);` as the second line of every file under `app/`.

---

## 2. Error Handling — PASS

All business failure modes are expressed via `ValidationException` with specific field keys (from `StoreThreadRequest` and `StoreMessageRequest`) or `AuthorizationException` (from the policy via `$this->authorize()`). No raw `new \Exception(...)` is thrown anywhere. No exceptions are caught and swallowed silently.

The one edge case — `abort_unless($message->thread_id === $thread->id, 404)` in `MessageReadController` — correctly throws an `HttpException`, not a generic exception.

---

## 3. Observability — FAIL

No `Log::` calls exist anywhere under `app/`. Every state-changing operation is silent:

| Operation | File | Log entry |
|---|---|---|
| Thread created | `CoachThreadController@store` | None |
| Thread archived | `CoachThreadController@destroy` | None |
| Message sent | `MessageController@store` | None |
| Message marked read | `MessageReadController@update` | None |

**Fix:** Add `Log::info()` with entity ID and actor ID to each of the four operations above. Example:

```php
Log::info('thread.created', ['thread_id' => $thread->id, 'coach_id' => $request->user()->id]);
```

---

## 4. Configuration — FAIL

Magic numbers appear directly in business logic. None are referenced via `config()`:

| Value | Location |
|---|---|
| `max:150` (subject length) | `StoreThreadRequest::rules()` |
| `max:5000` (body length) | `StoreMessageRequest::rules()` |
| `100` (last-message preview truncation) | `CoachThreadController@index` |

No `config/messaging.php` file exists.

**Fix:** Create `config/messaging.php` and replace the hardcoded values:

```php
// config/messaging.php
return [
    'subject_max_length'       => 150,
    'message_max_length'       => 5000,
    'preview_length'           => 100,
];
```

Then reference them: `config('messaging.subject_max_length')`.

---

## 5. Validation — FAIL

`StoreThreadRequest` queries the `users` table twice for the same `client_id` in a single request:

1. `exists:users,id` — runs `SELECT * FROM users WHERE id = ?`
2. The role-check closure calls `User::find($value)` — runs the same query again

**Fix:** Remove the `exists:users,id` rule and do one eager lookup in the first closure, then pass the result to the second:

```php
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
```

This reduces the validation from 3 DB queries to 2 (one user lookup, one thread existence check).

---

## 6. Data Integrity — FAIL

No `DB::transaction()` or `lockForUpdate()` is used anywhere.

Two read-then-write patterns lack pessimistic locks:

| Operation | Pattern | Risk |
|---|---|---|
| `MessageReadController@update` | Reads message, writes `read_at` | Concurrent requests could double-mark |
| `CoachThreadController@destroy` | Reads thread, writes `archived_at` | Concurrent archive requests could interleave |

Thread creation also has a TOCTOU gap: the duplicate check in `StoreThreadRequest` and the `INSERT` into `message_threads` are not wrapped in a transaction. A race between two concurrent requests from the same coach could pass the validation check simultaneously and both attempt the insert. The database unique constraint catches this, but the result is a 500 (QueryException) instead of a clean 422.

**Fix:**

```php
// CoachThreadController@store
$thread = DB::transaction(function () use ($request) {
    // duplicate check + create inside the same transaction
});

// MessageReadController@update
$message->lockForUpdate()->find($message->id);
$message->update(['read_at' => now()]);
```

---

## 7. Security — PARTIAL FAIL

**Passes:**
- All 7 API endpoints are behind `auth:sanctum` ✓
- `MessageThreadPolicy` is applied on every state-mutating endpoint ✓
- No admin endpoints, so `EnsureUserIsAdmin` is not applicable ✓

**Fails:**
- `.env.example` ships with `APP_DEBUG=true`. A developer who copies it verbatim and deploys will expose full stack traces, class names, file paths, and query structure in HTTP error responses.

**Fix:** Change `.env.example`:

```
APP_DEBUG=false
```

---

## 8. API Consistency — FAIL

**Status codes:**

| Endpoint | Current | Expected |
|---|---|---|
| `DELETE /api/coach/threads/{thread}` (archive) | `200` with body | `200` is acceptable for a soft operation with a response body |
| All validation errors | `422` (automatic) | `422` ✓ |
| All auth failures | `403` (automatic) | `403` ✓ |
| Creation endpoints | `201` | `201` ✓ |

Status codes are mostly correct. The archive returning `200` rather than `204` is intentional since a body is included.

**Response shapes — inconsistent:**

Controllers return three different shapes with no unifying API Resource layer:

| Controller | Shape |
|---|---|
| `CoachThreadController@index` | Manually mapped array |
| `CoachThreadController@store` | Raw Eloquent model |
| `ClientThreadController@index` | Raw Eloquent collection |
| `ThreadController@show` | Manually mapped array |
| `MessageController@store` | Raw Eloquent model |
| `MessageReadController@update` | Plain string message array |

The raw Eloquent responses (`@store` in both thread and message controllers) expose internal timestamps and all model attributes, while other endpoints return hand-shaped structures. A client consuming this API gets different shapes for create vs list on the same resource.

**Fix:** Introduce `ThreadResource` and `MessageResource` so every endpoint returns the same shape for the same resource type.

---

## 9. Tests Pass — PASS

`php vendor/bin/pest` exits with code 0. All 18 tests pass with 35 assertions. No tests are skipped or marked pending.

---

## 10. No Hardcoded Environment Values — FAIL

Two issues in `.env.example`:

1. `APP_DEBUG=true` — should be `false` for a production-example file (also flagged under Security)
2. `APP_KEY=` is required for the application to boot and encrypt/decrypt data, but is not annotated with `# REQUIRED`

No credentials or secrets appear in any tracked file. ✓

**Fix:**

```env
APP_KEY=  # REQUIRED — run: php artisan key:generate
APP_DEBUG=false
```

---

## Summary

| # | Criterion | Result |
|---|---|---|
| 1 | Type Safety | FAIL |
| 2 | Error Handling | PASS |
| 3 | Observability | FAIL |
| 4 | Configuration | FAIL |
| 5 | Validation | FAIL |
| 6 | Data Integrity | FAIL |
| 7 | Security | FAIL |
| 8 | API Consistency | FAIL |
| 9 | Tests Pass | PASS |
| 10 | No Hardcoded Env Values | FAIL |

**3 / 10 pass.**

The app has a solid foundation — routing, authorization, factories, and the full test suite are correct. The gaps are systemic rather than logic errors: missing `strict_types`, no logging, magic numbers outside config, and mixed response shapes. Each is straightforward to fix.
