<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if($hostname) {
    Route::domain($hostname->fqdn)->group(function () {
        Route::prefix('accounting')->middleware(['auth','redirect.module'])->group(function() {
            Route::get('/', 'AccountingController@index');
            Route::get('/columns', 'AccountingController@columns');

            // CRUD para Cuentas Contables
            Route::get('charts/records', 'ChartOfAccountController@records');
            Route::get('charts/all', 'ChartOfAccountController@allAccounts');
            Route::get('charts/columns', 'ChartOfAccountController@columns');
            Route::get('charts/children/{parent_id}', 'ChartOfAccountController@getChildren');
            Route::get('charts/parent/{parent_id}', 'ChartOfAccountController@getChildren');
            Route::get('charts/tree', 'ChartOfAccountController@tree');
            Route::get('charts/tables', 'ChartOfAccountController@tables');
            Route::get('charts/records-by-groups', 'ChartOfAccountController@recordsByGroups');
            Route::post('charts/import-excel', 'ChartOfAccountController@importExcel');
            Route::post('charts/accounts-configuration', 'ChartOfAccountController@accountConfiguration');
            Route::apiResource('charts', 'ChartOfAccountController')->names([
                'index'   => 'tenant.accounting.charts.index',
            ]);

            // CRUD para Prefijos de Asientos Contables
            Route::apiResource('journal/prefixes', 'JournalPrefixController');

            // CRUD para Asientos Contables
            Route::get('journal/entries/columns', 'JournalEntryController@columns');
            Route::get('journal/entries/records', 'JournalEntryController@records');
            Route::get('journal/search-accounts', 'JournalEntryController@searchAccounts');
            // Exportar e Importar Asientos Contables
            Route::get('journal/entries/import-format', 'JournalEntryExportImportController@downloadImportFormat');
            Route::get('journal/entries/export-excel', 'JournalEntryExportImportController@exportExcel');
            Route::post('journal/entries/import-excel', 'JournalEntryExportImportController@importExcel');
            Route::apiResource('journal/entries', 'JournalEntryController')->names([
                'index'   => 'tenant.accounting.journal.entries.index',
            ]);
            Route::get('journal/entries/pdf/{id}', 'JournalEntryController@getPdf');
            Route::put('journal/entries/{id}/request-approval', 'JournalEntryController@requestApproval');
            Route::put('journal/entries/{id}/approve', 'JournalEntryController@approve');
            Route::put('journal/entries/{id}/reject', 'JournalEntryController@reject');

            // Terceros
            Route::get('journal/thirds/third-parties', 'ThirdPartyController@index');
            Route::post('journal/thirds/sync-from-origin', 'ThirdPartyController@syncFromOrigin');
            Route::get('journal/thirds/all-third-parties', 'ThirdPartyController@allThirdParties');

            //Records
            Route::get('/bank-book/records', 'JournalEntryController@bankAccounts');
            Route::get('/payment-methods', 'JournalEntryController@paymentMethods');

            // CRUD para Detalles de Asientos Contables
            Route::apiResource('journal/entry-details', 'JournalEntryDetailController');
            Route::get('journal/entries/{id}/records-detail', 'JournalEntryDetailController@recordsDetail');

            // Reportes
            // Reporte de Detalles de Asientos Contables
            Route::get('/entry-details-report', 'JournalEntryDetailsReportController@index')->name('tenant.accounting.report.entry-details-report');
            Route::get('/entry-details-report/records', 'JournalEntryDetailsReportController@records');
            Route::get('/entry-details-report/columns', 'JournalEntryDetailsReportController@columns');
            Route::get('/entry-details-report/export', 'JournalEntryDetailsReportController@export');
            Route::get('/entry-details-report/export-excel', 'JournalEntryDetailsReportController@exportExcel');
            // Reporte de Situación Financiera
            Route::get('/financial-position', 'ReportFinancialPositionController@index')->name('tenant.accounting.report.financial-position');
            Route::get('/financial-position/records', 'ReportFinancialPositionController@records');
            Route::get('/financial-position/export', 'ReportFinancialPositionController@export');
            // Reporte de Estado de Resultados
            Route::get('/income-statement', 'ReportIncomeStatementController@index')->name('tenant.accounting.report.income-statement');
            Route::get('/income-statement/records', 'ReportIncomeStatementController@records');
            Route::get('/income-statement/export', 'ReportIncomeStatementController@export');
            // Reporte de Movimientos Auxiliares
            Route::get('/auxiliary-movement', 'ReportAuxiliaryMovementController@index')->name('tenant.accounting.report.auxiliary-movement');
            Route::get('/auxiliary-movement/records', 'ReportAuxiliaryMovementController@records');
            Route::get('/auxiliary-movement/export', 'ReportAuxiliaryMovementController@export');
            // Reporte de Libro Bancario
            Route::get('/bank-book', 'ReportBankBookController@index')->name('tenant.accounting.report.bank-book');
            // Route::get('/bank-book/records', 'ReportBankBookController@records');
            Route::get('/bank-book/export', 'ReportBankBookController@export');
            Route::get('/bank-book/preview', 'ReportBankBookController@preview');

            // Reporte de Conciliacion Bancaria
            Route::get('/bank-reconciliation', 'BankReconciliationController@index')->name('tenant.accounting.bank-reconciliation.index');
            Route::get('/bank-reconciliation/records', 'BankReconciliationController@records');
            Route::get('/bank-reconciliation/export', 'BankReconciliationController@export');
            Route::get('/bank-reconciliation/columns', 'BankReconciliationController@columns');
            Route::get('/bank-reconciliation/bank-accounts', 'BankReconciliationController@bankAccounts');
            Route::get('/bank-reconciliation/movements', 'BankReconciliationController@movements');
            Route::post('/bank-reconciliation/store', 'BankReconciliationController@store');
            Route::get('/bank-reconciliation/{id}/edit', 'BankReconciliationController@edit');
            Route::get('/bank-reconciliation/pdf/{id}', 'BankReconciliationController@pdf');
            Route::delete('/bank-reconciliation/{id}', 'BankReconciliationController@destroy');
            Route::get('/bank-reconciliation/export-excel', 'BankReconciliationController@exportExcel');
            
            // Reporte de Terceros
            Route::get('/third-report', 'ReportThirdController@index')->name('tenant.accounting.report.third-report');
            Route::get('/third-report/records', 'ReportThirdController@records');
            Route::get('/third-report/preview-records', 'ReportThirdController@previewRecords');
            Route::get('/third-report/export', 'ReportThirdController@export');
            Route::get('/third-report/export-all', 'ReportThirdController@exportAllThirds');
            Route::get('/third-report/export-excel', 'ReportThirdController@exportExcel');
            Route::get('/third-report/export-all-excel', 'ReportThirdController@exportAllThirdsExcel');

            // Reporte de Balance de Prueba General
            Route::get('/trial-balance', 'ReportTrialBalanceController@index')->name('tenant.accounting.report.trial-balance');
            Route::get('/trial-balance/records', 'ReportTrialBalanceController@records');
            Route::get('/trial-balance/export', 'ReportTrialBalanceController@export');

            Route::prefix('clasification-sale')->group(function () {
                Route::get('records', 'ChartAccountSaleConfigurationController@records');
                Route::get('record/{id}', 'ChartAccountSaleConfigurationController@record');
                Route::get('tables', 'ChartAccountSaleConfigurationController@tables');
                Route::post('', 'ChartAccountSaleConfigurationController@store');
                Route::put('{id}', 'ChartAccountSaleConfigurationController@update');
                Route::delete('{id}', 'ChartAccountSaleConfigurationController@destroy');
            });


        });
    });
}