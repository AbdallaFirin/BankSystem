<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\KycDocument;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComplianceController extends Controller
{
    public function index()
    {
        $pendingCustomers = Customer::with(['kycDocuments', 'branch'])
            ->whereIn('kyc_status', ['pending', 'under_review'])
            ->orderBy('created_at', 'desc')
            ->get();

        return \Inertia\Inertia::render('Staff/Compliance/Index', [
            'customers' => $pendingCustomers
        ]);
    }

    public function approveKyc(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $staff    = Auth::guard('staff')->user();

        DB::transaction(function () use ($customer, $staff) {
            $customer->update([
                'kyc_status'       => 'verified',
                'rejection_reason' => null,
            ]);

            KycDocument::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->update([
                    'status'      => 'verified',
                    'verified_by' => $staff->id,
                    'uploaded_at' => now(),
                ]);

            Account::where('customer_id', $customer->id)
                ->where('status', 'inactive')
                ->update(['status' => 'active', 'opened_at' => now()]);
        });

        return back()->with('success', "Customer {$customer->full_name} has been verified and accounts activated.");
    }

    public function rejectKyc(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update([
            'kyc_status'       => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', "Customer {$customer->full_name} registration has been rejected.");
    }
}
