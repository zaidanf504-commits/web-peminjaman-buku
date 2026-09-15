<?php
session_start();
session_unset();    // Menghapus semua variabel session
session_destroy();  // Menghancurkan session

// Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>