<?php

namespace App\Http\Controllers;

use App\Models\UserMenu;
use App\Models\UserFunction;
use Illuminate\Http\Request;

class MenuController extends Controller

{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    } 
    public function index()
    {
        // Recupera el ID de usuario desde la sesión (suponiendo que se ha implementado la autenticación)
        $id_users = auth()->user()->id;
    
        // Realiza la consulta para obtener los datos del menú y los subelementos
        $menuItems = UserMenu::with(['userFunctions' => function ($query) use ($id_users) {
                $query->where('id_users', $id_users);
            }])
            ->whereHas('userFunctions', function ($query) use ($id_users) {
                $query->where('id_users', $id_users);
            })
            ->orderBy('menu', 'asc')
            ->get()
            ->groupBy('id_usermenu')
            ->map(function ($item) {
                $item[0]->subItems = $item->flatMap->userFunctions;
                return $item[0];
            });
    
        return response()->json(['menuItems' => $menuItems]);
    }

    public function menuaspirantes()
    {
        // Recupera el ID de usuario desde la sesión (suponiendo que se ha implementado la autenticación)
        $id_users = 0;
    
        // Realiza la consulta para obtener los datos del menú y los subelementos
        $menuItems = UserMenu::with(['userFunctions' => function ($query) use ($id_users) {
                $query->where('id_users', $id_users);
            }])
            ->whereHas('userFunctions', function ($query) use ($id_users) {
                $query->where('id_users', $id_users);
            })
            ->orderBy('menu', 'asc')
            ->get()
            ->groupBy('id_usermenu')
            ->map(function ($item) {
                $item[0]->subItems = $item->flatMap->userFunctions;
                return $item[0];
            });
    
        return response()->json(['menuItems' => $menuItems]);
    }

    // Nueva función para obtener todos los elementos del menú
    public function getAllMenuItems()
    {
        $menuItems = UserMenu::orderBy('menu', 'asc')->get();
        return response()->json(['menuItems' => $menuItems]);
    }
    
}
