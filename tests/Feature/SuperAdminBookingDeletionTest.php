<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BookingDelivery;
use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\BookingStatusHistory;
use App\Models\CancellationRequest;
use App\Models\CashCollectionRequest;
use App\Models\CustomerReferral;
use App\Models\EmiPlan;
use App\Models\GoldBooking;
use App\Models\GstInvoice;
use App\Models\PaymentTransaction;
use App\Models\PriceLockCertificate;
use App\Models\Product;
use App\Models\Referral;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminBookingDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $staff;
    protected User $customer;
    protected Product $product;
    protected EmiPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccessControlSeeder::class);

        $this->superAdmin = $this->makeUser('super-admin', 'Root Admin');
        $this->admin = $this->makeUser('admin', 'Regular Admin');
        $this->staff = $this->makeUser('staff', 'Staff Member');
        $this->customer = $this->makeUser('customer', 'Jane Customer');

        $this->product = Product::create([
            'name' => '10g 24K Coin',
            'slug' => 'coin-' . uniqid(),
            'sku' => 'SKU' . uniqid(),
            'gold_type' => '24K',
            'weight_in_grams' => 10,
            'purity' => 999.99,
            'category' => 'coins',
            'status' => 'active',
        ]);

        $this->plan = EmiPlan::create([
            'plan_name' => '6 Month Saver',
            'plan_code' => 'P' . uniqid(),
            'duration_months' => 6,
            'minimum_booking_amount' => 1000,
            'maximum_booking_amount' => 1000000,
            'minimum_gold_weight' => 1,
            'maximum_gold_weight' => 1000,
            'processing_fee_type' => 'fixed',
            'processing_fee' => 0,
            'interest_type' => 'flat',
            'interest_rate' => 0,
            'rounding_type' => 'none',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => Str::slug($name) . '.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'status' => 'active',
        ]);
    }

    protected function makeBooking(string $status, ?User $customer = null): GoldBooking
    {
        $customer ??= $this->customer;

        return GoldBooking::create([
            'booking_number' => 'ZG26' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'product_id' => $this->product->id,
            'emi_plan_id' => $this->plan->id,
            'gold_weight' => 10,
            'gold_purity' => 99.99,
            'locked_price_per_gram' => 6000,
            'locked_gold_value' => 60000,
            'grand_total' => 61800,
            'monthly_emi' => 10300,
            'duration_months' => 6,
            'booking_date' => now(),
            'estimated_completion_date' => now()->addMonths(6),
            'status' => $status,
        ]);
    }

    /**
     * Attach one row of every booking-related table to the booking.
     */
    protected function attachChildren(GoldBooking $booking): array
    {
        $now = now();
        $u = uniqid();

        $scheduleId = DB::table('booking_emi_schedules')->insertGetId([
            'booking_id' => $booking->id, 'installment_number' => 1, 'due_date' => $now->toDateString(),
            'opening_principal' => 61800, 'principal_amount' => 10300, 'interest_amount' => 0,
            'emi_amount' => 10300, 'closing_principal' => 51500, 'outstanding_balance' => 51500,
            'status' => 'Paid', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $paymentId = DB::table('booking_payments')->insertGetId([
            'payment_number' => 'PAY' . $u, 'receipt_number' => 'RCPT' . $u, 'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id, 'emi_schedule_id' => $scheduleId, 'amount_paid' => 10300,
            'principal_paid' => 10300, 'interest_paid' => 0, 'payment_date' => $now, 'payment_mode' => 'Cash', 'status' => 'Paid',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $invoiceId = DB::table('gst_invoices')->insertGetId([
            'invoice_number' => 'INV' . $u, 'booking_id' => $booking->id, 'payment_id' => $paymentId,
            'customer_id' => $booking->customer_id, 'invoice_date' => $now, 'customer_name' => 'Jane',
            'customer_email' => 'jane@example.com', 'customer_phone' => '9999999999', 'billing_address' => 'Addr',
            'product_name' => 'Coin', 'gold_weight' => 10, 'gold_purity' => 99.99, 'locked_gold_price' => 6000,
            'gold_value' => 60000, 'gst_on_gold_percent' => 3, 'gst_on_gold_amount' => 1800, 'finance_charge' => 0,
            'storage_charge' => 0, 'gst_on_charges_percent' => 0, 'gst_on_charges_amount' => 0, 'subtotal' => 60000,
            'grand_total' => 61800, 'payment_received' => 10300, 'balance_amount' => 51500,
            'verification_token' => 'VT' . $u, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $deliveryId = DB::table('booking_deliveries')->insertGetId([
            'delivery_number' => 'DEL' . $u, 'booking_id' => $booking->id, 'customer_id' => $booking->customer_id, 'delivery_method' => 'Home Delivery',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $deliveryHistoryId = DB::table('delivery_status_histories')->insertGetId([
            'delivery_id' => $deliveryId, 'new_status' => 'Requested', 'changed_by_id' => $this->staff->id, 'created_at' => $now,
        ]);

        $certificateId = DB::table('price_lock_certificates')->insertGetId([
            'certificate_number' => 'PLC' . $u, 'booking_id' => $booking->id, 'customer_id' => $booking->customer_id,
            'issued_at' => $now, 'locked_price' => 6000, 'gold_weight' => 10, 'grand_total' => 61800,
            'verification_token' => 'PLCVT' . $u, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $cancellationId = DB::table('cancellation_requests')->insertGetId([
            'request_number' => 'CAN' . $u, 'booking_id' => $booking->id, 'customer_id' => $booking->customer_id,
            'cancellation_reason' => 'Test', 'cancellation_charge_percent' => 5, 'cancellation_charge_amount' => 500,
            'total_amount_paid' => 10300, 'refund_amount' => 9800, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $referralId = DB::table('referrals')->insertGetId([
            'referral_code' => 'REF' . $u, 'referred_customer_id' => $booking->customer_id, 'booking_id' => $booking->id,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // customer_referrals is one row per customer (unique customer_id), shared across bookings.
        $customerReferralId = DB::table('customer_referrals')->where('customer_id', $booking->customer_id)->value('id')
            ?? DB::table('customer_referrals')->insertGetId([
            'staff_id' => $this->staff->id, 'customer_id' => $booking->customer_id, 'booking_id' => $booking->id,
            'referral_code' => 'STF' . $u, 'referred_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $transactionId = DB::table('payment_transactions')->insertGetId([
            'transaction_number' => 'TXN' . $u, 'booking_id' => $booking->id, 'customer_id' => $booking->customer_id,
            'payment_type' => 'booking', 'gateway' => 'cashfree', 'gateway_order_id' => 'ORD' . $u,
            'payment_token' => 'TOK' . $u, 'amount' => 10300, 'payment_status' => 'Success',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $pendingTransactionId = DB::table('payment_transactions')->insertGetId([
            'transaction_number' => 'TXNP' . $u, 'booking_id' => $booking->id, 'customer_id' => $booking->customer_id,
            'payment_type' => 'emi', 'gateway' => 'cashfree', 'gateway_order_id' => 'ORDP' . $u,
            'payment_token' => 'TOKP' . $u, 'amount' => 10300, 'payment_status' => 'Pending',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $cashCollectionId = DB::table('cash_collection_requests')->insertGetId([
            'collection_number' => 'CC' . $u, 'transaction_id' => $transactionId, 'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id, 'payment_id' => $paymentId, 'collected_by_id' => $this->staff->id,
            'amount' => 10300, 'status' => 'Pending Verification', 'collection_date' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return compact(
            'scheduleId', 'paymentId', 'invoiceId', 'deliveryId', 'deliveryHistoryId', 'certificateId',
            'cancellationId', 'referralId', 'customerReferralId', 'transactionId', 'pendingTransactionId',
            'cashCollectionId'
        );
    }

    protected function assertOwnedRecordsSoftDeleted(array $ids): void
    {
        $this->assertSoftDeleted('booking_emi_schedules', ['id' => $ids['scheduleId']]);
        $this->assertSoftDeleted('booking_payments', ['id' => $ids['paymentId']]);
        $this->assertSoftDeleted('gst_invoices', ['id' => $ids['invoiceId']]);
        $this->assertSoftDeleted('booking_deliveries', ['id' => $ids['deliveryId']]);
        $this->assertSoftDeleted('price_lock_certificates', ['id' => $ids['certificateId']]);
        $this->assertSoftDeleted('cancellation_requests', ['id' => $ids['cancellationId']]);
        $this->assertSoftDeleted('referrals', ['id' => $ids['referralId']]);
    }

    protected function assertOwnedRecordsActive(array $ids): void
    {
        $this->assertNotSoftDeleted('booking_emi_schedules', ['id' => $ids['scheduleId']]);
        $this->assertNotSoftDeleted('booking_payments', ['id' => $ids['paymentId']]);
        $this->assertNotSoftDeleted('gst_invoices', ['id' => $ids['invoiceId']]);
        $this->assertNotSoftDeleted('booking_deliveries', ['id' => $ids['deliveryId']]);
        $this->assertNotSoftDeleted('price_lock_certificates', ['id' => $ids['certificateId']]);
        $this->assertNotSoftDeleted('cancellation_requests', ['id' => $ids['cancellationId']]);
        $this->assertNotSoftDeleted('referrals', ['id' => $ids['referralId']]);
    }

    public static function bookingStatuses(): array
    {
        return [
            'draft' => ['Draft'],
            'pending first emi' => ['Pending First EMI'],
            'booked' => ['Booked'],
            'active' => ['Active'],
            'paid' => ['Paid'],
            'completed' => ['Completed'],
            'cancelled' => ['Cancelled'],
            'refund initiated' => ['Refund Initiated'],
            'refunded' => ['Refunded'],
        ];
    }

    /**
     * @dataProvider bookingStatuses
     */
    public function test_super_admin_can_soft_delete_booking_in_any_status(string $status): void
    {
        $booking = $this->makeBooking($status);
        $ids = $this->attachChildren($booking);

        $this->actingAs($this->superAdmin)
            ->delete(route('bookings.destroy', $booking->id))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('gold_bookings', ['id' => $booking->id]);
        $this->assertOwnedRecordsSoftDeleted($ids);
        // Rows still exist: nothing is hard-deleted.
        $this->assertDatabaseHas('gold_bookings', ['id' => $booking->id]);
        $this->assertSame(1, BookingPayment::withTrashed()->whereKey($ids['paymentId'])->count());
    }

    public function test_user_id_one_super_admin_can_delete_booking(): void
    {
        $rootUser = User::find(1);
        $this->assertTrue($rootUser->isSuperAdmin());

        $booking = $this->makeBooking('Active');

        $this->actingAs($rootUser)->delete(route('bookings.destroy', $booking->id))->assertRedirect(route('bookings.index'));

        $this->assertSoftDeleted('gold_bookings', ['id' => $booking->id]);
    }

    public function test_financial_audit_and_shared_records_are_retained(): void
    {
        $booking = $this->makeBooking('Active');
        $ids = $this->attachChildren($booking);
        $customerSnapshot = $this->customer->fresh()->only(['email', 'password', 'role_id', 'status']);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $booking->id));

        // Gateway transactions keep their financial state.
        $this->assertDatabaseHas('payment_transactions', ['id' => $ids['transactionId'], 'payment_status' => 'Success']);
        $this->assertDatabaseHas('payment_transactions', ['id' => $ids['pendingTransactionId'], 'payment_status' => 'Pending', 'link_status' => null]);
        // Audit evidence is untouched.
        $this->assertDatabaseHas('cash_collection_requests', ['id' => $ids['cashCollectionId'], 'status' => 'Pending Verification']);
        $this->assertDatabaseHas('delivery_status_histories', ['id' => $ids['deliveryHistoryId']]);
        $this->assertGreaterThan(0, BookingStatusHistory::where('booking_id', $booking->id)->count());
        // Per-customer referral record is shared, not booking-owned.
        $this->assertNotSoftDeleted('customer_referrals', ['id' => $ids['customerReferralId']]);
        // Customer account is untouched.
        $this->assertSame($customerSnapshot, $this->customer->fresh()->only(['email', 'password', 'role_id', 'status']));
    }

    public function test_unrelated_bookings_and_their_records_remain_untouched(): void
    {
        $target = $this->makeBooking('Active');
        $this->attachChildren($target);

        $otherCustomer = $this->makeUser('customer', 'Other Customer');
        $sameCustomerBooking = $this->makeBooking('Active');
        $otherBooking = $this->makeBooking('Completed', $otherCustomer);
        $sameCustomerIds = $this->attachChildren($sameCustomerBooking);
        $otherIds = $this->attachChildren($otherBooking);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $target->id));

        $this->assertNotSoftDeleted('gold_bookings', ['id' => $sameCustomerBooking->id]);
        $this->assertNotSoftDeleted('gold_bookings', ['id' => $otherBooking->id]);
        $this->assertOwnedRecordsActive($sameCustomerIds);
        $this->assertOwnedRecordsActive($otherIds);
    }

    public function test_deletion_is_recorded_in_audit_trail(): void
    {
        $booking = $this->makeBooking('Active');
        $this->attachChildren($booking);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $booking->id));

        $log = ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $booking->id)
            ->where('action_type', 'booking_deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->superAdmin->id, $log->created_by_id);
        $this->assertStringContainsString($booking->booking_number, $log->description);
        $this->assertSame(1, $log->new_data['soft_deleted_records']['payments']);
    }

    public function test_regular_admin_staff_and_customer_cannot_delete_bookings(): void
    {
        $booking = $this->makeBooking('Active');
        $ids = $this->attachChildren($booking);

        $this->actingAs($this->admin)->delete(route('bookings.destroy', $booking->id))->assertForbidden();
        $this->actingAs($this->staff)->delete(route('bookings.destroy', $booking->id))->assertForbidden();
        $this->actingAs($this->customer)->delete(route('bookings.destroy', $booking->id))->assertStatus(302);

        $this->assertNotSoftDeleted('gold_bookings', ['id' => $booking->id]);
        $this->assertOwnedRecordsActive($ids);
    }

    public function test_unauthenticated_delete_request_is_rejected(): void
    {
        $booking = $this->makeBooking('Active');

        $this->delete(route('bookings.destroy', $booking->id))->assertRedirect(route('login'));

        $this->assertNotSoftDeleted('gold_bookings', ['id' => $booking->id]);
    }

    public function test_delete_button_is_shown_only_to_super_admin(): void
    {
        $booking = $this->makeBooking('Active');

        $this->actingAs($this->superAdmin)->get(route('bookings.index'))->assertOk()->assertSee('js-delete-booking', false);
        $this->actingAs($this->admin)->get(route('bookings.index'))->assertOk()->assertDontSee('js-delete-booking', false);
        $this->actingAs($this->superAdmin)->get(route('bookings.show', $booking->id))->assertOk()->assertSee('Delete Booking');
        $this->actingAs($this->admin)->get(route('bookings.show', $booking->id))->assertOk()->assertDontSee('Delete Booking');
    }

    public function test_repeated_and_missing_delete_requests_are_handled_safely(): void
    {
        $booking = $this->makeBooking('Active');
        $this->attachChildren($booking);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $booking->id))->assertSessionHas('success');
        $deletedAt = GoldBooking::withTrashed()->find($booking->id)->deleted_at;

        $this->actingAs($this->superAdmin)
            ->delete(route('bookings.destroy', $booking->id))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('success', "Booking {$booking->booking_number} has already been deleted.");

        $this->assertEquals($deletedAt, GoldBooking::withTrashed()->find($booking->id)->deleted_at);
        $this->assertSame(1, ActivityLog::where('action_type', 'booking_deleted')->where('record_id', $booking->id)->count());

        $this->actingAs($this->superAdmin)
            ->delete(route('bookings.destroy', 999999))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('error', 'Booking not found.');
    }

    public function test_failure_mid_deletion_rolls_back_everything(): void
    {
        $booking = $this->makeBooking('Active');
        $ids = $this->attachChildren($booking);

        // Referrals are processed after schedules, payments, invoices, deliveries, certificates and cancellations.
        Referral::deleting(function () {
            throw new \RuntimeException('Simulated database failure');
        });

        $this->actingAs($this->superAdmin)
            ->from(route('bookings.show', $booking->id))
            ->delete(route('bookings.destroy', $booking->id))
            ->assertRedirect(route('bookings.show', $booking->id))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('gold_bookings', ['id' => $booking->id]);
        $this->assertOwnedRecordsActive($ids);
        $this->assertSame(0, ActivityLog::where('action_type', 'booking_deleted')->count());
    }

    public function test_deleted_bookings_are_excluded_from_active_booking_list(): void
    {
        $deleted = $this->makeBooking('Active');
        $kept = $this->makeBooking('Active');

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $deleted->id));

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('bookings.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $numbers = collect($response->json('data'))->pluck('booking_number');
        $this->assertTrue($numbers->contains($kept->booking_number));
        $this->assertFalse($numbers->contains($deleted->booking_number));

        $this->actingAs($this->superAdmin)->get(route('bookings.show', $deleted->id))->assertNotFound();
    }

    public function test_payment_link_of_deleted_booking_can_no_longer_be_paid(): void
    {
        $booking = $this->makeBooking('Active');
        $ids = $this->attachChildren($booking);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $booking->id));

        $token = PaymentTransaction::find($ids['pendingTransactionId'])->payment_token;
        $this->get(route('payments.links.pay', $token))
            ->assertOk()
            ->assertSee('This payment link is no longer active.');

        $this->assertDatabaseHas('payment_transactions', ['id' => $ids['pendingTransactionId'], 'payment_status' => 'Pending']);
    }

    public function test_cash_collection_of_deleted_booking_cannot_be_verified(): void
    {
        $booking = $this->makeBooking('Active');
        $ids = $this->attachChildren($booking);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $booking->id));

        $this->actingAs($this->superAdmin)
            ->post(route('admin.cash-collections.verify', $ids['cashCollectionId']), ['remark' => 'Checked'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cash_collection_requests', ['id' => $ids['cashCollectionId'], 'status' => 'Pending Verification']);
        $this->assertDatabaseHas('payment_transactions', ['id' => $ids['transactionId'], 'payment_status' => 'Success']);
    }

    public function test_new_numbers_do_not_reuse_soft_deleted_numbers(): void
    {
        $bookingService = app(\App\Services\BookingService::class);
        $first = $bookingService->generateBookingNumber();
        $booking = $this->makeBooking('Active');
        $booking->update(['booking_number' => $first]);

        $this->actingAs($this->superAdmin)->delete(route('bookings.destroy', $booking->id));

        $this->assertNotSame($first, $bookingService->generateBookingNumber());
    }
}
