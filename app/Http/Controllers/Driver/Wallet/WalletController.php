<?php

namespace App\Http\Controllers\Driver\Wallet;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankAccount;
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
            ->whereIn('type', ['driver_charge', 'driver_clear', 'travel_income'])
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
            ->whereIn('type', ['driver_charge', 'driver_clear', 'travel_income'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'transactions' => $transactions,
            ],
        ]);
    }

    public function getBankAccounts(Request $request)
    {
        $user = Auth()->user();

        if ($user->type != 'driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_driver',
            ]);
        }

        $bankAccounts = BankAccount::where([
            'user_id' => $user->id,
        ])->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => [
                'bank_accounts' => $bankAccounts,
            ],
        ]);
    }

    public function createBankAccount(Request $request)
    {
        $user                           = Auth()->user();
        $shebaNumber                    = $request->get('sheba_number');

        $validator = Validator::make($request->all(), [
            'sheba_number'              => 'required|alpha_num|max:255',
        ], [
            'max' => 'max',
            'required' => 'required',
            'alpha_num' => 'alpha_num',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        if ($user->type != 'driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_driver',
            ]);
        }

        $bankAccount                    = new BankAccount;
        $bankAccount->user_id           = $user->id;
        $bankAccount->sheba_number      = $shebaNumber;
        $bankAccount->is_default        = 0;
        $bankAccount->status            = 'pending';
        $bankAccount->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'bank_account' => $bankAccount,
            ],
        ]);
    }

    public function removeBankAccount(Request $request)
    {
        $user                           = Auth()->user();
        $bankAccountId                  = $request->get('bank_account_id');

        if ($user->type != 'driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_driver',
            ]);
        }

        $bankAccount = BankAccount::where([
            'id' => $bankAccountId,
            'user_id' => $user->id,
        ])->first();

        if (!$bankAccount) {
            return response()->json([
                'status' => 'failed',
                'message' => 'bank_account_not_exist',
            ]);
        }

        $bankAccount->delete();

        return response()->json([
            'status' => 'success',
            'data' => [],
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
            'max' => 'max',
            'min' => 'min',
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
        $transaction->type          = 'driver_charge';
        $transaction->status        = 'success';
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction' => $transaction,
            ],
        ]);
    }

    public function clear(Request $request)
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

        $possession = Transaction::where([
            'user_id' => $userId,
            'status' => 'success',
        ])
            ->whereIn('type', ['driver_charge', 'driver_clear', 'travel_income'])
            ->sum('amount');

        if ($amount > $possession) {
            return response()->json([
                'status' => 'failed',
                'message' => 'credit_not_enough',
            ]);
        }

        $transaction                = new Transaction;
        $transaction->user_id       = $userId;
        $transaction->amount        = -$amount;
        $transaction->type          = 'driver_clear';
        $transaction->status        = 'success';
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction' => $transaction,
            ],
        ]);
    }
}
