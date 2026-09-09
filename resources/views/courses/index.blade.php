{{-- Halaman Daftar Mata Kuliah --}}
<x-layout title="Daftar Mata Kuliah">

    <div class="page-header">
        <h1>Mata Kuliah</h1>
        <p>Daftar seluruh mata kuliah yang tersedia di sistem</p>
    </div>

    {{-- Tabel daftar mata kuliah --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="text-align: left; padding: 0.75rem 1.25rem; font-weight: 600; color: #475569;">Kode</th>
                    <th style="text-align: left; padding: 0.75rem 1.25rem; font-weight: 600; color: #475569;">Nama Mata Kuliah</th>
                    <th style="text-align: center; padding: 0.75rem 1.25rem; font-weight: 600; color: #475569;">SKS</th>
                    <th style="text-align: left; padding: 0.75rem 1.25rem; font-weight: 600; color: #475569;">Dosen</th>
                    <th style="text-align: right; padding: 0.75rem 1.25rem; font-weight: 600; color: #475569;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($courses as $course)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 0.75rem 1.25rem;">
                            <span class="badge">{{ $course['code'] }}</span>
                        </td>
                        <td style="padding: 0.75rem 1.25rem; font-weight: 500; color: #0f172a;">
                            {{ $course['name'] }}
                        </td>
                        <td style="padding: 0.75rem 1.25rem; text-align: center; color: #64748b;">
                            {{ $course['sks'] }}
                        </td>
                        <td style="padding: 0.75rem 1.25rem; color: #64748b;">
                            {{ $course['lecturer'] }}
                        </td>
                        <td style="padding: 0.75rem 1.25rem; text-align: right;">
                            <a href="{{ route('courses.show', $course['id']) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                Detail &rarr;
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-layout>
