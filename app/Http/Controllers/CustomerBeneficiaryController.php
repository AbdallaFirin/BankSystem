<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerBeneficiaryController extends Controller
{
    private function customer()
    {
        return Auth::guard('customers')->user();
    }

    /** GET /customer/beneficiaries */
    public function index()
    {
        $customer     = $this->customer();
        $beneficiaries = Beneficiary::where('customer_id', $customer->id)
            ->orderBy('nickname')
            ->get();

        return Inertia::render('Customer/Beneficiaries', [
            'beneficiaries' => $beneficiaries,
        ]);
    }

    /** POST /customer/beneficiaries */
    public function store(Request $request)
    {
        $customer = $this->customer();

        $validated = $request->validate([
            'account_number' => 'required|string|max:30',
            'nickname'       => 'nullable|string|max:100',
        ]);

        // Look up the account to get the holder's name
        $account = Account::with('customer')
            ->where('account_number', $validated['account_number'])
            ->whereNotIn('account_number', function ($q) use ($customer) {
                // Exclude the customer's own accounts
                $q->select('account_number')
                  ->from('accounts')
                  ->where('customer_id', $customer->id);
            })
            ->first();

        if (!$account) {
            return back()->withErrors(['account_number' => 'Account not found or belongs to you.']);
        }

        // Prevent duplicate
        $exists = Beneficiary::where('customer_id', $customer->id)
            ->where('account_number', $validated['account_number'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['account_number' => 'This account is already in your beneficiaries.']);
        }

        Beneficiary::create([
            'customer_id'        => $customer->id,
            'account_number'     => $validated['account_number'],
            'account_holder_name'=> $account->customer?->full_name ?? 'Unknown',
            'nickname'           => $validated['nickname'] ?: ($account->customer?->full_name ?? 'Unknown'),
        ]);

        return back()->with('success', 'Beneficiary added successfully.');
    }

    /** DELETE /customer/beneficiaries/{id} */
    public function destroy(int $id)
    {
        $customer = $this->customer();

        Beneficiary::where('customer_id', $customer->id)->findOrFail($id)->delete();

        return back()->with('success', 'Beneficiary removed.');
    }

    /** GET /customer/beneficiaries/list — JSON for transfer page */
    public function list()
    {
        $customer = $this->customer();

        $beneficiaries = Beneficiary::where('customer_id', $customer->id)
            ->orderBy('nickname')
            ->get(['id', 'account_number', 'account_holder_name', 'nickname']);

        return response()->json($beneficiaries);
    }
}
