<x-layout title="Tambah Materi">

    <div class="mb-6">
        <a href="{{ route('mata-kuliah.show', $course->id) }}"
           class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition mb-4">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke {{ $course->name }}
        </a>
        <p class="text-sm font-semibold text-indigo-600 mb-1">{{ $course->code }} / MATERI</p>
        <h1 class="text-3xl font-bold text-slate-900">Tambah Materi Baru</h1>
        <p class="text-sm text-slate-500 mt-1">Unggah file, tautan, atau konten teks untuk mata kuliah ini.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 max-w-2xl">
        <form action="{{ route('materi.store', $course->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Judul --}}
            <div class="mb-5">
                <label for="title" class="block text-sm font-semibold text-slate-700 mb-2">
                    Judul Materi <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}"
                       placeholder="Contoh: Pertemuan 1 – Pengantar Sistem Informasi"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                              focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('title')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div class="mb-5">
                <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">
                    Deskripsi
                </label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Deskripsi singkat isi materi…"
                          class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                                 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipe --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Tipe Materi <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['file' => ['attach_file','Upload File'], 'link' => ['link','Tautan URL'], 'text' => ['article','Konten Teks']] as $val => [$icon, $label])
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="{{ $val }}"
                                   {{ old('type', 'file') === $val ? 'checked' : '' }}
                                   class="sr-only peer" onchange="toggleType()">
                            <div class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-slate-200
                                        peer-checked:border-indigo-500 peer-checked:bg-indigo-50
                                        hover:border-slate-300 transition text-center">
                                <span class="material-symbols-outlined text-slate-400 peer-checked:text-indigo-600 text-2xl">{{ $icon }}</span>
                                <span class="text-xs font-semibold text-slate-600">{{ $label }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('type')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Upload File --}}
            <div id="section-file" class="mb-5">
                <label for="file" class="block text-sm font-semibold text-slate-700 mb-2">
                    File <span class="text-red-500">*</span>
                    <span class="font-normal text-slate-400">(PDF, PPT, DOCX, ZIP – maks. 50 MB)</span>
                </label>
                <input type="file" id="file" name="file"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                              file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0
                              file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100 focus:outline-none">
                @error('file')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- External URL --}}
            <div id="section-link" class="mb-5 hidden">
                <label for="external_url" class="block text-sm font-semibold text-slate-700 mb-2">
                    URL Tautan <span class="text-red-500">*</span>
                </label>
                <input type="url" id="external_url" name="external_url"
                       value="{{ old('external_url') }}"
                       placeholder="https://drive.google.com/…"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm
                              focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('external_url')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Teks konten (info saja — disimpan di description) --}}
            <div id="section-text" class="mb-5 hidden">
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700">
                    Untuk tipe teks, konten ditulis di kolom <strong>Deskripsi</strong> di atas.
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('mata-kuliah.show', $course->id) }}"
                   class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-300
                          text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                               bg-indigo-600 text-white font-semibold text-sm hover:bg-indigo-700 transition">
                    <span class="material-symbols-outlined text-[18px]">upload</span>
                    Simpan Materi
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleType() {
            const type = document.querySelector('input[name="type"]:checked')?.value;
            document.getElementById('section-file').classList.toggle('hidden', type !== 'file');
            document.getElementById('section-link').classList.toggle('hidden', type !== 'link');
            document.getElementById('section-text').classList.toggle('hidden', type !== 'text');
        }
        toggleType();
    </script>

</x-layout>
