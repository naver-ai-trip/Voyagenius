<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Model Unit Tests
 * 
 * Tests for User model attributes, relationships, and social auth integration.
 * Following TDD principles - these tests drive the implementation.
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can be created with basic attributes.
     */
    public function test_user_can_be_created_with_basic_attributes(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->assertNotNull($user->id);
        $this->assertEquals('Test User', $user->name);
    }

    /**
     * Test user can be created with social auth provider fields.
     */
    public function test_user_can_be_created_with_social_auth_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'NAVER User',
            'email' => 'naver@example.com',
            'provider' => 'naver',
            'provider_id' => 'naver_12345',
            'naver_id' => 'naver_12345',
            'password' => null, // Social auth users may not have password
        ]);

        $this->assertDatabaseHas('users', [
            'provider' => 'naver',
            'provider_id' => 'naver_12345',
            'naver_id' => 'naver_12345',
        ]);

        $this->assertNull($user->password);
        $this->assertEquals('naver', $user->provider);
    }

    /**
     * Test user can have avatar path stored.
     */
    public function test_user_can_have_avatar_path(): void
    {
        $user = User::factory()->create([
            'avatar_path' => 'avatars/user123.jpg',
        ]);

        $this->assertEquals('avatars/user123.jpg', $user->avatar_path);
        $this->assertDatabaseHas('users', [
            'avatar_path' => 'avatars/user123.jpg',
        ]);
    }

    /**
     * Test user can have trip style preference.
     */
    public function test_user_can_have_trip_style(): void
    {
        $user = User::factory()->create([
            'trip_style' => 'adventure',
        ]);

        $this->assertEquals('adventure', $user->trip_style);
    }

    /**
     * Test user email must be unique.
     */
    public function test_user_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'unique@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'unique@example.com']);
    }

    /**
     * Test user naver_id must be unique when provided.
     */
    public function test_user_naver_id_must_be_unique(): void
    {
        User::factory()->create([
            'naver_id' => 'naver_unique_123',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create([
            'naver_id' => 'naver_unique_123',
        ]);
    }

    /**
     * Test user model has fillable attributes configured.
     */
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User();
        
        $fillable = $user->getFillable();
        
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('provider', $fillable);
        $this->assertContains('provider_id', $fillable);
        $this->assertContains('avatar_path', $fillable);
        $this->assertContains('trip_style', $fillable);
        $this->assertContains('naver_id', $fillable);
    }

    /**
     * Test user model hides sensitive attributes.
     */
    public function test_user_hides_sensitive_attributes(): void
    {
        $user = User::factory()->create();
        
        $array = $user->toArray();
        
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
    }
}
