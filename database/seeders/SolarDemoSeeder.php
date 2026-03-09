<?php

namespace Database\Seeders;

use App\Models\AmcContract;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Lead;
use App\Models\NumberSequence;
use App\Models\Organization;
use App\Models\PaymentMethod;
use App\Models\Quotation;
use App\Models\QuotationLineItem;
use App\Models\Role;
use App\Models\ServiceTicket;
use App\Models\Survey;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SolarDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // 1. MD Super Admin (no organization)
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@solar.test'],
            [
                'name' => 'MD Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'organization_id' => null,
                'role_id' => $superAdminRole?->id,
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        // 2. Branches (organizations)
        $branchNorth = Organization::firstOrCreate(
            ['name' => 'Solar Branch North'],
            ['base_currency' => 'INR', 'status' => 'active', 'user_limit' => 50]
        );
        $branchSouth = Organization::firstOrCreate(
            ['name' => 'Solar Branch South'],
            ['base_currency' => 'INR', 'status' => 'active', 'user_limit' => 50]
        );

        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $userRole = Role::where('slug', 'user')->first();

        // Branch North users
        $branchManagerNorth = User::updateOrCreate(
            ['email' => 'manager.north@solar.test'],
            [
                'name' => 'Branch Manager North',
                'username' => 'manager_north',
                'password' => Hash::make('password'),
                'organization_id' => $branchNorth->id,
                'role_id' => $managerRole?->id,
                'role' => 'manager',
                'status' => 'active',
            ]
        );
        $salesNorth = User::updateOrCreate(
            ['email' => 'sales.north@solar.test'],
            [
                'name' => 'Sales Executive North',
                'username' => 'sales_north',
                'password' => Hash::make('password'),
                'organization_id' => $branchNorth->id,
                'role_id' => $userRole?->id,
                'role' => 'user',
                'status' => 'active',
            ]
        );
        $surveyEngineer = User::updateOrCreate(
            ['email' => 'survey@solar.test'],
            [
                'name' => 'Site Survey Engineer',
                'username' => 'survey_eng',
                'password' => Hash::make('password'),
                'organization_id' => $branchNorth->id,
                'role_id' => $userRole?->id,
                'role' => 'user',
                'status' => 'active',
            ]
        );
        $installManager = User::updateOrCreate(
            ['email' => 'install.mgr@solar.test'],
            [
                'name' => 'Installation Manager',
                'username' => 'install_mgr',
                'password' => Hash::make('password'),
                'organization_id' => $branchNorth->id,
                'role_id' => $userRole?->id,
                'role' => 'user',
                'status' => 'active',
            ]
        );
        $technician = User::updateOrCreate(
            ['email' => 'tech@solar.test'],
            [
                'name' => 'Installation Technician',
                'username' => 'technician',
                'password' => Hash::make('password'),
                'organization_id' => $branchNorth->id,
                'role_id' => $userRole?->id,
                'role' => 'user',
                'status' => 'active',
            ]
        );

        // 3. Payment methods
        foreach ([$branchNorth, $branchSouth] as $org) {
            PaymentMethod::firstOrCreate(
                ['organization_id' => $org->id, 'name' => 'Cash'],
                ['is_active' => true, 'sort_order' => 0]
            );
            PaymentMethod::firstOrCreate(
                ['organization_id' => $org->id, 'name' => 'Bank Transfer'],
                ['is_active' => true, 'sort_order' => 1]
            );
            PaymentMethod::firstOrCreate(
                ['organization_id' => $org->id, 'name' => 'Bank EMI'],
                ['is_active' => true, 'sort_order' => 2]
            );
            NumberSequence::firstOrCreate(
                ['organization_id' => $org->id, 'sequence_type' => 'quotation'],
                ['prefix' => 'QT', 'current_value' => 0, 'padding' => 5]
            );
            NumberSequence::firstOrCreate(
                ['organization_id' => $org->id, 'sequence_type' => 'invoice'],
                ['prefix' => 'INV', 'current_value' => 0, 'padding' => 5]
            );
        }

        // 4. Products (solar catalog) - Branch North
        $panel = Item::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'sku' => 'SOLAR-400W'],
            [
                'name' => 'Solar Panel 400W',
                'product_type' => 'solar_panel',
                'item_type' => 'product',
                'price' => 18500,
                'cost' => 15000,
                'stock_quantity' => 50,
                'low_stock_alert' => 5,
                'unit' => 'each',
                'status' => 'active',
            ]
        );
        $inverter = Item::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'sku' => 'INV-5K'],
            [
                'name' => 'Hybrid Inverter 5kW',
                'product_type' => 'inverter',
                'item_type' => 'product',
                'price' => 45000,
                'cost' => 38000,
                'stock_quantity' => 20,
                'low_stock_alert' => 2,
                'unit' => 'each',
                'status' => 'active',
            ]
        );
        $battery = Item::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'sku' => 'BAT-10K'],
            [
                'name' => 'Battery 10kWh',
                'product_type' => 'battery',
                'item_type' => 'product',
                'price' => 95000,
                'cost' => 80000,
                'stock_quantity' => 10,
                'low_stock_alert' => 2,
                'unit' => 'each',
                'status' => 'active',
            ]
        );

        // 5. Vendors
        Vendor::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'name' => 'Solar Supplies Co'],
            ['contact_person' => 'Raj', 'phone' => '9876543210', 'status' => 'active']
        );

        // 6. Customers
        $customer1 = Customer::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'email' => 'ramesh@example.com'],
            ['name' => 'Ramesh Kumar', 'phone' => '9123456789', 'status' => 'active']
        );
        $customer2 = Customer::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'email' => 'sita@example.com'],
            ['name' => 'Sita Devi', 'phone' => '9234567890', 'status' => 'active']
        );

        // 7. Leads (workflow: new → contacted → site_survey → proposal → confirmed)
        $lead1 = Lead::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'lead_number' => 'L-00001'],
            [
                'customer_name' => 'Ramesh Kumar',
                'phone' => '9123456789',
                'email' => 'ramesh@example.com',
                'lead_source' => 'walk_in',
                'assigned_to' => $salesNorth->id,
                'status' => 'confirmed',
                'electricity_bill_amount' => 4500,
                'roof_type' => 'flat',
                'created_by' => $branchManagerNorth->id,
            ]
        );
        $lead2 = Lead::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'lead_number' => 'L-00002'],
            [
                'customer_name' => 'Sita Devi',
                'phone' => '9234567890',
                'email' => 'sita@example.com',
                'lead_source' => 'whatsapp',
                'assigned_to' => $salesNorth->id,
                'status' => 'site_survey',
                'created_by' => $branchManagerNorth->id,
            ]
        );
        Lead::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'lead_number' => 'L-00003'],
            [
                'customer_name' => 'Amit Singh',
                'phone' => '9345678901',
                'lead_source' => 'referral',
                'status' => 'new',
                'created_by' => $branchManagerNorth->id,
            ]
        );

        // 8. Survey for lead1
        $survey1 = Survey::firstOrCreate(
            ['lead_id' => $lead1->id],
            [
                'organization_id' => $branchNorth->id,
                'engineer_id' => $surveyEngineer->id,
                'roof_type' => 'flat',
                'roof_size_sqft' => 500,
                'system_size_kw' => 5,
                'direction' => 'south',
                'status' => 'completed',
                'survey_date' => now()->toDateString(),
                'completed_at' => now(),
            ]
        );

        // 9. Quotation (from lead/survey - customer approval step)
        $quotation = Quotation::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'quotation_number' => 'QT-DEMO01'],
            [
                'customer_id' => $customer1->id,
                'lead_id' => $lead1->id,
                'survey_id' => $survey1->id,
                'status' => 'accepted',
                'valid_until' => now()->addDays(30),
                'subtotal' => 230000,
                'total' => 230000,
                'created_by' => $salesNorth->id,
            ]
        );
        if (! $quotation->lineItems()->exists()) {
            QuotationLineItem::create([
                'quotation_id' => $quotation->id,
                'item_id' => $panel->id,
                'description' => 'Solar Panel 400W x 10',
                'quantity' => 10,
                'unit_price' => 18500,
                'amount' => 185000,
                'sort_order' => 0,
            ]);
            QuotationLineItem::create([
                'quotation_id' => $quotation->id,
                'item_id' => $inverter->id,
                'description' => 'Hybrid Inverter 5kW',
                'quantity' => 1,
                'unit_price' => 45000,
                'amount' => 45000,
                'sort_order' => 1,
            ]);
            $quotation->update(['subtotal' => 230000, 'total' => 230000]);
        }

        // 10. Installation (after customer approval)
        $installation = Installation::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'quotation_id' => $quotation->id],
            [
                'lead_id' => $lead1->id,
                'customer_id' => $customer1->id,
                'installation_manager_id' => $installManager->id,
                'installation_number' => 'INST-00001',
                'scheduled_date' => now()->addDays(5),
                'status' => 'scheduled',
                'notes' => 'Demo installation',
            ]
        );
        if (! $installation->checklists()->exists()) {
            foreach (['Panel mounting', 'Wiring', 'Inverter installation', 'Grid connection', 'System testing'] as $i => $task) {
                $installation->checklists()->create(['task_name' => $task, 'sort_order' => $i + 1]);
            }
        }

        // 11. Service ticket
        ServiceTicket::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'installation_id' => $installation->id],
            [
                'customer_id' => $customer1->id,
                'ticket_number' => 'SRV-00001',
                'status' => 'open',
                'priority' => 'medium',
                'complaint' => 'Demo service request',
            ]
        );

        // 12. AMC contract
        AmcContract::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'installation_id' => $installation->id],
            [
                'customer_id' => $customer1->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'amount' => 5000,
                'status' => 'active',
            ]
        );

        // 13. Invoice (for revenue in dashboard)
        Invoice::firstOrCreate(
            ['organization_id' => $branchNorth->id, 'invoice_number' => 'INV-DEMO01'],
            [
                'customer_id' => $customer1->id,
                'quotation_id' => $quotation->id,
                'installation_id' => $installation->id,
                'invoice_type' => 'standard',
                'status' => 'paid',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => 230000,
                'total' => 230000,
                'amount_paid' => 230000,
                'balance_due' => 0,
                'payment_mode' => 'full_cash',
                'created_by' => $branchManagerNorth->id,
            ]
        );

        // Demo login user for API docs testing (same as existing demo3 if any, or create)
        User::updateOrCreate(
            ['username' => 'demo3'],
            [
                'name' => 'Demo User',
                'email' => 'demo3@solar.test',
                'password' => Hash::make('password'),
                'organization_id' => $branchNorth->id,
                'role_id' => $userRole?->id,
                'role' => 'user',
                'status' => 'active',
            ]
        );
    }
}
