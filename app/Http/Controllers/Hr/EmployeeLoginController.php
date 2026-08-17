<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmployeeLoginController extends Controller
{
    public function showLoginForm()
    {
        // If already logged in, redirect to portal
        if (Auth::check()) {
            return redirect()->route('employee.portal.index');
        }

        return view('hr.employee-portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginId = $request->login_id;
        $password = $request->password;

        // Try to authenticate using Email
        if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $loginId, 'password' => $password], $request->boolean('remember'))) {
                $request->session()->regenerate();
                return redirect()->intended(route('employee.portal.index'));
            }
        } else {
            // Treat as Phone Number
            // Find employee by phone
            $employee = Employee::where('phone', $loginId)->whereNotNull('user_id')->first();
            
            if ($employee) {
                $user = User::find($employee->user_id);
                if ($user && Hash::check($password, $user->password)) {
                    Auth::login($user, $request->boolean('remember'));
                    $request->session()->regenerate();
                    return redirect()->intended(route('employee.portal.index'));
                }
            }
        }

        return back()->withErrors([
            'login_id' => 'The provided credentials do not match our records.',
        ])->onlyInput('login_id');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
