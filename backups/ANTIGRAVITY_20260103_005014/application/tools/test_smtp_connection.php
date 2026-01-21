<?php
$hosts = ['localhost', '127.0.0.1'];
$ports = [25, 2525, 587, 465, 1025];

echo "<h1>Test Connexions SMTP</h1>";

foreach ($hosts as $host) {
    foreach ($ports as $port) {
        echo "Test $host:$port ... ";
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($fp) {
            echo "<strong style='color:green'>OUVERT (Succès)</strong><br>";
            fclose($fp);
        } else {
            echo "<span style='color:red'>Fermé ($errstr - $errno)</span><br>";
        }
    }
}
