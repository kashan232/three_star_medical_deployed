<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BranchManagerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Curated List of Standard ERP Permissions for Branch Manager
        $managerPermissions = [
            // General / Core
            'home.view',
            'profile.view',
            'profile.edit',

            // Sales & Distribution
            'sales.view',
            'sales.create',
            'sales.edit',
            'sales.unpost',
            'sales.returns.view',
            'sales.returns.create',
            'sales.returns.edit',
            'customers.view',
            'customers.create',
            'customers.edit',
            'customer.ledger.view',
            'bookings.view',
            'bookings.create',
            'bookings.edit',
            'product.bookings.view',
            'product.bookings.create',
            'product.bookings.edit',
            'sales.officers.view',
            'sales.officers.create',
            'sales.officers.edit',
            'zones.view',

            // Purchases & Receiving
            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.unpost',
            'purchase.returns.view',
            'purchase.returns.create',
            'purchase.returns.edit',
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendor.bilties.view',
            'vendor.bilties.create',
            'vendor.bilties.edit',
            'inward.gatepass.view',
            'inward.gatepass.create',
            'inward.gatepass.edit',

            // Inventory & Warehouse Management
            'products.view',
            'products.create',
            'products.edit',
            'categories.view',
            'categories.create',
            'categories.edit',
            'subcategories.view',
            'subcategories.create',
            'subcategories.edit',
            'brands.view',
            'brands.create',
            'brands.edit',
            'units.view',
            'units.create',
            'units.edit',
            'warehouse.view',
            'warehouse.stock.view',
            'stock.transfer.view',
            'stock.transfer.create',
            'stock.transfer.edit',
            'stock.adjust.view',
            'stock.adjust.create',
            'stock.adjust.edit',
            'stocks.view',
            'inventory.onhand.view',
            'discount.products.view',
            'discount.products.create',
            'discount.products.edit',
            'package.types.view',

            // Accounts & Vouchers (Branch level operational accounts)
            'chart.of.accounts.view',
            'expense.voucher.view',
            'expense.voucher.create',
            'expense.voucher.edit',
            'receipts.voucher.view',
            'receipts.voucher.create',
            'receipts.voucher.edit',
            'payment.voucher.view',
            'payment.voucher.create',
            'payment.voucher.edit',
            'income.voucher.view',
            'income.voucher.create',
            'income.voucher.edit',
            'journal.voucher.view',
            'journal.voucher.create',
            'journal.voucher.edit',
            'narrations.view',
            'narrations.create',
            'narrations.edit',

            // Reporting
            'reporting.view',
            'sale.report.view',
            'purchase.report.view',
            'item.stock.report.view',

            // HR & Staff (Branch level management)
            'hr.employees.view',
            'hr.employees.create',
            'hr.employees.edit',
            'hr.departments.view',
            'hr.designations.view',
            'hr.shifts.view',
            'hr.holidays.view',
            'hr.attendance.view',
            'hr.attendance.create',
            'hr.attendance.mark',
            'hr.attendance.report',
            'hr.leaves.view',
            'hr.leaves.create',
            'hr.leaves.edit',
            'hr.leaves.approve',
            'hr.payroll.view',
            'hr.loans.view',
            'hr.loans.create',
            'hr.loans.edit',
            'hr.loans.approve',
        ];

        // 2. Ensure each permission exists in database
        foreach ($managerPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // 3. Find or Create Manager roles (handling various casing/naming)
        $targetRoleNames = ['manager', 'Manager', 'Branch Manager'];
        $existingRoles = Role::whereIn('name', $targetRoleNames)->get();

        if ($existingRoles->isEmpty()) {
            $existingRoles = collect([Role::firstOrCreate(['name' => 'Manager'])]);
        }

        // 4. Sync Permissions to all matching Manager roles
        foreach ($existingRoles as $role) {
            $role->syncPermissions($managerPermissions);
        }

        // Flush permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
