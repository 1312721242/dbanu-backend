<?php

namespace App\Http\Controllers;

use App\Models\UserMenu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $id_users = auth()->user()->id;
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
        $id_users = 0;
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

    public function getAllMenuItems()
    {
        $menuItems = UserMenu::orderBy('menu', 'asc')->get();
        return response()->json(['menuItems' => $menuItems]);
    }
    
    public function agregarMenu(Request $request)
    {
        $request->validate([
            'menu' => 'required|string|max:255',
            'icono' => 'nullable|string|max:255',
        ]);

        $menu = new UserMenu();
        $menu->menu = $request->menu;
        $menu->icono = $request->icono;
        $menu->created_at = now();
        $menu->updated_at = now();
        $menu->save();

        return response()->json(['success' => true, 'menu' => $menu]);
    }
}
