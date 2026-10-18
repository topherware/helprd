# Hasil Benchmark Standar Kualitas v1.2

Dibuat oleh `php tools/benchmark.php --tulis` pada 2026-10-18 21:38.

Kolom dimensi: Kons = Konsistensi, Ukur = Keterukuran, Jujur = Kejujuran, Aman = Keamanan (Wajib MVP), Stack = Kecocokan Stack, Lacak = Keterlacakan, Strk = Struktur, Baca = Keterbacaan (belum dinilai otomatis), Siap = Kesiapan Bangun (V12–V19). "–" = tidak berlaku atau belum dinilai.

Kekurangan 1–7: design system, peta rute, data dummy, skema kode, layanan & .env, beban keamanan, milestone & checkpoint.

| Fixture | Skor | Status | K/M/m | Kons | Ukur | Jujur | Aman | Stack | Lacak | Strk | Baca | Siap | Kekurangan 1–7 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| katering-lama.json | 69 | Belum lulus | 4/11/13 | 90 | 100 | 100 | 100 | 90 | 100 | 50 | – | 0 | ❌❌❌❌❌❌❌ |
| mock-katering.json | 100 | Lulus | 0/0/0 | 100 | 100 | 100 | 100 | 100 | 100 | 100 | – | 100 | ✅✅✅✅✅✅✅ |
| mock-kebersihan.json | 100 | Lulus | 0/0/0 | 100 | 100 | 100 | 100 | 100 | 100 | 100 | – | 100 | ✅✅✅✅✅✅✅ |
| mock-lab.json | 100 | Lulus | 0/0/0 | 100 | 100 | 100 | – | 100 | 100 | 100 | – | 100 | ✅✅✅➖➖➖✅ |
| ai-katering.json | 96 | Lulus | 0/1/2 | 100 | 100 | 100 | 100 | 100 | 100 | 100 | – | 84 | ✅✅❌✅✅✅✅ |

## Rincian

### katering-lama.json

Diekspor dari project #9 pada 2026-10-18 20:58

Temuan per validator: KecocokanStack (1), Konsistensi (1), Struktur (5), V12 (1), V13 (1), V14 (1), V15 (1), V16 (1), V17 (2), V18 (1), V19 (13).

### mock-katering.json

Mode tiruan: Full Supabase

Tidak ada temuan.

### mock-kebersihan.json

Mode tiruan: React Vite + Node.js

Tidak ada temuan.

### mock-lab.json

Mode tiruan: React + Cloudflare, mode UI-only

Tidak ada temuan.

### ai-katering.json

AI sungguhan (claude-sonnet-5 via proxy), Full Supabase, gaya Bebas, cara kerja per Fase; setelah Pass 3–5 (audit + perbaikan)

Temuan per validator: V14 (1), V19 (2).
