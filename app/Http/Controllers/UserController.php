<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::when($search, function ($query) use ($search) {

            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");

        })->paginate(5);

        return view('kelolauser.index', compact('users'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'  => 'required',
            'email' => 'required|email',
            'role'  => 'required',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public function updatePassword(Request $request, int $id)
    {
        $request->validate([
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
        ], [

            'password.min' =>
                'Password minimal 8 karakter.',

            'password.regex' =>
                'Password harus mengandung huruf besar, huruf kecil, dan angka.',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()
            ->back()
            ->with('success', 'Password berhasil direset.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function destroy(int $id)
    {
        User::findOrFail($id)->delete();

        return redirect()
            ->back()
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'role'     => 'required|in:admin,staff',
        'password' => 'required|min:8|confirmed',
    ]);

       User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'role'     => $request->role,
        'password' => bcrypt($request->password),
    ]);

        return redirect()->route('kelola-pengguna.index')
        ->with('success', 'Pengguna berhasil ditambahkan.');
}
}