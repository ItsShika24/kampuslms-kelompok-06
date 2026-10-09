<x-layout title="403 - Akses Ditolak">

    <div class="min-h-[70vh] flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">

            {{-- Icon Badge --}}
            <div class="w-20 h-20 mx-auto rounded-3xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 mb-6 shadow-sm">
                <span class="material-symbols-outlined text-4xl">gpp_bad</span>
            </div>

            <div class="text-7xl font-extrabold text-slate-900 tracking-tight">
                403
            </div>

            <h1 class="mt-3 text-2xl font-bold text-slate-800">
                Akses Ditolak
            </h1>

            <p class="mt-3 text-sm text-slate-500 leading-relaxed">
                @if ($exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.')
                    {{ $exception->getMessage() }}
                @else
                    Maaf, Anda tidak memiliki izin atau hak akses untuk mengakses halaman atau sumber daya ini.
                @endif
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                    type="button"
                    onclick="window.history.length > 1 ? window.history.back() : window.location.href = '{{ route('dashboard') }}'"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-slate-200 px-5 py-2.5
                           font-semibold text-slate-700 text-sm transition hover:bg-slate-300 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Kembali
                </button>

                <a
                    href="{{ route('dashboard') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5
                           font-semibold text-white text-sm transition hover:bg-indigo-700 shadow-sm"
                >
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    Ke Dashboard
                </a>
            </div>

        </div>
    </div>

</x-layout>
