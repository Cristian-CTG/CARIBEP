<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\ChartAccountSaleConfiguration;

class ChartAccountSaleConfigurationController extends Controller
{

    public function records(Request $request)
    {
        return [
            'account_sale_configurations' => ChartAccountSaleConfiguration::all(),
        ];
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'income_account' => 'required|string|max:10',
            'sales_returns_account' => 'required|max:10',
            'accounting_clasification' => 'required'
        ]);

        $account = ChartAccountSaleConfiguration::create($request->only(['income_account', 'sales_returns_account', 'accounting_clasification']));

        return response()->json([
            'success' => true,
            'message' => 'Clasificación contable creado exitosamente',
            'data' => $account
        ], 201);
    }

    public function record($id)
    {
        $record = ChartAccountSaleConfiguration::findOrFail($id);

        return $record;
    }


    public function update(Request $request, $id)
    {
        $account = ChartAccountSaleConfiguration::findOrFail($id);

        $request->validate([
            'income_account' => 'required|string|max:10',
            'sales_returns_account' => 'required|max:10',
            'accounting_clasification' => 'required'
        ]);

        $account->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Clasificación contable actualizado exitosamente',
            'data' => $account
        ], 200);
    }

    public function destroy($id)
    {
        $account = ChartAccountSaleConfiguration::findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Clasificación contable eliminado exitosamente']);
    }
    
    public function tables() {

        return [
            'chart_accounts_sales'=> ChartOfAccount::where('level','>=',4)->get(),
            'chart_accounts_purchases' => ChartOfAccount::where('level','>=',4)->get()
        ];

    }



}
