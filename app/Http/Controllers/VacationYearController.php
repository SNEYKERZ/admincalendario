<?php

namespace App\Http\Controllers;

use App\Models\VacationYear;
use Illuminate\Http\Request;

class VacationYearController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            VacationYear::where('user_id', $request->user_id)->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer',
            'allocated_days' => 'required|integer|min:1|max:15',
        ]);

        $data['expires_at'] = now()
            ->setYear($data['year'] + 3)
            ->endOfYear();

        $vacation = VacationYear::create($data);

        return response()->json($vacation, 201);
    }
}