Goal

Build a Laravel JSON API for one-to-one message threads between coaches and clients.

Each thread belongs to one coach and one client. There can only be one thread for each coach-client pair. Threads contain messages, and both the coach and client can view the thread and send messages if they are participants.

The most important parts of this task are:

Correct per-thread authorization
Correct unread counts
Correct latest-message ordering
Preventing duplicate coach-client threads
Avoiding N+1 queries in the coach thread list
Writing feature tests first before implementing the code
Build order

I will follow the workflow and write the feature tests before implementing the main code.

Order:

Finish workflow documents.
Set up Laravel, Sanctum, SQLite, and Pest.
Create failing feature tests for the acceptance criteria.
Create migrations.
Create models and relationships.
Add factories.
Add FormRequests.
Add routes.
Add controllers.
Implement authorization checks.
Implement thread list query, unread count, and latest-message ordering.
Run tests until they pass.
Paste failing and passing test output into BEFORE-AFTER.md.
Test-first approach

I will write the feature tests before writing the implementation.

The tests will prove the expected behaviour before the code exists. At first, these tests should fail. After implementation, they should pass.

Feature tests to write:

Coach can create a thread with a client.
Coach cannot create a duplicate thread with the same client.
Database unique constraint prevents duplicate coach-client pairs.
Coach cannot create a thread with themselves as the client.
Coach thread list is ordered by latest message time.
Coach thread list unread count ignores the coach’s own unread messages.
Coach thread list avoids N+1 queries.
User cannot view a thread they do not participate in.
Coach cannot read another coach’s thread.
Client cannot read an unrelated thread.
Messages inside a thread are returned oldest first.
Message body is required.
Message body has a max length of 5000 characters.
Mark read sets read_at.
Archive sets archived_at but does not delete the row.
Client thread list only returns threads where the authenticated user is the client.
Libraries and packages
Laravel

Used for the API framework, routing, controllers, validation, migrations, models, and testing helpers.

Laravel Sanctum

Used for API authentication.

All routes will be inside an auth:sanctum middleware group.

Pest

Used for feature tests.

The brief specifically expects tests, and Pest gives a clean syntax for API feature tests.

SQLite

Used for local development and testing because the brief asks for SQLite configuration.

Data model
users

The default Laravel users table will be extended with a role column.

Columns added
Column	Type	Notes
role	string	Allowed values: coach, client
Decision

I will use a string column for role.

Expected values:

coach
client

I will add test factory states for both roles:

User::factory()->coach()->create();
User::factory()->client()->create();
message_threads

Stores one conversation between one coach and one client.

Columns
Column	Type	Notes
id	bigIncrements	Primary key
coach_id	foreignId	References users.id
client_id	foreignId	References users.id
subject	string(150)	Thread subject
archived_at	timestamp nullable	Soft archive for coach
created_at	timestamp	Laravel timestamp
updated_at	timestamp	Laravel timestamp
Constraints
$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
$table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
$table->string('subject', 150);
$table->timestamp('archived_at')->nullable();
$table->unique(['coach_id', 'client_id']);
Important rule

There must only be one thread for each coach_id and client_id pair.

The subject is not part of the unique rule. A different subject does not allow a second thread for the same coach and client.

messages

Stores messages inside a thread.

Columns
Column	Type	Notes
id	bigIncrements	Primary key
thread_id	foreignId	References message_threads.id
sender_id	foreignId	References users.id
body	text	Message body
read_at	timestamp nullable	Null means unread by the recipient
created_at	timestamp	Laravel timestamp
updated_at	timestamp	Laravel timestamp
Constraints
$table->foreignId('thread_id')->constrained('message_threads')->cascadeOnDelete();
$table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
$table->text('body');
$table->timestamp('read_at')->nullable();
Models and relationships
User

Relationships:

public function coachThreads()
{
    return $this->hasMany(MessageThread::class, 'coach_id');
}

public function clientThreads()
{
    return $this->hasMany(MessageThread::class, 'client_id');
}

public function messages()
{
    return $this->hasMany(Message::class, 'sender_id');
}
MessageThread

