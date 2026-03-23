<?php

namespace App\Http\Controllers;

use App\Support\StaffAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRbacController extends Controller
{
    public function dashboard(): View
    {
        StaffAuth::ensureRbacTablesSeeded();

        $formTable = StaffAuth::formTableName();
        $usersCount = (int) DB::connection('oracle')->table('USERS')->count();
        $rolesCount = (int) DB::connection('oracle')->table('GROUP_USER')->count();
        $permissionCount = (int) DB::connection('oracle')->table($formTable)->count();
        $mappingCount = (int) DB::connection('oracle')->table('PERMISSION_GROUP')->count();

        return view('admin.rbac.dashboard', [
            'metrics' => [
                'users' => $usersCount,
                'roles' => $rolesCount,
                'permissions' => $permissionCount,
                'mappings' => $mappingCount,
            ],
            'rolesPreview' => DB::connection('oracle')
                ->table('GROUP_USER')
                ->selectRaw('GRUOP_NAME as role_name, GROUP_STATUS as role_description')
                ->whereNotNull('GRUOP_NAME')
                ->orderBy('GRUOP_NAME')
                ->limit(6)
                ->get(),
            'permissionsPreview' => DB::connection('oracle')
                ->table($formTable)
                ->selectRaw('FORM_NAME as code, FORM_TITLE as title')
                ->whereNotNull('FORM_NAME')
                ->orderBy('FORM_NAME')
                ->limit(6)
                ->get(),
            'mappingsPreview' => DB::connection('oracle')
                ->table('PERMISSION_GROUP as pg')
                ->join('GROUP_USER as g', 'g.G_ID', '=', 'pg.G_ID')
                ->join($formTable.' as f', 'f.FORM_ID', '=', 'pg.FORM_ID')
                ->selectRaw('g.GRUOP_NAME as role_name, f.FORM_NAME as code')
                ->orderBy('g.GRUOP_NAME')
                ->orderBy('f.FORM_NAME')
                ->limit(6)
                ->get(),
        ]);
    }

    public function usersIndex(): View
    {
        StaffAuth::ensureRbacTablesSeeded();

        $rolePermissions = StaffAuth::rolePermissionsMatrix();
        $users = $this->loadUsers()
            ->map(function (object $row) use ($rolePermissions): object {
                $role = mb_strtoupper((string) ($row->role_name ?? ''));
                $row->permission_codes = $rolePermissions[$role] ?? [];
                $row->status = mb_strtoupper((string) ($row->status ?? 'ACTIVE'));

                return $row;
            });

        return view('admin.rbac.users.index', [
            'users' => $users,
        ]);
    }

    public function usersCreate(): View
    {
        StaffAuth::ensureRbacTablesSeeded();

        return view('admin.rbac.users.create', [
            'employees' => $this->employeesForSelect(),
            'roles' => $this->rolesForSelect(),
            'defaultStatus' => 'ACTIVE',
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'group_id' => ['required', 'integer'],
            'password' => ['required', 'string', 'min:4', 'max:20'],
            'status' => ['required', 'string', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        $employeeId = (int) $validated['employee_id'];
        $groupId = (int) $validated['group_id'];
        $password = (string) $validated['password'];
        $status = mb_strtoupper((string) $validated['status']);

        $employeeExists = DB::connection('oracle')
            ->table('EMPLOYEES')
            ->where('EMPLOYEE_ID', '=', $employeeId)
            ->exists();
        if (! $employeeExists) {
            return back()->withInput()->withErrors(['employee_id' => 'Employee was not found.']);
        }

        $groupExists = DB::connection('oracle')
            ->table('GROUP_USER')
            ->where('G_ID', '=', $groupId)
            ->exists();
        if (! $groupExists) {
            return back()->withInput()->withErrors(['group_id' => 'Role was not found.']);
        }

        DB::connection('oracle')->transaction(function () use ($employeeId, $groupId, $password, $status): void {
            DB::connection('oracle')
                ->table('USERS')
                ->where('E_ID', '=', $employeeId)
                ->delete();

            DB::connection('oracle')->insert(
                'INSERT INTO USERS (E_ID, G_ID, PASSWORD, CREATE_DATE, STATUS)
                 VALUES (:employee_id, :group_id, :password, SYSDATE, :status)',
                [
                    'employee_id' => $employeeId,
                    'group_id' => $groupId,
                    'password' => $password,
                    'status' => $status,
                ]
            );
        });

        StaffAuth::clearRbacCache();

        return redirect()
            ->route('admin.rbac.users.index')
            ->with('success', 'User account created.');
    }

    public function usersEdit(int $userId): RedirectResponse|View
    {
        StaffAuth::ensureRbacTablesSeeded();
        $user = $this->findUser($userId);

        if (! $user) {
            return redirect()->route('admin.rbac.users.index')->with('error', 'User was not found.');
        }

        return view('admin.rbac.users.edit', [
            'user' => $user,
            'roles' => $this->rolesForSelect(),
        ]);
    }

    public function usersUpdate(Request $request, int $userId): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();
        $user = $this->findUser($userId);

        if (! $user) {
            return redirect()->route('admin.rbac.users.index')->with('error', 'User was not found.');
        }

        $validated = $request->validate([
            'group_id' => ['required', 'integer'],
            'password' => ['nullable', 'string', 'min:4', 'max:20'],
            'status' => ['required', 'string', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        $groupExists = DB::connection('oracle')
            ->table('GROUP_USER')
            ->where('G_ID', '=', (int) $validated['group_id'])
            ->exists();
        if (! $groupExists) {
            return back()->withInput()->withErrors(['group_id' => 'Role was not found.']);
        }

        $status = mb_strtoupper((string) $validated['status']);
        $currentUserId = (int) (StaffAuth::user()['user_id'] ?? 0);
        if ($currentUserId === $userId && $status === 'INACTIVE') {
            return back()->withInput()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        $payload = [
            'G_ID' => (int) $validated['group_id'],
            'STATUS' => $status,
        ];

        $password = trim((string) ($validated['password'] ?? ''));
        if ($password !== '') {
            $payload['PASSWORD'] = $password;
        }

        DB::connection('oracle')
            ->table('USERS')
            ->where('USER_ID', '=', $userId)
            ->update($payload);

        StaffAuth::clearRbacCache();

        return redirect()
            ->route('admin.rbac.users.index')
            ->with('success', 'User account updated.');
    }

    public function usersDestroy(int $userId): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();

        $currentUserId = (int) (StaffAuth::user()['user_id'] ?? 0);
        if ($currentUserId === $userId) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $deleted = DB::connection('oracle')
            ->table('USERS')
            ->where('USER_ID', '=', $userId)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'User was not found.');
        }

        StaffAuth::clearRbacCache();

        return back()->with('success', 'User account deleted.');
    }

    public function rolesIndex(): View
    {
        StaffAuth::ensureRbacTablesSeeded();

        $roles = DB::connection('oracle')
            ->table('GROUP_USER as g')
            ->leftJoin('PERMISSION_GROUP as pg', 'pg.G_ID', '=', 'g.G_ID')
            ->selectRaw('
                g.G_ID as group_id,
                g.GRUOP_NAME as role_name,
                g.GROUP_STATUS as role_description,
                COUNT(pg.FORM_ID) as permission_count
            ')
            ->groupBy('g.G_ID', 'g.GRUOP_NAME', 'g.GROUP_STATUS')
            ->orderBy('g.GRUOP_NAME')
            ->get();

        return view('admin.rbac.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function rolesCreate(): View
    {
        StaffAuth::ensureRbacTablesSeeded();

        return view('admin.rbac.roles.create', [
            'permissionGroups' => $this->permissionGroupsForForm(),
        ]);
    }

    public function rolesStore(Request $request): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();

        $catalogCodes = array_keys(StaffAuth::permissionCatalog());
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:20'],
            'role_description' => ['nullable', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($catalogCodes)],
        ]);

        $roleName = mb_strtoupper(trim((string) $validated['role_name']));
        $description = trim((string) ($validated['role_description'] ?? ''));
        $permissions = array_values(array_unique(array_map(
            static fn (mixed $code): string => mb_strtolower((string) $code),
            (array) ($validated['permissions'] ?? [])
        )));

        $exists = DB::connection('oracle')
            ->table('GROUP_USER')
            ->whereRaw('UPPER(GRUOP_NAME) = ?', [$roleName])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['role_name' => 'Role already exists.']);
        }

        DB::connection('oracle')->insert(
            'INSERT INTO GROUP_USER (GRUOP_NAME, GROUP_STATUS, CREATE_DATE)
             VALUES (:role_name, :role_description, SYSDATE)',
            [
                'role_name' => $roleName,
                'role_description' => $description !== '' ? $description : 'Role group',
            ]
        );

        StaffAuth::replaceRolePermissions($roleName, $permissions);
        StaffAuth::clearRbacCache();

        return redirect()
            ->route('admin.rbac.roles.index')
            ->with('success', 'Role created.');
    }

    public function rolesEdit(int $groupId): RedirectResponse|View
    {
        StaffAuth::ensureRbacTablesSeeded();
        $role = $this->findRole($groupId);

        if (! $role) {
            return redirect()->route('admin.rbac.roles.index')->with('error', 'Role was not found.');
        }

        return view('admin.rbac.roles.edit', [
            'role' => $role,
            'selectedPermissions' => StaffAuth::permissionsForRole((string) $role->role_name),
            'permissionGroups' => $this->permissionGroupsForForm(),
        ]);
    }

    public function rolesUpdate(Request $request, int $groupId): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();
        $role = $this->findRole($groupId);

        if (! $role) {
            return redirect()->route('admin.rbac.roles.index')->with('error', 'Role was not found.');
        }

        $catalogCodes = array_keys(StaffAuth::permissionCatalog());
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:20'],
            'role_description' => ['nullable', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($catalogCodes)],
        ]);

        $roleName = mb_strtoupper(trim((string) $validated['role_name']));
        $description = trim((string) ($validated['role_description'] ?? ''));
        $permissions = array_values(array_unique(array_map(
            static fn (mixed $code): string => mb_strtolower((string) $code),
            (array) ($validated['permissions'] ?? [])
        )));

        $duplicate = DB::connection('oracle')
            ->table('GROUP_USER')
            ->where('G_ID', '!=', $groupId)
            ->whereRaw('UPPER(GRUOP_NAME) = ?', [$roleName])
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors(['role_name' => 'Role name is already used.']);
        }

        DB::connection('oracle')
            ->table('GROUP_USER')
            ->where('G_ID', '=', $groupId)
            ->update([
                'GRUOP_NAME' => $roleName,
                'GROUP_STATUS' => $description !== '' ? $description : 'Role group',
            ]);

        StaffAuth::replaceRolePermissions($roleName, $permissions);
        StaffAuth::clearRbacCache();

        return redirect()
            ->route('admin.rbac.roles.index')
            ->with('success', 'Role updated.');
    }

    public function rolesDestroy(int $groupId): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();

        $inUse = DB::connection('oracle')
            ->table('USERS')
            ->where('G_ID', '=', $groupId)
            ->exists();
        if ($inUse) {
            return back()->with('error', 'Role is assigned to existing users and cannot be deleted.');
        }

        DB::connection('oracle')
            ->table('PERMISSION_GROUP')
            ->where('G_ID', '=', $groupId)
            ->delete();

        $deleted = DB::connection('oracle')
            ->table('GROUP_USER')
            ->where('G_ID', '=', $groupId)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'Role was not found.');
        }

        StaffAuth::clearRbacCache();

        return back()->with('success', 'Role deleted.');
    }

    public function permissionsIndex(): View
    {
        StaffAuth::ensureRbacTablesSeeded();
        $formTable = StaffAuth::formTableName();

        $permissions = DB::connection('oracle')
            ->table($formTable.' as f')
            ->leftJoin('PERMISSION_GROUP as pg', 'pg.FORM_ID', '=', 'f.FORM_ID')
            ->selectRaw('
                f.FORM_ID as form_id,
                f.FORM_NAME as code,
                f.FORM_TITLE as name,
                f.FORM_STATUS as description,
                COUNT(pg.G_ID) as role_count
            ')
            ->groupBy('f.FORM_ID', 'f.FORM_NAME', 'f.FORM_TITLE', 'f.FORM_STATUS')
            ->orderBy('f.FORM_NAME')
            ->get()
            ->map(function (object $row): object {
                $row->module = $this->permissionModule((string) ($row->code ?? ''));

                return $row;
            });

        return view('admin.rbac.permissions.index', [
            'permissions' => $permissions,
        ]);
    }

    public function permissionsCreate(): View
    {
        StaffAuth::ensureRbacTablesSeeded();

        return view('admin.rbac.permissions.create', [
            'modules' => $this->modulesForSelect(),
        ]);
    }

    public function permissionsStore(Request $request): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();
        $formTable = StaffAuth::formTableName();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'name' => ['required', 'string', 'max:20'],
            'module' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:20'],
        ]);

        $code = $this->normalizePermissionCode((string) $validated['code'], (string) ($validated['module'] ?? ''));
        $name = trim((string) $validated['name']);
        $description = trim((string) ($validated['description'] ?? ''));

        $exists = DB::connection('oracle')
            ->table($formTable)
            ->whereRaw('LOWER(FORM_NAME) = ?', [mb_strtolower($code)])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Permission code already exists.']);
        }

        DB::connection('oracle')->insert(
            "INSERT INTO {$formTable} (FORM_NAME, FORM_TITLE, CREATE_DATE, FORM_STATUS)
             VALUES (:code, :name, SYSDATE, :description)",
            [
                'code' => $code,
                'name' => $name,
                'description' => $description !== '' ? $description : 'Permission',
            ]
        );

        StaffAuth::clearRbacCache();

        return redirect()
            ->route('admin.rbac.permissions.index')
            ->with('success', 'Permission created.');
    }

    public function permissionsEdit(int $formId): RedirectResponse|View
    {
        StaffAuth::ensureRbacTablesSeeded();
        $permission = $this->findPermission($formId);

        if (! $permission) {
            return redirect()->route('admin.rbac.permissions.index')->with('error', 'Permission was not found.');
        }

        return view('admin.rbac.permissions.edit', [
            'permission' => $permission,
            'modules' => $this->modulesForSelect(),
        ]);
    }

    public function permissionsUpdate(Request $request, int $formId): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();
        $formTable = StaffAuth::formTableName();
        $permission = $this->findPermission($formId);

        if (! $permission) {
            return redirect()->route('admin.rbac.permissions.index')->with('error', 'Permission was not found.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'name' => ['required', 'string', 'max:20'],
            'module' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:20'],
        ]);

        $code = $this->normalizePermissionCode((string) $validated['code'], (string) ($validated['module'] ?? ''));
        $name = trim((string) $validated['name']);
        $description = trim((string) ($validated['description'] ?? ''));

        $duplicate = DB::connection('oracle')
            ->table($formTable)
            ->where('FORM_ID', '!=', $formId)
            ->whereRaw('LOWER(FORM_NAME) = ?', [mb_strtolower($code)])
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors(['code' => 'Permission code is already used.']);
        }

        DB::connection('oracle')
            ->table($formTable)
            ->where('FORM_ID', '=', $formId)
            ->update([
                'FORM_NAME' => $code,
                'FORM_TITLE' => $name,
                'FORM_STATUS' => $description !== '' ? $description : 'Permission',
            ]);

        StaffAuth::clearRbacCache();

        return redirect()
            ->route('admin.rbac.permissions.index')
            ->with('success', 'Permission updated.');
    }

    public function permissionsDestroy(int $formId): RedirectResponse
    {
        StaffAuth::ensureRbacTablesSeeded();
        $formTable = StaffAuth::formTableName();

        DB::connection('oracle')
            ->table('PERMISSION_GROUP')
            ->where('FORM_ID', '=', $formId)
            ->delete();

        $deleted = DB::connection('oracle')
            ->table($formTable)
            ->where('FORM_ID', '=', $formId)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'Permission was not found.');
        }

        StaffAuth::clearRbacCache();

        return back()->with('success', 'Permission deleted.');
    }

    private function loadUsers(): Collection
    {
        return DB::connection('oracle')
            ->table('USERS as u')
            ->join('EMPLOYEES as e', 'e.EMPLOYEE_ID', '=', 'u.E_ID')
            ->leftJoin('GROUP_USER as g', 'g.G_ID', '=', 'u.G_ID')
            ->selectRaw("
                u.USER_ID as user_id,
                u.E_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                e.PHONE as phone,
                u.G_ID as group_id,
                g.GRUOP_NAME as role_name,
                NVL(UPPER(u.STATUS), 'ACTIVE') as status
            ")
            ->orderByDesc('u.USER_ID')
            ->get();
    }

    private function findUser(int $userId): ?object
    {
        return DB::connection('oracle')
            ->table('USERS as u')
            ->join('EMPLOYEES as e', 'e.EMPLOYEE_ID', '=', 'u.E_ID')
            ->leftJoin('GROUP_USER as g', 'g.G_ID', '=', 'u.G_ID')
            ->selectRaw("
                u.USER_ID as user_id,
                u.E_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                e.PHONE as phone,
                u.G_ID as group_id,
                g.GRUOP_NAME as role_name,
                NVL(UPPER(u.STATUS), 'ACTIVE') as status
            ")
            ->where('u.USER_ID', '=', $userId)
            ->first();
    }

    private function findRole(int $groupId): ?object
    {
        return DB::connection('oracle')
            ->table('GROUP_USER')
            ->selectRaw('G_ID as group_id, GRUOP_NAME as role_name, GROUP_STATUS as role_description')
            ->where('G_ID', '=', $groupId)
            ->first();
    }

    private function findPermission(int $formId): ?object
    {
        $formTable = StaffAuth::formTableName();

        $permission = DB::connection('oracle')
            ->table($formTable)
            ->selectRaw('FORM_ID as form_id, FORM_NAME as code, FORM_TITLE as name, FORM_STATUS as description')
            ->where('FORM_ID', '=', $formId)
            ->first();

        if ($permission) {
            $permission->module = $this->permissionModule((string) ($permission->code ?? ''));
        }

        return $permission;
    }

    private function employeesForSelect()
    {
        return DB::connection('oracle')
            ->table('EMPLOYEES')
            ->selectRaw('EMPLOYEE_ID as employee_id, EMPLOYEE_NAME as employee_name')
            ->orderBy('EMPLOYEE_NAME')
            ->get();
    }

    private function rolesForSelect()
    {
        return DB::connection('oracle')
            ->table('GROUP_USER')
            ->selectRaw('G_ID as group_id, UPPER(GRUOP_NAME) as role_name')
            ->whereNotNull('GRUOP_NAME')
            ->orderBy('GRUOP_NAME')
            ->get();
    }

    private function permissionGroupsForForm(): array
    {
        $grouped = [];

        foreach (StaffAuth::permissionCatalog() as $permission) {
            $module = (string) ($permission['module'] ?? 'General');
            $grouped[$module] ??= [];
            $grouped[$module][] = $permission;
        }

        ksort($grouped);

        return $grouped;
    }

    private function modulesForSelect(): array
    {
        $modules = [];
        foreach (StaffAuth::permissionCatalog() as $permission) {
            $module = trim((string) ($permission['module'] ?? ''));
            if ($module !== '') {
                $modules[] = $module;
            }
        }

        $modules = array_values(array_unique($modules));
        sort($modules);

        if ($modules === []) {
            $modules = ['Dashboard', 'Users', 'Roles', 'Permissions'];
        }

        return $modules;
    }

    private function normalizePermissionCode(string $code, string $module): string
    {
        $normalized = mb_strtolower(trim($code));
        $normalized = str_replace('_', '.', $normalized);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        if (! str_contains($normalized, '.')) {
            $modulePart = mb_strtolower(trim($module));
            $modulePart = str_replace('_', '.', $modulePart);
            $modulePart = preg_replace('/\s+/', '', $modulePart) ?? $modulePart;

            if ($modulePart !== '') {
                $normalized = $modulePart.'.'.$normalized;
            }
        }

        return mb_substr($normalized, 0, 20);
    }

    private function permissionModule(string $permissionCode): string
    {
        $parts = explode('.', mb_strtolower(trim($permissionCode)));
        $module = trim((string) ($parts[0] ?? 'general'));
        if ($module === '') {
            $module = 'general';
        }

        $overrides = [
            'stock-status' => 'Product Status',
            'future-stock' => 'Analyst Future',
            'client-depts' => 'Client Debt',
        ];
        if (isset($overrides[$module])) {
            return $overrides[$module];
        }

        return ucwords(str_replace(['-', '_'], ' ', $module));
    }
}
