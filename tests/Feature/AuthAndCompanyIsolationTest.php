<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthAndCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database using exact prompt data
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test 1: Guest is redirected to /login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/users');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/vendors');
        $response->assertRedirect('/login');
    }

    /**
     * Test 2: User can log in with correct credentials and not with a wrong password.
     */
    public function test_user_can_login_with_correct_credentials_and_fails_with_wrong_password(): void
    {
        // Wrong password fails
        $response = $this->post('/login', [
            'email' => 'admin.mum@acme.test',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Correct password succeeds
        $response = $this->post('/login', [
            'email' => 'admin.mum@acme.test',
            'password' => 'password',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Logout succeeds
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test 3: Dashboard shows the correct company and roles.
     */
    public function test_dashboard_shows_the_correct_company_and_roles(): void
    {
        $user = User::withoutGlobalScopes()->where('email', 'depthead.mum@acme.test')->firstOrFail();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee('Acme Mumbai Pvt Ltd');
        $response->assertSee('Maharashtra');
        $response->assertSee('Dept Head');
        $response->assertSee('Requester');
    }

    /**
     * Test 4: Requester gets 403 on /admin/users.
     */
    public function test_requester_gets_403_on_admin_users(): void
    {
        $requester = User::withoutGlobalScopes()->where('email', 'req.mum@acme.test')->firstOrFail();

        $response = $this->actingAs($requester)->get('/admin/users');

        $response->assertForbidden();
    }

    /**
     * Test 5: Admin Mumbai sees only Mumbai users and vendors.
     */
    public function test_admin_mumbai_sees_only_mumbai_users_and_vendors(): void
    {
        $adminMum = User::withoutGlobalScopes()->where('email', 'admin.mum@acme.test')->firstOrFail();

        $response = $this->actingAs($adminMum)->get('/admin/users');
        $response->assertOk();
        $response->assertSee('req.mum@acme.test');
        $response->assertSee('depthead.mum@acme.test');
        // Must NOT see Bengaluru users
        $response->assertDontSee('admin.blr@acme.test');
        $response->assertDontSee('req.blr@acme.test');

        // Check Vendors list
        $vendorResponse = $this->actingAs($adminMum)->get('/admin/vendors');
        $vendorResponse->assertOk();
        $vendorResponse->assertSee('Shree Steel Traders'); // V001 (MUM)
        $vendorResponse->assertSee('Bangalore Tech Supplies'); // V002 (MUM)
        $vendorResponse->assertSee('QuickClean Services'); // V003 (MUM)
        // Must NOT see Deccan Packaging (V004 - BLR)
        $vendorResponse->assertDontSee('Deccan Packaging');
    }

    /**
     * Test 6: Admin Bengaluru cannot open a Mumbai vendor by URL (expect 404 or 403).
     */
    public function test_admin_bengaluru_cannot_open_a_mumbai_vendor_by_url(): void
    {
        $adminBlr = User::withoutGlobalScopes()->where('email', 'admin.blr@acme.test')->firstOrFail();
        $mumbaiVendor = Vendor::withoutGlobalScopes()->where('code', 'V001')->firstOrFail();

        // Route-model binding automatically fails with 404 because BelongsToCompany scopes the query
        $response = $this->actingAs($adminBlr)->get("/admin/vendors/{$mumbaiVendor->id}");

        // Asserts 404 Not Found (or 403 Forbidden via policy)
        $this->assertTrue(in_array($response->status(), [404, 403]), "Expected 404 or 403, got {$response->status()}");
    }

    /**
     * Test 7: A user with 2 roles (depthead.mum: Dept Head + Requester) passes both role checks.
     */
    public function test_user_with_two_roles_passes_both_role_checks(): void
    {
        $user = User::withoutGlobalScopes()->where('email', 'depthead.mum@acme.test')->firstOrFail();

        $this->assertTrue($user->hasRole('Dept Head'));
        $this->assertTrue($user->hasRole('Requester'));
        $this->assertFalse($user->hasRole('Admin'));

        $this->assertTrue($user->hasAnyRole(['Dept Head', 'Admin']));
        $this->assertTrue($user->hasAnyRole(['Requester']));
        $this->assertFalse($user->hasAnyRole(['Admin', 'CFO']));
    }

    /**
     * Additional Test: Gates and Policy checks
     */
    public function test_gates_and_vendor_policy_authorize_correctly(): void
    {
        $admin = User::withoutGlobalScopes()->where('email', 'admin.mum@acme.test')->firstOrFail();
        $procurement = User::withoutGlobalScopes()->where('email', 'proc.mum@acme.test')->firstOrFail();
        $requester = User::withoutGlobalScopes()->where('email', 'req.mum@acme.test')->firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($procurement)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($requester)->allows('manage-users'));

        $this->assertTrue(Gate::forUser($admin)->allows('manage-vendors'));
        $this->assertTrue(Gate::forUser($procurement)->allows('manage-vendors'));
        $this->assertFalse(Gate::forUser($requester)->allows('manage-vendors'));

        $mumVendor = Vendor::withoutGlobalScopes()->where('code', 'V001')->firstOrFail();
        $this->assertTrue(Gate::forUser($admin)->allows('view', $mumVendor));
        $this->assertTrue(Gate::forUser($procurement)->allows('view', $mumVendor));

        $blrAdmin = User::withoutGlobalScopes()->where('email', 'admin.blr@acme.test')->firstOrFail();
        $this->assertFalse(Gate::forUser($blrAdmin)->allows('view', $mumVendor));
    }
}
