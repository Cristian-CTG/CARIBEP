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
            Route::get('charts/columns', 'ChartOfAccountController@columns');
            Route::get('charts/children/{parent_id}', 'ChartOfAccountController@getChildren');
            Route::get('charts/parent/{parent_id}', 'ChartOfAccountController@getChildren');
            Route::get('charts/tree', 'ChartOfAccountController@tree');
            Route::get('charts/tables', 'ChartOfAccountController@tables');
            Route::post('charts/accounts-configuration', 'ChartOfAccountController@accountConfiguration');
            Route::apiResource('charts', 'ChartOfAccountController')->names([
                'index'   => 'tenant.accounting.charts.index',
            ]);

            // CRUD para Prefijos de Asientos Contables
            Route::apiResource('journal/prefixes', 'JournalPrefixController');

            // CRUD para Asientos Contables
            Route::get('journal/entries/columns', 'JournalEntryController@columns');
            Route::get('journal/entries/records', 'JournalEntryController@records');
            Route::apiResource('journal/entries', 'JournalEntryController')->names([
                'index'   => 'tenant.accounting.journal.entries.index',
            ]);
            Route::get('journal/entries/pdf/{id}', 'JournalEntryController@getPdf');
            Route::put('journal/entries/{id}/request-approval', 'JournalEntryController@requestApproval');
            Route::put('journal/entries/{id}/approve', 'JournalEntryController@approve');
            Route::put('journal/entries/{id}/reject', 'JournalEntryController@reject');

            // CRUD para Detalles de Asientos Contables
            Route::apiResource('journal/entry-details', 'JournalEntryDetailController');
            Route::get('journal/entries/{id}/records-detail', 'JournalEntryDetailController@recordsDetail');

            // Reportes
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