Relationships:

public function coach()
{
    return $this->belongsTo(User::class, 'coach_id');
}

public function client()
{
    return $this->belongsTo(User::class, 'client_id');
}

public function messages()
{
    return $this->hasMany(Message::class, 'thread_id');
}

public function lastMessage()
{
    return $this->hasOne(Message::class, 'thread_id')->latestOfMany();
}

I will also add helper methods for authorization checks:

public function isParticipant(User $user): bool
{
    return $this->coach_id === $user->id || $this->client_id === $user->id;
}
Message

Relationships:

public function thread()
{
    return $this->belongsTo(MessageThread::class, 'thread_id');
}

public function sender()
{
    return $this->belongsTo(User::class, 'sender_id');
}
Routes

All routes will be protected by auth:sanctum.

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/coach/threads', [CoachThreadController::class, 'index']);
    Route::post('/coach/threads', [CoachThreadController::class, 'store']);
    Route::delete('/coach/threads/{thread}', [CoachThreadController::class, 'destroy']);

    Route::get('/client/threads', [ClientThreadController::class, 'index']);

    Route::get('/threads/{thread}', [ThreadController::class, 'show']);
    Route::post('/threads/{thread}/messages', [MessageController::class, 'store']);
    Route::patch('/threads/{thread}/messages/{message}/read', [MessageReadController::class, 'update']);
});
Controllers

I will keep the controllers small and focused.

CoachThreadController

Handles coach-only thread actions.

Methods:

index
store
destroy

Responsibilities:

list coach threads
create a thread
archive a thread
ClientThreadController

Handles client-only thread listing.

Methods:

index

Responsibilities:

return only threads where client_id is the authenticated user
ThreadController

Handles viewing a single thread.

Methods:

show

Responsibilities:

check participant authorization
return thread details
return messages oldest first
MessageController

Handles sending messages.

Methods:

store

Responsibilities:

check participant authorization
validate body
create message using authenticated user as sender
MessageReadController

Handles marking messages as read.

Methods:

update

Responsibilities:

check participant authorization
check message belongs to the given thread
set read_at to now
FormRequests and validation
StoreThreadRequest

Used for:

POST /api/coach/threads

Validation rules:

'client_id' => ['required', 'integer', 'exists:users,id'],
'subject' => ['required', 'string', 'max:150'],

Extra validation after the base rules:

authenticated user must be a coach
selected user must have role client
selected client cannot be the same user as the coach
selected coach-client pair must not already have a thread

If a duplicate thread exists, return 422.

StoreMessageRequest

Used for:

POST /api/threads/{id}/messages

Validation rules:

'body' => ['required', 'string', 'max:5000'],
Authorization approach

I disagree with relying only on auth:sanctum.

auth:sanctum proves the user is logged in. It does not prove the user belongs to the requested thread.

For every route that loads a specific thread, I will check:

$thread->coach_id === $user->id || $thread->client_id === $user->id

If not, return 403.

This prevents an authenticated user from guessing a thread id and reading private messages from another coach-client pair.

Role checks

Coach-only routes:

GET /api/coach/threads
POST /api/coach/threads
DELETE /api/coach/threads/{id}

These require:

auth()->user()->role === 'coach'

Client-only route:

GET /api/client/threads

This requires:

auth()->user()->role === 'client'

Shared routes:

GET /api/threads/{id}
POST /api/threads/{id}/messages
PATCH /api/threads/{id}/messages/{msg}/read

These require the authenticated user to be a participant in the thread.

Coach thread list query

Endpoint:

GET /api/coach/threads

This is the most query-sensitive endpoint.

It needs to return:

thread id
subject
client name
latest message preview
latest message sent time
unread count

It must be ordered by latest message time, not thread creation time.

Avoiding N+1 queries

I will avoid loading the client, last message, or unread count one thread at a time.

The query will use:

eager loading for the client
eager loading for the latest message
aggregate/subquery for latest message timestamp
withCount for unread messages

Example approach:

