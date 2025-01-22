<?php

namespace App\Http\Controllers;

use App\Models\CpuDirAdmin;
use Illuminate\Http\Request;

class CpuDirAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dirAdmin = CpuDirAdmin::all();
        return response()->json($dirAdmin);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CpuDirAdmin $cpuDirAdmin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CpuDirAdmin $cpuDirAdmin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CpuDirAdmin $cpuDirAdmin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CpuDirAdmin $cpuDirAdmin)
    {
        //
    }
}
