<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuTipoSangre;

class CpuTipoSangreController extends Controller
{
    public function index()
    {
        return response()->json(CpuTipoSangre::all());
    }
}
