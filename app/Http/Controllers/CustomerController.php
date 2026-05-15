<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return Inertia::render('Customer/Register');
    }

    /**
     * Process the registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:customers',
            'primary_phone' => 'required|string',
            'home_branch_id' => 'required|exists:branches,id',
            'account_type_id' => 'required|exists:account_types,id',
            // Simplified validation for demonstration; realistically, we would validate all fields.
        ]);

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'full_name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'national_id' => $validated['national_id'],
                'phone' => $validated['primary_phone'],
                'home_branch_id' => $validated['home_branch_id'],
                'kyc_status' => 'pending'
            ]);

            // Auto-generate account number (Simulated)
            $accountNumber = 'ACC-' . strtoupper(substr(md5(uniqid()), 0, 8));

            Account::create([
                'customer_id' => $customer->id,
                'account_type_id' => $validated['account_type_id'],
                'home_branch_id' => $validated['home_branch_id'],
                'account_number' => $accountNumber,
                'status' => 'pending', // Pending initial deposit/KYC approval
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Customer registered successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer Registration Failed: ' . $e->getMessage());
            return redirect()->back()->withErrors(['message' => 'Registration failed. Please try again.']);
        }
    }
}
