<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Self-heal billing and email tables before seeding
        app(\App\Http\Controllers\DynamicCrudController::class)->ensureBillingTablesExist();
        app(\App\Http\Controllers\EmailController::class)->ensureEmailTablesExist();

        // 1. Default Workspace Company (Idempotent)
        $companyId = DB::table('companies')->where('code', 'MSERP')->value('id')
            ?? DB::table('companies')->value('id');
        if (!$companyId) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'MS ERP',
                'code' => 'MSERP',
                'logo' => null,
                'address' => null,
                'phone' => null,
                'email' => null,
                'gstin' => null,
                'state' => 'Maharashtra',
                'pan' => null,
                'bank_name' => null,
                'bank_acc_no' => null,
                'bank_ifsc' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Default Branch (Idempotent)
        $headBranchId = DB::table('branches')->where('code', 'HQ')->value('id')
            ?? DB::table('branches')->where('company_id', $companyId)->value('id');
        if (!$headBranchId) {
            $headBranchId = DB::table('branches')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Head Office',
                'code' => 'HQ',
                'address' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Departments (Idempotent)
        $depts = [
            'EXEC' => 'Executive Office',
            'SALES' => 'Sales Department',
            'ACCTS' => 'Accounts & Finance',
            'ENG' => 'Engineering',
            'SRV' => 'Service & Support',
            'HR' => 'Human Resources'
        ];
        
        $deptIds = [];
        foreach ($depts as $code => $name) {
            $deptId = DB::table('departments')->where('code', $code)->value('id');
            if (!$deptId) {
                $deptId = DB::table('departments')->insertGetId([
                    'branch_id' => $headBranchId,
                    'name' => $name,
                    'code' => $code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $deptIds[$code] = $deptId;
        }

        // 4. Roles (Idempotent)
        $roles = [
            'super-admin' => ['Super Admin', 'Unrestricted system access.'],
            'admin' => ['Admin', 'General administrator credentials.'],
            'sales-head' => ['Sales Head', 'Manages region representatives and customer flags.'],
            'sales-rep' => ['Sales Representative', 'Manages customer records and invoice drafts.'],
            'accounts-head' => ['Accounts Head', 'Manages ledger statements and signs workflows.'],
            'accounts-member' => ['Accounts Assistant', 'Reviews billing and audit records.'],
            'user' => ['General User', 'Basic read-only workspace access.']
        ];

        $roleIds = [];
        foreach ($roles as $slug => $info) {
            $roleId = DB::table('roles')->where('slug', $slug)->value('id');
            if (!$roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $info[0],
                    'slug' => $slug,
                    'description' => $info[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $roleIds[$slug] = $roleId;
        }

        // 5. Primary Administrator Account (Idempotent - no sample users)
        $password = Hash::make('password');
        $cfoId = DB::table('users')->where('email', 'admin@mserp.com')->value('id');
        if (!$cfoId) {
            $cfoId = DB::table('users')->insertGetId([
                'name' => 'Super Admin',
                'email' => 'admin@mserp.com',
                'password' => $password,
                'role_id' => $roleIds['super-admin'],
                'company_id' => $companyId,
                'branch_id' => $headBranchId,
                'department_id' => $deptIds['EXEC'],
                'reports_to_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. Navigation Modules (Low-Code Sidebar - seeded once when pages are missing)
        if (!DB::table('pages')->where('slug', 'customers')->exists()) {
        $modOrg = DB::table('modules')->insertGetId([
            'name' => 'Organization Panel',
            'icon' => 'bi-building',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modErp = DB::table('modules')->insertGetId([
            'name' => 'Core ERP Data',
            'icon' => 'bi-database',
            'sequence' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Pages Configurations (Metadata engine core)
        // Page A: Customers CRUD (India-Focused details)
        $pageCustId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Customers Management',
            'slug' => 'customers',
            'token' => 'CUST-100',
            'title' => 'Indian Customer Accounts Directory',
            'db_table' => 'customers',
            'primary_key' => 'id',
            'sql_query' => 'SELECT customers.id, customers.name, customers.gstin, customers.pan, customers.state, customers.city, users.name as assigned_to, customers.status FROM customers LEFT JOIN users ON customers.assigned_user_id = users.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Customer Name', 'flex' => 1.5],
                ['field' => 'gstin', 'headerName' => 'GSTIN (India)', 'flex' => 1.2],
                ['field' => 'pan', 'headerName' => 'PAN Card', 'flex' => 1.0],
                ['field' => 'state', 'headerName' => 'State', 'flex' => 1.0],
                ['field' => 'city', 'headerName' => 'City', 'flex' => 0.8],
                ['field' => 'assigned_to', 'headerName' => 'Assigned Officer', 'flex' => 1.2],
                ['field' => 'status', 'headerName' => 'Status', 'flex' => 0.8]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Customer Corporate Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'email', 'label' => 'Primary Email', 'type' => 'email', 'validation' => 'required|email|max:255', 'grid_width' => 6],
                ['name' => 'phone', 'label' => 'Phone / Contact No', 'type' => 'text', 'validation' => 'nullable|string|max:50', 'grid_width' => 6],
                ['name' => 'gstin', 'label' => 'GSTIN Number (15 Digits)', 'type' => 'text', 'validation' => 'required|string|size:15', 'grid_width' => 6],
                ['name' => 'pan', 'label' => 'PAN Card No (10 Digits)', 'type' => 'text', 'validation' => 'required|string|size:10', 'grid_width' => 6],
                ['name' => 'state', 'label' => 'State', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 4, 'options' => [
                    ['value' => 'Maharashtra', 'label' => 'Maharashtra'],
                    ['value' => 'Karnataka', 'label' => 'Karnataka'],
                    ['value' => 'Tamil Nadu', 'label' => 'Tamil Nadu'],
                    ['value' => 'Delhi', 'label' => 'Delhi'],
                    ['value' => 'Gujarat', 'label' => 'Gujarat'],
                    ['value' => 'Uttar Pradesh', 'label' => 'Uttar Pradesh'],
                    ['value' => 'West Bengal', 'label' => 'West Bengal'],
                    ['value' => 'Telangana', 'label' => 'Telangana']
                ]],
                ['name' => 'city', 'label' => 'City', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 4, 'options' => []],
                ['name' => 'business_type', 'label' => 'Business Entity Type', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 4, 'options' => [
                    ['value' => 'Sole Proprietorship', 'label' => 'Sole Proprietorship'],
                    ['value' => 'Partnership', 'label' => 'Partnership'],
                    ['value' => 'Private Limited', 'label' => 'Private Limited'],
                    ['value' => 'Public Limited', 'label' => 'Public Limited']
                ]],
                ['name' => 'assigned_user_id', 'label' => 'Assigned Officer / Representative', 'type' => 'select', 'validation' => 'nullable|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'users', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'is_restricted_subordinates', 'label' => 'Supervisor Visibility Overrides', 'type' => 'select', 'validation' => 'required|boolean', 'grid_width' => 6, 'options' => [
                    ['value' => 0, 'label' => 'No restriction - Visible to subordinates'],
                    ['value' => 1, 'label' => 'Restrict - Only visible to assigned head/manager']
                ]],
                ['name' => 'address', 'label' => 'Corporate Office Address', 'type' => 'textarea', 'validation' => 'nullable|string', 'grid_width' => 12],
                ['name' => 'status', 'label' => 'Account Status', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 12, 'options' => [
                    ['value' => 'Active', 'label' => 'Active'],
                    ['value' => 'Inactive', 'label' => 'Inactive']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-people',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page B: Sales Invoices CRUD
        $pageInvId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Sales Invoices',
            'slug' => 'sales-invoices',
            'token' => 'INV-200',
            'title' => 'Sales Ledger & Billings',
            'db_table' => 'sales_invoices',
            'primary_key' => 'id',
            'sql_query' => 'SELECT sales_invoices.id, sales_invoices.invoice_no, customers.name as customer_name, sales_invoices.invoice_date, sales_invoices.total_amount, sales_invoices.status FROM sales_invoices LEFT JOIN customers ON sales_invoices.customer_id = customers.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'invoice_no', 'headerName' => 'Invoice No', 'flex' => 1.2],
                ['field' => 'customer_name', 'headerName' => 'Customer Name', 'flex' => 1.8],
                ['field' => 'invoice_date', 'headerName' => 'Date issued', 'flex' => 1.0],
                ['field' => 'total_amount', 'headerName' => 'Total Amount (₹)', 'flex' => 1.2],
                ['field' => 'status', 'headerName' => 'Approval Status', 'flex' => 1.0]
            ]),
            'form_schema' => json_encode([
                ['name' => 'invoice_no', 'label' => 'Invoice Reference', 'type' => 'text', 'validation' => 'required|string|unique:sales_invoices,invoice_no', 'grid_width' => 6],
                ['name' => 'customer_id', 'label' => 'Select Customer', 'type' => 'select', 'validation' => 'required|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'customers', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'invoice_date', 'label' => 'Billing Date', 'type' => 'date', 'validation' => 'required|date', 'grid_width' => 6],
                ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date', 'validation' => 'required|date', 'grid_width' => 6],
                ['name' => 'amount', 'label' => 'Subtotal Amount (₹)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 4],
                ['name' => 'tax', 'label' => 'Tax Amount (₹)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 4],
                ['name' => 'total_amount', 'label' => 'Total Amount (₹)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 4],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 12, 'options' => [
                    ['value' => 'Draft', 'label' => 'Draft'],
                    ['value' => 'Pending Approval', 'label' => 'Pending Approval'],
                    ['value' => 'Approved', 'label' => 'Approved'],
                    ['value' => 'Paid', 'label' => 'Paid']
                ]]
            ]),
            'is_custom' => true,
            'custom_view' => 'modules/sales_billing',
            'is_active' => true,
            'icon' => 'bi-receipt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page B.0: Sales Quotations CRUD
        $pageSalesQuotationId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Sales Quotations',
            'slug' => 'sales-quotations',
            'token' => 'QTN-100',
            'title' => 'Sales Quotations & Estimates',
            'db_table' => 'sales_quotations',
            'primary_key' => 'id',
            'sql_query' => 'SELECT sales_quotations.id, sales_quotations.quote_no, customers.name as customer_name, sales_quotations.quote_date, sales_quotations.total_amount, sales_quotations.status FROM sales_quotations LEFT JOIN customers ON sales_quotations.customer_id = customers.id',
            'grid_schema' => json_encode([]),
            'form_schema' => json_encode([]),
            'is_custom' => true,
            'custom_view' => 'modules/sales_billing',
            'is_active' => true,
            'icon' => 'bi-file-earmark-text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page B.1: Sales Orders CRUD
        $pageSalesOrderId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Sales Orders',
            'slug' => 'sales-orders',
            'token' => 'SO-300',
            'title' => 'Sales Orders Confirmations',
            'db_table' => 'sales_orders',
            'primary_key' => 'id',
            'sql_query' => 'SELECT sales_orders.id, sales_orders.order_no, customers.name as customer_name, sales_orders.order_date, sales_orders.total_amount, sales_orders.status FROM sales_orders LEFT JOIN customers ON sales_orders.customer_id = customers.id',
            'grid_schema' => json_encode([]),
            'form_schema' => json_encode([]),
            'is_custom' => true,
            'custom_view' => 'modules/sales_billing',
            'is_active' => true,
            'icon' => 'bi-cart-check',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page B.2: Purchase Orders CRUD
        $pagePurchaseOrderId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Purchase Orders',
            'slug' => 'purchase-orders',
            'token' => 'PO-400',
            'title' => 'Purchase Orders to Vendors',
            'db_table' => 'purchase_orders',
            'primary_key' => 'id',
            'sql_query' => 'SELECT purchase_orders.id, purchase_orders.po_no, vendors.name as vendor_name, purchase_orders.po_date, purchase_orders.total_amount, purchase_orders.status FROM purchase_orders LEFT JOIN vendors ON purchase_orders.vendor_id = vendors.id',
            'grid_schema' => json_encode([]),
            'form_schema' => json_encode([]),
            'is_custom' => true,
            'custom_view' => 'modules/sales_billing',
            'is_active' => true,
            'icon' => 'bi-file-earmark-arrow-down',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page B.3: Vendor Bills CRUD
        $pagePurchaseInvoiceId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Vendor Bills',
            'slug' => 'purchase-invoices',
            'token' => 'BILL-500',
            'title' => 'Vendor Bills & Receipts',
            'db_table' => 'purchase_invoices',
            'primary_key' => 'id',
            'sql_query' => 'SELECT purchase_invoices.id, purchase_invoices.bill_no, vendors.name as vendor_name, purchase_invoices.bill_date, purchase_invoices.total_amount, purchase_invoices.status FROM purchase_invoices LEFT JOIN vendors ON purchase_invoices.vendor_id = vendors.id',
            'grid_schema' => json_encode([]),
            'form_schema' => json_encode([]),
            'is_custom' => true,
            'custom_view' => 'modules/sales_billing',
            'is_active' => true,
            'icon' => 'bi-file-earmark-check',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page C: Inventory Items CRUD
        $pageInvItem = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Inventory Items',
            'slug' => 'inventory-items',
            'token' => 'STK-300',
            'title' => 'Warehouse & Inventory Stock',
            'db_table' => 'inventory_items',
            'primary_key' => 'id',
            'sql_query' => 'SELECT * FROM inventory_items',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'item_code', 'headerName' => 'SKU Code', 'flex' => 1.0],
                ['field' => 'name', 'headerName' => 'Item Description', 'flex' => 1.8],
                ['field' => 'category', 'headerName' => 'Category', 'flex' => 1.2],
                ['field' => 'qty_on_hand', 'headerName' => 'Stock Qty', 'flex' => 0.8],
                ['field' => 'unit_price', 'headerName' => 'Unit Price (₹)', 'flex' => 1.0],
                ['field' => 'status', 'headerName' => 'Status', 'flex' => 1.0]
            ]),
            'form_schema' => json_encode([
                ['name' => 'item_code', 'label' => 'SKU / Item Code', 'type' => 'text', 'validation' => 'required|string|unique:inventory_items,item_code', 'grid_width' => 6],
                ['name' => 'name', 'label' => 'Item Description', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 6],
                ['name' => 'category', 'label' => 'Category Type', 'type' => 'text', 'validation' => 'nullable|string|max:100', 'grid_width' => 6],
                ['name' => 'qty_on_hand', 'label' => 'Stock Quantity', 'type' => 'number', 'validation' => 'required|integer|min:0', 'grid_width' => 6],
                ['name' => 'unit_price', 'label' => 'Unit Price (₹)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 6],
                ['name' => 'reorder_level', 'label' => 'Reorder Threshold Level', 'type' => 'number', 'validation' => 'required|integer|min:1', 'grid_width' => 6],
                ['name' => 'status', 'label' => 'Stock status', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 12, 'options' => [
                    ['value' => 'In Stock', 'label' => 'In Stock'],
                    ['value' => 'Low Stock', 'label' => 'Low Stock'],
                    ['value' => 'Out of Stock', 'label' => 'Out of Stock']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-box-seam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page D: User Profiles Settings Configuration
        $pageUserConfig = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Staff Profiles',
            'slug' => 'staff-profiles',
            'token' => 'USR-400',
            'title' => 'Corporate User Accounts Manager',
            'db_table' => 'users',
            'primary_key' => 'id',
            'sql_query' => 'SELECT users.id, users.name, users.email, roles.name as role_name, head.name as supervisor, users.is_active FROM users LEFT JOIN roles ON users.role_id = roles.id LEFT JOIN users as head ON users.reports_to_id = head.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Full Name', 'flex' => 1.5],
                ['field' => 'email', 'headerName' => 'Corporate Email', 'flex' => 1.8],
                ['field' => 'role_name', 'headerName' => 'Security Role', 'flex' => 1.0],
                ['field' => 'supervisor', 'headerName' => 'Reporting Supervisor', 'flex' => 1.2],
                ['field' => 'is_active', 'headerName' => 'Account Active', 'flex' => 0.8]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Employee Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 6],
                ['name' => 'email', 'label' => 'Corporate Email', 'type' => 'email', 'validation' => 'required|email|unique:users,email', 'grid_width' => 6],
                ['name' => 'password', 'label' => 'Password credentials', 'type' => 'password', 'validation' => 'required|string|min:4', 'grid_width' => 6],
                ['name' => 'role_id', 'label' => 'Security Role Class', 'type' => 'select', 'validation' => 'required|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'roles', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'reports_to_id', 'label' => 'Reporting Supervisor / Head', 'type' => 'select', 'validation' => 'nullable|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'users', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'is_active', 'label' => 'Account Enabled Status', 'type' => 'select', 'validation' => 'required|boolean', 'grid_width' => 12, 'options' => [
                    ['value' => 1, 'label' => 'Active / Enabled'],
                    ['value' => 0, 'label' => 'Disabled']
                ]]
            ]),
            'is_custom' => true,
            'custom_view' => 'modules/organization_panel/user_management',
            'is_active' => true,
            'icon' => 'bi-person-badge',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page E: Custom Role-Permission Matrix Manager
        $pagePermMatrixId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Permissions Matrix',
            'slug' => 'permissions-matrix',
            'token' => 'ADM-500',
            'title' => 'Dynamic Access Permissions Control',
            'db_table' => 'role_permissions',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/organization_panel/permissions_matrix',
            'is_active' => true,
            'icon' => 'bi-shield-lock',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page F: Developer Module Studio (SQL to CRUD Generator)
        $pageDevStudioId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Developer Studio',
            'slug' => 'developer-module',
            'token' => 'DEV-100',
            'title' => 'SQL to AG Grid CRUD Page Generator',
            'db_table' => 'pages',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/developer',
            'is_active' => true,
            'icon' => 'bi-code-slash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page F: Notification Routing
        $pageNotifRoutingId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Notification Routing',
            'slug' => 'notification-routing',
            'token' => 'ADM-501',
            'title' => 'Notification Routing Hierarchy',
            'db_table' => 'notification_routes',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/organization_panel/notification_routing',
            'is_active' => true,
            'icon' => 'bi-diagram-3',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Page G: Broadcast Alerts
        $pageBroadcastId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Broadcast Alerts',
            'slug' => 'broadcast-alerts',
            'token' => 'ADM-502',
            'title' => 'System Alert Broadcasts Manager',
            'db_table' => 'broadcasts',
            'primary_key' => 'id',
            'sql_query' => null,
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'title', 'headerName' => 'Title', 'flex' => 1.5],
                ['field' => 'message', 'headerName' => 'Message', 'flex' => 3.0],
                ['field' => 'scope', 'headerName' => 'Scope', 'flex' => 1.0],
                ['field' => 'created_at', 'headerName' => 'Created At', 'flex' => 1.2]
            ]),
            'form_schema' => json_encode([
                ['name' => 'title', 'label' => 'Alert Title', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'message', 'label' => 'Alert Message Details', 'type' => 'textarea', 'validation' => 'required|string', 'grid_width' => 12],
                ['name' => 'scope', 'label' => 'Target Scope', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 6, 'options' => [
                    ['value' => 'everyone', 'label' => 'Everyone (All Staff)'],
                    ['value' => 'department', 'label' => 'Specific Department'],
                    ['value' => 'user', 'label' => 'Specific Employee']
                ]],
                ['name' => 'target_id', 'label' => 'Target ID (Required for Department/Employee)', 'type' => 'number', 'validation' => 'nullable|integer', 'grid_width' => 6]
            ]),
            'is_custom' => false,
            'custom_view' => null,
            'is_active' => true,
            'icon' => 'bi-megaphone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7.5 Additional Modules & Pages
        $modComm = DB::table('modules')->insertGetId([
            'name' => 'Communication Hub',
            'icon' => 'bi-envelope',
            'sequence' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modAnalytics = DB::table('modules')->insertGetId([
            'name' => 'Analytics Console',
            'icon' => 'bi-bar-chart-steps',
            'sequence' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageInboxId = DB::table('pages')->insertGetId([
            'module_id' => $modComm,
            'name' => 'Email Inbox',
            'slug' => 'email-inbox',
            'token' => 'EML-101',
            'title' => 'Corporate Email Inbox',
            'db_table' => 'emails',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/email/inbox',
            'is_active' => true,
            'icon' => 'bi-envelope',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageContactsId = DB::table('pages')->insertGetId([
            'module_id' => $modComm,
            'name' => 'Address Book',
            'slug' => 'email-contacts',
            'token' => 'EML-102',
            'title' => 'Email Contact Directory',
            'db_table' => 'email_contacts',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/email/contacts',
            'is_active' => true,
            'icon' => 'bi-journal-bookmark',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageComposeId = DB::table('pages')->insertGetId([
            'module_id' => $modComm,
            'name' => 'Compose Email',
            'slug' => 'email-compose',
            'token' => 'EML-103',
            'title' => 'Write Corporate Email',
            'db_table' => 'emails',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/email/compose',
            'is_active' => true,
            'icon' => 'bi-pencil-square',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageReportsId = DB::table('pages')->insertGetId([
            'module_id' => $modAnalytics,
            'name' => 'Report Builder',
            'slug' => 'report-builder',
            'token' => 'RPT-600',
            'title' => 'Dynamic Enterprise Reports',
            'db_table' => 'reports',
            'primary_key' => 'id',
            'is_custom' => true,
            'custom_view' => 'modules/analytics_console/reports',
            'is_active' => true,
            'icon' => 'bi-bar-chart-steps',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Companies CRUD Config
        $pageCompId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Company Master',
            'slug' => 'companies',
            'token' => 'COMP-100',
            'title' => 'Companies Registry',
            'db_table' => 'companies',
            'primary_key' => 'id',
            'sql_query' => 'SELECT * FROM companies',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Company Name', 'flex' => 2.0],
                ['field' => 'code', 'headerName' => 'Code', 'flex' => 1.0],
                ['field' => 'address', 'headerName' => 'Address', 'flex' => 2.0],
                ['field' => 'phone', 'headerName' => 'Phone', 'flex' => 1.2],
                ['field' => 'email', 'headerName' => 'Email', 'flex' => 1.5]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Company Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:companies,code', 'grid_width' => 6],
                ['name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'validation' => 'nullable|email|max:255', 'grid_width' => 6],
                ['name' => 'phone', 'label' => 'Contact Phone', 'type' => 'text', 'validation' => 'nullable|string|max:50', 'grid_width' => 6],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'validation' => 'nullable|string', 'grid_width' => 12]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-building-fill',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Branches CRUD Config
        $pageBranchId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Branch Master',
            'slug' => 'branches',
            'token' => 'BRCH-100',
            'title' => 'Branches Registry',
            'db_table' => 'branches',
            'primary_key' => 'id',
            'sql_query' => 'SELECT branches.id, branches.name, branches.code, companies.name as company_name, branches.address FROM branches JOIN companies ON branches.company_id = companies.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Branch Name', 'flex' => 1.8],
                ['field' => 'code', 'headerName' => 'Code', 'flex' => 1.0],
                ['field' => 'company_name', 'headerName' => 'Associated Company', 'flex' => 1.8],
                ['field' => 'address', 'headerName' => 'Address', 'flex' => 2.0]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Branch Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique Branch Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:branches,code', 'grid_width' => 6],
                ['name' => 'company_id', 'label' => 'Select Company', 'type' => 'select', 'validation' => 'required|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'companies', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'address', 'label' => 'Branch Address', 'type' => 'textarea', 'validation' => 'nullable|string', 'grid_width' => 12]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-geo-alt-fill',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Department Master CRUD Config
        $pageDeptId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Department Master',
            'slug' => 'departments',
            'token' => 'DEPT-100',
            'title' => 'Departments Registry',
            'db_table' => 'departments',
            'primary_key' => 'id',
            'sql_query' => 'SELECT departments.id, departments.name, departments.code, branches.name as branch_name, mgr.name as manager_name FROM departments JOIN branches ON departments.branch_id = branches.id LEFT JOIN users as mgr ON departments.manager_id = mgr.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Department Name', 'flex' => 1.8],
                ['field' => 'code', 'headerName' => 'Code', 'flex' => 1.0],
                ['field' => 'branch_name', 'headerName' => 'Branch Office', 'flex' => 1.8],
                ['field' => 'manager_name', 'headerName' => 'Department Manager', 'flex' => 1.5]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Department Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique Department Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:departments,code', 'grid_width' => 6],
                ['name' => 'branch_id', 'label' => 'Select Branch', 'type' => 'select', 'validation' => 'required|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'branches', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'manager_id', 'label' => 'Select Manager', 'type' => 'select', 'validation' => 'nullable|integer', 'grid_width' => 12, 'options_source' => 'table', 'options_table' => 'users', 'options_key' => 'id', 'options_value' => 'name']
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-diagram-2-fill',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Vendors CRUD Config
        $pageVendorId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Vendors Management',
            'slug' => 'vendors',
            'token' => 'VND-100',
            'title' => 'Vendors/Suppliers Directory',
            'db_table' => 'vendors',
            'primary_key' => 'id',
            'sql_query' => 'SELECT vendors.id, vendors.name, vendors.code, vendors.email, vendors.phone, companies.name as company_name, branches.name as branch_name, vendors.status FROM vendors LEFT JOIN companies ON vendors.company_id = companies.id LEFT JOIN branches ON vendors.branch_id = branches.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Vendor Name', 'flex' => 1.8],
                ['field' => 'code', 'headerName' => 'SKU Code', 'flex' => 1.0],
                ['field' => 'email', 'headerName' => 'Email', 'flex' => 1.5],
                ['field' => 'phone', 'headerName' => 'Phone', 'flex' => 1.2],
                ['field' => 'company_name', 'headerName' => 'Company', 'flex' => 1.5],
                ['field' => 'branch_name', 'headerName' => 'Branch', 'flex' => 1.5],
                ['field' => 'status', 'headerName' => 'Status', 'flex' => 0.8]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Vendor Corporate Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique Vendor Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:vendors,code', 'grid_width' => 6],
                ['name' => 'email', 'label' => 'Primary Email', 'type' => 'email', 'validation' => 'nullable|email|max:255', 'grid_width' => 6],
                ['name' => 'phone', 'label' => 'Phone / Contact No', 'type' => 'text', 'validation' => 'nullable|string|max:50', 'grid_width' => 6],
                ['name' => 'gstin', 'label' => 'GSTIN Number (15 Digits)', 'type' => 'text', 'validation' => 'nullable|string|size:15', 'grid_width' => 6],
                ['name' => 'pan', 'label' => 'PAN Card No (10 Digits)', 'type' => 'text', 'validation' => 'nullable|string|size:10', 'grid_width' => 6],
                ['name' => 'company_id', 'label' => 'Select Company', 'type' => 'select', 'validation' => 'nullable|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'companies', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'branch_id', 'label' => 'Select Branch', 'type' => 'select', 'validation' => 'nullable|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'branches', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'address', 'label' => 'Corporate Office Address', 'type' => 'textarea', 'validation' => 'nullable|string', 'grid_width' => 12],
                ['name' => 'status', 'label' => 'Account Status', 'type' => 'select', 'validation' => 'required|string', 'grid_width' => 12, 'options' => [
                    ['value' => 'Active', 'label' => 'Active'],
                    ['value' => 'Inactive', 'label' => 'Inactive']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-truck',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Tax Masters CRUD Config
        $pageTaxId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Tax Master (GST)',
            'slug' => 'tax-masters',
            'token' => 'TAX-100',
            'title' => 'GST Tax Rate Masters',
            'db_table' => 'tax_masters',
            'primary_key' => 'id',
            'sql_query' => 'SELECT * FROM tax_masters',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Tax Label', 'flex' => 1.8],
                ['field' => 'code', 'headerName' => 'Tax Code', 'flex' => 1.0],
                ['field' => 'rate', 'headerName' => 'Combined Rate (%)', 'flex' => 1.2],
                ['field' => 'cgst_rate', 'headerName' => 'CGST (%)', 'flex' => 1.0],
                ['field' => 'sgst_rate', 'headerName' => 'SGST (%)', 'flex' => 1.0],
                ['field' => 'igst_rate', 'headerName' => 'IGST (%)', 'flex' => 1.0],
                ['field' => 'is_active', 'headerName' => 'Active Status', 'flex' => 0.8]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Tax Name / Label', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique Tax Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:tax_masters,code', 'grid_width' => 6],
                ['name' => 'rate', 'label' => 'GST Combined Tax Percentage', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 6],
                ['name' => 'cgst_rate', 'label' => 'CGST Percentage (Central)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 4],
                ['name' => 'sgst_rate', 'label' => 'SGST Percentage (State)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 4],
                ['name' => 'igst_rate', 'label' => 'IGST Percentage (Integrated)', 'type' => 'number', 'validation' => 'required|numeric|min:0', 'grid_width' => 4],
                ['name' => 'is_active', 'label' => 'Tax Rule Enabled', 'type' => 'select', 'validation' => 'required|boolean', 'grid_width' => 12, 'options' => [
                    ['value' => 1, 'label' => 'Active'],
                    ['value' => 0, 'label' => 'Disabled']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-percent',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // UOM CRUD Config
        $pageUomId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Units of Measure',
            'slug' => 'uoms',
            'token' => 'UOM-100',
            'title' => 'UOM Units Master',
            'db_table' => 'uoms',
            'primary_key' => 'id',
            'sql_query' => 'SELECT * FROM uoms',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'UOM Unit Name', 'flex' => 2.0],
                ['field' => 'code', 'headerName' => 'UOM Code', 'flex' => 1.2],
                ['field' => 'is_active', 'headerName' => 'Status', 'flex' => 1.0]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'UOM Unit Description', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique UOM Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:uoms,code', 'grid_width' => 6],
                ['name' => 'is_active', 'label' => 'UOM Enabled', 'type' => 'select', 'validation' => 'required|boolean', 'grid_width' => 6, 'options' => [
                    ['value' => 1, 'label' => 'Active'],
                    ['value' => 0, 'label' => 'Disabled']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-rulers',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Cost Centers CRUD Config
        $pageCostCenterId = DB::table('pages')->insertGetId([
            'module_id' => $modErp,
            'name' => 'Cost Centers',
            'slug' => 'cost-centers',
            'token' => 'CSTC-100',
            'title' => 'Cost Centers Directory',
            'db_table' => 'cost_centers',
            'primary_key' => 'id',
            'sql_query' => 'SELECT cost_centers.id, cost_centers.name, cost_centers.code, companies.name as company_name, cost_centers.is_active FROM cost_centers LEFT JOIN companies ON cost_centers.company_id = companies.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Cost Center Description', 'flex' => 1.8],
                ['field' => 'code', 'headerName' => 'Cost Center Code', 'flex' => 1.2],
                ['field' => 'company_name', 'headerName' => 'Company Associated', 'flex' => 1.8],
                ['field' => 'is_active', 'headerName' => 'Active', 'flex' => 0.8]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Cost Center Name', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'code', 'label' => 'Unique Cost Center Code', 'type' => 'text', 'validation' => 'required|string|max:50|unique:cost_centers,code', 'grid_width' => 6],
                ['name' => 'company_id', 'label' => 'Select Company Group', 'type' => 'select', 'validation' => 'nullable|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'companies', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'description', 'label' => 'Operational Description', 'type' => 'textarea', 'validation' => 'nullable|string', 'grid_width' => 12],
                ['name' => 'is_active', 'label' => 'Enabled Status', 'type' => 'select', 'validation' => 'required|boolean', 'grid_width' => 12, 'options' => [
                    ['value' => 1, 'label' => 'Active'],
                    ['value' => 0, 'label' => 'Disabled']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-cash-coin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Workflows Config CRUD Config
        $pageWorkflowConfigId = DB::table('pages')->insertGetId([
            'module_id' => $modOrg,
            'name' => 'Workflows Config',
            'slug' => 'workflows',
            'token' => 'WF-100',
            'title' => 'Workflow Approval Chains',
            'db_table' => 'workflows',
            'primary_key' => 'id',
            'sql_query' => 'SELECT workflows.id, workflows.name, pages.name as page_name, workflows.is_active FROM workflows JOIN pages ON workflows.page_id = pages.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'name', 'headerName' => 'Workflow Chain Name', 'flex' => 1.8],
                ['field' => 'page_name', 'headerName' => 'Target ERP Page', 'flex' => 1.8],
                ['field' => 'is_active', 'headerName' => 'Status', 'flex' => 0.8]
            ]),
            'form_schema' => json_encode([
                ['name' => 'name', 'label' => 'Approval Chain Label', 'type' => 'text', 'validation' => 'required|string|max:255', 'grid_width' => 12],
                ['name' => 'page_id', 'label' => 'Target ERP Module Page', 'type' => 'select', 'validation' => 'required|integer', 'grid_width' => 6, 'options_source' => 'table', 'options_table' => 'pages', 'options_key' => 'id', 'options_value' => 'name'],
                ['name' => 'steps', 'label' => 'Workflow Steps (JSON format e.g. [{"step":1, "role_id":3, "name":"Manager Review"}])', 'type' => 'textarea', 'validation' => 'required|string', 'grid_width' => 12],
                ['name' => 'is_active', 'label' => 'Enabled Status', 'type' => 'select', 'validation' => 'required|boolean', 'grid_width' => 6, 'options' => [
                    ['value' => 1, 'label' => 'Active'],
                    ['value' => 0, 'label' => 'Disabled']
                ]]
            ]),
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-diagram-3-fill',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Audit Log Viewer CRUD Config (Read Only)
        $pageAuditLogId = DB::table('pages')->insertGetId([
            'module_id' => $modAnalytics,
            'name' => 'Audit Log Viewer',
            'slug' => 'audit-logs',
            'token' => 'AUDIT-100',
            'title' => 'Enterprise System Audit Trails',
            'db_table' => 'audit_logs',
            'primary_key' => 'id',
            'sql_query' => 'SELECT audit_logs.id, users.name as user_name, audit_logs.action, audit_logs.table_name, audit_logs.record_id, audit_logs.ip_address, audit_logs.created_at FROM audit_logs LEFT JOIN users ON audit_logs.user_id = users.id',
            'grid_schema' => json_encode([
                ['field' => 'id', 'headerName' => 'ID', 'flex' => 0.5],
                ['field' => 'user_name', 'headerName' => 'Employee / Actor', 'flex' => 1.8],
                ['field' => 'action', 'headerName' => 'Action Type', 'flex' => 1.0],
                ['field' => 'table_name', 'headerName' => 'Target Entity Table', 'flex' => 1.5],
                ['field' => 'record_id', 'headerName' => 'Record ID', 'flex' => 0.8],
                ['field' => 'ip_address', 'headerName' => 'IP Address', 'flex' => 1.2],
                ['field' => 'created_at', 'headerName' => 'Logged Date', 'flex' => 1.5]
            ]),
            'form_schema' => json_encode([]), // No form schema means view-only
            'is_custom' => false,
            'is_active' => true,
            'icon' => 'bi-shield-check',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 8. Seeding Permissions Matrix
        $pages = [
            $pageCustId, $pageInvId, $pageSalesQuotationId, $pageSalesOrderId, $pagePurchaseOrderId, $pagePurchaseInvoiceId, $pageInvItem, $pageUserConfig, $pagePermMatrixId, $pageDevStudioId, 
            $pageNotifRoutingId, $pageBroadcastId, $pageInboxId, $pageContactsId, 
            $pageComposeId, $pageReportsId,
            $pageCompId, $pageBranchId, $pageDeptId, $pageVendorId, $pageTaxId, 
            $pageUomId, $pageCostCenterId, $pageWorkflowConfigId, $pageAuditLogId
        ];

        // Super Admin gets unrestricted bypass automatically.
        // Seed Admin permissions
        foreach ($pages as $p) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds['admin'],
                'page_id' => $p,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_export' => true,
                'can_print' => true,
                'can_approve' => true,
                'can_reject' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Sales Head permissions (sees and modifies ERP core)
        foreach ($pages as $p) {
            $isRestricted = in_array($p, [$pagePermMatrixId, $pageNotifRoutingId, $pageBroadcastId, $pageWorkflowConfigId, $pageAuditLogId]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds['sales-head'],
                'page_id' => $p,
                'can_view' => $isRestricted ? false : true,
                'can_create' => $isRestricted ? false : true,
                'can_edit' => $isRestricted ? false : true,
                'can_delete' => false,
                'can_export' => true,
                'can_print' => true,
                'can_approve' => true,
                'can_reject' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Sales Representative permissions (only Customers & Invoices)
        foreach ($pages as $p) {
            $hasAccess = in_array($p, [$pageCustId, $pageInvId, $pageSalesQuotationId, $pageSalesOrderId, $pageInboxId, $pageContactsId, $pageComposeId, $pageReportsId, $pageVendorId, $pageTaxId, $pageUomId]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds['sales-rep'],
                'page_id' => $p,
                'can_view' => $hasAccess,
                'can_create' => $hasAccess,
                'can_edit' => $hasAccess,
                'can_delete' => false,
                'can_export' => $hasAccess,
                'can_print' => $hasAccess,
                'can_approve' => false,
                'can_reject' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Accounts Head permissions
        foreach ($pages as $p) {
            $hasAccess = in_array($p, [$pageInvId, $pageSalesQuotationId, $pageSalesOrderId, $pagePurchaseOrderId, $pagePurchaseInvoiceId, $pageInvItem, $pageInboxId, $pageContactsId, $pageComposeId, $pageReportsId, $pageVendorId, $pageTaxId, $pageUomId, $pageCostCenterId]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds['accounts-head'],
                'page_id' => $p,
                'can_view' => $hasAccess,
                'can_create' => $hasAccess,
                'can_edit' => $hasAccess,
                'can_delete' => false,
                'can_export' => true,
                'can_print' => true,
                'can_approve' => true,
                'can_reject' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed Accounts Member permissions
        foreach ($pages as $p) {
            $hasAccess = in_array($p, [$pageInvId, $pageSalesQuotationId, $pageSalesOrderId, $pagePurchaseOrderId, $pagePurchaseInvoiceId, $pageInvItem, $pageInboxId, $pageContactsId, $pageComposeId, $pageReportsId, $pageVendorId, $pageTaxId, $pageUomId, $pageCostCenterId]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds['accounts-member'],
                'page_id' => $p,
                'can_view' => $hasAccess,
                'can_create' => false,
                'can_edit' => false,
                'can_delete' => false,
                'can_export' => true,
                'can_print' => true,
                'can_approve' => false,
                'can_reject' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed General User permissions
        foreach ($pages as $p) {
            $isRestricted = in_array($p, [$pagePermMatrixId, $pageUserConfig, $pageNotifRoutingId, $pageBroadcastId, $pageWorkflowConfigId, $pageAuditLogId]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds['user'],
                'page_id' => $p,
                'can_view' => $isRestricted ? false : true,
                'can_create' => false,
                'can_edit' => false,
                'can_delete' => false,
                'can_export' => true,
                'can_print' => true,
                'can_approve' => false,
                'can_reject' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        }

        // 9. Reference Tax Masters (Idempotent - no sample business transactions)
        $taxMasters = [
            ['code' => 'GST-5', 'name' => 'GST 5%', 'rate' => 5.00, 'cgst_rate' => 2.50, 'sgst_rate' => 2.50, 'igst_rate' => 5.00],
            ['code' => 'GST-12', 'name' => 'GST 12%', 'rate' => 12.00, 'cgst_rate' => 6.00, 'sgst_rate' => 6.00, 'igst_rate' => 12.00],
            ['code' => 'GST-18', 'name' => 'GST 18%', 'rate' => 18.00, 'cgst_rate' => 9.00, 'sgst_rate' => 9.00, 'igst_rate' => 18.00],
        ];
        foreach ($taxMasters as $tax) {
            DB::table('tax_masters')->updateOrInsert(
                ['code' => $tax['code']],
                array_merge($tax, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // 10. Reference UOMs (Idempotent)
        $uoms = [
            ['code' => 'PCS', 'name' => 'Pieces'],
            ['code' => 'KGS', 'name' => 'Kilograms'],
        ];
        foreach ($uoms as $uom) {
            DB::table('uoms')->updateOrInsert(
                ['code' => $uom['code']],
                array_merge($uom, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->call(ComprehensiveErpSeeder::class);
    }
}
