<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // 1. Lấy danh sách Menu
    public function index(Request $request)
    {
        $query = Menu::where('status', '!=', 0)->orderBy('sort_order', 'asc');

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }
        
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $menus = $query->get();

        return response()->json([
            'status' => true,
            'menus' => $menus
        ]);
    }

    // 2. Lấy chi tiết Menu (cho trang Edit)
    public function show($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy menu'], 404);
        }
        return response()->json(['status' => true, 'menu' => $menu]);
    }

    // 3. Thêm mới Menu
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'link' => 'required',
            'type' => 'required',
            'position' => 'required',
        ]);

        $menu = new Menu();
        $menu->name = $request->name;
        $menu->link = $request->link;
        $menu->type = $request->type;
        $menu->table_id = $request->table_id ?? 0;
        $menu->parent_id = $request->parent_id ?? 0;
        $menu->sort_order = $request->sort_order ?? 0;
        $menu->position = $request->position;
        $menu->status = $request->status ?? 1;
        $menu->created_at = now();
        $menu->save();

        return response()->json(['status' => true, 'message' => 'Thêm menu thành công', 'menu' => $menu]);
    }

    // 4. Cập nhật Menu
    public function update(Request $request, $id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy menu'], 404);
        }

        $menu->name = $request->name;
        $menu->link = $request->link;
        $menu->type = $request->type;
        $menu->table_id = $request->table_id ?? 0;
        $menu->parent_id = $request->parent_id ?? 0;
        $menu->sort_order = $request->sort_order ?? 0;
        $menu->position = $request->position;
        $menu->status = $request->status ?? 1;
        $menu->updated_at = now();
        $menu->save();

        return response()->json(['status' => true, 'message' => 'Cập nhật thành công', 'menu' => $menu]);
    }

    // 5. Xóa Menu
    public function destroy($id)
    {
        $menu = Menu::find($id);
        if (!$menu) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy menu'], 404);
        }
        $menu->delete();
        return response()->json(['status' => true, 'message' => 'Xóa thành công']);
    }
}