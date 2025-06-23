<?php
require_once __DIR__ . '/src/Message.php';

$dummyMessages = [
    "Halo",
    "Ini adalah pesan rahasia",
    "Kamu harus merahasiakan pesan ini karena sangat penting",
    "Seluruh komunikasi ini telah dienkripsi menggunakan algoritma ganda untuk memastikan kerahasiaannya",
    str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ", 20), // ~1000 chars
    str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ", 200) // ~10000 chars
];

$testCases = [
    ["message" => $dummyMessages[0], "key" => "K3y#S3cur3@2024!P4ss"],
    ["message" => $dummyMessages[1], "key" => "Str0ng#S3cur1ty!K3y$2024"],
    ["message" => $dummyMessages[2], "key" => "Sup3r#S3cur3@K3y!2024P4ss"],
    ["message" => $dummyMessages[3], "key" => "V3ry#L0ng@S3cur3!K3y$2024#P4ss"],
    ["message" => $dummyMessages[4], "key" => "L0ng#M3ss4g3@K3y!2024S3cur3"],
    ["message" => $dummyMessages[5], "key" => "V3ry#L0ng@M3ss4g3!K3y$2024#S3cur3"]
];

// HTML structure with Bootstrap
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encryption Test Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Hasil Pengujian Enkripsi</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Panjang</th>
                        <th>Kunci</th>
                        <th>Waktu Enkripsi</th>
                        <th>Waktu Dekripsi</th>
                        <th>Total Waktu</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testCases as $index => $test):
                        $message = $test["message"];
                        $key = $test["key"];

                        $startEnc = microtime(true);
                        $encrypted = base64_encode(encryptMessage($message, $key));
                        $endEnc = microtime(true);

                        $startDec = microtime(true);
                        $decrypted = decryptMessage(base64_decode($encrypted), $key);
                        $endDec = microtime(true);

                        $encTime = round(($endEnc - $startEnc) * 1000, 3);
                        $decTime = round(($endDec - $startDec) * 1000, 3);
                        $totalTime = round($encTime + $decTime, 3);
                        $status = $message === $decrypted ? "SUKSES" : "GAGAL";
                        $statusClass = $status === "SUKSES" ? "text-success" : "text-danger";
                    ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= strlen($message) ?> char</td>
                            <td><?= htmlspecialchars($key) ?></td>
                            <td><?= $encTime ?> ms</td>
                            <td><?= $decTime ?> ms</td>
                            <td><?= $totalTime ?> ms</td>
                            <td class="<?= $statusClass ?>"><?= $status ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>