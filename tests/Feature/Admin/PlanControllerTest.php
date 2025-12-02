<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PLAN CONTROLLER FEATURE TESTS
 * ==============================
 *
 * Testet den Admin PlanController:
 * - Index (Liste aller Pläne)
 * - Store (Plan erstellen)
 * - Update (Plan aktualisieren)
 * - Delete (Plan löschen)
 * - Toggle Active/Popular
 */
class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Admin kann Plans-Liste sehen
     */
    public function test_admin_can_view_plans_index()
    {
        // Arrange: Admin User erstellen
        $admin = User::factory()->create(['is_admin' => true]);
        Plan::factory()->count(3)->create();

        // Act: Als Admin einloggen und Index aufrufen
        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        // Assert: Erfolgreicher Response mit allen Plans
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Plans/Index')
            ->has('plans', 3)
        );
    }

    /**
     * Test: Nicht-Admin kann Plans-Index NICHT sehen
     */
    public function test_non_admin_cannot_view_plans_index()
    {
        // Arrange: Normaler User (kein Admin)
        $user = User::factory()->create(['is_admin' => false]);

        // Act: Als User einloggen und Index aufrufen
        $response = $this->actingAs($user)->get(route('admin.plans.index'));

        // Assert: Zugriff verweigert (403 Forbidden)
        $response->assertStatus(403);
    }

    /**
     * Test: Admin kann neuen Plan erstellen
     */
    public function test_admin_can_create_new_plan()
    {
        // Arrange: Admin User
        $admin = User::factory()->create(['is_admin' => true]);

        // Act: Plan erstellen
        $response = $this->actingAs($admin)->post(route('admin.plans.store'), [
            'name' => 'Business Plan',
            'slug' => 'business',
            'stripe_plan_id' => 'price_123456',
            'price' => 49.99,
            'max_platforms' => 5,
            'description' => 'For growing businesses',
            'features' => ['AI Responses', 'Multi User', 'Priority Support'],
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 2,
        ]);

        // Assert: Redirect zu Index mit Success-Message
        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');

        // Assert: Plan wurde in DB gespeichert
        $this->assertDatabaseHas('plans', [
            'name' => 'Business Plan',
            'slug' => 'business',
            'price' => 49.99,
            'is_active' => true,
            'is_popular' => true,
        ]);
    }

    /**
     * Test: Plan-Erstellung validiert Required Fields
     */
    public function test_plan_creation_validates_required_fields()
    {
        // Arrange: Admin User
        $admin = User::factory()->create(['is_admin' => true]);

        // Act: Plan ohne Required Fields erstellen
        $response = $this->actingAs($admin)->post(route('admin.plans.store'), [
            'name' => '', // REQUIRED
            'slug' => '', // REQUIRED
            'price' => '', // REQUIRED
        ]);

        // Assert: Validation Errors
        $response->assertSessionHasErrors(['name', 'slug', 'price']);
    }

    /**
     * Test: Admin kann Plan aktualisieren
     */
    public function test_admin_can_update_plan()
    {
        // Arrange: Admin und existierender Plan
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = Plan::factory()->create([
            'name' => 'Old Name',
            'price' => 29.99,
        ]);

        // Act: Plan aktualisieren
        $response = $this->actingAs($admin)->patch(route('admin.plans.update', $plan), [
            'name' => 'New Name',
            'slug' => $plan->slug,
            'stripe_plan_id' => $plan->stripe_plan_id,
            'price' => 39.99,
            'max_platforms' => 3,
            'is_active' => true,
            'is_popular' => false,
        ]);

        // Assert: Redirect mit Success
        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');

        // Assert: DB wurde aktualisiert
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'New Name',
            'price' => 39.99,
        ]);
    }

    /**
     * Test: Admin kann Plan aktivieren/deaktivieren
     */
    public function test_admin_can_toggle_plan_active_status()
    {
        // Arrange: Admin und Plan
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = Plan::factory()->create(['is_active' => true]);

        // Act: Toggle Active
        $response = $this->actingAs($admin)->post(route('admin.plans.toggle-active', $plan));

        // Assert: Status wurde geändert
        $response->assertSessionHas('success');
        $plan->refresh();
        $this->assertFalse($plan->is_active);

        // Act: Nochmal togglen → wieder aktiv
        $this->actingAs($admin)->post(route('admin.plans.toggle-active', $plan));
        $plan->refresh();
        $this->assertTrue($plan->is_active);
    }

    /**
     * Test: Admin kann Plan als Popular markieren/entfernen
     */
    public function test_admin_can_toggle_plan_popular_status()
    {
        // Arrange: Admin und Plan
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = Plan::factory()->create(['is_popular' => false]);

        // Act: Toggle Popular
        $response = $this->actingAs($admin)->post(route('admin.plans.toggle-popular', $plan));

        // Assert: Status wurde geändert
        $response->assertSessionHas('success');
        $plan->refresh();
        $this->assertTrue($plan->is_popular);
    }

    /**
     * Test: Admin kann Plan NICHT löschen wenn User ihn nutzen
     */
    public function test_admin_cannot_delete_plan_with_users()
    {
        // Arrange: Admin, Plan mit Users
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = Plan::factory()->create();
        User::factory()->count(2)->create(['plan_id' => $plan->id]);

        // Act: Versuche Plan zu löschen
        $response = $this->actingAs($admin)->delete(route('admin.plans.destroy', $plan));

        // Assert: Error Message (Plan nicht gelöscht)
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    /**
     * Test: Admin kann Plan löschen wenn keine User ihn nutzen
     */
    public function test_admin_can_delete_plan_without_users()
    {
        // Arrange: Admin, Plan ohne Users
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = Plan::factory()->create(['slug' => 'test-plan']);

        // Act: Plan löschen
        $response = $this->actingAs($admin)->delete(route('admin.plans.destroy', $plan));

        // Assert: Success & Plan wurde gelöscht
        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    /**
     * Test: Free-Plan kann nicht gelöscht werden
     */
    public function test_admin_cannot_delete_free_plan()
    {
        // Arrange: Admin, Free-Plan
        $admin = User::factory()->create(['is_admin' => true]);
        $freePlan = Plan::factory()->create(['slug' => 'free']);

        // Act: Versuche Free-Plan zu löschen
        $response = $this->actingAs($admin)->delete(route('admin.plans.destroy', $freePlan));

        // Assert: Error & Plan noch in DB
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('plans', ['id' => $freePlan->id, 'slug' => 'free']);
    }
}
