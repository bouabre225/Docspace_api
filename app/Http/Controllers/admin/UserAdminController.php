<?php

namespace App\Http\Controllers\admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', '!=', 'admin')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function suspend($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        $user->update(['statut' => 'suspendu']);
        return response()->json(['success' => true, 'message' => 'Utilisateur suspendu.']);
    }

    public function reactivate($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        $user->update(['statut' => 'actif']);
        return response()->json(['success' => true, 'message' => 'Utilisateur réactivé.']);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        \DB::table('users')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Utilisateur supprimé.']);
    }
}