<?php

namespace App\Http\Controllers;

use App\Support\SessionCart;
use App\Support\StaffAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    public function __construct(private readonly SessionCart $cart) {}

    public function showLogin(): RedirectResponse|View
    {
        if (StaffAuth::check()) {
            return redirect()->route('dashboard.entry');
        }

        StaffAuth::ensureRbacTablesSeeded();

        return view('auth.staff-login', [
            'employees' => $this->loginEmployees(),
        ]);
    }

    public function showSignup(): RedirectResponse|View
    {
        if (StaffAuth::check()) {
            return redirect()->route('dashboard.entry');
        }

        StaffAuth::ensureRbacTablesSeeded();

        return view('auth.staff-signup', [
            'employees' => $this->signupEmployees(),
            'roles' => $this->roles(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        if (StaffAuth::check()) {
            return redirect()->route('dashboard.entry');
        }

        StaffAuth::ensureRbacTablesSeeded();

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'password' => ['required', 'string', 'max:20'],
        ]);

        $employeeId = (int) $validated['employee_id'];
        $password = (string) $validated['password'];

        $staff = DB::connection('oracle')
            ->table('USERS as u')
            ->join('EMPLOYEES as e', 'e.EMPLOYEE_ID', '=', 'u.E_ID')
            ->leftJoin('GROUP_USER as g', 'g.G_ID', '=', 'u.G_ID')
            ->selectRaw("
                u.USER_ID as user_id,
                u.E_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                e.PHONE as phone,
                u.G_ID as group_id,
                g.GRUOP_NAME as job_title,
                u.PASSWORD as user_password,
                NVL(UPPER(u.STATUS), 'ACTIVE') as account_status
            ")
            ->where('u.E_ID', '=', $employeeId)
            ->whereRaw("NVL(UPPER(u.STATUS), 'ACTIVE') <> 'INACTIVE'")
            ->orderByDesc('u.USER_ID')
            ->first();

        $storedPassword = (string) ($staff->user_password ?? $staff->PASSWORD ?? '');
        if (! $staff || $storedPassword === '' || ! hash_equals($storedPassword, $password)) {
            return back()
                ->withInput($request->only('employee_id'))
                ->withErrors(['employee_id' => 'Invalid employee or password.']);
        }

        StaffAuth::login((array) $staff);
        $request->session()->regenerate();

        $target = $request->session()->pull('url.intended', route('dashboard.entry'));
        $request->session()->put('login.redirect_to', $target);

        return redirect()->route('staff.login.loading')
            ->with('success', 'Welcome, '.$staff->employee_name.'.');
    }

    public function signup(Request $request): RedirectResponse
    {
        if (StaffAuth::check()) {
            return redirect()->route('dashboard.entry');
        }

        StaffAuth::ensureRbacTablesSeeded();

        $roleNames = $this->roles()->pluck('role_name')->all();

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'role_name' => ['required', 'string', Rule::in($roleNames)],
            'password' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ]);

        $employeeId = (int) $validated['employee_id'];
        $roleName = mb_strtoupper(trim((string) $validated['role_name']));
        $password = (string) $validated['password'];

        $employee = DB::connection('oracle')
            ->table('EMPLOYEES')
            ->selectRaw('EMPLOYEE_ID as employee_id, EMPLOYEE_NAME as employee_name, PHONE as phone')
            ->where('EMPLOYEE_ID', '=', $employeeId)
            ->first();

        if (! $employee) {
            return back()->withInput()->withErrors(['employee_id' => 'Selected employee was not found.']);
        }

        $role = DB::connection('oracle')
            ->table('GROUP_USER')
            ->selectRaw('G_ID as group_id, GRUOP_NAME as role_name')
            ->whereRaw('UPPER(GRUOP_NAME) = ?', [$roleName])
            ->first();

        if (! $role) {
            return back()->withInput()->withErrors(['role_name' => 'Selected role was not found.']);
        }

        DB::connection('oracle')->transaction(function () use ($employeeId, $role, $password): void {
            DB::connection('oracle')
                ->table('USERS')
                ->where('E_ID', '=', $employeeId)
                ->delete();

            DB::connection('oracle')->insert(
                'INSERT INTO USERS (E_ID, G_ID, PASSWORD, CREATE_DATE, STATUS)
                 VALUES (:employee_id, :group_id, :password, SYSDATE, :status)',
                [
                    'employee_id' => $employeeId,
                    'group_id' => (int) ($role->group_id ?? $role->G_ID ?? 0),
                    'password' => $password,
                    'status' => 'ACTIVE',
                ]
            );
        });

        $staff = DB::connection('oracle')
            ->table('USERS as u')
            ->join('EMPLOYEES as e', 'e.EMPLOYEE_ID', '=', 'u.E_ID')
            ->leftJoin('GROUP_USER as g', 'g.G_ID', '=', 'u.G_ID')
            ->selectRaw('
                u.USER_ID as user_id,
                u.E_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                e.PHONE as phone,
                u.G_ID as group_id,
                g.GRUOP_NAME as job_title
            ')
            ->where('u.E_ID', '=', $employeeId)
            ->orderByDesc('u.USER_ID')
            ->first();

        if (! $staff) {
            return back()->withInput()->withErrors(['employee_id' => 'Unable to create user account.']);
        }

        StaffAuth::clearRbacCache();
        StaffAuth::login((array) $staff);
        $request->session()->regenerate();

        return redirect()->route('dashboard.entry')
            ->with('success', 'Account created. Welcome, '.$employee->employee_name.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->cart->clear();
        StaffAuth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.logout.loading');
    }

    public function showLoginLoading(Request $request): RedirectResponse|View
    {
        if (! StaffAuth::check()) {
            return redirect()->route('staff.login');
        }

        $target = $request->session()->pull('login.redirect_to', route('dashboard.entry'));
        $appHost = parse_url(config('app.url') ?? '', PHP_URL_HOST);
        $targetHost = parse_url((string) $target, PHP_URL_HOST);
        if ($targetHost && $appHost && $targetHost !== $appHost) {
            $target = route('dashboard.entry');
        }

        return view('auth.staff-login-loading', [
            'target' => $target,
        ]);
    }

    public function showLogoutLoading(): View
    {
        return view('auth.staff-logout-loading', [
            'target' => route('staff.login'),
        ]);
    }

    private function loginEmployees()
    {
        return DB::connection('oracle')
            ->table('USERS as u')
            ->join('EMPLOYEES as e', 'e.EMPLOYEE_ID', '=', 'u.E_ID')
            ->leftJoin('GROUP_USER as g', 'g.G_ID', '=', 'u.G_ID')
            ->selectRaw('
                u.E_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                g.GRUOP_NAME as job_title
            ')
            ->whereRaw("NVL(UPPER(u.STATUS), 'ACTIVE') <> 'INACTIVE'")
            ->orderBy('e.EMPLOYEE_NAME')
            ->get();
    }

    private function signupEmployees()
    {
        return DB::connection('oracle')
            ->table('EMPLOYEES as e')
            ->selectRaw('e.EMPLOYEE_ID as employee_id, e.EMPLOYEE_NAME as employee_name')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('USERS as u')
                    ->whereColumn('u.E_ID', 'e.EMPLOYEE_ID');
            })
            ->orderBy('e.EMPLOYEE_NAME')
            ->get();
    }

    private function roles()
    {
        return DB::connection('oracle')
            ->table('GROUP_USER')
            ->selectRaw('G_ID as group_id, UPPER(GRUOP_NAME) as role_name')
            ->whereNotNull('GRUOP_NAME')
            ->orderBy('GRUOP_NAME')
            ->get();
    }
}
