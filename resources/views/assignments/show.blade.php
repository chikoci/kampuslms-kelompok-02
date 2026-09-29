<x-layout :title="$assignment->title">
    <div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
            <a href="{{ route('courses.show', $assignment->course_id) }}" style="color: #2563eb; text-decoration: none; font-size: 0.875rem;">&larr; Kembali ke {{ $assignment->course->name ?? 'Mata Kuliah' }}</a>
            <h1 style="font-size: 1.75rem; font-weight: 700; color: #111827; margin-top: 0.5rem;">{{ $assignment->title }}</h1>
            <p style="color: #6b7280; font-size: 0.875rem;">Batas Pengumpulan: <strong>{{ $assignment->due_at->translatedFormat('d F Y, H:i') }}</strong> | Nilai Maksimal: <strong>{{ $assignment->max_score }}</strong></p>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Petunjuk Tugas:</h3>
            <p style="color: #4b5563; line-height: 1.6;">{{ $assignment->instructions ?: 'Tidak ada petunjuk khusus.' }}</p>
        </div>

        <div style="padding: 1.5rem; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db;">
            <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Form Pengumpulan Tugas</h4>
            <p style="font-size: 0.875rem; color: #6b7280;">Kumpulkan file tugas Anda sebelum batas waktu yang ditentukan.</p>
        </div>
    </div>
</x-layout>