$threads = MessageThread::query()
    ->where('coach_id', $coach->id)
    ->whereNull('archived_at')
    ->with('client:id,name')
    ->with('lastMessage:id,thread_id,body,created_at')
    ->withCount([
        'messages as unread_count' => function ($query) use ($coach) {
            $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', $coach->id);
        },
    ])
    ->addSelect([
        'last_message_sent_at' => Message::query()
            ->select('created_at')
            ->whereColumn('thread_id', 'message_threads.id')
            ->latest('created_at')
            ->limit(1),
    ])
    ->orderByDesc('last_message_sent_at')
    ->get();

This keeps the number of queries constant instead of running extra queries for every thread.

Unread count logic

For the coach thread list, unread messages are messages:

in that thread
with read_at set to null
where sender_id is not the authenticated coach

Query logic:

$query
    ->whereNull('read_at')
    ->where('sender_id', '!=', $coach->id);

Example:

A thread has 3 messages:

Client message, read_at: null
Coach message, read_at: null
Client message, read_at: null

The coach unread count is:

2

The coach’s own message does not count as unread for the coach, even though its read_at value is null.

Thread ordering logic

The coach thread list must be ordered by latest message activity.

Correct order:

ORDER BY last_message_sent_at DESC

The last_message_sent_at value comes from the newest message in the thread:

MAX(messages.created_at)

or an equivalent latest-message subquery.

Incorrect order:

ORDER BY message_threads.created_at DESC

That would sort by when the thread was created, not when the most recent message was sent.

API response formatting

For the coach thread list, each item should look like:

{
  "id": 3,
  "subject": "Monday check-in",
  "client": {
    "name": "Sam Nguyen"
  },
  "last_message": {
    "body": "Ready for Monday's session — should I bring my training log?",
    "sent_at": "2026-06-15T14:23:00Z"
  },
  "unread_count": 2
}

The latest message body preview will be limited to the first 100 characters.

Example:

Str::limit($thread->lastMessage->body, 100, '')

I will use API Resources or carefully shaped JSON arrays so the response matches the required structure.

Duplicate thread handling

The brief says there can only be one thread for a given coach-client pair.

This means the unique identity of a thread is:

coach_id + client_id

The subject does not matter for uniqueness.

If Coach A already has a thread with Client B, another request from Coach A for Client B should return 422, even if the new subject is different.

I will enforce this in two places:

1. Validation / application logic

Before creating the thread, check:

MessageThread::where('coach_id', $coach->id)
    ->where('client_id', $clientId)
    ->exists();

If it exists, return a validation error.

2. Database constraint

The database will also enforce:

$table->unique(['coach_id', 'client_id']);

This protects against race conditions where two requests try to create the same thread at the same time.

Endpoint behaviour
GET /api/coach/threads

Authenticated coach lists their unarchived threads.

Behaviour:

require role coach
filter by coach_id = auth()->id()
exclude rows where archived_at is not null
include client name
include last message preview
include last message sent time
include unread count
order by latest message sent time descending
avoid N+1 queries
POST /api/coach/threads

Authenticated coach creates a thread with a client.

Behaviour:

require role coach
validate client_id
validate subject
make sure selected user is a client
make sure coach is not creating a thread with themselves
make sure thread does not already exist for same coach-client pair
create the thread
return JSON response
DELETE /api/coach/threads/{id}

Authenticated coach archives a thread.

Behaviour:

require role coach
require thread belongs to authenticated coach
set archived_at = now()
do not delete the row
return success response

A direct database check should show:

the row still exists
archived_at is populated
GET /api/client/threads

Authenticated client lists their threads.

Behaviour:

require role client
filter by client_id = auth()->id()
return only threads the client participates in

The brief gives detailed response requirements for the coach list only. I will keep the client response simple but useful.

GET /api/threads/{id}

Authenticated coach or client views one thread.

Behaviour:

require authenticated user to be the coach or client on that thread
return 403 if not a participant
return messages ordered by created_at ASC
do not automatically mark messages as read
POST /api/threads/{id}/messages

Authenticated coach or client sends a message.

Behaviour:

