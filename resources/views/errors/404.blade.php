<x-layout title="404 - Halaman Tidak Ditemukan">

    <div class="min-h-[70vh] flex items-center justify-center">
        <div class="text-center">

            <div class="text-8xl font-bold text-indigo-600">
                404
            </div>

            <h1 class="mt-4 text-3xl font-bold text-slate-800">
                Halaman Tidak Ditemukan
            </h1>

            <p class="mt-3 text-slate-500">
                Maaf, tautan atau halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.
            </p>

            <div class="mt-8">
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3
                           font-semibold text-white transition hover:bg-indigo-700 shadow-sm"
                >
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    Kembali ke Dashboard
                </a>
            </div>

        </div>
    </div>

</x-layout>