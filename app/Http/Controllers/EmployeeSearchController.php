<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeSearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $employees = $this->fetchEmployees($q);

        return view('employees.search', [
            'q' => $q,
            'employees' => $employees,
        ]);
    }

    public function data(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $employees = $this->fetchEmployees($q);

        return response()->json([
            'q' => $q,
            'count' => $employees->count(),
            'employees' => $employees->values(),
        ]);
    }

    private function fetchEmployees(string $q)
    {
        try {
            $rows = DB::connection('oracle')->executeProcedureWithCursor(
                'search_emp',
                ['p_name' => $q],
                ':p_result'
            );

            return collect($rows)->map(function ($row) {
                return (object) [
                    'emp_id' => $row->EMP_ID ?? $row->emp_id ?? null,
                    'emp_name' => $row->EMP_NAME ?? $row->emp_name ?? null,
                ];
            });
        } catch (\Throwable $e) {
            $query = DB::connection('oracle')
                ->table('EMPLOYEES')
                ->selectRaw('EMPLOYEE_ID as emp_id, EMPLOYEE_NAME as emp_name');

            if ($q !== '') {
                $keyword = '%'.mb_strtoupper($q).'%';
                $query->whereRaw('UPPER(EMPLOYEE_NAME) LIKE ?', [$keyword]);
            }

            return $query->orderBy('EMPLOYEE_NAME')->limit(100)->get();
        }
    }
}
