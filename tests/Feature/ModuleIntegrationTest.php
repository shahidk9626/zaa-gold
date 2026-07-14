<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Referral;
use App\Models\SellOldGoldEnquiry;
use App\Models\FranchiseEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer1;
    protected $customer2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles & Permissions
        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        // 2. Retrieve roles
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $customerRole = Role::where('slug', 'customer')->first();

        // 3. Create Admin & Customer mock users
        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin.test.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);

        $this->customer1 = User::create([
            'name' => 'Customer One',
            'email' => 'cust1.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $this->customer2 = User::create([
            'name' => 'Customer Two',
            'email' => 'cust2.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test Referral Module routes
     */
    public function test_referral_module_endpoints(): void
    {
        $this->actingAs($this->admin);

        // 1. Access Index
        $response = $this->get(route('referrals.index'));
        $response->assertStatus(200);

        // 2. Access Create form
        $response = $this->get(route('referrals.create'));
        $response->assertStatus(200);

        // 3. Store Referral entry
        $data = [
            'referral_code' => 'TESTREF123',
            'referrer_customer_id' => $this->customer1->id,
            'referred_customer_id' => $this->customer2->id,
            'reward_type' => 'Cash',
            'reward_amount' => 1500.00,
            'reward_status' => 'Pending',
            'remarks' => 'Direct recommendation.'
        ];

        $response = $this->post(route('referrals.store'), $data);
        $response->assertRedirect(route('referrals.index'));
        $this->assertDatabaseHas('referrals', ['referral_code' => 'TESTREF123']);

        $referral = Referral::where('referral_code', 'TESTREF123')->first();

        // 4. Show Details
        $response = $this->get(route('referrals.show', $referral->id));
        $response->assertStatus(200);

        // 5. Update referral reward status
        $updateData = array_merge($data, [
            'reward_status' => 'Eligible',
            'remarks' => 'Linked to locked plan.'
        ]);
        $response = $this->post(route('referrals.update', $referral->id), $updateData);
        $response->assertRedirect(route('referrals.show', $referral->id));
        $this->assertDatabaseHas('referrals', [
            'id' => $referral->id,
            'reward_status' => 'Eligible'
        ]);

        // 6. Export to CSV
        $response = $this->get(route('referrals.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=Referral_Report_' . now()->format('YmdHis') . '.csv');
    }

    /**
     * Test Sell Old Gold Enquiry endpoints
     */
    public function test_sell_old_gold_endpoints(): void
    {
        $this->actingAs($this->admin);

        // 1. Access Listing
        $response = $this->get(route('sell-old-gold.index'));
        $response->assertStatus(200);

        // 2. Log Enquiry
        $data = [
            'customer_name' => 'Alice Cooper',
            'mobile' => '9876543210',
            'email' => 'alice@cooper.com',
            'city' => 'Mumbai',
            'gold_type' => '22K',
            'estimated_weight' => 25.50,
            'estimated_value' => 125000.00,
            'remarks' => 'Scrap jewelry evaluation.',
            'status' => 'New',
            'followup_date' => now()->addDays(2)->format('Y-m-d'),
        ];
        $response = $this->post(route('sell-old-gold.store'), $data);
        $response->assertRedirect(route('sell-old-gold.index'));
        $this->assertDatabaseHas('sell_old_gold_enquiries', ['customer_name' => 'Alice Cooper']);

        $enquiry = SellOldGoldEnquiry::where('customer_name', 'Alice Cooper')->first();

        // 3. Show Details
        $response = $this->get(route('sell-old-gold.show', $enquiry->id));
        $response->assertStatus(200);

        // 4. Update status
        $response = $this->post(route('sell-old-gold.change_status', $enquiry->id), [
            'status' => 'Contacted',
            'remarks' => 'Spoke over phone. Scheduled visit.'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('sell_old_gold_enquiries', [
            'id' => $enquiry->id,
            'status' => 'Contacted'
        ]);

        // 5. Assign staff member
        $response = $this->post(route('sell-old-gold.assign', $enquiry->id), [
            'assigned_to' => $this->admin->id
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('sell_old_gold_enquiries', [
            'id' => $enquiry->id,
            'assigned_to' => $this->admin->id
        ]);

        // 6. Post Note
        $response = $this->post(route('sell-old-gold.add_note', $enquiry->id), [
            'note' => 'Customer wants instant cash payout.'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'sell_old_gold_enquiry',
            'record_id' => $enquiry->id,
            'action_type' => 'internal_note'
        ]);

        // 7. Delete Enquiry
        $response = $this->delete(route('sell-old-gold.destroy', $enquiry->id));
        $response->assertRedirect(route('sell-old-gold.index'));
        $this->assertSoftDeleted('sell_old_gold_enquiries', ['id' => $enquiry->id]);
    }

    /**
     * Test Franchise Enquiry endpoints
     */
    public function test_franchise_endpoints(): void
    {
        $this->actingAs($this->admin);

        // 1. Access Listing
        $response = $this->get(route('franchise.index'));
        $response->assertStatus(200);

        // 2. Log Enquiry
        $data = [
            'full_name' => 'Bob Builder',
            'mobile' => '9887766554',
            'email' => 'bob@builder.com',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'investment_budget' => '₹50L - ₹1Cr',
            'current_business' => 'Real Estate Developer',
            'message' => 'Interested in prime location showroom.',
            'status' => 'New',
            'followup_date' => now()->addDays(5)->format('Y-m-d'),
        ];
        $response = $this->post(route('franchise.store'), $data);
        $response->assertRedirect(route('franchise.index'));
        $this->assertDatabaseHas('franchise_enquiries', ['full_name' => 'Bob Builder']);

        $enquiry = FranchiseEnquiry::where('full_name', 'Bob Builder')->first();

        // 3. Show Details
        $response = $this->get(route('franchise.show', $enquiry->id));
        $response->assertStatus(200);

        // 4. Update status
        $response = $this->post(route('franchise.change_status', $enquiry->id), [
            'status' => 'Meeting Scheduled',
            'remarks' => 'Zoom call scheduled for proposal overview.'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('franchise_enquiries', [
            'id' => $enquiry->id,
            'status' => 'Meeting Scheduled'
        ]);

        // 5. Assign staff member
        $response = $this->post(route('franchise.assign', $enquiry->id), [
            'assigned_to' => $this->admin->id
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('franchise_enquiries', [
            'id' => $enquiry->id,
            'assigned_to' => $this->admin->id
        ]);

        // 6. Post Note
        $response = $this->post(route('franchise.add_note', $enquiry->id), [
            'note' => 'Bob has a 1200 sq.ft. commercial site.'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'franchise_enquiry',
            'record_id' => $enquiry->id,
            'action_type' => 'internal_note'
        ]);

        // 7. Delete Enquiry
        $response = $this->delete(route('franchise.destroy', $enquiry->id));
        $response->assertRedirect(route('franchise.index'));
        $this->assertSoftDeleted('franchise_enquiries', ['id' => $enquiry->id]);
    }

    /**
     * Test Reports & Analytics Dashboard
     */
    public function test_reports_dashboard_endpoints(): void
    {
        $this->actingAs($this->admin);

        // 1. Access Reports Dashboard
        $response = $this->get(route('reports.dashboard'));
        $response->assertStatus(200);

        // 2. Access specific report tab (e.g. customer)
        $response = $this->get(route('reports.dashboard', ['report' => 'customer']));
        $response->assertStatus(200);

        // 3. Export specific report to CSV
        $response = $this->get(route('reports.export', ['type' => 'customer']));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=Customer_Report_' . now()->format('YmdHis') . '.csv');
    }

    /**
     * Test Main Dashboard
     */
    public function test_main_dashboard_endpoint(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}
