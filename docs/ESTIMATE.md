Step 1

    Project set up
                1. Start new Laravel project
                2. connect to Github repo
                                                                                                    10 mins

----------------------------------------------------------------------------------------------------------------

Step 2

    Documentation
                1. Write out the Understand.md
                2. Write out the Time Estimate.md
                3. Add the Ai Time estimate to the Estimate.md
                4. Write out the Aproach.md
                                                                                                        120 mins

----------------------------------------------------------------------------------------------------------------

Step 3

    Finish Project set up
                1. Install dependencies
                2. Install Sanctum
                3. Install Pest
                4. Confirm API/auth setup
                                                                                                    20 mins

----------------------------------------------------------------------------------------------------------------

Step 4 

    Write Tests first

                Write feature tests for:
                    Coach can create a thread with a client.
                    Coach cannot create a duplicate thread with same client.
                    Database prevents duplicate coach-client pair.
                    Coach cannot create thread with themselves.
                    Coach thread list is ordered by latest message time.
                    Coach thread list unread count ignores coach’s own unread messages.
                    Coach thread list avoids N+1 queries.
                    User cannot view thread they do not participate in.
                    Coach cannot read another coach’s thread.
                    Client cannot read unrelated thread.
                    Messages in thread are oldest first.
                    Message body is required.
                    Message body max is 5000 chars.
                    Mark read sets read_at.
                    Archive sets archived_at but row still exists.
                    Client thread list only returns threads where user is client.
                                                                                                    90 mins

----------------------------------------------------------------------------------------------------------------

Step 5 

        Database migrations

                1. Create migration to add role to users.
                            role string or enum-like string.
                            Default maybe client, or no default depending on tests.
                
                2. Create message_threads table.
                            id
                            coach_id
                            client_id
                            subject
                            archived_at
                            timestamps
                
                3. Add foreign keys:
                            coach_id references users.id
                            client_id references users.id

                4. Add unique constraint
                            $table->unique(['coach_id', 'client_id']);

                5. Create messages table.
                            id
                            thread_id
                            sender_id
                            body
                            read_at
                            timestamps
                
                6. Add foreign keys:
                            thread_id references message_threads.id
                            sender_id references users.id
                                                                                                    35 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 6

        Models and relationships
                        
                1. Create MessageThread model.
                2. Create Message model.

                3. User relationships
                            coachThreads()
                            clientThreads()
                            messages()
                
                4. MessageThread relationships
                            coach()
                            client()
                            messages()
                            lastMessage()
                
                5. Message relationships
                            thread()
                            sender()
                                                                                                    45 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 7

        FormRequests / validation

                1. Create StoreThreadRequest.
                            client_id required.
                            client_id exists in users.
                            selected user must be a client.
                            client cannot be same as authenticated coach.
                            duplicate coach-client pair returns 422.
                            subject required.
                            subject max 150.
                
                2. Create StoreMessageRequest
                            body required.
                            body max 5000.
                                                                                                    40 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 8

        Authorization

                1. Add checks for coach-only endpoints.
                            GET /api/coach/threads
                            POST /api/coach/threads
                            DELETE /api/coach/threads/{id}
                
                2. Add checks for client-only endpoint.
                            GET /api/client/threads

                3. Add participant checks for shared endpoints.
                            GET /api/threads/{id}
                            POST /api/threads/{id}/messages
                            PATCH /api/threads/{id}/messages/{msg}/read

                4. Return 403 when the user is authenticated but not allowed to access that thread.    
                                                                                                    40 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 9

        Routes

                1. Add routes inside routes/api.php with auth:sanctum.
                                                                                                    30 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 10

        Controllers

                1. Create controller or controllers, for example:
                        CoachThreadController
                        ClientThreadController
                        ThreadController
                        MessageController
                
                2. Coach thread controller
                        List coach threads.
                        Create new coach-client thread.
                        Archive coach thread.

                3. Client thread controller
                        List threads where authenticated user is the client.

                4. Thread controller
                        Show a thread.
                        Return messages oldest first.

                5. Message controller
                        Store message.
                        Mark message as read.
                                                                                                    50 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 11

        Coach thread list query

                1. Build GET /api/coach/threads so it returns:
                        thread id
                        subject
                        client name
                        last message preview
                        last message sent time
                        unread count
                
                2. Avoid N+1 queries by using:
                        eager loading for client
                        eager loading or aggregate for lastMessage
                        withCount or subquery for unread count
                        subquery / aggregate for latest message time
                                                                                                    30 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 12

        Thread creation logic

                1. Confirm authenticated user is a coach.
                2. Validate selected client_id.
                3. Confirm selected user role is client.
                4. Confirm coach_id !== client_id.
                5. Check no existing thread with same coach_id + client_id.
                6. Create thread.
                7. Return created thread JSON.
                8. Handle duplicate database constraint safely as 422 if needed.
                                                                                                    45 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 13

        Send message logic

                1. Confirm authenticated user is part of the thread.
                2. Validate message body.
                3. Create message with:
                        thread_id
                        sender_id = auth()->id()
                        body
                        read_at = null
                
                4. Return message JSON.
                                                                                                    45 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 14

        Mark read logic

                1. Confirm thread exists.
                2. Confirm message belongs to thread.
                3. Confirm authenticated user is part of thread.
                                                                                                    35 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 15

        Archive thread logic

                1. Confirm authenticated user is a coach.
                2. Confirm thread belongs to that coach.
                                                                                                    25 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 16

        Test factories

                1. Update UserFactory to support roles.
                        coach state
                        client state
                
                2. Create MessageThreadFactory.
                3. Create MessageFactory.
                                                                                                    30 mins
                                                                                                    
----------------------------------------------------------------------------------------------------------------

Step 17

        API response formatting

                1. Decide whether to use API Resources or direct JSON arrays.
                2. Format coach thread list exactly enough for tests:
                                id
                                subject
                                client.name
                                last_message.body
                                last_message.sent_at
                                unread_count
                
                3. Limit last message body to first 100 characters.
                                                                                                    30 mins

----------------------------------------------------------------------------------------------------------------

Step 18

    BEFORE-AFTER.md
                                                                                                    30 mins

----------------------------------------------------------------------------------------------------------------

                                                                                                    12.5 hrs

---------------------------------------------------------------------------------------------------------------- 

AI estimate

AI estimate: 11–12 hours

The AI estimate is slightly lower because the app is small and the endpoints are straightforward once the schema and relationships are correct. However, this task has several edge cases that need careful tests, especially authorization, unread counts, and the optimized thread list query.
