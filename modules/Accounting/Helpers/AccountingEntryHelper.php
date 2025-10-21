<?php

namespace Modules\Accounting\Helpers;

use Modules\Accounting\Helpers\AccountBalanceHelper;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryDetail;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Factcolombia1\Models\Tenant\Tax;
use Carbon\Carbon;

class AccountingEntryHelper
{

    public static function registerEntry(array $document): void
    {
        $entry = JournalEntry::createWithNumber([
            'date' => date('Y-m-d'),
            'journal_prefix_id' => $document['prefix_id'],
            'description' => $document['description'],
            'document_id' => $document['document_id'] ?? null,
            'purchase_id' => $document['purchase_id'] ?? null,
            'document_pos_id' => $document['document_pos_id'] ?? null,
            'document_payroll_id' => $document['document_payroll_id'] ?? null,
            'support_document_id' => $document['support_document_id'] ?? null,
            'status' => 'posted',
        ]);

        foreach ($document['movements'] as $movement) {
            $entry->details()->create([
                'chart_of_account_id' => $movement['account_id'],
                'debit' => $movement['debit'],
                'credit' => $movement['credit'],
                'third_party_id' => $movement['third_party_id'] ?? null,
                'payment_method_name' => $movement['payment_method_name'] ?? null,
                'bank_account_id' => $movement['bank_account_id'] ?? null,
            ]);

            if ($movement['affects_balance'] ?? false) {
                AccountBalanceHelper::applyMovementToBalance(
                    $movement['account_id'],
                    Carbon::now(),
                    $movement['debit'],
                    $movement['credit']
                );
            }
        }

        // Procesar impuestos
        if (!empty($document['taxes'])) {
            self::handleTaxes($entry, $document['taxes'], $document['tax_config']);
        }
    }

    private static function handleTaxes($entry, array $taxes, array $taxConfig): void
    {
        foreach ($taxes as $taxData) {
            $tax = is_array($taxData) ? (object)$taxData : $taxData;
            $taxModel = Tax::find($tax->id);
            if (!$taxModel) continue;

            // Obtener el tercer implicado si viene en taxConfig
            $thirdPartyId = $taxConfig['third_party_id'] ?? null;
            $payment_method_name = $taxConfig['payment_method_name'] ?? null; // <-- nuevo

            // Impuestos normales
            if (floatval($tax->total) > 0 && isset($taxConfig['tax_field'])) {
                $accountCode = $taxModel->{$taxConfig['tax_field']} ?? null;
                if ($accountCode) {
                    $account = ChartOfAccount::where('code', $accountCode)->first();
                    if ($account) {
                        $entry->details()->create([
                            'chart_of_account_id' => $account->id,
                            'debit' => $taxConfig['tax_debit'] ? $tax->total : 0,
                            'credit' => $taxConfig['tax_credit'] ? $tax->total : 0,
                            'third_party_id' => $thirdPartyId ?? null,
                            'payment_method_name' => $payment_method_name, // <-- aquí
                        ]);
                    }
                }
            }

            // Retención
            if ($tax->is_retention && floatval($tax->retention) > 0 && isset($taxConfig['tax_field'])) {
                $accountCode = $taxModel->{$taxConfig['tax_field']} ?? null;
                if ($accountCode) {
                    $account = ChartOfAccount::where('code', $accountCode)->first();
                    if ($account) {
                        $entry->details()->create([
                            'chart_of_account_id' => $account->id,
                            'debit' => $taxConfig['retention_debit'] ? $tax->retention : 0,
                            'credit' => $taxConfig['retention_credit'] ? $tax->retention : 0,
                            'third_party_id' => $thirdPartyId ?? null,
                            'payment_method_name' => $payment_method_name, // <-- aquí
                        ]);
                    }
                }
            }
        }
    }
}