<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Employee list',
            'employees' => [] // Return a list of employees here
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'Employee_email' => 'required|email|unique:employees,email',
            'company_name' => 'required|string',
            'gender' => 'required|string',
            'contact_no' => 'required|string',
            'password' => 'required|string',
            'joining_date' => 'required|string',
            'tag' => 'required|string',
            'logo' => 'required|array',
            'job_title' => 'required|string',
        ]);

        $validated['email']=$validated['Employee_email'];
        $validated['logo']=json_encode($validated['logo']);
        $validated["password"]=bcrypt($validated['password']);
        // Logic to create a new employee
        $employee = Employee::create($validated);
        $user=User::create(
            [
                "name"=>$validated['name'],
                "email"=>$validated['email'],
                "password"=>bcrypt($validated['password'])
            ]
        );
        $employee->owner_id = $user->id;
        $employee->save();
        return response()->json([
            'message' => 'Employee created successfully',
            'employee' => $employee
        ], 201);
    }
}

