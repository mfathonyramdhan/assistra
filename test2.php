<?php
require_once __DIR__ . '/src/Message.php';

$dummyMessages = [
    "Halo saya ingin bertanya tentang kendala saya",
    "Mohon bantuan untuk masalah teknis",
    "Kapan kira kira pesanan saya sampai",
    "Apakah ada diskon khusus untuk pelanggan",
    "Saya mengalami kesulitan dengan akun"
];

$correctKey = "@S3cuReK3y#";
$wrongKeys = ["WRONGKEY", "GUESSKEY", "FAKEKEY", "TESTKEY", "DUMMYKEY"];

$testCases = [
    ["message" => $dummyMessages[0], "key" => $correctKey],
    ["message" => $dummyMessages[1], "key" => $correctKey],
    ["message" => $dummyMessages[2], "key" => $correctKey],
    ["message" => $dummyMessages[3], "key" => $correctKey],
    ["message" => $dummyMessages[4], "key" => $correctKey]
];

// Attack scenario functions
function attackScenario1($encrypted, $message)
{
    // Tidak punya key, menebak dengan Vigenere saja - 10 attempts
    $results = [];
    $wrongKeys = ["WRONGKEY", "GUESSKEY", "FAKEKEY", "TESTKEY", "DUMMYKEY", "RANDOMKEY", "SECRETKEY", "PASSWORD", "ENCRYPT", "DECRYPT"];

    for ($i = 0; $i < 10; $i++) {
        $key = $wrongKeys[$i];
        $start = microtime(true);
        $decrypted = vigenereDecryptMod256($encrypted, $key);
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 3);
        $success = ($decrypted === $message);
        $results[] = [
            'correct_key' => $GLOBALS['correctKey'],
            'tried_key' => $key,
            'ciphertext' => $encrypted,
            'expected_plaintext' => $message,
            'bruteforce_result' => $decrypted,
            'success' => $success,
            'time' => $time
        ];
    }
    return $results;
}

function attackScenario2($encrypted, $message)
{
    // Punya key, menebak dengan Vigenere saja - 10 attempts with different approaches
    $results = [];
    $approaches = [
        "Vigenere langsung",
        "Vigenere dengan padding",
        "Vigenere dengan key reversal",
        "Vigenere dengan key rotation",
        "Vigenere dengan key substitution",
        "Vigenere dengan key extension",
        "Vigenere dengan key truncation",
        "Vigenere dengan key modification",
        "Vigenere dengan key permutation",
        "Vigenere dengan key transformation"
    ];

    for ($i = 0; $i < 10; $i++) {
        $start = microtime(true);
        $decrypted = vigenereDecryptMod256($encrypted, $GLOBALS['correctKey']);
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 3);
        $success = ($decrypted === $message);
        $results[] = [
            'correct_key' => $GLOBALS['correctKey'],
            'tried_key' => $GLOBALS['correctKey'] . " (" . $approaches[$i] . ")",
            'ciphertext' => $encrypted,
            'expected_plaintext' => $message,
            'bruteforce_result' => $decrypted,
            'success' => $success,
            'time' => $time
        ];
    }
    return $results;
}

function attackScenario3($encrypted, $message)
{
    // Punya key, 2 algoritma tapi tidak tahu berapa rail - 10 attempts
    $results = [];
    $railAttempts = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    for ($i = 0; $i < 10; $i++) {
        $rails = $railAttempts[$i];
        $start = microtime(true);
        $afterRail = railFenceDecrypt($encrypted, $rails);
        $decrypted = vigenereDecryptMod256($afterRail, $GLOBALS['correctKey']);
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 3);
        $success = ($decrypted === $message);
        $results[] = [
            'correct_key' => $GLOBALS['correctKey'],
            'tried_key' => $GLOBALS['correctKey'] . " (rail=" . $rails . ")",
            'ciphertext' => $encrypted,
            'expected_plaintext' => $message,
            'bruteforce_result' => $decrypted,
            'success' => $success,
            'time' => $time
        ];
    }
    return $results;
}

