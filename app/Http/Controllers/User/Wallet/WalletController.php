<?php

namespace App\Http\Controllers\User\Wallet;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Helpers\IdPay;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    protected int $maxAmount = 50_000_000; // Rials
    protected int $minAmount =     30_000; // Rials

    public function getWallet(Request $request)
    {
        $userId = Auth()->id();

        $amount = Transaction::where([
            'user_id' => $userId,
            'status' => 'success',
        ])
            ->whereIn('type', ['user_charge', 'user_clear', 'travel_cost'])
            ->sum('amount');

        return response()->json([
            'status' => 'success',
            'data' => [
                'amount' => $amount,
            ],
        ]);
    }

    public function getTransactions(Request $request)
    {
        $userId = Auth()->id();

        $transactions = Transaction::where('user_id', $userId)
            ->whereIn('type', ['user_charge', 'user_clear', 'travel_cost'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'transactions' => $transactions,
            ],
        ]);
    }

    public function charge(Request $request)
    {
        $userId = Auth()->id();
        $amount = (int) $request->get('amount');

        $validator = Validator::make($request->all(), [
            'amount' => "required|numeric|max:{$this->maxAmount}|min:{$this->minAmount}",
        ], [
            'numeric' => 'numeric',
            'min' => 'min',
            'max' => 'max',
            'required' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $transaction                = new Transaction;
        $transaction->user_id       = $userId;
        $transaction->amount        = $amount;
        $transaction->type          = 'user_charge';
        $transaction->status        = 'success';
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction' => $transaction,
            ],
        ]);
    }

    // public function clear(Request $request)
    // {
    //     $userId = Auth()->id();
    //     $amount = (int) $request->get('amount');

    //     $validator = Validator::make($request->all(), [
    //         'amount' => "required|numeric|max:{$this->maxAmount}|min:{$this->minAmount}",
    //     ], [
    //          'numeric' => 'numeric',
    //          'min' => 'min',
    //          'max' => 'max',
    //          'required' => 'required',
    //      ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'validation' => $validator->errors(),
    //         ]);
    //     }

    //     $possession = Transaction::where([
    //         'user_id' => $userId,
    //         'status' => 'success',
    //     ])
    //         ->whereIn('type', ['user_charge', 'user_clear', 'travel_cost'])
    //         ->sum('amount');

    //     if ($amount > $possession) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'credit_not_enough',
    //         ]);
    //     }

    //     $transaction                = new Transaction;
    //     $transaction->user_id       = $userId;
    //     $transaction->amount        = -$amount;
    //     $transaction->type          = 'user_clear';
    //     $transaction->status        = 'success';
    //     $transaction->save();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'transaction' => $transaction,
    //         ],
    //     ]);
    // }
}