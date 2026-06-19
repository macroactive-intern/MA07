What I need to build

This task is asking me to build a Laravel JSON API for one-to-one messaging between coaches and clients.

A coach can create a message thread with a specific client. Each thread belongs to exactly one coach and exactly one client. There can only be one thread for the same coach-client pair.

Threads contain messages. Messages are returned in chronological order when viewing a thread. Coaches also need a thread list ordered by recent activity, where the most recently messaged thread appears first.

The coach thread list needs to show:

        Thread id
        Thread subject
        Client name
        Preview of the latest message body, limited to the first 100 characters
        Latest message sent time
        unread_count for the authenticated coach

--------------------------------------------------------------------------------------------------------------------------------------------------

Data I need to store

-------------------------------------------

        users

The normal Laravel users table needs an extra role column.

Allowed roles:

coach
client

-------------------------------------------

        message_threads

Columns:

id
coach_id
client_id
subject
archived_at
created_at
updated_at

Important rules:

coach_id references users.id
client_id references users.id
subject is a string with max length 150
archived_at is nullable
There must be at most one thread for the same coach_id and client_id
A coach cannot create a thread with themselves as the client


-------------------------------------------

        messages

Columns:

id
thread_id
sender_id
body
read_at
created_at
updated_at

Important rules:

thread_id references message_threads.id
sender_id references users.id
body is required and max 5000 characters
read_at is nullable
read_at: null means the message has not been read by the recipient, not necessarily by every user

--------------------------------------------------------------------------------------------------------------------------------------------------

Endpoints and expected behaviour

GET /api/coach/threads

Authenticated coach lists their own threads.

Returns threads where:

coach_id is the authenticated user
archived threads should not appear in the normal coach list

The response includes:

id
subject
client.name
last_message.body
last_message.sent_at
unread_count

-------------------------------------------

GET /api/client/threads

Authenticated client lists only threads where they are the client.

Returns only threads where:

client_id = auth()->id()

-------------------------------------------

GET /api/threads/{id}

Authenticated coach or client views a thread and its messages.

The user can view the thread only if they are either:

the thread’s coach, or
the thread’s client

-------------------------------------------

POST /api/coach/threads

Authenticated coach creates a new thread with a client.

Expected input:

{
  "client_id": 2,
  "subject": "Monday check-in"
}

-------------------------------------------

POST /api/threads/{id}/messages

Authenticated coach or client sends a message in a thread.

Expected input:

{
  "body": "Ready for Monday's session."
}

Validation:

body is required
body max length is 5000 characters

-------------------------------------------

PATCH /api/threads/{id}/messages/{msg}/read

Marks a message as read by setting read_at to the current timestamp.

Important checks:

the authenticated user must be part of the thread
the message must belong to the given thread
read_at should be set to now

-------------------------------------------

DELETE /api/coach/threads/{id}

Authenticated coach archives a thread.

This does not delete the row.

It sets:

archived_at = now()

--------------------------------------------------------------------------------------------------------------------------------------------------

Response to the senior dev note

The senior dev note says:

        “Since auth:sanctum is applied globally to the API, you don't need to add explicit per-thread authorization checks — the authentication middleware already ensures only authenticated users can access the API.”

I disagree with this note.

auth:sanctum only proves the user is logged in. It does not prove the user owns or participates in the specific thread they are requesting.

Without per-thread authorization checks, an authenticated user could guess or change the thread id in the URL and access someone else’s private conversation.

--------------------------------------------------------------------------------------------------------------------------------------------------

How unread_count works

For the coach thread list, unread_count should count only messages where:

the message belongs to the thread
read_at is null
sender_id is not the authenticated coach’s id

So this should not simply count every message with read_at: null

--------------------------------------------------------------------------------------------------------------------------------------------------

Required unread_count scenario

A thread has 3 messages:

Message 1 from Client, read_at: null
Message 2 from Coach, read_at: null
Message 3 from Client, read_at: null

For the Coach, unread_count should be:

2

Reason:

Message 1 counts because it was sent by the client and has not been read by the coach.
Message 2 does not count because the coach sent it. Even if read_at is null, it is not unread for the coach.
Message 3 counts because it was sent by the client and has not been read by the coach.

--------------------------------------------------------------------------------------------------------------------------------------------------

How thread list ordering works

The coach thread list must be ordered by the time of the most recent message in each thread.

The order should not use:

message_threads.created_at

because a thread may have been created earlier but received a newer message.

The ordering value should come from the latest related message:

MAX(messages.created_at)

or from a latest message relationship using created_at.

The final order should be:

ORDER BY last_message_sent_at DESC

where last_message_sent_at comes from the newest message in that thread.

--------------------------------------------------------------------------------------------------------------------------------------------------

What happens if a coach creates a second thread with the same client

If Coach A already has a thread with Client B, and Coach A tries to create another thread with Client B, the API should return 422.

This should be enforced in two ways:

Request validation / service logic should detect the existing pair and return a validation error.
A database unique constraint should prevent duplicate rows even if two requests happen at the same time.

--------------------------------------------------------------------------------------------------------------------------------------------------

What seemed unclear or under-specified

Can a thread be created without an initial message?

threads with messages are ordered by latest message time
threads with no messages should appear after threads with messages, or use thread created_at only as a fallback if needed

--------------------------------------------------------------------------------------------------------------------------------------------------

Should archived threads appear in GET /api/coach/threads?

The column says:

archived_at — nullable — soft delete for coach

Because this is a coach archive, I assume archived threads should be hidden from the coach’s normal thread list.

I also assume the row still exists and the client may still see the thread unless the product says otherwise.

--------------------------------------------------------------------------------------------------------------------------------------------------

Can a coach create a new thread with the same client after archiving the old one?

So if a thread exists with archived_at populated, creating another thread for the same pair should still return 422.

--------------------------------------------------------------------------------------------------------------------------------------------------

Who is allowed to mark a message as read?

The endpoint says to mark a message as read, but does not explicitly say whether the sender can mark their own message as read.

Because read_at means unread by recipient, I assume the recipient should be the one marking it as read.

So I will at minimum require that the authenticated user is a participant in the thread, and preferably require that they are not the sender of the message.

--------------------------------------------------------------------------------------------------------------------------------------------------

Should viewing a thread automatically mark messages as read?

Because of that, I assume GET /api/threads/{id} should not automatically update read_at.

Reading messages and marking messages as read are separate actions.

--------------------------------------------------------------------------------------------------------------------------------------------------

What should the client thread list include?

GET /api/client/threads should return a similar list of threads the client participates in

--------------------------------------------------------------------------------------------------------------------------------------------------

Should role checks be explicit?

/api/coach/* endpoints require role = coach
/api/client/* endpoints require role = client
shared thread/message endpoints require participant authorization, regardless of role