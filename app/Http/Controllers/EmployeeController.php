<?php

namespace App\Http\Controllers;

use App\Support\StaffAuth;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $conn = DB::connection('oracle');
        $q = trim((string) $request->query('q', ''));
        $applySearchFilter = static function ($query) use ($q): void {
            if ($q === '') {
                return;
            }

            $keyword = '%'.mb_strtoupper($q).'%';
            $query->where(function ($sub) use ($keyword, $q): void {
                $sub->whereRaw('UPPER(e.EMPLOYEE_NAME) LIKE ?', [$keyword])
                    ->orWhereRaw('e.PHONE LIKE ?', ['%'.$q.'%'])
                    ->orWhereRaw('UPPER(j.JOB_TITLE) LIKE ?', [$keyword]);
            });
        };

        $employeesQuery = $conn->table('EMPLOYEES as e')
            ->leftJoin('JOBS as j', 'j.JOB_ID', '=', 'e.JOB_ID')
            ->selectRaw('
                e.EMPLOYEE_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                e.GENDER as gender,
                e.BIRTH_DATE as birth_date,
                e.PHONE as phone,
                e.ADDRESS as address,
                e.SALARY as salary,
                e.REMARKS as remarks,
                UTL_RAW.CAST_TO_VARCHAR2(DBMS_LOB.SUBSTR(e.PHOTO, 4000, 1)) as photo_path,
                e.JOB_ID as job_id,
                j.JOB_TITLE as job_title
            ');

        $applySearchFilter($employeesQuery);

        $employees = $employeesQuery
            ->orderBy('e.EMPLOYEE_NAME')
            ->paginate(15)
            ->appends($request->query());

        $genderRows = $conn->table('EMPLOYEES as e')
            ->leftJoin('JOBS as j', 'j.JOB_ID', '=', 'e.JOB_ID')
            ->selectRaw("UPPER(TRIM(NVL(e.GENDER, 'OTHER'))) as gender_key, COUNT(*) as total");
        $applySearchFilter($genderRows);
        $genderRows = $genderRows
            ->groupByRaw("UPPER(TRIM(NVL(e.GENDER, 'OTHER')))")
            ->get();

        $genderCounts = [
            'male' => 0,
            'female' => 0,
            'other' => 0,
            'unknown' => 0,
            'total' => 0,
        ];

        foreach ($genderRows as $row) {
            $genderKey = mb_strtoupper(trim((string) ($row->gender_key ?? 'OTHER')));
            $count = (int) ($row->total ?? 0);
            if ($genderKey === 'MALE') {
                $genderCounts['male'] += $count;
            } elseif ($genderKey === 'FEMALE') {
                $genderCounts['female'] += $count;
            } elseif ($genderKey === 'OTHER') {
                $genderCounts['other'] += $count;
            } else {
                $genderCounts['unknown'] += $count;
            }
            $genderCounts['total'] += $count;
        }

        $jobs = $conn->table('JOBS')
            ->selectRaw('JOB_ID as job_id, JOB_TITLE as job_title')
            ->orderBy('JOB_TITLE')
            ->get();

        return view('employees.index', [
            'employees' => $employees,
            'jobs' => $jobs,
            'q' => $q,
            'genderCounts' => $genderCounts,
            'canManageEmployees' => StaffAuth::can('employees.manage'),
        ]);
    }

    public function jobs(Request $request): View
    {
        $conn = DB::connection('oracle');
        $q = trim((string) $request->query('q', ''));

        $jobsQuery = $conn->table('JOBS as j')
            ->leftJoin('EMPLOYEES as e', 'e.JOB_ID', '=', 'j.JOB_ID')
            ->selectRaw('
                j.JOB_ID as job_id,
                j.JOB_TITLE as job_title,
                COUNT(e.EMPLOYEE_ID) as employee_count
            ');

        if ($q !== '') {
            $keyword = '%'.mb_strtoupper($q).'%';
            $jobsQuery->whereRaw('UPPER(j.JOB_TITLE) LIKE ?', [$keyword]);
        }

        $jobs = $jobsQuery
            ->groupBy('j.JOB_ID', 'j.JOB_TITLE')
            ->orderBy('j.JOB_TITLE')
            ->paginate(20)
            ->appends($request->query());

        $totalJobsQuery = $conn->table('JOBS as j');
        $assignedEmployeesQuery = $conn->table('EMPLOYEES as e')
            ->join('JOBS as j', 'j.JOB_ID', '=', 'e.JOB_ID');

        if ($q !== '') {
            $keyword = '%'.mb_strtoupper($q).'%';
            $totalJobsQuery->whereRaw('UPPER(j.JOB_TITLE) LIKE ?', [$keyword]);
            $assignedEmployeesQuery->whereRaw('UPPER(j.JOB_TITLE) LIKE ?', [$keyword]);
        }

        return view('employees.jobs', [
            'jobs' => $jobs,
            'q' => $q,
            'totalJobs' => (int) $totalJobsQuery->count(),
            'assignedEmployees' => (int) $assignedEmployeesQuery->count(),
            'canManageJobs' => StaffAuth::can('jobs.manage'),
        ]);
    }

    public function jobsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'job_title' => ['required', 'string', 'max:50'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $title = trim((string) $validated['job_title']);
        if ($title === '') {
            return back()->withInput()->withErrors(['job_title' => 'Job title is required.']);
        }

        $conn = DB::connection('oracle');
        $exists = $conn->table('JOBS')
            ->whereRaw('UPPER(JOB_TITLE) = ?', [mb_strtoupper($title)])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['job_title' => 'Job title already exists.']);
        }

        $minSalary = $validated['min_salary'] !== null ? (float) $validated['min_salary'] : null;
        $maxSalary = $validated['max_salary'] !== null ? (float) $validated['max_salary'] : null;
        if ($minSalary !== null && $maxSalary !== null && $maxSalary < $minSalary) {
            return back()->withInput()->withErrors(['max_salary' => 'Max salary must be greater than or equal to min salary.']);
        }

        try {
            $conn->table('JOBS')->insert([
                'JOB_TITLE' => $title,
                'MIN_SALARY' => $minSalary,
                'MAX_SALARY' => $maxSalary,
            ]);
        } catch (QueryException $e) {
            $message = strtoupper($e->getMessage());

            if (str_contains($message, 'ORA-01400') && str_contains($message, 'JOB_ID')) {
                $nextJobId = (int) $conn->table('JOBS')->max('JOB_ID') + 1;
                if ($nextJobId <= 0) {
                    $nextJobId = 1;
                }

                try {
                    $conn->table('JOBS')->insert([
                        'JOB_ID' => $nextJobId,
                        'JOB_TITLE' => $title,
                        'MIN_SALARY' => $minSalary,
                        'MAX_SALARY' => $maxSalary,
                    ]);

                    return back()->with('success', "Job '{$title}' created.");
                } catch (QueryException $nested) {
                    $message = strtoupper($nested->getMessage());
                    if (str_contains($message, 'ORA-00001') && str_contains($message, 'UQ_JOBS_TITLE')) {
                        return back()->withInput()->withErrors(['job_title' => 'Job title already exists.']);
                    }
                    if (str_contains($message, 'ORA-02290') && str_contains($message, 'CK_JOBS_MAX_GE_MIN')) {
                        return back()->withInput()->withErrors(['max_salary' => 'Max salary must be greater than or equal to min salary.']);
                    }

                    return back()->withInput()->with('error', 'Failed to create job. Please check the input values.');
                }
            }

            if (str_contains($message, 'ORA-00001') && str_contains($message, 'UQ_JOBS_TITLE')) {
                return back()->withInput()->withErrors(['job_title' => 'Job title already exists.']);
            }

            if (str_contains($message, 'ORA-02290') && str_contains($message, 'CK_JOBS_MAX_GE_MIN')) {
                return back()->withInput()->withErrors(['max_salary' => 'Max salary must be greater than or equal to min salary.']);
            }

            return back()->withInput()->with('error', 'Failed to create job. Please check the input values.');
        }

        return back()->with('success', "Job '{$title}' created.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:80'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'birth_date' => ['required', 'date'],
            'job_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:15'],
            'salary' => ['required', 'numeric', 'min:0'],
            'remarks' => ['required', 'string', 'max:200'],
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        $jobId = (int) $validated['job_id'];
        $jobExists = DB::connection('oracle')
            ->table('JOBS')
            ->where('JOB_ID', '=', $jobId)
            ->exists();
        if (! $jobExists) {
            return back()->with('error', 'Selected job was not found.');
        }

        $photoPath = $this->storePhoto($request->file('photo'));

        try {
            DB::connection('oracle')->insert(
                'INSERT INTO EMPLOYEES (EMPLOYEE_NAME, GENDER, BIRTH_DATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS, PHOTO)
                 VALUES (:employee_name, :gender, :birth_date, :job_id, :address, :phone, :salary, :remarks, TO_BLOB(UTL_RAW.CAST_TO_RAW(:photo)))',
                [
                    'employee_name' => (string) $validated['employee_name'],
                    'gender' => (string) $validated['gender'],
                    'birth_date' => (string) $validated['birth_date'],
                    'job_id' => $jobId,
                    'address' => (string) $validated['address'],
                    'phone' => (string) $validated['phone'],
                    'salary' => (float) $validated['salary'],
                    'remarks' => (string) $validated['remarks'],
                    'photo' => $photoPath,
                ]
            );
        } catch (QueryException $e) {
            return back()->with('error', $this->friendlyOracleError($e));
        }

        return back()->with('success', 'Employee created.');
    }

    public function show(int $employeeId): View
    {
        $conn = DB::connection('oracle');

        $employee = $conn->table('EMPLOYEES as e')
            ->leftJoin('JOBS as j', 'j.JOB_ID', '=', 'e.JOB_ID')
            ->selectRaw('
                e.EMPLOYEE_ID as employee_id,
                e.EMPLOYEE_NAME as employee_name,
                e.GENDER as gender,
                e.BIRTH_DATE as birth_date,
                e.PHONE as phone,
                e.ADDRESS as address,
                e.SALARY as salary,
                e.REMARKS as remarks,
                UTL_RAW.CAST_TO_VARCHAR2(DBMS_LOB.SUBSTR(e.PHOTO, 4000, 1)) as photo_path,
                e.JOB_ID as job_id,
                j.JOB_TITLE as job_title
            ')
            ->where('e.EMPLOYEE_ID', '=', $employeeId)
            ->first();

        if (! $employee) {
            abort(404);
        }

        $jobs = $conn->table('JOBS')
            ->selectRaw('JOB_ID as job_id, JOB_TITLE as job_title')
            ->orderBy('JOB_TITLE')
            ->get();

        return view('employees.show', [
            'employee' => $employee,
            'jobs' => $jobs,
            'canManageEmployees' => StaffAuth::can('employees.manage'),
        ]);
    }

    public function update(Request $request, int $employeeId): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:80'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'birth_date' => ['required', 'date'],
            'job_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:15'],
            'salary' => ['required', 'numeric', 'min:0'],
            'remarks' => ['required', 'string', 'max:200'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $jobId = (int) $validated['job_id'];
        $jobExists = DB::connection('oracle')
            ->table('JOBS')
            ->where('JOB_ID', '=', $jobId)
            ->exists();
        if (! $jobExists) {
            return back()->with('error', 'Selected job was not found.');
        }

        $payload = [
            'EMPLOYEE_NAME' => (string) $validated['employee_name'],
            'GENDER' => (string) $validated['gender'],
            'BIRTH_DATE' => (string) $validated['birth_date'],
            'JOB_ID' => $jobId,
            'ADDRESS' => (string) $validated['address'],
            'PHONE' => (string) $validated['phone'],
            'SALARY' => (float) $validated['salary'],
            'REMARKS' => (string) $validated['remarks'],
        ];

        try {
            if ($request->hasFile('photo')) {
                $photoPath = $this->storePhoto($request->file('photo'));
                DB::connection('oracle')->update(
                    'UPDATE EMPLOYEES
                     SET EMPLOYEE_NAME = :employee_name,
                         GENDER = :gender,
                         BIRTH_DATE = :birth_date,
                         JOB_ID = :job_id,
                         ADDRESS = :address,
                         PHONE = :phone,
                         SALARY = :salary,
                         REMARKS = :remarks,
                         PHOTO = TO_BLOB(UTL_RAW.CAST_TO_RAW(:photo))
                     WHERE EMPLOYEE_ID = :employee_id',
                    [
                        'employee_name' => $payload['EMPLOYEE_NAME'],
                        'gender' => $payload['GENDER'],
                        'birth_date' => $payload['BIRTH_DATE'],
                        'job_id' => $payload['JOB_ID'],
                        'address' => $payload['ADDRESS'],
                        'phone' => $payload['PHONE'],
                        'salary' => $payload['SALARY'],
                        'remarks' => $payload['REMARKS'],
                        'photo' => $photoPath,
                        'employee_id' => $employeeId,
                    ]
                );
            } else {
                DB::connection('oracle')
                    ->table('EMPLOYEES')
                    ->where('EMPLOYEE_ID', '=', $employeeId)
                    ->update($payload);
            }
        } catch (QueryException $e) {
            return back()->with('error', $this->friendlyOracleError($e));
        }

        return back()->with('success', "Employee #{$employeeId} updated.");
    }

    public function destroy(int $employeeId): RedirectResponse
    {
        $conn = DB::connection('oracle');
        $photoPath = $conn->table('EMPLOYEES')
            ->selectRaw('UTL_RAW.CAST_TO_VARCHAR2(DBMS_LOB.SUBSTR(PHOTO, 4000, 1)) as photo_path')
            ->where('EMPLOYEE_ID', '=', $employeeId)
            ->value('photo_path');

        try {
            $deleted = $conn->table('EMPLOYEES')
                ->where('EMPLOYEE_ID', '=', $employeeId)
                ->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Failed to delete employee: '.$e->getMessage());
        }

        if (! $deleted) {
            return back()->with('error', "Employee #{$employeeId} was not found.");
        }

        $this->deleteStoredPhoto($photoPath);

        return back()->with('success', "Employee #{$employeeId} deleted.");
    }

    private function storePhoto(\Illuminate\Http\UploadedFile $photo): string
    {
        $directory = public_path('images');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $ext = strtolower($photo->getClientOriginalExtension() ?: 'jpg');
        $filename = 'employee_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $photo->move($directory, $filename);

        return 'images/'.$filename;
    }

    private function deleteStoredPhoto(?string $photoPath): void
    {
        if (! $photoPath) {
            return;
        }

        $relativePath = ltrim($photoPath, '/\\');
        $fullPath = public_path($relativePath);
        $imagesDir = realpath(public_path('images'));
        $realPath = realpath($fullPath);

        if ($imagesDir && $realPath && str_starts_with($realPath, $imagesDir) && is_file($realPath)) {
            @unlink($realPath);
        }
    }

    private function friendlyOracleError(QueryException $e): string
    {
        $message = strtoupper($e->getMessage());

        if (
            str_contains($message, 'ORA-20000') ||
            str_contains($message, 'SALARY') ||
            str_contains($message, 'POSITION') ||
            str_contains($message, 'TRIGGER')
        ) {
            return 'The salary does not match the position.';
        }

        if (str_contains($message, 'ORA-12899') && str_contains($message, 'PHONE')) {
            return 'Phone number is too long (max 15 characters).';
        }

        return 'Failed to save employee. Please check the input values.';
    }
}
