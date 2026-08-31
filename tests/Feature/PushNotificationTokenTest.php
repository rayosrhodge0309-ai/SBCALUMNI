<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can save their firebase cloud messaging token', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('fcm-token.store'), [
            'token' => 'sample-fcm-token',
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Token saved',
        ]);

    expect($user->fresh()->fcm_token)->toBe('sample-fcm-token');
});
