<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    // APA: Method index untuk menampilkan daftar semua mata kuliah.
    // KENAPA: Sebagai titik masuk awal saat pengguna mengakses halaman /courses.
    public function index()
    {
        // APA: Data statis (dummy) sementara berupa array.
        // KENAPA: Karena kita belum menggunakan database di minggu ini.
        $courses = [
            ['id' => 1, 'code' => 'SI2514', 'name' => 'Pemrograman Web', 'sks' => 3, 'lecturer' => 'Dr. Budi'],
            ['id' => 2, 'code' => 'SI2515', 'name' => 'Basis Data', 'sks' => 4, 'lecturer' => 'Dr. Andi'],
            ['id' => 3, 'code' => 'SI2516', 'name' => 'Jaringan Komputer', 'sks' => 3, 'lecturer' => 'Dr. Cici'],
        ];

        // APA: Me-return view courses.index dan mengirimkan variabel courses.
        // KENAPA: Agar view bisa membaca dan menampilkan daftar mata kuliah tersebut.
        return view('courses.index', compact('courses'));
    }

    // APA: Method show untuk menampilkan detail SATU mata kuliah berdasarkan ID.
    // KENAPA: Agar pengguna bisa melihat informasi lebih lengkap dari mata kuliah yang dipilih.
    public function show($course)
    {
        // APA: Menggunakan data statis yang sama untuk mencari mata kuliah berdasarkan ID.
        // KENAPA: Sebagai simulasi pencarian data dari database berdasarkan Primary Key (ID).
        $allCourses = [
            1 => ['id' => 1, 'code' => 'SI2514', 'name' => 'Pemrograman Web', 'sks' => 3, 'lecturer' => 'Dr. Budi', 'description' => 'Mata kuliah ini membahas pengembangan aplikasi web modern menggunakan Laravel 12.'],
            2 => ['id' => 2, 'code' => 'SI2515', 'name' => 'Basis Data', 'sks' => 4, 'lecturer' => 'Dr. Andi', 'description' => 'Belajar perancangan database relasional dan SQL tingkat lanjut.'],
            3 => ['id' => 3, 'code' => 'SI2516', 'name' => 'Jaringan Komputer', 'sks' => 3, 'lecturer' => 'Dr. Cici', 'description' => 'Mempelajari arsitektur jaringan TCP/IP dan protokol komunikasi.'],
        ];

        // APA: Mengecek apakah mata kuliah dengan ID tersebut ada.
        // KENAPA: Untuk menghindari error jika pengguna mengetik URL ID yang salah/tidak terdaftar.
        if (!isset($allCourses[$course])) {
            abort(404); // Menampilkan halaman Not Found
        }

        $courseData = $allCourses[$course];

        // APA: Me-return view courses.show dan mengirimkan variabel course.
        return view('courses.show', ['course' => $courseData]);
    }
}
