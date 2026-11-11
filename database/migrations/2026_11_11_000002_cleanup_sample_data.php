<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove previously seeded demonstration/sample business records cleanly
     * without affecting real user-created data or core system configuration.
     */
    public function up(): void
    {
        // 1. Generated test/dev module artifacts in DB
        if (Schema::hasTable('pages')) {
            $testPageIds = DB::table('pages')
                ->whereIn('slug', ['custom-todos-test', 'task-manager-test-moin'])
                ->pluck('id');

            if ($testPageIds->isNotEmpty()) {
                if (Schema::hasTable('role_permissions')) {
                    DB::table('role_permissions')->whereIn('page_id', $testPageIds)->delete();
                }
                if (Schema::hasTable('user_permissions')) {
                    DB::table('user_permissions')->whereIn('page_id', $testPageIds)->delete();
                }
                DB::table('pages')->whereIn('id', $testPageIds)->delete();
            }

            if (Schema::hasTable('modules')) {
                $testModuleNames = ['Task Management', 'Communication Hub'];
                foreach ($testModuleNames as $modName) {
                    $modId = DB::table('modules')->where('name', $modName)->value('id');
                    if ($modId && DB::table('pages')->where('module_id', $modId)->count() === 0) {
                        DB::table('modules')->where('id', $modId)->delete();
                    }
                }
            }
        }

        // 2. Demo Email Data & Accounts
        if (Schema::hasTable('email_accounts')) {
            $demoAccountIds = DB::table('email_accounts')
                ->whereIn('email', ['demo.user@gmail.com', 'user@mserp.in'])
                ->pluck('id');

            if (Schema::hasTable('emails')) {
                $demoEmailIds = DB::table('emails')
                    ->where(function ($q) use ($demoAccountIds) {
                        $q->where('from_address', 'sophia.martinez@mserp-client.com')
                          ->orWhere('message_id', 'like', '%@mserp-client.com%')
                          ->orWhere('subject', 'like', '%#INV-2026-004%');
                        if ($demoAccountIds->isNotEmpty()) {
                            $q->orWhereIn('email_account_id', $demoAccountIds);
                        }
                    })
                    ->pluck('id');

                if ($demoEmailIds->isNotEmpty()) {
                    if (Schema::hasTable('email_bodies')) {
                        DB::table('email_bodies')->whereIn('email_id', $demoEmailIds)->delete();
                    }
                    if (Schema::hasTable('email_attachments')) {
                        DB::table('email_attachments')->whereIn('email_id', $demoEmailIds)->delete();
                    }
                    DB::table('emails')->whereIn('id', $demoEmailIds)->delete();
                }
            }

            if ($demoAccountIds->isNotEmpty()) {
                if (Schema::hasTable('email_account_users')) {
                    DB::table('email_account_users')->whereIn('email_account_id', $demoAccountIds)->delete();
                }
                DB::table('email_accounts')->whereIn('id', $demoAccountIds)->delete();
            }
        }

        if (Schema::hasTable('email_contacts')) {
            DB::table('email_contacts')->where('email', 'sophia.martinez@mserp-client.com')->delete();
        }
        if (Schema::hasTable('email_templates')) {
            DB::table('email_templates')->where('name', 'Invoice Approved Notification')->delete();
        }
        if (Schema::hasTable('email_signatures')) {
            DB::table('email_signatures')->where('name', 'Corporate Executive Sign')->delete();
        }

        // 3. Seeded Workflow Instance, Notifications, Audit Logs, Sales Invoices, Customers, Vendors, Items
        if (Schema::hasTable('sales_invoices')) {
            $demoInvoiceIds = DB::table('sales_invoices')
                ->whereIn('invoice_no', ['INV-2026-001', 'INV-2026-002', 'INV-2026-003'])
                ->pluck('id');

            if ($demoInvoiceIds->isNotEmpty()) {
                if (Schema::hasTable('sales_invoice_items')) {
                    DB::table('sales_invoice_items')->whereIn('sales_invoice_id', $demoInvoiceIds)->delete();
                }
                if (Schema::hasTable('workflow_instances')) {
                    DB::table('workflow_instances')
                        ->where('table_name', 'sales_invoices')
                        ->whereIn('record_id', $demoInvoiceIds)
                        ->delete();
                }
                DB::table('sales_invoices')->whereIn('id', $demoInvoiceIds)->delete();
            }
        }

        if (Schema::hasTable('workflows')) {
            DB::table('workflows')->where('name', 'High Value Invoice Approval Chain')->delete();
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('title', 'Workflow Approval Required')
                ->where('message', "A new record in 'Sales Invoices' requires your approval.")
                ->delete();
        }

        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')
                ->where('table_name', 'sales_invoices')
                ->where('ip_address', '192.168.1.100')
                ->where('new_values', 'like', '%INV-2026-001%')
                ->delete();
        }

        if (Schema::hasTable('customers')) {
            DB::table('customers')->whereIn('email', [
                'billing@rel-retail.in',
                'invoices@hcl.in',
                'billing@infosys.com',
                'accounts@tcs.co.in',
                'mrf@mrf-india.in',
            ])->delete();
        }

        if (Schema::hasTable('vendors')) {
            DB::table('vendors')->whereIn('code', ['VND001', 'VND002'])->delete();
        }

        // 4. Sample Manufacturing & Inventory Items
        if (Schema::hasTable('work_orders')) {
            DB::table('work_orders')->where('work_order_no', 'WO-2026-0001')->delete();
        }
        if (Schema::hasTable('boms')) {
            $demoBomIds = DB::table('boms')->where('bom_no', 'BOM-SERVER-001')->pluck('id');
            if ($demoBomIds->isNotEmpty()) {
                if (Schema::hasTable('bom_items')) {
                    DB::table('bom_items')->whereIn('bom_id', $demoBomIds)->delete();
                }
                DB::table('boms')->whereIn('id', $demoBomIds)->delete();
            }
        }
        if (Schema::hasTable('work_centers')) {
            DB::table('work_centers')->where('code', 'WC-ASSY-01')->delete();
        }
        if (Schema::hasTable('inventory_items')) {
            DB::table('inventory_items')->whereIn('item_code', ['ITM001', 'ITM002'])->delete();
        }
        if (Schema::hasTable('cost_centers')) {
            DB::table('cost_centers')->whereIn('code', ['CC-MUM', 'CC-BLR'])->delete();
        }

        // 5. Sample Warehouses & Bins
        if (Schema::hasTable('warehouses')) {
            $demoWhIds = DB::table('warehouses')->whereIn('code', ['WH-MUM-MAIN', 'WH-BLR-TECH'])->pluck('id');
            if ($demoWhIds->isNotEmpty()) {
                if (Schema::hasTable('warehouse_bins')) {
                    DB::table('warehouse_bins')->whereIn('warehouse_id', $demoWhIds)->delete();
                }
                DB::table('warehouses')->whereIn('id', $demoWhIds)->delete();
            }
        }

        // 6. Reset seeded Chart of Accounts balances only if no General Ledger transactions exist yet
        if (Schema::hasTable('chart_of_accounts')) {
            $hasGlEntries = Schema::hasTable('general_ledger') && DB::table('general_ledger')->exists();
            if (!$hasGlEntries) {
                DB::table('chart_of_accounts')
                    ->where('code', '1020')
                    ->where('name', 'HDFC Bank Operating Account')
                    ->update(['name' => 'Primary Bank Operating Account']);

                DB::table('chart_of_accounts')
                    ->whereIn('code', ['1010', '1020', '1030', '1040', '2010', '2020', '2021', '2022', '3010', '3020', '4010', '4020', '5010', '5020', '5030'])
                    ->update([
                        'opening_balance' => 0.00,
                        'current_balance' => 0.00,
                        'updated_at' => now(),
                    ]);
            }
        }

        // 7. Reassign Admin and clean demo users, demo company branding & extra demo branch
        if (Schema::hasTable('users')) {
            $adminUser = DB::table('users')->where('email', 'admin@mserp.com')->first();
            if ($adminUser && $adminUser->name === 'Michael Chang (CFO)') {
                DB::table('users')->where('id', $adminUser->id)->update(['name' => 'Super Admin']);
            }

            DB::table('users')->whereIn('email', [
                'north.head@mserp.com',
                'south.head@mserp.com',
                'accounts.head@mserp.com',
                'rep.north1@mserp.com',
                'rep.north2@mserp.com',
                'rep.south1@mserp.com',
                'accounts.member@mserp.com',
                'user@mserp.com',
            ])->delete();
        }

        if (Schema::hasTable('branches')) {
            $hqBranch = DB::table('branches')->where('code', 'ACME-BOM')->first();
            $blrBranch = DB::table('branches')->where('code', 'ACME-BLR')->first();

            if ($hqBranch) {
                DB::table('branches')->where('id', $hqBranch->id)->update([
                    'name' => 'Head Office',
                    'code' => 'HQ',
                    'address' => null,
                    'updated_at' => now(),
                ]);

                if ($blrBranch) {
                    if (Schema::hasTable('users')) {
                        DB::table('users')->where('branch_id', $blrBranch->id)->update(['branch_id' => $hqBranch->id]);
                    }
                    if (Schema::hasTable('departments')) {
                        DB::table('departments')->where('branch_id', $blrBranch->id)->update(['branch_id' => $hqBranch->id]);
                    }
                    DB::table('branches')->where('id', $blrBranch->id)->delete();
                }
            }
        }

        if (Schema::hasTable('companies')) {
            $acmeCompany = DB::table('companies')->where('code', 'ACME')->first();
            if ($acmeCompany) {
                $companyUpdate = [
                    'name' => 'MS ERP',
                    'code' => 'MSERP',
                    'address' => null,
                    'phone' => null,
                    'email' => null,
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('companies', 'gstin')) {
                    $companyUpdate['gstin'] = null;
                }
                if (Schema::hasColumn('companies', 'pan')) {
                    $companyUpdate['pan'] = null;
                }
                if (Schema::hasColumn('companies', 'bank_name')) {
                    $companyUpdate['bank_name'] = null;
                }
                if (Schema::hasColumn('companies', 'bank_acc_no')) {
                    $companyUpdate['bank_acc_no'] = null;
                }
                if (Schema::hasColumn('companies', 'bank_ifsc')) {
                    $companyUpdate['bank_ifsc'] = null;
                }
                DB::table('companies')->where('id', $acmeCompany->id)->update($companyUpdate);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cleanup migration is non-reversible.
    }
};

