<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::select(
                'id',
                'name',
                'first_name',
                'last_name',
                'identification',
                'gender',
                'phone',
                'email',
                'role',
                'is_active',
                'birth_date',
                'hire_date',
                'photo_path',
                'area_id'
            )->with('area')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'identification' => ['nullable', 'string', 'max:50', Rule::unique('users', 'identification')],
            'gender' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,colaborador',
            'is_active' => 'sometimes|boolean',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $data['name'] = trim($data['first_name'].' '.$data['last_name']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        unset($data['photo']);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['tenant_id'] = $request->user()->tenant_id;

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $user->load(['vacationYears', 'area']);

        $allocated = $user->vacationYears->sum('allocated_days');
        $used = $user->vacationYears->sum('used_days');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'identification' => $user->identification,
            'gender' => $user->gender,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'birth_date' => $user->birth_date?->toDateString(),
            'hire_date' => $user->hire_date?->toDateString(),
            'photo_path' => $user->photo_path,
            'photo_url' => $user->photo_url,
            'allocated' => $allocated,
            'used' => $used,
            'available' => $allocated - $used,
            'area_id' => $user->area_id,
            'area_name' => $user->area?->name,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'identification' => ['nullable', 'string', 'max:50', Rule::unique('users', 'identification')->ignore($user->id)],
            'gender' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'role' => 'sometimes|in:admin,colaborador',
            'is_active' => 'sometimes|boolean',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $firstName = $data['first_name'] ?? $user->first_name;
        $lastName = $data['last_name'] ?? $user->last_name;
        $data['name'] = trim($firstName.' '.$lastName);

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        unset($data['photo']);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('usuarios');

        $headers = [
            'nombre',
            'apellidos',
            'identificacion',
            'genero',
            'correo',
            'numero_celular',
            'area',
            'rol',
            'fecha_nacimiento',
            'fecha_contratacion',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $exampleRows = [
            ['Juan', 'Perez', '123456789', 'masculino', 'juan.perez@empresa.com', '3001234567', 'Recursos Humanos', 'colaborador', '1990-05-10', '2024-01-15'],
            ['Ana', 'Gomez', '987654321', 'femenino', 'ana.gomez@empresa.com', '3017654321', 'Finanzas', 'admin', '1988-09-20', '2023-07-01'],
        ];

        foreach ($exampleRows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 2, $value);
            }
        }

        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $fileName = 'plantilla_cargue_masivo_usuarios.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $areaMap = Area::query()
            ->get(['id', 'name'])
            ->mapWithKeys(function (Area $area) {
                return [$this->normalizeForMatch($area->name) => $area->id];
            });

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $tenantId = $request->user()->tenant_id;

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber === 1) {
                continue;
            }

            $firstName = $this->sanitizeText((string) ($row['A'] ?? ''));
            $lastName = $this->sanitizeText((string) ($row['B'] ?? ''));
            $identification = $this->sanitizeText((string) ($row['C'] ?? ''));
            $gender = $this->normalizeGender((string) ($row['D'] ?? ''));
            $email = Str::lower($this->sanitizeText((string) ($row['E'] ?? '')));
            $phone = $this->sanitizeText((string) ($row['F'] ?? ''));
            $areaName = $this->sanitizeText((string) ($row['G'] ?? ''));
            $role = $this->normalizeRole((string) ($row['H'] ?? ''));
            $birthDate = $this->normalizeDate((string) ($row['I'] ?? ''));
            $hireDate = $this->normalizeDate((string) ($row['J'] ?? ''));

            if (
                $firstName === '' &&
                $lastName === '' &&
                $identification === '' &&
                $email === '' &&
                $areaName === ''
            ) {
                continue;
            }

            $requiredMissing = [];
            if ($firstName === '') {
                $requiredMissing[] = 'nombre';
            }
            if ($lastName === '') {
                $requiredMissing[] = 'apellidos';
            }
            if ($identification === '') {
                $requiredMissing[] = 'identificacion';
            }
            if ($gender === null) {
                $requiredMissing[] = 'genero';
            }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $requiredMissing[] = 'correo_valido';
            }
            if ($phone === '') {
                $requiredMissing[] = 'numero_celular';
            }
            if ($areaName === '') {
                $requiredMissing[] = 'area';
            }

            if (! empty($requiredMissing)) {
                $errors[] = "Fila {$rowNumber}: faltan/invalidos -> ".implode(', ', $requiredMissing);
                $skipped++;
                continue;
            }

            $areaId = $areaMap[$this->normalizeForMatch($areaName)] ?? null;
            if (! $areaId) {
                $errors[] = "Fila {$rowNumber}: area '{$areaName}' no existe.";
                $skipped++;
                continue;
            }

            try {
                DB::beginTransaction();

                $userByIdentification = User::query()->where('identification', $identification)->first();
                $userByEmail = User::query()->where('email', $email)->first();

                if ($userByIdentification && $userByEmail && $userByIdentification->id !== $userByEmail->id) {
                    throw new \RuntimeException("Fila {$rowNumber}: identificación y correo pertenecen a usuarios distintos.");
                }

                $user = $userByIdentification ?? $userByEmail;
                $payload = [
                    'tenant_id' => $tenantId,
                    'name' => trim($firstName.' '.$lastName),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'identification' => $identification,
                    'gender' => $gender,
                    'phone' => $phone,
                    'email' => $email,
                    'role' => $role,
                    'is_active' => true,
                    'area_id' => $areaId,
                    'birth_date' => $birthDate,
                    'hire_date' => $hireDate,
                ];

                if ($user) {
                    $emailOwner = User::query()->where('email', $email)->where('id', '!=', $user->id)->first();
                    if ($emailOwner) {
                        throw new \RuntimeException("Fila {$rowNumber}: el correo '{$email}' ya pertenece a otro usuario.");
                    }

                    $identificationOwner = User::query()
                        ->where('identification', $identification)
                        ->where('id', '!=', $user->id)
                        ->first();
                    if ($identificationOwner) {
                        throw new \RuntimeException("Fila {$rowNumber}: la identificación '{$identification}' ya pertenece a otro usuario.");
                    }

                    $user->update($payload);
                    $updated++;
                } else {
                    $payload['password'] = Hash::make($identification !== '' ? $identification : Str::random(12));
                    User::create($payload);
                    $created++;
                }

                DB::commit();
            } catch (\Throwable $exception) {
                DB::rollBack();
                $errors[] = $exception->getMessage();
                $skipped++;
            }
        }

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => 'Cargue masivo procesado',
        ]);
    }

    protected function sanitizeText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $ascii = Str::ascii($value);
        return trim((string) preg_replace('/\s+/', ' ', $ascii));
    }

    protected function normalizeForMatch(string $value): string
    {
        return Str::lower((string) preg_replace('/[^a-z0-9]/', '', Str::ascii($value)));
    }

    protected function normalizeRole(string $value): string
    {
        $normalized = $this->normalizeForMatch($value);

        return in_array($normalized, ['admin', 'administrador', 'superadmin'], true)
            ? 'admin'
            : 'colaborador';
    }

    protected function normalizeGender(string $value): ?string
    {
        $normalized = $this->normalizeForMatch($value);

        return match ($normalized) {
            'masculino', 'hombre', 'male', 'm' => 'masculino',
            'femenino', 'mujer', 'female', 'f' => 'femenino',
            'otro', 'other', 'nobinario', 'nobinaria', 'no binario', 'no binaria' => 'otro',
            default => null,
        };
    }

    protected function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
