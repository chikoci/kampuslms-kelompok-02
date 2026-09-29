<x-layout title="Detail Pengumpulan Tugas">
    <div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #111827;">Pengumpulan Tugas #{{ $submission->id }}</h1>
            <p style="color: #6b7280; font-size: 0.875rem;">
                Tugas: <strong>{{ $submission->assignment->title ?? 'Tugas Perkuliahan' }}</strong> |
                Mata Kuliah: <strong>{{ $submission->assignment->course->name ?? '-' }}</strong>
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Nama Mahasiswa</span>
                <p style="font-size: 1rem; font-weight: 600; color: #111827; margin-top: 0.25rem;">{{ $submission->student->name ?? 'Mahasiswa' }}</p>
            </div>
            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600;">Waktu Pengumpulan</span>
                <p style="font-size: 1rem; font-weight: 600; color: #111827; margin-top: 0.25rem;">{{ $submission->submitted_at ? $submission->submitted_at->translatedFormat('d F Y, H:i') : '-' }}</p>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Catatan Mahasiswa:</h3>
            <p style="color: #4b5563; line-height: 1.5; background: #fdfdfd; padding: 1rem; border-radius: 8px; border: 1px solid #f3f4f6;">{{ $submission->note ?: 'Tidak ada catatan.' }}</p>
        </div>

        @if($submission->file_path)
            <div style="margin-top: 1.5rem;">
                <a href="{{ route('submissions.download', $submission) }}" 
                   style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; font-size: 0.875rem; font-weight: 500; border-radius: 8px; background-color: #2563eb; color: #ffffff; text-decoration: none;">
                    &#128190; Unduh Berkas: {{ $submission->original_name ?: 'Berkas Tugas' }}
                </a>
            </div>
        @endif
    </div>
</x-layout>
