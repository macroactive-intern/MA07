--------------------------------------------------------------------------

  Before

--------------------------------------------------------------------------


  ✓ coach cannot create a thread with themselves as the client                                                                               0.01s  
  ✓ archive sets archived_at but row still exists                                                                                            0.01s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                                            0.02s  

   PASS  Tests\Feature\MessageTest
  ✓ message body is required                                                                                                                 0.02s  
  ✓ message body max is 5000 characters                                                                                                      0.01s  
  ✓ mark read sets read_at on the message                                                                                                    0.01s  

   PASS  Tests\Feature\ThreadAccessTest
  ✓ user cannot view a thread they do not participate in                                                                                     0.01s  
  ✓ coach cannot read another coach's thread                                                                                                 0.01s  
  ✓ client cannot read an unrelated thread                                                                                                   0.02s  
  ✓ messages in thread are returned oldest first                                                                                             0.01s  
  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\CoachThreadListTest > coach thread list is ordered by latest message time                                                  
  Expected response status code [200] but received 500.
Failed asserting that 500 is identical to 200.

----------------------------------------------------------------------------------

SQLSTATE[HY000]: General error: 1 ambiguous column name: thread_id (Connection: sqlite, Database: :memory:, SQL: select "id", "thread_id", "body", "created_at" from "messages" inner join (select MAX("messages"."id") as "id_aggregate", "messages"."thread_id" from "messages" where "messages"."thread_id" in (1) group by "messages"."thread_id") as "latestOfMany" on "latestOfMany"."id_aggregate" = "messages"."id" and "latestOfMany"."thread_id" = "messages"."thread_id")

  at tests\Feature\CoachThreadListTest.php:58
     54▕     ]);
     55▕ 
     56▕     $response = $this->actingAs($coach)->getJson('/api/coach/threads');
     57▕ 
  ➜  58▕     $response->assertStatus(200);
     59▕     expect($response->json('0.unread_count'))->toBe(3);
     60▕ });
     61▕ 
     62▕ test('coach thread list avoids N+1 queries', function () {

  ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\CoachThreadListTest > coach thread list avoids N+1 queries                                                                 
  Expected response status code [200] but received 500.
Failed asserting that 500 is identical to 200.

SQLSTATE[HY000]: General error: 1 ambiguous column name: thread_id (Connection: sqlite, Database: :memory:, SQL: select "id", "thread_id", "body", "created_at" from "messages" inner join (select MAX("messages"."id") as "id_aggregate", "messages"."thread_id" from "messages" where "messages"."thread_id" in (1, 2, 3, 4, 5) group by "messages"."thread_id") as "latestOfMany" on "latestOfMany"."id_aggregate" = "messages"."id" and "latestOfMany"."thread_id" = "messages"."thread_id")

  at tests\Feature\CoachThreadListTest.php:78
     74▕         ]);
     75▕     }
     76▕ 
     77▕     DB::enableQueryLog();
  ➜  78▕     $this->actingAs($coach)->getJson('/api/coach/threads')->assertStatus(200);
     79▕     $queryCount = count(DB::getQueryLog());
     80▕     DB::disableQueryLog();
     81▕ 
     82▕     expect($queryCount)->toBeLessThanOrEqual(5);


  Tests:    3 failed, 15 passed (30 assertions)
  Duration: 0.66s

--------------------------------------------------------------------------

  After

--------------------------------------------------------------------------

 PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\ClientThreadListTest
  ✓ client thread list only returns threads where the user is the client                                                                     0.22s  

   PASS  Tests\Feature\CoachThreadListTest
  ✓ coach thread list is ordered by latest message time                                                                                      0.02s  
  ✓ coach thread list unread count ignores coach own messages                                                                                0.01s  
  ✓ coach thread list avoids N+1 queries                                                                                                     0.02s  

   PASS  Tests\Feature\CoachThreadTest
  ✓ coach can create a thread with a client                                                                                                  0.02s  
  ✓ coach cannot create a duplicate thread with the same client                                                                              0.01s  
  ✓ database prevents duplicate coach-client pair                                                                                            0.01s  
  ✓ coach cannot create a thread with themselves as the client                                                                               0.01s  
  ✓ archive sets archived_at but row still exists                                                                                            0.01s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                                            0.02s  

   PASS  Tests\Feature\MessageTest
  ✓ message body is required                                                                                                                 0.02s  
  ✓ message body max is 5000 characters                                                                                                      0.01s  
  ✓ mark read sets read_at on the message                                                                                                    0.01s  

   PASS  Tests\Feature\ThreadAccessTest
  ✓ user cannot view a thread they do not participate in                                                                                     0.01s  
  ✓ coach cannot read another coach's thread                                                                                                 0.01s  
  ✓ client cannot read an unrelated thread                                                                                                   0.01s  
  ✓ messages in thread are returned oldest first                                                                                             0.01s  

  Tests:    18 passed (35 assertions)
  Duration: 0.64s