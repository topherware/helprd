<?php
require __DIR__ . '/../src/helpers.php';

$project = require_project(2);

page_header('Beberapa pertanyaan', 2, $project, '',
    '<span class="topbar-group" id="actions" hidden><a class="btn btn-ghost btn-sm" href="index.php">Kembali</a><button type="button" class="btn btn-sm" id="submitAnswers">Lanjut</button></span>');
?>
<main class="page">
    <div class="title-row">
        <div>
            <h1>Beberapa pertanyaan</h1>
            <p class="lead">Biar peta fiturnya lebih akurat. Jawab yang kamu tahu, lewati yang belum yakin.</p>
        </div>
        <span class="counter" id="counter" aria-live="polite"></span>
    </div>

    <div id="questions" aria-busy="true">
        <div class="loading">
            <span class="spinner"></span>
            <p>AI sedang menyusun pertanyaan untuk ide kamu…</p>
        </div>
    </div>

    <p class="form-error" id="formError" role="alert" hidden></p>

</main>
<?php page_footer(['step3.js']);
