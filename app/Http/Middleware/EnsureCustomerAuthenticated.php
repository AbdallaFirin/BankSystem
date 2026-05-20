<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCustomerAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('customers')->check()) {
            return redirect()->route('customer.login')
                ->with('error', 'Please log in to access the customer portal.');
        }

        $customer = Auth::guard('customers')->user();

        // Force password change on first login
        if ($customer->must_change_password
            && !$request->routeIs('customer.change-password')
            && !$request->routeIs('customer.change-password.post')
            && !$request->routeIs('customer.logout')
        ) {
            return redirect()->route('customer.change-password')
                ->with('info', 'You must change your temporary password before continuing.');
        }

        return $next($request);
    }
}
