<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\ApprovalRule;
use App\Models\ApprovalRuleStep;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ApprovalRuleSeeder extends Seeder
{
    public function run(): void
    {
        $deptHead = Role::where('name', 'Dept Head')->firstOrFail();
        $itHead = Role::where('name', 'IT Head')->firstOrFail();
        $finHead = Role::where('name', 'Finance Head')->firstOrFail();
        $cfo = Role::where('name', 'CFO')->firstOrFail();

        $companies = Company::whereIn('code', ['ACME-MUM', 'ACME-BLR'])->get();

        foreach ($companies as $company) {
            // Rule 1: PR, 0 to 50000, any category: Dept Head
            $rule1 = ApprovalRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'document_type' => DocumentType::PR,
                'min_amount' => 0.00,
                'max_amount' => 50000.00,
                'category' => null,
                'rule_type' => 'BASE',
                'insert_after_role' => null,
                'is_active' => true,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule1->id,
                'sequence' => 1,
                'role_id' => $deptHead->id,
            ]);

            // Rule 2: PR, 50000.01 to 500000, any: Dept Head > Finance Head
            $rule2 = ApprovalRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'document_type' => DocumentType::PR,
                'min_amount' => 50000.01,
                'max_amount' => 500000.00,
                'category' => null,
                'rule_type' => 'BASE',
                'insert_after_role' => null,
                'is_active' => true,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule2->id,
                'sequence' => 1,
                'role_id' => $deptHead->id,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule2->id,
                'sequence' => 2,
                'role_id' => $finHead->id,
            ]);

            // Rule 3: PR, 500000.01 to unlimited, any: Dept Head > Finance Head > CFO
            $rule3 = ApprovalRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'document_type' => DocumentType::PR,
                'min_amount' => 500000.01,
                'max_amount' => null,
                'category' => null,
                'rule_type' => 'BASE',
                'insert_after_role' => null,
                'is_active' => true,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule3->id,
                'sequence' => 1,
                'role_id' => $deptHead->id,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule3->id,
                'sequence' => 2,
                'role_id' => $finHead->id,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule3->id,
                'sequence' => 3,
                'role_id' => $cfo->id,
            ]);

            // Rule 4: PR, any amount, category IT: EXTENSION rule, inserts "IT Head" after "Dept Head"
            $rule4 = ApprovalRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'document_type' => DocumentType::PR,
                'min_amount' => 0.00,
                'max_amount' => null,
                'category' => 'IT',
                'rule_type' => 'EXTENSION',
                'insert_after_role' => 'Dept Head',
                'is_active' => true,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule4->id,
                'sequence' => 1,
                'role_id' => $itHead->id,
            ]);

            // Rule 5: PO, 0 to 200000, any: Finance Head
            $rule5 = ApprovalRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'document_type' => DocumentType::PO,
                'min_amount' => 0.00,
                'max_amount' => 200000.00,
                'category' => null,
                'rule_type' => 'BASE',
                'insert_after_role' => null,
                'is_active' => true,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule5->id,
                'sequence' => 1,
                'role_id' => $finHead->id,
            ]);

            // Rule 6: PO, 200000.01 to unlimited, any: Finance Head > CFO
            $rule6 = ApprovalRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'document_type' => DocumentType::PO,
                'min_amount' => 200000.01,
                'max_amount' => null,
                'category' => null,
                'rule_type' => 'BASE',
                'insert_after_role' => null,
                'is_active' => true,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule6->id,
                'sequence' => 1,
                'role_id' => $finHead->id,
            ]);
            ApprovalRuleStep::create([
                'approval_rule_id' => $rule6->id,
                'sequence' => 2,
                'role_id' => $cfo->id,
            ]);
        }
    }
}
