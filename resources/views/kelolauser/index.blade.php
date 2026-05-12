@extends('layouts.sentara')

@section('content')

<div class="space-y-6">

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-2xl">
        {{ session('success') }}
    </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl">
        {{ session('error') }}
    </div>
    @endif

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">Kelola Pengguna</h1>
            <p class="text-slate-500 mt-1">Kelola akun pengguna sistem SENTARA.</p>
        </div>

        <!-- BUTTON TAMBAH -->
        <button
            onclick="openTambahModal()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-2xl text-sm font-semibold shadow transition">
            + Tambah Pengguna
        </button>

    </div>

    <!-- CARD -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">

        <!-- TABLE -->
        <div class="overflow-x-auto mt-6">

            <table class="w-full text-sm min-w-[900px]">

                <thead>
                    <tr class="text-slate-500 border-b">
                        <th class="py-4 text-left">No</th>
                        <th class="py-4 text-left">Nama</th>
                        <th class="py-4 text-left">Email</th>
                        <th class="py-4 text-left">Role</th>
                        <th class="py-4 text-left">Status</th>
                        <th class="py-4 text-left">Terakhir Aktif</th>
                        <th class="py-4 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-slate-700">

                    @forelse ($users as $user)

                    <tr class="border-b hover:bg-slate-50 transition">

                        <td class="py-4">{{ $loop->iteration }}</td>

                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <span class="font-medium">{{ $user->name }}</span>
                            </div>
                        </td>

                        <td class="py-4">{{ $user->email }}</td>

                        <td class="py-4">
                            @if($user->role == 'admin')
                            <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-semibold">Admin</span>
                            @else
                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">Staff</span>
                            @endif
                        </td>

                        <td class="py-4">
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Aktif</span>
                        </td>

                        <td class="py-4">{{ $user->updated_at->format('d M Y, H:i') }}</td>

                        <td class="py-4">
                            <div class="flex justify-center gap-2">

                                <button
                                    onclick="openEditModal('{{ $user->id }}', '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->role }}')"
                                    class="w-11 h-11 rounded-2xl border border-blue-200 text-blue-600 hover:bg-blue-50 flex items-center justify-center transition"
                                    title="Edit Pengguna">✏️</button>

                                <button
                                    onclick="openPasswordModal('{{ $user->id }}')"
                                    class="w-11 h-11 rounded-2xl border border-amber-200 text-amber-500 hover:bg-amber-50 flex items-center justify-center transition"
                                    title="Reset Password">🔒</button>

                                <button
                                    onclick="openDeleteModal('{{ $user->id }}', '{{ $user->role }}')"
                                    class="w-11 h-11 rounded-2xl border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition"
                                    title="Hapus Pengguna">🗑️</button>

                            </div>
                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400">
                            Data pengguna belum tersedia.
                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-6">
            <p class="text-sm text-slate-500">
                Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }}
                dari {{ $users->total() }} pengguna
            </p>
            {{ $users->links() }}
        </div>

    </div>

</div>


<!-- ================= MODAL TAMBAH PENGGUNA ================= -->
<div id="tambahModal"
    class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">

    <div class="bg-white rounded-3xl p-6 shadow-2xl w-full" style="max-width: 480px;">

        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Tambah Pengguna</h2>
                <p class="text-slate-500 text-sm mt-1">Buat akun pengguna baru untuk sistem SENTARA.</p>
            </div>
            <button onclick="closeTambahModal()"
                class="text-slate-400 hover:text-slate-700 text-2xl leading-none ml-4 flex-shrink-0">×</button>
        </div>

        {{-- SESUDAH --}}
<form method="POST" action="/kelola-pengguna" id="tambahForm">

            @csrf

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Pengguna</label>
                <input type="text" name="name" placeholder="Masukkan nama lengkap"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email" placeholder="contoh@email.com"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Role Pengguna</label>
                <select name="role"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <input type="password" name="password" placeholder="Minimal 8 karakter"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" placeholder="Ulangi password"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeTambahModal()"
                    class="flex-1 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
                    Tambah
                </button>
            </div>

        </form>

    </div>

</div>


