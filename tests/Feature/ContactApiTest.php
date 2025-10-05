<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to authenticate a fresh user with Sanctum.
     */
    private function asUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_requires_auth(): void
    {
        // No Sanctum::actingAs() here — intentionally unauthenticated
        $this->get('/api/contacts')->assertUnauthorized();
        $this->post('/api/contacts', [])->assertUnauthorized();
        $this->patch('/api/contacts/anything', [])->assertUnauthorized();
        $this->delete('/api/contacts/anything')->assertUnauthorized();
    }

    public function test_lists_only_my_contacts(): void
    {
        $me = $this->asUser();
        $them = User::factory()->create();

        Contact::factory()->count(2)->create(['user_id' => $me->id]);
        Contact::factory()->count(3)->create(['user_id' => $them->id]);

        $res = $this->getJson('/api/contacts')->assertOk()->json();

        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        foreach ($res as $row) {
            $this->assertEquals($me->id, $row['user_id']);
        }
    }

    public function test_creates_a_contact(): void
    {
        $user = $this->asUser();

        $payload = [
            'name' => 'Jane Doe',
            'phone' => '+255700000001',
            'email' => 'jane@example.com',
            'version' => 0,
        ];

        $this->postJson('/api/contacts', $payload)
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Jane Doe',
                'phone' => '+255700000001',
                'email' => 'jane@example.com',
            ]);

        $this->assertDatabaseHas('contacts', [
            'user_id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_updates_a_contact_with_version_check(): void
    {
        $user = $this->asUser();

        /** @var Contact $c */
        $c = Contact::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'phone' => '111',
            'email' => 'old@example.com',
            'version' => 1,
        ]);

        $this->patchJson("/api/contacts/{$c->id}", [
            'name' => 'New Name',
            'version' => 1, // client has current version
        ])->assertOk()->assertJsonFragment([
                    'name' => 'New Name',
                ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $c->id,
            'name' => 'New Name',
            'version' => 2, // bumped
        ]);
    }

    public function test_returns_409_conflict_if_client_version_is_behind(): void
    {
        $user = $this->asUser();

        /** @var Contact $c */
        $c = Contact::factory()->create([
            'user_id' => $user->id,
            'name' => 'Server Name',
            'version' => 3, // server ahead
        ]);

        $this->patchJson("/api/contacts/{$c->id}", [
            'name' => 'Client Name',
            'version' => 1, // stale client version
        ])->assertStatus(409);
    }

    public function test_soft_deletes_a_contact(): void
    {
        $user = $this->asUser();

        /** @var Contact $c */
        $c = Contact::factory()->create([
            'user_id' => $user->id,
            'name' => 'To Delete',
            'version' => 0,
        ]);

        $this->deleteJson("/api/contacts/{$c->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('contacts', ['id' => $c->id]);
    }

    public function test_blocks_access_to_others_contacts(): void
    {
        $me = $this->asUser();
        $them = User::factory()->create();

        $theirContact = Contact::factory()->create([
            'user_id' => $them->id,
            'name' => 'Private',
            'version' => 0,
        ]);

        $this->getJson("/api/contacts/{$theirContact->id}")->assertStatus(403);
        $this->patchJson("/api/contacts/{$theirContact->id}", ['name' => 'X', 'version' => 0])->assertStatus(403);
        $this->deleteJson("/api/contacts/{$theirContact->id}")->assertStatus(403);
    }
}
