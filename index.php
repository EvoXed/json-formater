<?php
session_start();

function fPrint ($array) {
    echo '<pre>', var_dump($array), '</pre>';
}

function formatStr($str)
{
    if (preg_match('/[A-Za-zА-Яа-я]+/', $str)) {
        return trim($str);
    }
    $dotPos = strrpos($str, '.');
    $commaPos = strrpos($str, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
  
    if (!$sep) {
        return intval(preg_replace("/[^0-9]/", "", $str));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($str, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($str, $sep + 1, strlen($str)))
    );
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $file = file_get_contents($_FILES['jfile']['tmp_name']);
    $arr = [];
    if ($_FILES['jfile']['type'] == 'application/json') {
        $arr = json_decode($file, true);
    }
    if ($_FILES['jfile']['type'] == 'application/vnd.ms-excel') {
        $arr = array_map(fn($v) => str_getcsv($v, ';'), str_getcsv($file, "\n"));
        array_walk($arr[0], function (&$k) { $k = trim($k); });
        array_walk($arr, function(&$a) use ($arr) {
            $a = array_combine($arr[0], $a);
        });
        array_shift($arr);
    }
    array_walk_recursive($arr, function(&$v) {
        $v = formatStr($v);
    });
    $pjson = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $_SESSION['json'] = $pjson;
    $_SESSION['filename'] = substr($_FILES['jfile']['name'], 0, strrpos($_FILES['jfile']['name'], '.', -1)) . '.json';
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['d'])) {
    header("Cache-Control: private");
    header("Content-Description: File Transfer");
    header("Content-Type: application/json");
    header("Content-Length: " . strlen($_SESSION['json']));
    header("Content-Disposition: attachment; filename=" . $_SESSION['filename']);
    echo($_SESSION['json']);
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON Formater</title>
</head>
<body>
<style>
    body {
        margin: 0;
        padding: 0;
        width: 100%;
    }
    .container {
        padding: 30px 10px;
    }
    .wrapper {
        display: flex;
    }
    .col {
        width: 50%;
    }
</style>
<div class="container">
    <form action="" method="post" enctype="multipart/form-data">
        <label for="jfile">JSON or CSV File:</label>
        <input type="file" name="jfile" id="jfile">
        <button type="submit">Format</button>
        <button>
            <a href="./index.php?d=<?= $_SESSION['filename'] ?>" download>Download</a>
        </button>
    </form>

    <?php if (isset($pjson) && $file) : ?>
        <div class="wrapper">
            <div class="col">
                <?php fPrint($file) ?>
            </div>
            <div class="col">
                <pre><?= $pjson ?></pre>
            </div>
        </div>

    <?php endif ?>
</div>
<script>
    const fileInput = document.querySelector('input');
    const divOutput = document.querySelector('.col');
    fileInput.addEventListener('change', function () {
        console.log(this.files[0]);
    });
</script>
</body>
</html>
<?php } ?>