<!-- ================= MODAL EDIT ================= -->
<div id="editModal"
    class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">

    <div class="bg-white rounded-3xl p-6 shadow-2xl w-full" style="max-width: 480px;">

        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Edit Pengguna</h2>
                <p class="text-slate-500 text-sm mt-1">Perbarui informasi akun pengguna SENTARA.</p>
            </div>
            <button onclick="closeEditModal()"
                class="text-slate-400 hover:text-slate-700 text-2xl leading-none ml-4 flex-shrink-0">×</button>
        </div>

        <form method="POST" id="editForm">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Pengguna</label>
                <input type="text" name="name" id="editName"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email" id="editEmail"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Role Pengguna</label>
                <select name="role" id="editRole"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeEditModal()"
                    class="flex-1 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
                    Simpan
                </button>
            </div>
        </form>

    </div>

</div>


<!-- ================= MODAL RESET PASSWORD ================= -->
<div id="passwordModal"
    class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">

    <div class="bg-white rounded-3xl p-6 shadow-2xl w-full" style="max-width: 480px;">

        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Reset Password</h2>
                <p class="text-slate-500 text-sm mt-1">Buat password baru pengguna.</p>
            </div>
            <button onclick="closePasswordModal()"
                class="text-slate-400 hover:text-slate-700 text-2xl leading-none ml-4 flex-shrink-0">×</button>
        </div>

        <div class="mb-5 bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <p class="text-sm text-amber-700 leading-relaxed">
                Password minimal 8 karakter dan harus kombinasi huruf besar, huruf kecil, dan angka.
            </p>
        </div>

        <form method="POST" id="passwordForm">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                <input type="password" name="password"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password</label>
                <input type="password" name="password_confirmation"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closePasswordModal()"
                    class="flex-1 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition">
                    Reset
                </button>
            </div>
        </form>

    </div>

</div>


<!-- ================= MODAL DELETE ================= -->
<div id="deleteModal"
    class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">

    <div class="bg-white rounded-3xl p-6 shadow-2xl w-full text-center" style="max-width: 400px;">

        <div class="w-16 h-16 mx-auto rounded-full bg-red-100 flex items-center justify-center text-3xl mb-4">🗑️</div>
        <h2 class="text-2xl font-bold text-slate-800">Hapus Pengguna?</h2>
        <p class="text-slate-500 text-sm mt-2 leading-relaxed">
            Data pengguna akan dihapus permanen dan tidak dapat dikembalikan.
        </p>

        <form method="POST" id="deleteForm" class="mt-6">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="button" onclick="closeDeleteModal()"
                    class="flex-1 py-3 rounded-2xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-2xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition">
                    Hapus
                </button>
            </div>
        </form>

    </div>

</div>


<!-- ================= MODAL ADMIN TIDAK BISA DIHAPUS ================= -->
<div id="adminAlertModal"
    class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">

    <div class="bg-white rounded-3xl p-6 shadow-2xl w-full text-center" style="max-width: 400px;">

        <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 flex items-center justify-center text-3xl mb-4">⚠️</div>
        <h2 class="text-2xl font-bold text-slate-800">Akses Ditolak</h2>
        <p class="text-slate-500 text-sm mt-2 leading-relaxed">
            Akun administrator tidak dapat dihapus demi menjaga keamanan sistem.
        </p>

        <button onclick="closeAdminAlertModal()"
            class="w-full mt-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
            Mengerti
        </button>

    </div>

</div>


<!-- ================= SCRIPT ================= -->
<script>

/* ---- TAMBAH ---- */
function openTambahModal() {
    document.getElementById('tambahModal').classList.remove('hidden');
    document.getElementById('tambahForm').reset();
}
function closeTambahModal() {
    document.getElementById('tambahModal').classList.add('hidden');
}

/* ---- EDIT ---- */
function openEditModal(id, name, email, role) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editName').value  = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value  = role;
    document.getElementById('editForm').action = '/kelola-pengguna/' + id;
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

/* ---- RESET PASSWORD ---- */
function openPasswordModal(id) {
    document.getElementById('passwordModal').classList.remove('hidden');
    document.getElementById('passwordForm').action =
        '/kelola-pengguna/' + id + '/reset-password';
}
function closePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
}

/* ---- DELETE ---- */
function openDeleteModal(id, role) {
    if (role === 'admin') {
        document.getElementById('adminAlertModal').classList.remove('hidden');
        return;
    }
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteForm').action = '/kelola-pengguna/' + id;
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
function closeAdminAlertModal() {
    document.getElementById('adminAlertModal').classList.add('hidden');
}

</script>

@endsection