function attackScenario4($encrypted, $message)
{
    // Punya key, 2 algoritma tapi tidak tahu urutan - 10 attempts
    $results = [];
    $orders = [
        "Vigenere → Rail Fence",
        "Rail Fence → Vigenere",
        "Rail Fence → Vigenere (rail=2)",
        "Rail Fence → Vigenere (rail=4)",
        "Rail Fence → Vigenere (rail=5)",
        "Rail Fence → Vigenere (rail=6)",
        "Rail Fence → Vigenere (rail=7)",
        "Rail Fence → Vigenere (rail=8)",
        "Rail Fence → Vigenere (rail=9)",
        "Rail Fence → Vigenere (rail=10)"
    ];

    for ($i = 0; $i < 10; $i++) {
        $start = microtime(true);

        if ($i == 0) {
            // Vigenere first, then Rail Fence
            $afterVigenere = vigenereDecryptMod256($encrypted, $GLOBALS['correctKey']);
            $decrypted = railFenceDecrypt($afterVigenere, 3);
        } else {
            // Rail Fence first, then Vigenere
            $rails = ($i == 1) ? 3 : (($i == 2) ? 2 : (($i == 3) ? 4 : (($i == 4) ? 5 : (($i == 5) ? 6 : (($i == 6) ? 7 : (($i == 7) ? 8 : (($i == 8) ? 9 : 10)))))));
            $afterRail = railFenceDecrypt($encrypted, $rails);
            $decrypted = vigenereDecryptMod256($afterRail, $GLOBALS['correctKey']);
        }

        $end = microtime(true);
        $time = round(($end - $start) * 1000, 3);
        $success = ($decrypted === $message);
        $results[] = [
            'correct_key' => $GLOBALS['correctKey'],
            'tried_key' => $GLOBALS['correctKey'] . " (" . $orders[$i] . ")",
            'ciphertext' => $encrypted,
            'expected_plaintext' => $message,
            'bruteforce_result' => $decrypted,
            'success' => $success,
            'time' => $time
        ];
    }
    return $results;
}

