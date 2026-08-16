<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WalletTransaction;

class WalletController extends Controller
{
    public function addBalance(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        // زيادة الرصيد
        $user->wallet_balance += $request->amount;
        $user->save();

        // تسجيل العملية
        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'add',
            'description' => $request->description ?? 'شحن رصيد',
        ]);

        return response()->json([
            'message' => 'تم شحن الرصيد بنجاح',
            'balance' => $user->wallet_balance,
        ]);
    }
    public function transactions()
{
    $user = auth()->user();

    $transactions = WalletTransaction::where('user_id', $user->id)
        ->orderBy('id', 'desc')
        ->get();

    return response()->json($transactions);
}

}
