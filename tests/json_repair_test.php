<?php
/**
 * Uji perbaikan JSON keluaran model (PartialJson::sanitize): JSON valid tidak berubah, kesalahan umum diperbaiki.
 * Jalankan: C:\xampp\php\php.exe tests\json_repair_test.php
 */
require __DIR__ . '/_lib.php';

$decode = fn (string $s) => json_decode(PartialJson::sanitize($s), true);

$valid = json_encode(['a' => 'kutip "di dalam" dan \\ backslash', 'b' => [1, 2], 'c' => ['d' => 'https://x.id/a?b=1', 'e' => "baris\nbaru"]], JSON_UNESCAPED_UNICODE);
check('JSON valid tidak berubah', PartialJson::sanitize($valid) === $valid);
check('kutip di tengah string yang lupa di-escape', $decode('{"a":"tombol "primer" biru","b":"x"}') === ['a' => 'tombol "primer" biru', 'b' => 'x']);
check('koma sebelum } dan ]', $decode('{"a":[1,2,],"b":{"c":1,},}') === ['a' => [1, 2], 'b' => ['c' => 1]]);
check('escape tidak sah (\\_)', $decode('{"a":"nama\\_tabel"}') === ['a' => 'nama\\_tabel']);
check('baris baru mentah di dalam string', $decode("{\"m\":\"sequenceDiagram\n  A->>B: x\"}") === ['m' => "sequenceDiagram\n  A->>B: x"]);
check('spasi di antara token tetap dikenali', $decode('{"a" : "b" , "c" : [ "d" , "e" ] }') === ['a' => 'b', 'c' => ['d', 'e']]);

finish();
