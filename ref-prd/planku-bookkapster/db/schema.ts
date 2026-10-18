import { pgTable, uuid, text, integer, boolean, timestamp, time, date, pgEnum } from "drizzle-orm/pg-core";

export const peranEnum = pgEnum("peran", ["admin", "kapster"]);
export const hariEnum = pgEnum("hari_dalam_minggu", ["senin", "selasa", "rabu", "kamis", "jumat", "sabtu", "minggu"]);
export const statusBookingEnum = pgEnum("status_booking", ["dikonfirmasi", "selesai", "batal"]);
export const statusPengirimanEnum = pgEnum("status_pengiriman", ["terkirim", "gagal"]);

export const pengguna = pgTable("pengguna", {
  id: uuid("id").primaryKey().defaultRandom(),
  nama: text("nama").notNull(),
  email: text("email").notNull().unique(),
  peran: peranEnum("peran").notNull(),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
});

export const kapster = pgTable("kapster", {
  id: uuid("id").primaryKey().defaultRandom(),
  penggunaId: uuid("pengguna_id").references(() => pengguna.id),
  nama: text("nama").notNull(),
  fotoUrl: text("foto_url"),
  aktif: boolean("aktif").default(true).notNull(),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
});

export const jamKerjaKapster = pgTable("jam_kerja_kapster", {
  id: uuid("id").primaryKey().defaultRandom(),
  kapsterId: uuid("kapster_id").references(() => kapster.id).notNull(),
  hariDalamMinggu: hariEnum("hari_dalam_minggu").notNull(),
  jamMulai: time("jam_mulai").notNull(),
  jamSelesai: time("jam_selesai").notNull(),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
});

export const hariLiburKapster = pgTable("hari_libur_kapster", {
  id: uuid("id").primaryKey().defaultRandom(),
  kapsterId: uuid("kapster_id").references(() => kapster.id).notNull(),
  tanggal: date("tanggal").notNull(),
  alasan: text("alasan"),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
});

export const layanan = pgTable("layanan", {
  id: uuid("id").primaryKey().defaultRandom(),
  nama: text("nama").notNull(),
  deskripsi: text("deskripsi"),
  harga: integer("harga").notNull(),
  durasiMenit: integer("durasi_menit").notNull(),
  aktif: boolean("aktif").default(true).notNull(),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
  diperbaruiPada: timestamp("diperbarui_pada").defaultNow().notNull(),
});

export const booking = pgTable("booking", {
  id: uuid("id").primaryKey().defaultRandom(),
  namaTamu: text("nama_tamu").notNull(),
  kontakTamu: text("kontak_tamu").notNull(),
  kapsterId: uuid("kapster_id").references(() => kapster.id).notNull(),
  layananId: uuid("layanan_id").references(() => layanan.id).notNull(),
  mulaiPada: timestamp("mulai_pada").notNull(),
  selesaiPada: timestamp("selesai_pada").notNull(),
  namaLayananSnapshot: text("nama_layanan_snapshot").notNull(),
  hargaSnapshot: integer("harga_snapshot").notNull(),
  durasiMenitSnapshot: integer("durasi_menit_snapshot").notNull(),
  status: statusBookingEnum("status").default("dikonfirmasi").notNull(),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
  diperbaruiPada: timestamp("diperbarui_pada").defaultNow().notNull(),
}, (table) => ({
  slotKapsterUnik: {
    columns: [table.kapsterId, table.mulaiPada],
    isUnique: true,
  },
}));

export const riwayatStatusBooking = pgTable("riwayat_status_booking", {
  id: uuid("id").primaryKey().defaultRandom(),
  bookingId: uuid("booking_id").references(() => booking.id).notNull(),
  statusSebelum: statusBookingEnum("status_sebelum"),
  statusSesudah: statusBookingEnum("status_sesudah").notNull(),
  diubahOleh: uuid("diubah_oleh").references(() => pengguna.id).notNull(),
  diubahPada: timestamp("diubah_pada").defaultNow().notNull(),
});

export const logPengingatWa = pgTable("log_pengingat_wa", {
  id: uuid("id").primaryKey().defaultRandom(),
  bookingId: uuid("booking_id").references(() => booking.id).notNull(),
  statusPengiriman: statusPengirimanEnum("status_pengiriman").notNull(),
  pesanTerkirim: text("pesan_terkirim"),
  keteranganGagal: text("keterangan_gagal"),
  dikirimPada: timestamp("dikirim_pada").defaultNow().notNull(),
});

export const logAudit = pgTable("log_audit", {
  id: uuid("id").primaryKey().defaultRandom(),
  aktorId: uuid("aktor_id").references(() => pengguna.id).notNull(),
  aksi: text("aksi").notNull(),
  entitas: text("entitas").notNull(),
  entitasId: uuid("entitas_id").notNull(),
  ringkasanPerubahan: text("ringkasan_perubahan").notNull(),
  dibuatPada: timestamp("dibuat_pada").defaultNow().notNull(),
});