require authenticated user to be a participant
validate body with StoreMessageRequest
create message using authenticated user as sender_id
set read_at to null
return created message
PATCH /api/threads/{id}/messages/{msg}/read

Authenticated coach or client marks a message as read.

Behaviour:

require authenticated user to be a participant
require message belongs to the provided thread
set read_at = now()

Decision:

The product wording says read_at means unread by the recipient. Ideally, the sender should not need to mark their own message as read. However, the acceptance criteria only says the endpoint sets read_at. I will at minimum enforce that the authenticated user is a participant and the message belongs to the thread.

If time allows, I will also prevent marking your own sent message as read because that better matches the product meaning of read_at.

Edge cases and how I will handle them
Duplicate coach-client thread

Problem:

A coach tries to create a second thread with the same client.

Handling:

return 422
validation checks existing thread
database unique constraint also prevents duplicates
Different subject for same coach-client pair

Problem:

Coach tries to create another thread with same client but a different subject.

Handling:

still return 422
subject is not part of the unique rule
Coach tries to create thread with themselves

Problem:

client_id is the same as the authenticated coach id.

Handling:

return 422
Coach tries to create thread with another coach

Problem:

client_id belongs to a user whose role is coach.

Handling:

return 422
selected user must have role client
Authenticated user tries to view someone else’s thread

Problem:

A logged-in user guesses a thread id.

Handling:

return 403
every thread-specific endpoint checks participant ownership
Coach tries to read another coach’s thread

Problem:

Coach A calls GET /api/threads/{id} for Coach B’s thread.

Handling:

return 403
Client tries to read unrelated thread

Problem:

Client A calls GET /api/threads/{id} for a thread between another coach and another client.

Handling:

return 403
Coach thread list unread count includes coach’s own messages

Problem:

A coach message has read_at: null, and a naive count would count it as unread.

Handling:

count only messages where read_at is null and sender_id != coach_id
Thread list ordered by thread creation date

Problem:

Older thread with a newer message should appear before newer thread with no recent message.

Handling:

order by latest message timestamp
do not order by message_threads.created_at
Thread with no messages

Problem:

The brief does not clearly say whether thread creation includes an initial message.

Handling:

allow a thread to exist without messages
last_message may be null
threads with no messages should appear after threads with messages
if needed, use thread created_at only as a fallback, but the main ordering is latest message time
Archived thread

Problem:

The coach archives a thread.

Handling:

set archived_at
do not delete row
hide archived threads from GET /api/coach/threads
keep row available in the database
Creating a new thread after archiving old one

Problem:

Coach archives a thread and then tries to create another thread with the same client.

Handling:

still return 422
the unique constraint applies regardless of archived_at
the brief says there can only be one thread between a coach-client pair
Message route mismatch

Problem:

A user calls:

PATCH /api/threads/1/messages/99/read

but message 99 belongs to another thread.

Handling:

return 404 or 403
implementation should query the message through the thread:
$message = $thread->messages()->findOrFail($messageId);

This prevents marking a message from a different thread.

Long message body

Problem:

Message body is over 5000 characters.

Handling:

StoreMessageRequest returns 422
Empty message body

Problem:

Message body is missing or empty.

Handling:

StoreMessageRequest returns 422
Decisions for unclear parts of the brief
Does creating a thread also create a first message?

Decision:

No. The create thread endpoint only requires client_id and subject.

Messages are created separately through:

POST /api/threads/{id}/messages
Should viewing a thread automatically mark messages as read?

Decision:

No.

There is a dedicated read endpoint, so viewing and marking as read are separate actions.

Should archived threads appear in coach thread list?

Decision:

No.

archived_at is described as a soft delete for the coach, so archived threads should be hidden from the coach’s normal list.

Should clients still see archived threads?

Decision:

Probably yes, unless the brief says otherwise.

archived_at is described as a coach soft delete, not a global delete.

Should the client list response match the coach list response?

Decision:

The brief only specifies the exact shape for the coach thread list. I will keep the client list simple and focused on returning only the client’s own threads. If practical, I will reuse a similar resource shape for consistency.