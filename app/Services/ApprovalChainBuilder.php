<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\ApprovalRule;
use App\Models\Role;
use RuntimeException;

class ApprovalChainBuilder
{
    /**
     * Build the ordered list of role names for approval.
     * Uses bcmath to compare money amounts precisely without float drift.
     *
     * @param string|DocumentType $documentType PR or PO
     * @param string|float|int $amount Total document value (e.g. "195000.00")
     * @param string|array|null $categories Single category, array of distinct categories, or null
     * @param int $companyId Company entity ID
     * @return array<int, string> Ordered list of role names e.g. ['Dept Head', 'IT Head', 'Finance Head']
     */
    public function build(
        string|DocumentType $documentType,
        string|float|int $amount,
        string|array|null $categories,
        int $companyId
    ): array {
        $docType = $documentType instanceof DocumentType ? $documentType->value : $documentType;
        $amountStr = number_format((float) $amount, 2, '.', '');

        // Normalize categories into unique list
        $categoryList = [];
        if (is_array($categories)) {
            $categoryList = array_values(array_filter(array_unique($categories)));
        } elseif (is_string($categories) && trim($categories) !== '') {
            $categoryList = [trim($categories)];
        }

        // 1. Find the ONE active BASE rule where min_amount <= amount <= max_amount
        $baseRules = ApprovalRule::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('document_type', $docType)
            ->where('rule_type', 'BASE')
            ->where('is_active', true)
            ->with(['steps.role'])
            ->get();

        $matchedBaseRule = null;
        foreach ($baseRules as $rule) {
            $min = number_format((float) $rule->min_amount, 2, '.', '');
            $minMatches = bccomp($amountStr, $min, 2) >= 0;

            $maxMatches = true;
            if ($rule->max_amount !== null) {
                $max = number_format((float) $rule->max_amount, 2, '.', '');
                $maxMatches = bccomp($amountStr, $max, 2) <= 0;
            }

            if ($minMatches && $maxMatches) {
                $matchedBaseRule = $rule;
                break;
            }
        }

        if (! $matchedBaseRule) {
            throw new RuntimeException("No active BASE approval rule found for {$docType} amount {$amountStr} and company {$companyId}.");
        }

        // 2. Start the chain from that rule's steps in sequence order
        $chain = [];
        foreach ($matchedBaseRule->steps->sortBy('sequence') as $step) {
            $chain[] = $step->role->name;
        }

        // 3. Find active EXTENSION rules matching the categories and amount
        if (! empty($categoryList)) {
            $extensionRules = ApprovalRule::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('document_type', $docType)
                ->where('rule_type', 'EXTENSION')
                ->where('is_active', true)
                ->whereIn('category', $categoryList)
                ->with(['steps.role'])
                ->get();

            // Track extension roles inserted to avoid duplicate entries if multiple lines have same category
            $appliedExtensionRuleIds = [];

            foreach ($extensionRules as $extRule) {
                if (in_array($extRule->id, $appliedExtensionRuleIds, true)) {
                    continue;
                }

                // Check amount boundaries for extension rule if specified
                $min = number_format((float) $extRule->min_amount, 2, '.', '');
                $minMatches = bccomp($amountStr, $min, 2) >= 0;

                $maxMatches = true;
                if ($extRule->max_amount !== null) {
                    $max = number_format((float) $extRule->max_amount, 2, '.', '');
                    $maxMatches = bccomp($amountStr, $max, 2) <= 0;
                }

                if (! ($minMatches && $maxMatches)) {
                    continue;
                }

                $rolesToInsert = [];
                foreach ($extRule->steps->sortBy('sequence') as $extStep) {
                    $rolesToInsert[] = $extStep->role->name;
                }

                // Insert their roles right after rule.insert_after_role
                $targetRole = $extRule->insert_after_role;
                $targetIndex = array_search($targetRole, $chain, true);

                if ($targetIndex !== false) {
                    array_splice($chain, $targetIndex + 1, 0, $rolesToInsert);
                } else {
                    // Fallback if target role not in chain: append to end
                    foreach ($rolesToInsert as $r) {
                        $chain[] = $r;
                    }
                }

                $appliedExtensionRuleIds[] = $extRule->id;
            }
        }

        return array_values($chain);
    }
}
