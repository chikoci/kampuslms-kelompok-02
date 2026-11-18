<x-layout :title="'Kelola Rubrik - ' . $assignment->title . ' - KampusLMS'" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Mata Kuliah', 'url' => route('courses.index')],
    ['label' => $assignment->course->name, 'url' => route('courses.show', $assignment->course)],
    ['label' => $assignment->title, 'url' => route('assignments.show', $assignment)],
    ['label' => 'Kelola Rubrik']
]">

    <div class="card-modern rubric-manage-card">
        {{-- Tombol Navigasi Kembali --}}
        <div style="margin-bottom: 1.25rem;">
            <a href="{{ route('assignments.show', $assignment) }}" class="btn-back-action" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                <span>Kembali ke Tugas</span>
            </a>
        </div>

        {{-- Header Halaman --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge-code-modern" style="margin-bottom: 0.4rem; display: inline-block;">FITUR DIFERENSIASI</span>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem 0; letter-spacing: -0.02em;">
                    Rubrik Penilaian Bertingkat
                </h1>
                <p style="color: #64748b; font-size: 0.875rem; margin: 0;">
                    Tugas: <strong>{{ $assignment->title }}</strong> &bull; Nilai Maksimal: <strong style="color: #2563eb;">{{ $assignment->max_score }}</strong> poin
                </p>
            </div>

            @if($rubric)
                <form action="{{ route('assignments.rubric.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rubrik ini?');" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary btn-sm" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 0.5rem 0.95rem; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        </svg>
                        <span>Hapus Rubrik</span>
                    </button>
                </form>
            @endif
        </div>

        @if($isAssessed)
            <div style="padding: 1rem 1.25rem; background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.875rem; color: #b45309; display: flex; align-items: center; gap: 0.65rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div><strong>Perhatian:</strong> Rubrik ini sudah digunakan untuk menilai satu atau lebih pengumpulan mahasiswa. Mengubah kriteria atau bobot dapat memengaruhi konsistensi nilai.</div>
            </div>
        @endif

        @if($errors->any())
            <div style="padding: 1rem 1.25rem; background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; margin-bottom: 1.5rem;">
                <p style="color: #dc2626; font-weight: 700; margin: 0 0 0.5rem 0; font-size: 0.9rem;">Terdapat kendala pada form rubrik:</p>
                <ul style="margin: 0; padding-left: 1.25rem; color: #b91c1c; font-size: 0.85rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('assignments.rubric.store', $assignment) }}" method="POST" id="rubricForm">
            @csrf

            {{-- 1. Informasi Umum Rubrik --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label for="rubric_title" style="display: block; font-weight: 700; color: #1e293b; font-size: 0.875rem; margin-bottom: 0.35rem;">
                        Judul Rubrik <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" id="rubric_title" name="title" value="{{ old('title', $rubric->title ?? 'Rubrik Penilaian - ' . $assignment->title) }}" required
                           class="filter-search-input" style="width: 100%; height: 42px; border-radius: 10px; padding: 0 0.85rem;" placeholder="Contoh: Rubrik Penilaian Proyek Web">
                </div>

                <div>
                    <label for="rubric_description" style="display: block; font-weight: 700; color: #1e293b; font-size: 0.875rem; margin-bottom: 0.35rem;">
                        Deskripsi / Panduan Umum (Opsional)
                    </label>
                    <textarea id="rubric_description" name="description" rows="2"
                              class="filter-search-input" style="width: 100%; height: auto; border-radius: 10px; padding: 0.65rem 0.85rem; line-height: 1.5;"
                              placeholder="Panduan umum bagi mahasiswa dan dosen terkait kriteria rubrik ini...">{{ old('description', $rubric->description ?? '') }}</textarea>
                </div>
            </div>

            {{-- 2. Ringkasan Status Total Bobot --}}
            <div class="rubric-weight-banner">
                <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                    <span style="font-size: 0.85rem; color: #1e40af; font-weight: 600;">Status Total Bobot Kriteria:</span>
                    <span id="weightSummary" style="font-size: 1.2rem; font-weight: 800; color: #1d4ed8;">0% / 100%</span>
                </div>
                <div id="weightBadge" class="rubric-weight-badge" style="background: #fee2e2; color: #dc2626;">
                    Wajib Tepat 100%
                </div>
            </div>

            {{-- 3. Daftar Kriteria Penilaian --}}
            <div id="criteriaContainer">
                {{-- Data kriteria dirender di sini via Blade & JavaScript --}}
            </div>

            {{-- Tombol Tambah Kriteria --}}
            <div style="margin-bottom: 2rem;">
                <button type="button" id="btnAddCriterion" class="btn-add-criterion-dashed">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Tambah Kriteria Penilaian Baru</span>
                </button>
            </div>

            {{-- Action Submit --}}
            <div style="border-top: 1px solid #e2e8f0; padding-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-secondary" style="padding: 0.65rem 1.25rem; border-radius: 10px; font-weight: 600; text-decoration: none;">
                    Batal
                </a>
                <button type="submit" id="btnSubmitRubric" class="btn btn-primary" style="padding: 0.65rem 1.75rem; border-radius: 10px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    <span>Simpan Rubrik Penilaian</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Script Interaktif Penambahan/Penghapusan Kriteria & Level --}}
    <script>
        const initialCriteria = @json(old('criteria', $initialCriteria));

        const criteriaContainer = document.getElementById('criteriaContainer');
        const btnAddCriterion = document.getElementById('btnAddCriterion');
        const weightSummary = document.getElementById('weightSummary');
        const weightBadge = document.getElementById('weightBadge');
        const btnSubmitRubric = document.getElementById('btnSubmitRubric');

        let criterionCount = 0;

        function updateWeightSummary() {
            let total = 0;
            const weightInputs = document.querySelectorAll('.criterion-weight-input');
            weightInputs.forEach(input => {
                const val = parseFloat(input.value) || 0;
                total += val;
            });

            total = Math.round(total * 100) / 100;
            weightSummary.textContent = `${total}% / 100%`;

            if (Math.abs(total - 100.0) <= 0.05) {
                weightBadge.textContent = 'Bobot Tepat 100% (Valid ✔)';
                weightBadge.style.background = '#dcfce7';
                weightBadge.style.color = '#15803d';
                btnSubmitRubric.disabled = false;
                btnSubmitRubric.style.opacity = '1';
                btnSubmitRubric.style.cursor = 'pointer';
            } else {
                weightBadge.textContent = `Total bobot ${total}% (Harus 100% ⚠️)`;
                weightBadge.style.background = '#fee2e2';
                weightBadge.style.color = '#dc2626';
            }
        }

        function createLevelRow(cIdx, lIdx, level = {}) {
            const row = document.createElement('div');
            row.className = 'level-row-item';

            row.innerHTML = `
                <input type="hidden" name="criteria[${cIdx}][levels][${lIdx}][id]" value="${level.id || ''}">
                <div class="level-title-col">
                    <input type="text" name="criteria[${cIdx}][levels][${lIdx}][title]" value="${level.title || ''}" required
                           placeholder="Nama Tingkat (e.g. Sangat Baik)" class="filter-search-input" style="width: 100%; height: 38px; font-size: 0.85rem; font-weight: 600; padding: 0 0.65rem; border-radius: 8px;">
                </div>
                <div class="level-desc-col">
                    <input type="text" name="criteria[${cIdx}][levels][${lIdx}][description]" value="${level.description || ''}"
                           placeholder="Indikator capaian mahasiswa..." class="filter-search-input" style="width: 100%; height: 38px; font-size: 0.825rem; padding: 0 0.65rem; border-radius: 8px;">
                </div>
                <div class="level-score-col">
                    <div style="display: flex; align-items: center; gap: 0.25rem; justify-content: flex-end;">
                        <input type="number" step="0.01" min="0" max="100" name="criteria[${cIdx}][levels][${lIdx}][score_percentage]" value="${level.score_percentage !== undefined ? level.score_percentage : 100}" required
                               class="filter-search-input" style="width: 65px; height: 38px; font-size: 0.85rem; font-weight: 800; text-align: right; padding: 0 0.4rem; border-radius: 8px;">
                        <span style="font-size: 0.8rem; font-weight: 800; color: #64748b;">%</span>
                    </div>
                </div>
                <div class="level-action-col" style="text-align: right;">
                    <button type="button" class="btn-remove-level" title="Hapus Level" style="background: #fef2f2; border: 1px solid #fee2e2; color: #ef4444; border-radius: 8px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; cursor: pointer; transition: all 0.15s ease;">
                        &times;
                    </button>
                </div>
            `;

            row.querySelector('.btn-remove-level').addEventListener('click', () => {
                const parent = row.parentElement;
                if (parent.querySelectorAll('.level-row-item').length <= 1) {
                    alert('Setiap kriteria harus memiliki minimal 1 tingkatan (level).');
                    return;
                }
                row.remove();
            });

            return row;
        }

        function createCriterionCard(cIdx, data = {}) {
            const card = document.createElement('div');
            card.className = 'criterion-card-modern';

            card.innerHTML = `
                <input type="hidden" name="criteria[${cIdx}][id]" value="${data.id || ''}">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="badge" style="background: #2563eb; color: #ffffff; padding: 0.25rem 0.65rem; border-radius: 6px; font-weight: 800; font-size: 0.75rem;">
                            Kriteria #${cIdx + 1}
                        </span>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a;">Pengaturan Aspek Penilaian</h4>
                    </div>
                    <button type="button" class="btn-remove-criterion" style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        </svg>
                        <span>Hapus Kriteria</span>
                    </button>
                </div>

                <div class="criterion-header-grid">
                    <div>
                        <label style="display: block; font-weight: 700; color: #334155; font-size: 0.825rem; margin-bottom: 0.35rem;">
                            Judul Kriteria <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="criteria[${cIdx}][title]" value="${data.title || ''}" required
                               class="filter-search-input" style="width: 100%; height: 40px; border-radius: 8px; padding: 0 0.75rem;" placeholder="Contoh: Kualitas Kode & Arsitektur">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; color: #334155; font-size: 0.825rem; margin-bottom: 0.35rem;">
                            Bobot Kriteria (%) <span style="color: #dc2626;">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <input type="number" step="0.01" min="1" max="100" name="criteria[${cIdx}][weight]" value="${data.weight !== undefined ? data.weight : 50}" required
                                   class="filter-search-input criterion-weight-input" style="width: 100%; height: 40px; border-radius: 8px; padding: 0 0.5rem; font-weight: 800; text-align: right; color: #2563eb;">
                            <span style="font-weight: 800; color: #64748b;">%</span>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 700; color: #334155; font-size: 0.825rem; margin-bottom: 0.35rem;">
                        Deskripsi Penilaian (Opsional)
                    </label>
                    <input type="text" name="criteria[${cIdx}][description]" value="${data.description || ''}"
                           class="filter-search-input" style="width: 100%; height: 38px; border-radius: 8px; padding: 0 0.75rem;" placeholder="Penjelasan apa yang dinilai pada kriteria ini...">
                </div>

                {{-- Bagian Tingkatan / Levels --}}
                <div class="levels-container-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <h5 style="margin: 0; font-size: 0.825rem; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.03em; display: flex; align-items: center; gap: 0.4rem;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20v-6M6 20V10M18 20V4"/>
                            </svg>
                            <span>Tingkatan Capaian (Levels / Tiers)</span>
                        </h5>
                        <button type="button" class="btn-add-level-pill">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            <span>Tambah Tingkat</span>
                        </button>
                    </div>

                    <div class="levels-header-grid">
                        <span>Nama Tingkatan</span>
                        <span>Deskripsi Capaian</span>
                        <span style="text-align: right; padding-right: 0.5rem;">Skor (%)</span>
                        <span></span>
                    </div>

                    <div class="levels-container"></div>
                </div>
            `;

            const levelsContainer = card.querySelector('.levels-container');
            const levels = data.levels && data.levels.length > 0 ? data.levels : [
                { title: 'Sangat Baik', description: 'Memenuhi seluruh ekspektasi', score_percentage: 100 },
                { title: 'Cukup', description: 'Memenuhi sebagian kriteria', score_percentage: 60 }
            ];

            levels.forEach((lvl, lIdx) => {
                levelsContainer.appendChild(createLevelRow(cIdx, lIdx, lvl));
            });

            card.querySelector('.btn-add-level-pill').addEventListener('click', () => {
                const nextLIdx = levelsContainer.querySelectorAll('.level-row-item').length;
                levelsContainer.appendChild(createLevelRow(cIdx, nextLIdx, { title: 'Baru', score_percentage: 50 }));
            });

            card.querySelector('.btn-remove-criterion').addEventListener('click', () => {
                if (document.querySelectorAll('.criterion-card-modern').length <= 1) {
                    alert('Rubrik harus memiliki minimal 1 kriteria.');
                    return;
                }
                card.remove();
                updateWeightSummary();
            });

            const weightInput = card.querySelector('.criterion-weight-input');
            weightInput.addEventListener('input', updateWeightSummary);

            return card;
        }

        // Render kriteria awal
        initialCriteria.forEach((crit, idx) => {
            criteriaContainer.appendChild(createCriterionCard(idx, crit));
            criterionCount++;
        });

        btnAddCriterion.addEventListener('click', () => {
            const newCard = createCriterionCard(criterionCount, {
                title: '',
                description: '',
                weight: 0,
                levels: [
                    { title: 'Sangat Baik', description: 'Memenuhi kriteria secara sempurna', score_percentage: 100 },
                    { title: 'Baik', description: 'Memenuhi kriteria dengan sedikit kekurangan', score_percentage: 80 },
                    { title: 'Cukup', description: 'Cukup memenuhi kriteria dasar', score_percentage: 60 },
                    { title: 'Kurang', description: 'Belum memenuhi kriteria', score_percentage: 40 }
                ]
            });
            criteriaContainer.appendChild(newCard);
            criterionCount++;
            updateWeightSummary();
        });

        // Inisialisasi hitung total
        updateWeightSummary();
    </script>
</x-layout>


