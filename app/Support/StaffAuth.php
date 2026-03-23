<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class StaffAuth
{
    public const SESSION_KEY = 'staff.auth';

    private const ORACLE_CONNECTION = 'oracle';

    private const DEFAULT_PERMISSION_CATALOG = [
        ['code' => 'dashboard.read', 'name' => 'Staff Dashboard', 'module' => 'Dashboard', 'description' => 'View staff dashboard'],
        ['code' => 'dashboard.manage', 'name' => 'Admin Dashboard', 'module' => 'Dashboard', 'description' => 'Admin dashboard'],
        ['code' => 'shop.read', 'name' => 'View Shop', 'module' => 'Shop', 'description' => 'Browse product catalog'],
        ['code' => 'checkout.process', 'name' => 'Checkout', 'module' => 'Orders', 'description' => 'Create invoice'],
        ['code' => 'orders.read', 'name' => 'View Orders', 'module' => 'Orders', 'description' => 'Read orders'],
        ['code' => 'orders.manage', 'name' => 'Manage Orders', 'module' => 'Orders', 'description' => 'Update orders'],
        ['code' => 'total-sales.read', 'name' => 'View Sales', 'module' => 'Total Sales', 'description' => 'View total sales'],
        ['code' => 'total-sales.manage', 'name' => 'Manage Sales', 'module' => 'Total Sales', 'description' => 'Manage totals'],
        ['code' => 'invoices.read', 'name' => 'View Invoices', 'module' => 'Invoices', 'description' => 'Read invoices'],
        ['code' => 'invoices.manage', 'name' => 'Manage Invoices', 'module' => 'Invoices', 'description' => 'Create invoices'],
        ['code' => 'purchases.read', 'name' => 'View Purchases', 'module' => 'Purchases', 'description' => 'Read purchases'],
        ['code' => 'purchases.manage', 'name' => 'Manage Purchases', 'module' => 'Purchases', 'description' => 'Edit purchases'],
        ['code' => 'clients.read', 'name' => 'View Clients', 'module' => 'Clients', 'description' => 'Read clients'],
        ['code' => 'clients.manage', 'name' => 'Manage Clients', 'module' => 'Clients', 'description' => 'Edit clients'],
        ['code' => 'client-depts.read', 'name' => 'Read Client Debt', 'module' => 'Client Debt', 'description' => 'View client debts'],
        ['code' => 'client-depts.manage', 'name' => 'Manage Client Debt', 'module' => 'Client Debt', 'description' => 'Manage client debts'],
        ['code' => 'client-types.manage', 'name' => 'Client Types', 'module' => 'Clients', 'description' => 'Manage types'],
        ['code' => 'currencies.read', 'name' => 'View Currencies', 'module' => 'Currencies', 'description' => 'Read currencies'],
        ['code' => 'currencies.manage', 'name' => 'Manage Currencies', 'module' => 'Currencies', 'description' => 'Edit currencies'],
        ['code' => 'products.read', 'name' => 'View Products', 'module' => 'Products', 'description' => 'Read products'],
        ['code' => 'products.manage', 'name' => 'Manage Products', 'module' => 'Products', 'description' => 'Edit products'],
        ['code' => 'product-types.manage', 'name' => 'Product Types', 'module' => 'Products', 'description' => 'Manage types'],
        ['code' => 'stock-status.read', 'name' => 'View Status', 'module' => 'Product Status', 'description' => 'Read stock status'],
        ['code' => 'stock-status.manage', 'name' => 'Manage Status', 'module' => 'Product Status', 'description' => 'Update stock status'],
        ['code' => 'future-stock.read', 'name' => 'View Forecast', 'module' => 'Analyst Future', 'description' => 'Read forecast'],
        ['code' => 'future-stock.manage', 'name' => 'Manage Forecast', 'module' => 'Analyst Future', 'description' => 'Update forecast'],
        ['code' => 'employees.read', 'name' => 'View Employees', 'module' => 'Employees', 'description' => 'Read employees'],
        ['code' => 'employees.manage', 'name' => 'Manage Employees', 'module' => 'Employees', 'description' => 'Edit employees'],
        ['code' => 'jobs.read', 'name' => 'View Jobs', 'module' => 'Jobs', 'description' => 'Read jobs'],
        ['code' => 'jobs.manage', 'name' => 'Manage Jobs', 'module' => 'Jobs', 'description' => 'Edit jobs'],
        ['code' => 'users.read', 'name' => 'View Users', 'module' => 'Users', 'description' => 'Read users'],
        ['code' => 'users.manage', 'name' => 'Manage Users', 'module' => 'Users', 'description' => 'Edit users'],
        ['code' => 'roles.read', 'name' => 'View Roles', 'module' => 'Roles', 'description' => 'Read roles'],
        ['code' => 'roles.manage', 'name' => 'Manage Roles', 'module' => 'Roles', 'description' => 'Edit roles'],
        ['code' => 'permissions.read', 'name' => 'View Permissions', 'module' => 'Permissions', 'description' => 'Read permissions'],
        ['code' => 'permissions.manage', 'name' => 'Manage Permissions', 'module' => 'Permissions', 'description' => 'Edit permissions'],
        ['code' => 'system.audit', 'name' => 'System Audit', 'module' => 'System', 'description' => 'Open Oracle deep check'],
    ];

    private const DEFAULT_ROLE_PERMISSIONS = [
        'STORE MANAGER' => [
            'dashboard.read', 'dashboard.manage', 'shop.read', 'checkout.process',
            'orders.read', 'orders.manage', 'total-sales.read', 'total-sales.manage',
            'invoices.read', 'invoices.manage', 'purchases.read', 'purchases.manage',
            'clients.read', 'clients.manage', 'client-depts.read', 'client-depts.manage', 'client-types.manage',
            'currencies.read', 'currencies.manage',
            'products.read', 'products.manage', 'product-types.manage',
            'stock-status.read', 'stock-status.manage', 'future-stock.read', 'future-stock.manage',
            'employees.read', 'employees.manage', 'jobs.read', 'jobs.manage',
            'users.read', 'users.manage', 'roles.read', 'roles.manage',
            'permissions.read', 'permissions.manage', 'system.audit',
        ],
        'ASSISTANT MANAGER' => [
            'dashboard.read', 'dashboard.manage', 'shop.read', 'checkout.process',
            'orders.read', 'orders.manage', 'total-sales.read', 'total-sales.manage',
            'invoices.read', 'invoices.manage', 'purchases.read', 'purchases.manage',
            'clients.read', 'clients.manage', 'client-depts.read', 'client-depts.manage', 'client-types.manage',
            'currencies.read', 'currencies.manage',
            'products.read', 'products.manage', 'product-types.manage',
            'stock-status.read', 'stock-status.manage', 'future-stock.read', 'future-stock.manage',
            'employees.read', 'employees.manage', 'jobs.read', 'jobs.manage',
            'users.read', 'users.manage', 'roles.read', 'roles.manage',
            'permissions.read', 'permissions.manage', 'system.audit',
        ],
        'CASHIER' => [
            'dashboard.read', 'shop.read', 'checkout.process', 'orders.read',
            'total-sales.read', 'invoices.read', 'invoices.manage', 'clients.read', 'client-depts.read',
            'currencies.read',
        ],
        'INVENTORY CLERK' => [
            'dashboard.read', 'shop.read', 'products.read', 'products.manage',
            'product-types.manage', 'stock-status.read', 'stock-status.manage',
            'future-stock.read', 'future-stock.manage', 'purchases.read', 'purchases.manage',
        ],
        'SHIPPING CLERK' => [
            'dashboard.read', 'orders.read', 'total-sales.read', 'invoices.read', 'clients.read', 'client-depts.read',
        ],
    ];

    private const DEPRECATED_PERMISSION_CODES = [
        'cart.manage',
    ];

    private const PERMISSION_GROUPS = [
        ['dashboard.manage', 'dashboard.admin', 'rbac.manage'],
        ['dashboard.read', 'dashboard.staff', 'dashboard.view'],
        ['shop.read', 'shop.view'],
        ['checkout.process', 'cart.manage'],
        ['orders.read', 'orders.view'],
        ['employees.read', 'employees.search'],
        ['system.audit', 'deep-check.view'],
    ];

    private const ADMIN_ROLES = ['ADMIN'];

    private const REQUIRED_PERMISSIONS = [
        ['code' => 'orders.manage', 'name' => 'Manage Orders', 'description' => 'Create and update'],
        ['code' => 'client-depts.read', 'name' => 'Read Client Debt', 'description' => 'View client debts'],
        ['code' => 'client-depts.manage', 'name' => 'Manage Client Debt', 'description' => 'Manage client debts'],
    ];

    private static bool $rbacReady = false;

    private static ?array $rolePermissionsCache = null;

    private static ?array $permissionCatalogCache = null;

    private static ?array $roleListCache = null;

    private static ?string $formTableCache = null;

    public static function login(array $staff): void
    {
        session([
            self::SESSION_KEY => [
                'user_id' => (int) ($staff['user_id'] ?? $staff['USER_ID'] ?? 0),
                'employee_id' => (int) ($staff['employee_id'] ?? $staff['E_ID'] ?? 0),
                'employee_name' => (string) ($staff['employee_name'] ?? $staff['EMPLOYEE_NAME'] ?? ''),
                'group_id' => (int) ($staff['group_id'] ?? $staff['G_ID'] ?? 0),
                'job_title' => self::normalizeRole((string) ($staff['job_title'] ?? $staff['group_name'] ?? $staff['GRUOP_NAME'] ?? '')),
                'phone' => (string) ($staff['phone'] ?? $staff['PHONE'] ?? ''),
            ],
        ]);
    }

    public static function logout(): void
    {
        session()->forget(self::SESSION_KEY);
        self::clearRbacCache();
    }

    public static function user(): ?array
    {
        $user = session(self::SESSION_KEY);

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $user = self::user();

        return isset($user['job_title']) ? self::normalizeRole((string) $user['job_title']) : null;
    }

    public static function hasRole(string $role): bool
    {
        $currentRole = self::role();

        return $currentRole !== null && $currentRole === self::normalizeRole($role);
    }

    public static function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if (self::hasRole((string) $role)) {
                return true;
            }
        }

        return false;
    }

    public static function isAdmin(): bool
    {
        return self::hasAnyRole(self::ADMIN_ROLES) || self::hasPermission('dashboard.manage');
    }

    public static function can(string $ability): bool
    {
        return self::hasPermission($ability);
    }

    public static function hasPermission(string $permissionCode): bool
    {
        if (! self::check()) {
            return false;
        }

        $permissions = self::permissionsForRole();
        if ($permissions === []) {
            return false;
        }

        foreach (self::permissionAliases($permissionCode) as $candidate) {
            if (in_array($candidate, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public static function abilityMatrix(): array
    {
        return self::rolePermissionsMatrix();
    }

    public static function rolePermissionsMatrix(): array
    {
        if (self::$rolePermissionsCache !== null) {
            return self::$rolePermissionsCache;
        }

        self::ensureRbacTablesSeeded();
        $matrix = self::loadRolePermissionsFromDb();

        if ($matrix === []) {
            $matrix = self::normalizedDefaultRolePermissions();
        }

        foreach (self::roles() as $role) {
            if (! isset($matrix[$role])) {
                $matrix[$role] = [];
            }
        }

        foreach ($matrix as $role => $permissions) {
            $normalized = array_values(array_unique(array_map(
                static fn (string $permission): string => self::canonicalPermission($permission),
                $permissions
            )));
            sort($normalized);
            $matrix[$role] = $normalized;
        }

        ksort($matrix);
        self::$rolePermissionsCache = $matrix;

        return self::$rolePermissionsCache;
    }

    public static function roles(): array
    {
        if (self::$roleListCache !== null) {
            return self::$roleListCache;
        }

        self::ensureRbacTablesSeeded();
        $roles = [];

        try {
            $rows = DB::connection(self::ORACLE_CONNECTION)
                ->table('GROUP_USER')
                ->selectRaw('GRUOP_NAME as group_name')
                ->whereNotNull('GRUOP_NAME')
                ->orderBy('GRUOP_NAME')
                ->get();

            foreach ($rows as $row) {
                $role = self::normalizeRole((string) ($row->group_name ?? $row->GRUOP_NAME ?? ''));
                if ($role !== '') {
                    $roles[] = $role;
                }
            }
        } catch (\Throwable) {
            // fallback to defaults
        }

        if ($roles === []) {
            $roles = array_keys(self::normalizedDefaultRolePermissions());
        }

        $roles = array_values(array_unique($roles));
        sort($roles);

        self::$roleListCache = $roles;

        return self::$roleListCache;
    }

    public static function permissionCatalog(): array
    {
        if (self::$permissionCatalogCache !== null) {
            return self::$permissionCatalogCache;
        }

        self::ensureRbacTablesSeeded();
        $catalog = self::loadPermissionCatalogFromDb();

        if ($catalog === []) {
            $catalog = self::permissionCatalogDefaults();
        }

        ksort($catalog);
        self::$permissionCatalogCache = $catalog;

        return self::$permissionCatalogCache;
    }

    public static function permissionsForRole(?string $role = null): array
    {
        $resolvedRole = $role !== null ? self::normalizeRole($role) : self::role();
        if ($resolvedRole === null || $resolvedRole === '') {
            return [];
        }

        $matrix = self::rolePermissionsMatrix();
        $permissions = $matrix[$resolvedRole] ?? [];
        $permissions = array_values(array_unique(array_map(
            static fn (string $permission): string => self::canonicalPermission($permission),
            $permissions
        )));
        sort($permissions);

        return $permissions;
    }

    public static function setRolePermission(string $role, string $permissionCode, bool $enabled): bool
    {
        $role = self::normalizeRole($role);
        $permissionCode = self::canonicalPermission($permissionCode);

        if ($role === '' || $permissionCode === '') {
            return false;
        }

        self::ensureRbacTablesSeeded();
        $groupId = self::groupIdByRole($role);
        $formId = self::formIdByPermissionCode($permissionCode);

        if ($groupId === null || $formId === null) {
            return false;
        }

        $conn = DB::connection(self::ORACLE_CONNECTION);

        if ($enabled) {
            self::insertPermissionGroup($conn, $groupId, $formId);
        } else {
            $conn->delete(
                'DELETE FROM PERMISSION_GROUP WHERE G_ID = :group_id AND FORM_ID = :form_id',
                ['group_id' => $groupId, 'form_id' => $formId]
            );
        }

        self::clearRbacCache();

        return true;
    }

    public static function replaceRolePermissions(string $role, array $permissions): bool
    {
        $role = self::normalizeRole($role);
        if ($role === '') {
            return false;
        }

        self::ensureRbacTablesSeeded();
        $groupId = self::groupIdByRole($role);
        if ($groupId === null) {
            return false;
        }

        $catalog = self::permissionCatalog();
        $formIds = [];
        $conn = DB::connection(self::ORACLE_CONNECTION);

        foreach ($permissions as $permission) {
            $code = self::canonicalPermission((string) $permission);
            if ($code === '' || ! isset($catalog[$code])) {
                continue;
            }

            $formId = self::formIdByPermissionCode($code);
            if ($formId === null) {
                self::ensurePermissionRow($conn, $code, $catalog[$code]);
                $formId = self::formIdByPermissionCode($code);
            }
            if ($formId !== null) {
                $formIds[$formId] = true;
            }
        }

        $conn->transaction(function () use ($conn, $groupId, $formIds): void {
            $conn->delete('DELETE FROM PERMISSION_GROUP WHERE G_ID = :group_id', ['group_id' => $groupId]);

            foreach (array_keys($formIds) as $formId) {
                self::insertPermissionGroup($conn, $groupId, (int) $formId);
            }
        });

        self::clearRbacCache();

        return true;
    }

    public static function ensureRbacTablesSeeded(): void
    {
        $conn = DB::connection(self::ORACLE_CONNECTION);

        if (self::$rbacReady) {
            try {
                self::ensureRequiredPermissions($conn);
            } catch (\Throwable) {
                // ignore
            }

            return;
        }

        foreach ([
            static fn () => self::ensureFormTableExists($conn),
            static fn () => self::ensureUsersForeignKey($conn),
            static fn () => self::seedGroups($conn),
            static fn () => self::seedPermissions($conn),
            static fn () => self::ensureRequiredPermissions($conn),
            static fn () => self::syncClientDebtPermissions($conn),
            static fn () => self::pruneDeprecatedPermissions($conn),
            static fn () => self::seedRolePermissions($conn),
            static fn () => self::syncAdminRolePermissions($conn),
        ] as $seedStep) {
            try {
                $seedStep();
            } catch (\Throwable) {
                // ignore individual failures and continue to other seed steps
            }
        }

        self::$rbacReady = true;
    }

    public static function clearRbacCache(): void
    {
        self::$rolePermissionsCache = null;
        self::$permissionCatalogCache = null;
        self::$roleListCache = null;
        self::$formTableCache = null;
        self::$rbacReady = false;
    }

    public static function formTableName(): string
    {
        return self::formTable();
    }

    private static function ensureUsersForeignKey(ConnectionInterface $conn): void
    {
        if (! self::tableExists($conn, 'USERS')) {
            return;
        }

        $fkRows = $conn->select(
            "SELECT cc.COLUMN_NAME AS COLUMN_NAME
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE c.TABLE_NAME = 'USERS'
               AND c.CONSTRAINT_NAME = 'USERS_FK1'"
        );

        $currentColumn = '';
        if ($fkRows !== []) {
            $currentColumn = mb_strtoupper((string) self::rowValue($fkRows[0], 'column_name', 'COLUMN_NAME'));
        }

        $fkCountRow = $conn->selectOne(
            "SELECT COUNT(*) AS CNT
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE c.TABLE_NAME = 'USERS'
               AND c.CONSTRAINT_TYPE = 'R'
               AND cc.COLUMN_NAME = 'E_ID'"
        );
        $hasEmployeeFk = (int) self::rowValue($fkCountRow, 'cnt', 'CNT') > 0;

        if ($currentColumn === 'E_ID' || $hasEmployeeFk) {
            return;
        }

        try {
            $conn->statement('ALTER TABLE USERS DROP CONSTRAINT USERS_FK1');
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'ORA-02443')) {
                throw $e;
            }
        }

        try {
            $conn->statement('ALTER TABLE USERS ADD CONSTRAINT USERS_FK1 FOREIGN KEY (E_ID) REFERENCES EMPLOYEES (EMPLOYEE_ID)');
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'ORA-02275')) {
                throw $e;
            }
        }
    }

    private static function ensureFormTableExists(ConnectionInterface $conn): void
    {
        if (self::tableExists($conn, 'FORM_CONTROL')) {
            self::$formTableCache = 'FORM_CONTROL';

            return;
        }

        if (self::tableExists($conn, 'FORM_CONTOL')) {
            self::$formTableCache = 'FORM_CONTOL';

            return;
        }

        try {
            $conn->statement(<<<'SQL'
CREATE TABLE FORM_CONTOL (
    FORM_ID NUMBER GENERATED ALWAYS AS IDENTITY,
    FORM_NAME VARCHAR2(20) NOT NULL,
    FORM_TITLE VARCHAR2(20),
    CREATE_DATE DATE DEFAULT SYSDATE,
    FORM_STATUS VARCHAR2(20),
    CONSTRAINT FORM_CONTOL_PK PRIMARY KEY (FORM_ID)
)
SQL);
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'ORA-00955')) {
                throw $e;
            }
        }

        self::$formTableCache = 'FORM_CONTOL';
    }

    private static function seedGroups(ConnectionInterface $conn): void
    {
        if (! self::tableExists($conn, 'GROUP_USER')) {
            return;
        }

        // Do not recreate roles on every request.
        // Seed only when GROUP_USER is empty (initial bootstrap).
        $hasGroups = (bool) $conn->table('GROUP_USER')->exists();
        if ($hasGroups) {
            return;
        }

        $roles = array_values(array_unique(array_map(
            static fn (string $role): string => self::shortText(self::normalizeRole($role), 20),
            array_keys(self::normalizedDefaultRolePermissions())
        )));

        try {
            $jobRows = $conn->table('JOBS')
                ->selectRaw('JOB_TITLE as job_title')
                ->whereNotNull('JOB_TITLE')
                ->get();

            foreach ($jobRows as $row) {
                $role = self::shortText(
                    self::normalizeRole((string) ($row->job_title ?? $row->JOB_TITLE ?? '')),
                    20
                );
                if ($role !== '') {
                    $roles[] = $role;
                }
            }
        } catch (\Throwable) {
            // no-op
        }

        $roles = array_values(array_unique(array_filter($roles)));
        sort($roles);

        foreach ($roles as $role) {
            $exists = (bool) $conn->table('GROUP_USER')
                ->whereRaw('UPPER(GRUOP_NAME) = ?', [mb_strtoupper($role)])
                ->exists();

            if ($exists) {
                continue;
            }

            $conn->insert(
                'INSERT INTO GROUP_USER (GRUOP_NAME, GROUP_STATUS, CREATE_DATE) VALUES (:group_name, :group_status, SYSDATE)',
                [
                    'group_name' => $role,
                    'group_status' => self::shortText(self::defaultRoleDescription($role), 20),
                ]
            );
        }
    }

    private static function seedPermissions(ConnectionInterface $conn): void
    {
        $formTable = self::formTable();

        // Do not recreate deleted permissions on every request.
        // Seed only when permission table is empty (initial bootstrap).
        $hasPermissions = (bool) $conn->table($formTable)->exists();
        if ($hasPermissions) {
            return;
        }

        $defaults = self::permissionCatalogDefaults();

        foreach ($defaults as $permission) {
            $code = self::canonicalPermission($permission['code']);
            if ($code === '') {
                continue;
            }

            $exists = (bool) $conn->table($formTable)
                ->whereRaw('LOWER(FORM_NAME) = ?', [mb_strtolower($code)])
                ->exists();

            if ($exists) {
                continue;
            }

            $conn->insert(
                "INSERT INTO {$formTable} (FORM_NAME, FORM_TITLE, CREATE_DATE, FORM_STATUS)
                 VALUES (:form_name, :form_title, SYSDATE, :form_status)",
                [
                    'form_name' => self::shortText($code, 20),
                    'form_title' => self::shortText($permission['name'], 20),
                    'form_status' => self::shortText($permission['description'], 20),
                ]
            );
        }
    }

    private static function ensureRequiredPermissions(ConnectionInterface $conn): void
    {
        foreach (self::REQUIRED_PERMISSIONS as $permission) {
            $code = self::canonicalPermission((string) ($permission['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            self::ensurePermissionRow($conn, $code, $permission);
        }
    }

    private static function syncClientDebtPermissions(ConnectionInterface $conn): void
    {
        if (! self::tableExists($conn, 'PERMISSION_GROUP')) {
            return;
        }

        $formMap = self::permissionCodeToFormIdMap($conn);
        $clientsReadId = $formMap['clients.read'] ?? null;
        $clientsManageId = $formMap['clients.manage'] ?? null;
        $clientDebtReadId = $formMap['client-depts.read'] ?? null;
        $clientDebtManageId = $formMap['client-depts.manage'] ?? null;

        if (! $clientDebtReadId || ! $clientDebtManageId) {
            return;
        }

        $clientDebtFormIds = array_values(array_filter([
            (int) $clientDebtReadId,
            (int) $clientDebtManageId,
        ]));
        if ($clientDebtFormIds !== []) {
            $hasClientDebtMappings = $conn->table('PERMISSION_GROUP')
                ->whereIn('FORM_ID', $clientDebtFormIds)
                ->exists();
            if ($hasClientDebtMappings) {
                return;
            }
        }

        if ($clientsReadId) {
            $rows = $conn->table('PERMISSION_GROUP')
                ->selectRaw('G_ID as group_id')
                ->where('FORM_ID', '=', (int) $clientsReadId)
                ->get();

            foreach ($rows as $row) {
                $groupId = (int) ($row->group_id ?? $row->G_ID ?? 0);
                if ($groupId > 0) {
                    self::insertPermissionGroup($conn, $groupId, (int) $clientDebtReadId);
                }
            }
        }

        if ($clientsManageId) {
            $rows = $conn->table('PERMISSION_GROUP')
                ->selectRaw('G_ID as group_id')
                ->where('FORM_ID', '=', (int) $clientsManageId)
                ->get();

            foreach ($rows as $row) {
                $groupId = (int) ($row->group_id ?? $row->G_ID ?? 0);
                if ($groupId > 0) {
                    self::insertPermissionGroup($conn, $groupId, (int) $clientDebtManageId);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private static function ensurePermissionRow(ConnectionInterface $conn, string $code, array $meta): void
    {
        $formTable = self::formTable();
        $exists = (bool) $conn->table($formTable)
            ->whereRaw('LOWER(FORM_NAME) = ?', [mb_strtolower($code)])
            ->exists();
        if ($exists) {
            return;
        }

        $name = (string) ($meta['name'] ?? $code);
        $description = (string) ($meta['description'] ?? $name);

        $conn->insert(
            "INSERT INTO {$formTable} (FORM_NAME, FORM_TITLE, CREATE_DATE, FORM_STATUS)
             VALUES (:form_name, :form_title, SYSDATE, :form_status)",
            [
                'form_name' => self::shortText($code, 20),
                'form_title' => self::shortText($name, 20),
                'form_status' => self::shortText($description, 20),
            ]
        );
    }

    private static function pruneDeprecatedPermissions(ConnectionInterface $conn): void
    {
        $formTable = self::formTable();
        $hasPermissionGroupTable = self::tableExists($conn, 'PERMISSION_GROUP');

        foreach (self::DEPRECATED_PERMISSION_CODES as $permissionCode) {
            // Deprecated entries must be removed by their exact stored code.
            // Do not canonicalize here, otherwise aliases can target active permissions.
            $code = self::normalizePermissionCode($permissionCode);
            if ($code === '') {
                continue;
            }

            $rows = $conn->table($formTable)
                ->selectRaw('FORM_ID as form_id')
                ->whereRaw('LOWER(FORM_NAME) = ?', [mb_strtolower($code)])
                ->get();

            foreach ($rows as $row) {
                $formId = (int) ($row->form_id ?? $row->FORM_ID ?? 0);
                if ($formId <= 0) {
                    continue;
                }

                if ($hasPermissionGroupTable) {
                    $conn->table('PERMISSION_GROUP')
                        ->where('FORM_ID', '=', $formId)
                        ->delete();
                }

                $conn->table($formTable)
                    ->where('FORM_ID', '=', $formId)
                    ->delete();
            }
        }
    }

    private static function seedRolePermissions(ConnectionInterface $conn): void
    {
        if (! self::tableExists($conn, 'PERMISSION_GROUP')) {
            return;
        }

        $hasRows = (bool) $conn->table('PERMISSION_GROUP')->exists();
        if ($hasRows) {
            return;
        }

        $groupMap = self::groupNameToIdMap($conn);
        $formMap = self::permissionCodeToFormIdMap($conn);

        foreach (self::normalizedDefaultRolePermissions() as $role => $permissions) {
            $groupId = $groupMap[$role] ?? null;
            if ($groupId === null) {
                continue;
            }

            foreach ($permissions as $permissionCode) {
                $formId = $formMap[$permissionCode] ?? null;
                if ($formId === null) {
                    continue;
                }

                self::insertPermissionGroup($conn, (int) $groupId, (int) $formId);
            }
        }
    }

    private static function syncAdminRolePermissions(ConnectionInterface $conn): void
    {
        $formMap = self::permissionCodeToFormIdMap($conn);
        if ($formMap === []) {
            return;
        }

        $groupMap = self::groupNameToIdMap($conn);
        foreach (self::ADMIN_ROLES as $adminRole) {
            $role = self::normalizeRole($adminRole);
            $groupId = $groupMap[$role] ?? null;
            if ($groupId === null) {
                continue;
            }

            foreach (array_values($formMap) as $formId) {
                self::insertPermissionGroup($conn, (int) $groupId, (int) $formId);
            }
        }
    }

    private static function insertPermissionGroup(ConnectionInterface $conn, int $groupId, int $formId): void
    {
        try {
            $conn->insert(
                'INSERT INTO PERMISSION_GROUP (G_ID, FORM_ID, GRANT_DATE) VALUES (:group_id, :form_id, SYSDATE)',
                ['group_id' => $groupId, 'form_id' => $formId]
            );
        } catch (QueryException $e) {
            if (! self::isOracleDuplicateKey($e)) {
                throw $e;
            }
        }
    }

    private static function loadPermissionCatalogFromDb(): array
    {
        $catalog = [];
        $formTable = self::formTable();

        try {
            $rows = DB::connection(self::ORACLE_CONNECTION)
                ->table($formTable)
                ->selectRaw('FORM_NAME as form_name, FORM_TITLE as form_title, FORM_STATUS as form_status')
                ->orderBy('FORM_NAME')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        foreach ($rows as $row) {
            $code = self::canonicalPermission((string) ($row->form_name ?? $row->FORM_NAME ?? ''));
            if ($code === '') {
                continue;
            }

            $title = trim((string) ($row->form_title ?? $row->FORM_TITLE ?? ''));
            $description = trim((string) ($row->form_status ?? $row->FORM_STATUS ?? ''));

            $catalog[$code] = [
                'code' => $code,
                'name' => $title !== '' ? $title : $code,
                'module' => self::permissionModule($code),
                'description' => $description !== '' ? $description : ($title !== '' ? $title : $code),
            ];
        }

        return $catalog;
    }

    private static function loadRolePermissionsFromDb(): array
    {
        $matrix = [];
        $formTable = self::formTable();

        try {
            $rows = DB::connection(self::ORACLE_CONNECTION)
                ->table('PERMISSION_GROUP as pg')
                ->join('GROUP_USER as g', 'g.G_ID', '=', 'pg.G_ID')
                ->join($formTable.' as f', 'f.FORM_ID', '=', 'pg.FORM_ID')
                ->selectRaw('g.GRUOP_NAME as group_name, f.FORM_NAME as form_name')
                ->orderBy('g.GRUOP_NAME')
                ->orderBy('f.FORM_NAME')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        foreach ($rows as $row) {
            $role = self::normalizeRole((string) ($row->group_name ?? $row->GRUOP_NAME ?? ''));
            $permission = self::canonicalPermission((string) ($row->form_name ?? $row->FORM_NAME ?? ''));

            if ($role === '' || $permission === '') {
                continue;
            }

            $matrix[$role] ??= [];
            $matrix[$role][] = $permission;
        }

        foreach ($matrix as $role => $permissions) {
            $normalized = array_values(array_unique($permissions));
            sort($normalized);
            $matrix[$role] = $normalized;
        }

        return $matrix;
    }

    private static function permissionCatalogDefaults(): array
    {
        $catalog = [];

        foreach (self::DEFAULT_PERMISSION_CATALOG as $permission) {
            $code = self::canonicalPermission($permission['code']);
            $catalog[$code] = [
                'code' => $code,
                'name' => (string) $permission['name'],
                'module' => (string) $permission['module'],
                'description' => (string) $permission['description'],
            ];
        }

        return $catalog;
    }

    private static function normalizedDefaultRolePermissions(): array
    {
        $matrix = [];

        foreach (self::DEFAULT_ROLE_PERMISSIONS as $role => $permissions) {
            $normalizedRole = self::normalizeRole($role);
            $normalizedPermissions = array_values(array_unique(array_map(
                static fn (string $permission): string => self::canonicalPermission($permission),
                $permissions
            )));
            sort($normalizedPermissions);
            $matrix[$normalizedRole] = $normalizedPermissions;
        }

        return $matrix;
    }

    private static function groupNameToIdMap(ConnectionInterface $conn): array
    {
        $map = [];

        $rows = $conn->table('GROUP_USER')
            ->selectRaw('G_ID as group_id, GRUOP_NAME as group_name')
            ->whereNotNull('GRUOP_NAME')
            ->get();

        foreach ($rows as $row) {
            $groupId = (int) ($row->group_id ?? $row->G_ID ?? 0);
            $groupName = self::normalizeRole((string) ($row->group_name ?? $row->GRUOP_NAME ?? ''));

            if ($groupId > 0 && $groupName !== '') {
                $map[$groupName] = $groupId;
            }
        }

        return $map;
    }

    private static function permissionCodeToFormIdMap(ConnectionInterface $conn): array
    {
        $map = [];
        $formTable = self::formTable();

        $rows = $conn->table($formTable)
            ->selectRaw('FORM_ID as form_id, FORM_NAME as form_name')
            ->whereNotNull('FORM_NAME')
            ->get();

        foreach ($rows as $row) {
            $formId = (int) ($row->form_id ?? $row->FORM_ID ?? 0);
            $code = self::canonicalPermission((string) ($row->form_name ?? $row->FORM_NAME ?? ''));

            if ($formId > 0 && $code !== '') {
                $map[$code] = $formId;
            }
        }

        return $map;
    }

    private static function groupIdByRole(string $role): ?int
    {
        $role = self::normalizeRole($role);
        if ($role === '') {
            return null;
        }

        $row = DB::connection(self::ORACLE_CONNECTION)
            ->table('GROUP_USER')
            ->selectRaw('G_ID as group_id')
            ->whereRaw('UPPER(GRUOP_NAME) = ?', [mb_strtoupper($role)])
            ->orderByDesc('G_ID')
            ->first();

        $groupId = (int) ($row->group_id ?? $row->G_ID ?? 0);

        return $groupId > 0 ? $groupId : null;
    }

    private static function formIdByPermissionCode(string $permissionCode): ?int
    {
        $code = self::canonicalPermission($permissionCode);
        if ($code === '') {
            return null;
        }

        $formTable = self::formTable();

        $row = DB::connection(self::ORACLE_CONNECTION)
            ->table($formTable)
            ->selectRaw('FORM_ID as form_id')
            ->whereRaw('LOWER(FORM_NAME) = ?', [mb_strtolower($code)])
            ->orderByDesc('FORM_ID')
            ->first();

        $formId = (int) ($row->form_id ?? $row->FORM_ID ?? 0);

        return $formId > 0 ? $formId : null;
    }

    private static function formTable(): string
    {
        if (self::$formTableCache !== null) {
            return self::$formTableCache;
        }

        $conn = DB::connection(self::ORACLE_CONNECTION);

        if (self::tableExists($conn, 'FORM_CONTROL')) {
            self::$formTableCache = 'FORM_CONTROL';
        } elseif (self::tableExists($conn, 'FORM_CONTOL')) {
            self::$formTableCache = 'FORM_CONTOL';
        } else {
            self::$formTableCache = 'FORM_CONTOL';
        }

        return self::$formTableCache;
    }

    private static function tableExists(ConnectionInterface $conn, string $table): bool
    {
        $row = $conn->selectOne(
            'SELECT COUNT(*) AS CNT FROM USER_TABLES WHERE TABLE_NAME = :table_name',
            ['table_name' => mb_strtoupper($table)]
        );

        return (int) self::rowValue($row, 'cnt', 'CNT') > 0;
    }

    private static function defaultRoleDescription(string $role): string
    {
        $descriptions = [
            'STORE MANAGER' => 'Administrator',
            'ASSISTANT MANAGER' => 'Assistant admin',
            'CASHIER' => 'Checkout staff',
            'INVENTORY CLERK' => 'Stock manager',
            'SHIPPING CLERK' => 'Shipping team',
        ];

        return $descriptions[$role] ?? 'Role group';
    }

    private static function permissionModule(string $permissionCode): string
    {
        $parts = explode('.', mb_strtolower(trim($permissionCode)));
        $first = trim((string) ($parts[0] ?? 'general'));
        if ($first === '') {
            $first = 'general';
        }

        $overrides = [
            'stock-status' => 'Product Status',
            'future-stock' => 'Analyst Future',
            'client-depts' => 'Client Debt',
        ];
        if (isset($overrides[$first])) {
            return $overrides[$first];
        }

        return ucwords(str_replace(['-', '_'], ' ', $first));
    }

    private static function canonicalPermission(string $permissionCode): string
    {
        $code = self::normalizePermissionCode($permissionCode);
        if ($code === '') {
            return '';
        }

        foreach (self::PERMISSION_GROUPS as $group) {
            $normalizedGroup = [];

            foreach ($group as $item) {
                $normalizedItem = self::normalizePermissionCode($item);
                if ($normalizedItem !== '') {
                    $normalizedGroup[] = $normalizedItem;
                }
            }

            if (in_array($code, $normalizedGroup, true)) {
                return $normalizedGroup[0] ?? $code;
            }
        }

        return $code;
    }

    private static function permissionAliases(string $permissionCode): array
    {
        $code = self::canonicalPermission($permissionCode);
        if ($code === '') {
            return [];
        }

        $aliases = [$code];

        foreach (self::PERMISSION_GROUPS as $group) {
            $normalizedGroup = [];

            foreach ($group as $item) {
                $normalizedItem = self::normalizePermissionCode($item);
                if ($normalizedItem !== '') {
                    $normalizedGroup[] = $normalizedItem;
                }
            }

            if (in_array($code, $normalizedGroup, true)) {
                $aliases = array_merge($aliases, $normalizedGroup);
            }
        }

        if (str_contains($code, '.')) {
            $aliases[] = str_replace('.', '_', $code);
        }
        if (str_contains($code, '_')) {
            $aliases[] = str_replace('_', '.', $code);
        }
        if (str_ends_with($code, '.read')) {
            $aliases[] = substr($code, 0, -5).'.view';
            $aliases[] = substr($code, 0, -5).'.manage';
        }
        if (str_ends_with($code, '.view')) {
            $aliases[] = substr($code, 0, -5).'.read';
            $aliases[] = substr($code, 0, -5).'.manage';
        }

        $aliases = array_values(array_unique(array_map(
            static fn (string $alias): string => self::normalizePermissionCode($alias),
            $aliases
        )));

        return array_values(array_filter($aliases, static fn (string $alias): bool => $alias !== ''));
    }

    private static function normalizePermissionCode(string $permissionCode): string
    {
        $code = mb_strtolower(trim($permissionCode));
        $code = str_replace('_', '.', $code);
        $code = preg_replace('/\s+/', '', $code) ?? $code;

        return $code;
    }

    private static function normalizeRole(string $role): string
    {
        $role = str_replace('_', ' ', $role);
        $role = preg_replace('/\s+/', ' ', $role) ?? $role;

        return mb_strtoupper(trim($role));
    }

    private static function shortText(string $value, int $max): string
    {
        return mb_substr(trim($value), 0, $max);
    }

    private static function isOracleDuplicateKey(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'ORA-00001');
    }

    private static function rowValue(?object $row, string $lower, string $upper): mixed
    {
        if (! $row) {
            return null;
        }

        if (property_exists($row, $lower)) {
            return $row->{$lower};
        }

        if (property_exists($row, $upper)) {
            return $row->{$upper};
        }

        return null;
    }
}
