<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Requester',
            'Dept Head',
            'IT Head',
            'Finance Head',
            'CFO',
            'Procurement Officer',
            'Store Keeper',
            'Accounts',
            'Admin',
        ];

        $roleModels = [];
        foreach ($roles as $roleName) {
            $roleModels[$roleName] = Role::firstOrCreate(['name' => $roleName]);
        }

        $mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $blr = Company::where('code', 'ACME-BLR')->firstOrFail();

        $users = [
            // ACME-MUM
            ['email' => 'admin.mum@acme.test', 'company' => $mum, 'roles' => ['Admin']],
            ['email' => 'req.mum@acme.test', 'company' => $mum, 'roles' => ['Requester']],
            ['email' => 'depthead.mum@acme.test', 'company' => $mum, 'roles' => ['Dept Head', 'Requester']],
            ['email' => 'ithead.mum@acme.test', 'company' => $mum, 'roles' => ['IT Head']],
            ['email' => 'finhead.mum@acme.test', 'company' => $mum, 'roles' => ['Finance Head']],
            ['email' => 'cfo.mum@acme.test', 'company' => $mum, 'roles' => ['CFO']],
            ['email' => 'proc.mum@acme.test', 'company' => $mum, 'roles' => ['Procurement Officer']],
            ['email' => 'store.mum@acme.test', 'company' => $mum, 'roles' => ['Store Keeper']],
            ['email' => 'accounts.mum@acme.test', 'company' => $mum, 'roles' => ['Accounts']],

            // ACME-BLR
            ['email' => 'admin.blr@acme.test', 'company' => $blr, 'roles' => ['Admin']],
            ['email' => 'req.blr@acme.test', 'company' => $blr, 'roles' => ['Requester']],
            ['email' => 'depthead.blr@acme.test', 'company' => $blr, 'roles' => ['Dept Head']],
            ['email' => 'proc.blr@acme.test', 'company' => $blr, 'roles' => ['Procurement Officer']],
        ];

        foreach ($users as $userData) {
            $name = ucwords(str_replace('.', ' ', explode('@', $userData['email'])[0]));
            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'company_id' => $userData['company']->id,
                    'name' => $name,
                    'password' => Hash::make('password'),
                ]
            );

            $roleIds = collect($userData['roles'])->map(fn($r) => $roleModels[$r]->id);
            $user->roles()->sync($roleIds);
        }
    }
}
