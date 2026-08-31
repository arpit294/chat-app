<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $alice;
    protected User $bob;
    protected User $charlie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->bob = User::create([
            'name' => 'Bob Smith',
            'email' => 'bob@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->charlie = User::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * 1. Test that an authorized user can send a message and it is persisted in the database.
     */
    public function test_user_can_send_message_and_it_is_persisted(): void
    {
        // Create conversation between Alice and Bob
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([
            $this->alice->id => ['joined_at' => now(), 'last_read_at' => now()],
            $this->bob->id => ['joined_at' => now(), 'last_read_at' => null],
        ]);

        $response = $this->actingAs($this->alice)->postJson(route('messages.store', $conversation), [
            'body' => 'Hello Bob! This is a test message.',
            'type' => 'text',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => [
                'body' => 'Hello Bob! This is a test message.',
                'user_id' => $this->alice->id,
                'is_sender' => true,
            ],
        ]);

        // Assert message persisted in database
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->alice->id,
            'body' => 'Hello Bob! This is a test message.',
            'type' => 'text',
        ]);

        // Assert sender's last_read_at updated
        $alicePivot = $conversation->users()->where('users.id', $this->alice->id)->first()->pivot;
        $this->assertNotNull($alicePivot->last_read_at);
    }

    /**
     * 2. Test channel authorization: a user NOT in a conversation cannot subscribe to its presence channel.
     */
    public function test_user_not_in_conversation_cannot_authorize_presence_channel(): void
    {
        // Conversation between Alice and Bob
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([
            $this->alice->id => ['joined_at' => now(), 'last_read_at' => now()],
            $this->bob->id => ['joined_at' => now(), 'last_read_at' => null],
        ]);

        // Retrieve registered channel callback
        $channels = Broadcast::getChannels();
        $this->assertArrayHasKey('chat.{conversationId}', $channels);
        $channelCallback = $channels['chat.{conversationId}'];

        // Alice (member) is authorized and receives her profile payload
        $aliceAuth = $channelCallback($this->alice, $conversation->id);
        $this->assertIsArray($aliceAuth);
        $this->assertEquals($this->alice->id, $aliceAuth['id']);
        $this->assertEquals('Alice Johnson', $aliceAuth['name']);

        // Charlie (NOT a member) is denied authorization (returns false)
        $charlieAuth = $channelCallback($this->charlie, $conversation->id);
        $this->assertFalse($charlieAuth);
    }

    /**
     * 3. Use Event::fake() to assert MessageSent is dispatched with correct data on message creation.
     */
    public function test_message_sent_event_is_dispatched_with_correct_data(): void
    {
        Event::fake([MessageSent::class]);

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([
            $this->alice->id => ['joined_at' => now(), 'last_read_at' => now()],
            $this->bob->id => ['joined_at' => now(), 'last_read_at' => null],
        ]);

        $this->actingAs($this->alice)->postJson(route('messages.store', $conversation), [
            'body' => 'Real-time broadcasting test payload',
            'type' => 'text',
        ]);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation) {
            $payload = $event->broadcastWith();

            return $event->message->body === 'Real-time broadcasting test payload'
                && $event->message->user_id === $this->alice->id
                && $event->message->conversation_id === $conversation->id
                && $payload['sender']['id'] === $this->alice->id
                && $payload['sender']['name'] === 'Alice Johnson'
                && $event->broadcastOn()[0]->name === 'presence-chat.' . $conversation->id;
        });
    }

    /**
     * 4. Test that unauthorized users get a 403 when accessing another user's conversation.
     */
    public function test_unauthorized_user_gets_403_when_accessing_conversation(): void
    {
        // Conversation between Alice and Bob
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([
            $this->alice->id => ['joined_at' => now(), 'last_read_at' => now()],
            $this->bob->id => ['joined_at' => now(), 'last_read_at' => null],
        ]);

        // Charlie tries to view Alice & Bob's conversation -> 403 Forbidden
        $response = $this->actingAs($this->charlie)->get(route('conversations.show', $conversation));
        $response->assertStatus(403);

        // Charlie tries to send a message to Alice & Bob's conversation -> 403 Forbidden
        $sendResponse = $this->actingAs($this->charlie)->postJson(route('messages.store', $conversation), [
            'body' => 'Intruder message',
        ]);
        $sendResponse->assertStatus(403);
    }

    /**
     * 5. Test file attachment upload and storage.
     */
    public function test_user_can_send_message_with_attachment(): void
    {
        Storage::fake('public');

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([
            $this->alice->id => ['joined_at' => now(), 'last_read_at' => now()],
            $this->bob->id => ['joined_at' => now(), 'last_read_at' => null],
        ]);

        $file = UploadedFile::fake()->image('avatar_sample.png', 200, 200);

        $response = $this->actingAs($this->alice)->post(route('messages.store', $conversation), [
            'body' => 'Look at this photo',
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('conversations.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->alice->id,
            'type' => 'image',
            'body' => 'Look at this photo',
        ]);

        $this->assertDatabaseHas('message_attachments', [
            'original_name' => 'avatar_sample.png',
        ]);
    }
}
