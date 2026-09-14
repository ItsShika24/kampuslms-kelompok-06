<x-layout title="Edit Tugas">

    <div class="mb-6">
        <a href="{{ route('mata-kuliah.show', $assignment->course_id) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition mb-4">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke {{ $assignment->course->name }}
        </a>
        <p class="text-sm font-semibold text-indigo-600 mb-1">
            {{ $assignment->course->code }} / EDIT TUGAS
        </p>
        <h1 class="text-3xl font-bold text-slate-900">Edit Tugas</h1>
        <p class="text-sm text-slate-500 mt-1">Perbarui informasi tugas pada mata kuliah ini.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 max-w-2xl">
        <form action="{{ route('tugas.update', $assignment->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Judul --}}
            <div class="mb-5">
                <label for="title" class="block text-sm font-semibold text-slate-700 mb-2">
                    Judul Tugas <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title"
                       value="{{ old('title', $assignment->title) }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                              focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('title')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Instruksi --}}
            <div class="mb-5">
                <label for="instructions" class="block text-sm font-semibold text-slate-700 mb-2">
                    Instruksi / Deskripsi
                </label>
                <textarea id="instructions" name="instructions" rows="5"
                          class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                                 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('instructions', $assignment->instructions) }}</textarea>
                @error('instructions')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tenggat & Nilai Maks --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label for="due_at" class="block text-sm font-semibold text-slate-700 mb-2">
                        Tenggat Waktu <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" id="due_at" name="due_at"
                           value="{{ old('due_at', $assignment->due_at->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                                  focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('due_at')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="max_score" class="block text-sm font-semibold text-slate-700 mb-2">
                        Nilai Maksimal <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="max_score" name="max_score"
                           value="{{ old('max_score', $assignment->max_score) }}"
                           min="1" max="1000"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                                  focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('max_score')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Status & Allow Late --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                                   focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="draft"  {{ old('status', $assignment->status) === 'draft'  ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ old('status', $assignment->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="closed" {{ old('status', $assignment->status) === 'closed' ? 'selected' : '' }}>Ditutup</option>
                    </select>
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="allow_late" value="0">
                        <input type="checkbox" id="allow_late" name="allow_late" value="1"
                               {{ old('allow_late', $assignment->allow_late) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-300 text-indigo-600
                                      focus:ring-indigo-500 cursor-pointer">
                        <span class="text-sm font-medium text-slate-700">
                            Izinkan pengumpulan terlambat
                        </span>
                    </label>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('mata-kuliah.show', $assignment->course_id) }}"
                   class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-300
                          text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                               bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</x-layout>
