<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BalanceController extends Controller
{
    public function deposit(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string',
        ]);

        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        DB::beginTransaction();
        try {
            $balance = Balance::firstOrCreate(
                ['user_id' => $data['user_id']],
                ['amount' => 0]
            );

            $balance->increment('amount', $data['amount']);

            Transaction::create([
                'user_id' => $data['user_id'],
                'type' => 'deposit',
                'amount' => $data['amount'],
                'comment' => $data['comment'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Deposit successful'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transaction failed'], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string',
        ]);

        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        DB::beginTransaction();
        try {
            $balance = Balance::where('user_id', $data['user_id'])->lockForUpdate()->first();

            if (!$balance || $balance->amount < $data['amount']) {
                DB::rollBack();
                return response()->json(['error' => 'Insufficient funds'], 409);
            }

            $balance->decrement('amount', $data['amount']);

            Transaction::create([
                'user_id' => $data['user_id'],
                'type' => 'withdraw',
                'amount' => $data['amount'],
                'comment' => $data['comment'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Withdraw successful'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transaction failed'], 500);
        }
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'from_user_id' => 'required|numeric|min:1',
            'to_user_id' => 'required|numeric|min:1|different:from_user_id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string',
        ]);

        if ($data['from_user_id'] == $data['to_user_id']) {
            return response()->json(['error' => 'Cannot transfer to self'], 422);
        }

        $user1 = User::find($data['from_user_id']);
        if (!$user1) {
            return response()->json(['error' => 'User-Sender not found'], 404);
        }

        $user2 = User::find($data['to_user_id']);
        if (!$user2) {
            return response()->json(['error' => 'User-Receiver not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Блокировка балансов в порядке возрастания ID, чтобы избежать дедлоков
            $userIds = [$data['from_user_id'], $data['to_user_id']];
            sort($userIds);
            $balances = Balance::whereIn('user_id', $userIds)->lockForUpdate()->get()->keyBy('user_id');

            $fromBalance = $balances[$data['from_user_id']] ?? null;
            if (!$fromBalance || $fromBalance->amount < $data['amount']) {
                DB::rollBack();
                return response()->json(['error' => 'Insufficient funds'], 409);
            }

            $toBalance = $balances[$data['to_user_id']] ?? Balance::firstOrCreate(
                ['user_id' => $data['to_user_id']],
                ['amount' => 0]
            );

            $fromBalance->decrement('amount', $data['amount']);
            $toBalance->increment('amount', $data['amount']);

            Transaction::create([
                'user_id' => $data['from_user_id'],
                'type' => 'transfer_out',
                'amount' => $data['amount'],
                'comment' => $data['comment'],
                'related_user_id' => $data['to_user_id'],
            ]);

            Transaction::create([
                'user_id' => $data['to_user_id'],
                'type' => 'transfer_in',
                'amount' => $data['amount'],
                'comment' => $data['comment'],
                'related_user_id' => $data['from_user_id'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Transfer successful'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transfer failed'], 500);
        }
    }

    public function showBalance($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $balance = $user->balance?->amount ?? 0;

        return response()->json([
            'user_id' => (int)$userId,
            'balance' => (float)$balance,
        ]);
    }
}
