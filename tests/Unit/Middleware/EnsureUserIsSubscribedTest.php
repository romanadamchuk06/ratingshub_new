<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserIsSubscribed;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * ENSURE USER IS SUBSCRIBED MIDDLEWARE TESTS
 * ===========================================
 *
 * Testet das EnsureUserIsSubscribed Middleware:
 * - Admin hat immer Zugriff
 * - User mit plan_id hat Zugriff
 * - User mit Trial hat Zugriff
 * - User ohne Abo wird weitergeleitet
 */
class EnsureUserIsSubscribedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Admin hat immer Zugriff (auch ohne Abo)
     */
    public function test_admin_always_has_access()
    {
        // Arrange: Admin ohne Abo/Plan
        $admin = User::factory()->create([
            'is_admin' => true,
            'plan_id' => null,
            'trial_ends_at' => null,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $admin);

        $middleware = new EnsureUserIsSubscribed();

        // Act
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert: Admin wurde durchgelassen
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test: User mit plan_id hat Zugriff
     */
    public function test_user_with_plan_id_has_access()
    {
        // Arrange: User mit zugewiesenem Plan
        $plan = Plan::factory()->create();
        $user = User::factory()->create([
            'is_admin' => false,
            'plan_id' => $plan->id,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new EnsureUserIsSubscribed();

        // Act
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert: User wurde durchgelassen
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test: User mit aktivem Trial hat Zugriff
     */
    public function test_user_with_trial_has_access()
    {
        // Arrange: User im Trial (ohne Plan)
        $user = User::factory()->create([
            'is_admin' => false,
            'plan_id' => null,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new EnsureUserIsSubscribed();

        // Act
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert: User wurde durchgelassen
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test: User ohne Abo wird zu /subscription weitergeleitet
     */
    public function test_user_without_subscription_is_redirected()
    {
        // Arrange: User ohne Abo, Plan oder Trial
        $user = User::factory()->create([
            'is_admin' => false,
            'plan_id' => null,
            'trial_ends_at' => null,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new EnsureUserIsSubscribed();

        // Act
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert: User wird weitergeleitet
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect(route('subscription.index')));
    }

    /**
     * Test: User mit abgelaufenem Trial hat KEINEN Zugriff
     */
    public function test_user_with_expired_trial_has_no_access()
    {
        // Arrange: User mit abgelaufenem Trial
        $user = User::factory()->create([
            'is_admin' => false,
            'plan_id' => null,
            'trial_ends_at' => now()->subDays(1), // Trial vor 1 Tag abgelaufen
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);

        $middleware = new EnsureUserIsSubscribed();

        // Act
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        // Assert: User wird weitergeleitet
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect(route('subscription.index')));
    }
}