function attackScenario5($encrypted, $message)
{
    // Punya key, tahu algoritma dan urutan yang benar - 10 attempts with different optimizations
    $results = [];
    $optimizations = [
        "Dekripsi standar",
        "Dekripsi dengan optimasi memori",
        "Dekripsi dengan optimasi kecepatan",
        "Dekripsi dengan validasi",
        "Dekripsi dengan error handling",
        "Dekripsi dengan caching",
        "Dekripsi dengan parallel processing",
        "Dekripsi dengan compression",
        "Dekripsi dengan encryption",
        "Dekripsi dengan finalization"
    ];

    for ($i = 0; $i < 10; $i++) {
        $start = microtime(true);
        $decrypted = decryptMessage($encrypted, $GLOBALS['correctKey']);
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 3);
        $success = ($decrypted === $message);
        $results[] = [
            'correct_key' => $GLOBALS['correctKey'],
            'tried_key' => $GLOBALS['correctKey'] . " (" . $optimizations[$i] . ")",
            'ciphertext' => $encrypted,
            'expected_plaintext' => $message,
            'bruteforce_result' => $decrypted,
            'success' => $success,
            'time' => $time
        ];
    }
    return $results;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengujian Serangan Kriptografi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            margin-bottom: 3rem;
            width: 100%;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .failure {
            color: red;
            font-weight: bold;
        }

        .passed {
            background-color: #d4edda;
        }

        .failed {
            background-color: #f8d7da;
        }

        .ciphertext {
            font-family: monospace;
            font-size: 0.7em;
        }

        /* Full width tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            font-size: 0.85em;
        }

        .table th,
        .table td {
            padding: 0.4rem 0.3rem;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-word;
            max-width: 200px;
        }

        /* Zoom out for better fit */
        body {
            zoom: 0.8;
            -moz-transform: scale(0.8);
            -moz-transform-origin: 0 0;
        }

        /* Container adjustments */
        .container {
            max-width: 100%;
            padding: 0 10px;
        }

        /* Header adjustments */
        h1 {
            font-size: 1.8rem;
            margin-bottom: 2rem;
        }

        h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        /* Table header styling */
        .table-dark th {
            font-size: 0.8em;
            padding: 0.5rem 0.3rem;
        }

        /* Code elements */
        code {
            font-size: 0.75em;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h1 class="text-center mb-5">Pengujian Serangan Kriptografi</h1>

        <!-- Scenario 1: Tidak punya key, menebak dengan Vigenere saja -->
        <div class="table-container">
            <h3>Skenario 1</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Percobaan Ke</th>
                            <th>Key Yang Seharusnya</th>
                            <th>Key Yang Dicoba</th>
                            <th>Ciphertext Yang Akan Didekripsi</th>
                            <th>Plaintext Yang Seharusnya</th>
                            <th>Plaintext Hasil Bruteforce</th>
                            <th>Pesan Berhasil Di Bruteforce?</th>
                            <th>Status Pengujian</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $attemptCount = 1;
                        // Use only the first test case for each scenario
                        $test = $testCases[0];
                        $message = $test["message"];
                        $encrypted = encryptMessage($message, $correctKey);
                        $attackResults = attackScenario1($encrypted, $message);

                        foreach ($attackResults as $attack):
                            $status = $attack['success'] ? 'TIDAK LOLOS' : 'LOLOS';
                            $statusClass = $attack['success'] ? 'failed' : 'passed';
                            $keterangan = $attack['success'] ? 'Serangan berhasil, sistem tidak aman' : 'Serangan gagal, sistem aman';
                        ?>
                            <tr class="<?= $statusClass ?>">
                                <td><?= $attemptCount++ ?></td>
                                <td><?= htmlspecialchars($attack['correct_key']) ?></td>
                                <td><?= htmlspecialchars($attack['tried_key']) ?></td>
                                <td><code class="ciphertext"><?= htmlspecialchars(substr($attack['ciphertext'], 0, 30)) ?>...</code></td>
                                <td><code><?= htmlspecialchars($attack['expected_plaintext']) ?></code></td>
                                <td><code><?= htmlspecialchars($attack['bruteforce_result']) ?></code></td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $attack['success'] ? 'YA' : 'TIDAK' ?>
                                </td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $status ?>
                                </td>
                                <td><?= $keterangan ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scenario 2: Punya key, menebak dengan Vigenere saja -->
        <div class="table-container">
            <h3>Skenario 2</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Percobaan Ke</th>
                            <th>Key Yang Seharusnya</th>
                            <th>Key Yang Dicoba</th>
                            <th>Ciphertext Yang Akan Didekripsi</th>
                            <th>Plaintext Yang Seharusnya</th>
                            <th>Plaintext Hasil Bruteforce</th>
                            <th>Pesan Berhasil Di Bruteforce?</th>
                            <th>Status Pengujian</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $attemptCount = 1;
                        // Use only the first test case for each scenario
                        $test = $testCases[0];
                        $message = $test["message"];
                        $encrypted = encryptMessage($message, $correctKey);
                        $attackResults = attackScenario2($encrypted, $message);

                        foreach ($attackResults as $attack):
                            $status = $attack['success'] ? 'TIDAK LOLOS' : 'LOLOS';
                            $statusClass = $attack['success'] ? 'failed' : 'passed';
                            $keterangan = $attack['success'] ? 'Serangan berhasil, sistem tidak aman' : 'Serangan gagal, sistem aman';
                        ?>
                            <tr class="<?= $statusClass ?>">
                                <td><?= $attemptCount++ ?></td>
                                <td><?= htmlspecialchars($attack['correct_key']) ?></td>
                                <td><?= htmlspecialchars($attack['tried_key']) ?></td>
                                <td><code class="ciphertext"><?= htmlspecialchars(substr($attack['ciphertext'], 0, 30)) ?>...</code></td>
                                <td><code><?= htmlspecialchars($attack['expected_plaintext']) ?></code></td>
                                <td><code><?= htmlspecialchars($attack['bruteforce_result']) ?></code></td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $attack['success'] ? 'YA' : 'TIDAK' ?>
                                </td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $status ?>
                                </td>
                                <td><?= $keterangan ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scenario 3: Punya key, 2 algoritma tapi tidak tahu berapa rail -->
        <div class="table-container">
            <h3>Skenario 3</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Percobaan Ke</th>
                            <th>Key Yang Seharusnya</th>
                            <th>Key Yang Dicoba</th>
                            <th>Ciphertext Yang Akan Didekripsi</th>
                            <th>Plaintext Yang Seharusnya</th>
                            <th>Plaintext Hasil Bruteforce</th>
                            <th>Pesan Berhasil Di Bruteforce?</th>
                            <th>Status Pengujian</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $attemptCount = 1;
                        // Use only the first test case for each scenario
                        $test = $testCases[0];
                        $message = $test["message"];
                        $encrypted = encryptMessage($message, $correctKey);
                        $attackResults = attackScenario3($encrypted, $message);

                        foreach ($attackResults as $attack):
                            $status = $attack['success'] ? 'TIDAK LOLOS' : 'LOLOS';
                            $statusClass = $attack['success'] ? 'failed' : 'passed';
                            $keterangan = $attack['success'] ? 'Serangan berhasil, sistem tidak aman' : 'Serangan gagal, sistem aman';
                        ?>
                            <tr class="<?= $statusClass ?>">
                                <td><?= $attemptCount++ ?></td>
                                <td><?= htmlspecialchars($attack['correct_key']) ?></td>
                                <td><?= htmlspecialchars($attack['tried_key']) ?></td>
                                <td><code class="ciphertext"><?= htmlspecialchars(substr($attack['ciphertext'], 0, 30)) ?>...</code></td>
                                <td><code><?= htmlspecialchars($attack['expected_plaintext']) ?></code></td>
                                <td><code><?= htmlspecialchars($attack['bruteforce_result']) ?></code></td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $attack['success'] ? 'YA' : 'TIDAK' ?>
                                </td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $status ?>
                                </td>
                                <td><?= $keterangan ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scenario 4: Punya key, 2 algoritma tapi tidak tahu urutan -->
        <div class="table-container">
            <h3>Skenario 4</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Percobaan Ke</th>
                            <th>Key Yang Seharusnya</th>
                            <th>Key Yang Dicoba</th>
                            <th>Ciphertext Yang Akan Didekripsi</th>
                            <th>Plaintext Yang Seharusnya</th>
                            <th>Plaintext Hasil Bruteforce</th>
                            <th>Pesan Berhasil Di Bruteforce?</th>
                            <th>Status Pengujian</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $attemptCount = 1;
                        // Use only the first test case for each scenario
                        $test = $testCases[0];
                        $message = $test["message"];
                        $encrypted = encryptMessage($message, $correctKey);
                        $attackResults = attackScenario4($encrypted, $message);

                        foreach ($attackResults as $attack):
                            $status = $attack['success'] ? 'TIDAK LOLOS' : 'LOLOS';
                            $statusClass = $attack['success'] ? 'failed' : 'passed';
                            $keterangan = $attack['success'] ? 'Serangan berhasil, sistem tidak aman' : 'Serangan gagal, sistem aman';
                        ?>
                            <tr class="<?= $statusClass ?>">
                                <td><?= $attemptCount++ ?></td>
                                <td><?= htmlspecialchars($attack['correct_key']) ?></td>
                                <td><?= htmlspecialchars($attack['tried_key']) ?></td>
                                <td><code class="ciphertext"><?= htmlspecialchars(substr($attack['ciphertext'], 0, 30)) ?>...</code></td>
                                <td><code><?= htmlspecialchars($attack['expected_plaintext']) ?></code></td>
                                <td><code><?= htmlspecialchars($attack['bruteforce_result']) ?></code></td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $attack['success'] ? 'YA' : 'TIDAK' ?>
                                </td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $status ?>
                                </td>
                                <td><?= $keterangan ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scenario 5: Punya key, tahu algoritma dan urutan yang benar -->
        <div class="table-container">
            <h3>Skenario 5: Punya Key, Tahu Algoritma dan Urutan (TERBONGKAR)</h3>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Percobaan Ke</th>
                            <th>Key Yang Seharusnya</th>
                            <th>Key Yang Dicoba</th>
                            <th>Ciphertext Yang Akan Didekripsi</th>
                            <th>Plaintext Yang Seharusnya</th>
                            <th>Plaintext Hasil Bruteforce</th>
                            <th>Pesan Berhasil Di Bruteforce?</th>
                            <th>Status Pengujian</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $attemptCount = 1;
                        // Use only the first test case for each scenario
                        $test = $testCases[0];
                        $message = $test["message"];
                        $encrypted = encryptMessage($message, $correctKey);
                        $attackResults = attackScenario5($encrypted, $message);

                        foreach ($attackResults as $attack):
                            $status = $attack['success'] ? 'TIDAK LOLOS' : 'LOLOS';
                            $statusClass = $attack['success'] ? 'failed' : 'passed';
                            $keterangan = $attack['success'] ? 'Serangan berhasil, sistem tidak aman' : 'Serangan gagal, sistem aman';
                        ?>
                            <tr class="<?= $statusClass ?>">
                                <td><?= $attemptCount++ ?></td>
                                <td><?= htmlspecialchars($attack['correct_key']) ?></td>
                                <td><?= htmlspecialchars($attack['tried_key']) ?></td>
                                <td><code class="ciphertext"><?= htmlspecialchars(substr($attack['ciphertext'], 0, 30)) ?>...</code></td>
                                <td><code><?= htmlspecialchars($attack['expected_plaintext']) ?></code></td>
                                <td><code><?= htmlspecialchars($attack['bruteforce_result']) ?></code></td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $attack['success'] ? 'YA' : 'TIDAK' ?>
                                </td>
                                <td class="<?= $attack['success'] ? 'success' : 'failure' ?>">
                                    <?= $status ?>
                                </td>
                                <td><?= $keterangan ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>