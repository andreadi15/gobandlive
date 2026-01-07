<?php
/**
 * FILE: auth/logout.php
 * FUNGSI: Proses logout dan hapus session
 */

require_once '../config/config.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke landing page
setAlert('success', 'Anda telah berhasil logout');
redirect('index.php');
?>