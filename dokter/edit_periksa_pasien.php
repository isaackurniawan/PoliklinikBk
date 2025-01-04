<?php
session_start();

$id_daftar_poli = isset($_GET["id"]) ? $_GET["id"] : 0;

// Redirect jika ID tidak valid
if ($id_daftar_poli == 0) {
    echo "<script>alert('ID tidak valid.'); window.location.href='index.php';</script>";
    exit;
}

// Cek sesi login
if (!isset($_SESSION["login"])) {
    header("Location: ../../index.php");
    exit;
}

require '../../functions/connect_database.php';
require '../../functions/dokter_functions.php';

// Ambil data dari tabel daftar_poli dengan JOIN ke tabel pasien dan jadwal_periksa
$data_pasien = query("SELECT *
                      FROM daftar_poli
                      JOIN pasien ON daftar_poli.id_pasien = pasien.id
                      JOIN jadwal_periksa ON daftar_poli.id_jadwal = jadwal_periksa.id
                      WHERE daftar_poli.id = $id_daftar_poli");

// Cek jika data pasien kosong
if (!$data_pasien || count($data_pasien) == 0) {
    echo "<script>alert('Data tidak ditemukan.'); window.location.href='index.php';</script>";
    exit;
} else {
    $data_pasien = $data_pasien[0];
}

// Ambil data obat
$obat = query("SELECT * FROM obat");

// Ambil data periksa
$periksa = query("SELECT * FROM periksa WHERE id_daftar_poli = $id_daftar_poli ORDER BY id DESC");

// Cek jika data periksa kosong
if (!$periksa || count($periksa) == 0) {
    $periksa = ['catatan' => '', 'id' => 0];
    $id_periksa = 0;
} else {
    $periksa = $periksa[0];
    $id_periksa = $periksa['id'];
}

// Ambil data detail_periksa
$getObat = query("SELECT * FROM detail_periksa WHERE id_periksa = $id_periksa");

// Inisialisasi array kosong untuk obat
$id_selected_obat = [];
if ($getObat && count($getObat) > 0) {
    foreach ($getObat as $value) {
        $id_selected_obat[] = $value['id_obat'];
    }
}

// Cek apakah tombol submit ditekan
if (isset($_POST["submit"])) {
    if (tambah_periksa_pasien($_POST) > 0) {
        echo "<script>alert('Data berhasil ditambahkan!');</script>";
        header("Location: memeriksa_pasien.php");
    } else {
        echo "<script>alert('Data gagal ditambahkan!');</script>";
        header("Location: memeriksa_pasien.php");
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Periksa Pasien</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to right, #1A1F2B, #1A1F2B);
        }
    </style>
</head>

<body class="flex gap-5">
    <!-- Side Bar -->
    <?= include("../../components/sidebar_dokter.php"); ?>
    <!-- Side Bar End -->

    <main class="flex flex-col w-full bg-white pb-10 rounded-lg shadow-lg">
        <header class="flex items-center gap-3 px-8 py-7 shadow-lg">
            <img src="../../assets/icons/stethoscope-icon.svg" alt="" width="30px" class="invert">
            <h1 class="text-3xl font-medium">123Edit Periksa Pasien</h1>
        </header>

        <article class="mx-8 mt-8 p-8 bg-white shadow-lg rounded-lg">
            <h2 class="text-2xl font-medium text-[#0277BD] mb-5">Edit Periksa Pasien</h2>
            <form action="" method="post" class="flex flex-col gap-5">
                <input type="hidden" name="id_daftar_poli" id="id_daftar_poli" value="<?= $id_daftar_poli ?>">

                <div class="flex flex-col gap-3">
                    <label for="nama" class="text-lg font-medium">Nama Pasien</label>
                    <input type="text" name="" id="nama" readonly value="<?= $data_pasien["nama"] ?>"
                        class="px-4 py-3 text-gray-400 outline-none rounded-lg border border-gray-300">
                </div>

                <div class="flex flex-col gap-3">
                    <label for="hari" class="text-lg font-medium">Hari Periksa</label>
                    <input type="text" name="hari" id="hari" value="<?= $data_pasien["hari"] ?>"
                        class="px-4 py-3 outline-none rounded-lg border border-gray-300">
                </div>

                <div class="flex flex-col gap-3">
                    <label for="catatan" class="text-lg font-medium">Catatan</label>
                    <textarea name="catatan" rows="10" cols="" class="p-3 rounded-lg border border-gray-300"><?= isset($periksa['catatan']) ? $periksa['catatan'] : '' ?></textarea>
                </div>

                <div class="flex flex-col gap-3">
                    <label for="harga" class="text-lg font-medium">Obat</label>
                    <select id="id_obat" name="id_obat" class="px-4 py-3 outline-none rounded-lg border border-gray-300">
                        <?php foreach ($obat as $item) : ?>
                        <option value="<?= $item["id"] ?>"><?= $item["nama_obat"] ?> - <?= $item["harga"] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="buttonAddObat" class="btn btn-outline-primary btn-primary" style="width: 150px"><i class="mdi mdi-plus me-2"></i>Tambah</button>
                </div>

                <div class="flex flex-col gap-3">
                    <label for="biaya_periksa" class="text-lg font-medium">Total Harga</label>
                    <input type="text" name="biaya_periksa_mock" id="biaya_periksa_mock" value="Rp. 150.000" disabled class="px-4 py-3 outline-none rounded-lg border border-gray-300">
                    <input type="hidden" name="biaya_periksa" id="biaya_periksa" value="150000" readonly class="px-4 py-3 outline-none rounded-lg border border-gray-300">
                    <input type="hidden" name="id_obat_selected" value="[<?= implode(',', $id_selected_obat) ?>]">

                </div>

                <div class="flex flex-col gap-3">
                    <div id="info-obat" class="col-sm-10 mt-3">
                    </div>
                </div>

                <button type="submit" name="submit"
                    class="bg-[#0288D1] w-fit mx-auto py-3 px-6 text-white font-medium rounded-lg hover:bg-[#0277BD]">Simpan Perubahan</button>
            </form>
        </article>
    </main>
</body>

</html>

<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

<script>
    var data_obat = <?= json_encode($obat) ?>;

    function formatRupiah(angka) {
        var number_string = angka.toString(),
            sisa = number_string.length % 3,
            rupiah = number_string.substr(0, sisa),
            ribuan = number_string.substr(sisa).match(/\d{3}/g);

        if (ribuan) {
            var separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return 'Rp ' + rupiah; //Menambahkan prefix "Rp " (Rupiah) di depan angka yang sudah diformat 
        //55000 -> 55.000
    }

    const renderInfoObat = () => {
        let id_obat_selected = $('input[name="id_obat_selected"]').val();

        // Parse the existing value to an array
        id_obat_selected = id_obat_selected ? JSON.parse(id_obat_selected) : [];

        const renderHtml = id_obat_selected.map(id => {
            const obat = data_obat.find(o => o.id == id);
            if (!obat) return '';
            return `<p>${obat.nama_obat} - ${formatRupiah(obat.harga)}</p>`;
        });

        const biaya_periksa = 150000;
        const total = id_obat_selected.reduce((acc, curr) => {
            const obat = data_obat.find(o  => o.id == curr);
            if (!obat) return acc;
            return acc + parseInt(obat.harga);
        }, 0);

        $('input[name="biaya_periksa_mock"]').val(formatRupiah(total + biaya_periksa));
        $('input[name="biaya_periksa"]').val(total + biaya_periksa);
        $('#info-obat').html(renderHtml);
    }
    const addObat = () => {
        const id_obat = $('#id_obat').val();
        let id_obat_selected = $('input[name="id_obat_selected"]').val();

        // Parse the existing value to an array
        id_obat_selected = id_obat_selected ? JSON.parse(id_obat_selected) : [];

        // Check if id_obat already exists in the array
        if (!id_obat_selected.includes(id_obat)) {
            // Push the new id_obat to the array
            id_obat_selected.push(id_obat);

            // Update the input value with the new array
            $('input[name="id_obat_selected"]').val(JSON.stringify(id_obat_selected));
        } else {
            alert('Obat sudah ditambahkan.');
        }

        renderInfoObat();
    }

    jQuery(document).ready(function() {

        // getObat();
        renderInfoObat();

        $('#buttonAddObat').on('click', function() {
            addObat();
        });
    });
</script>