<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
    if (isset($_GET['email'])) {
        $id = $_GET['email'];
        
        $mySql = "DELETE FROM users WHERE email='$id'";
        $myQry = mysqli_query($koneksidb, $mySql);

        if ($myQry) {
            echo "<script type='text/javascript'>
                alert('Berhasil menghapus pengguna.'); 
                document.location = 'reg-users.php';
            </script>";
        } else {
            $errorMsg = "Error deleting user: " . mysqli_error($koneksidb);
            echo "<script type='text/javascript'>
                alert('Terjadi kesalahan, silahkan coba lagi!. Error: $errorMsg'); 
                document.location = 'reg-users.php'; 
            </script>";
        }
    } else {
        echo "<script type='text/javascript'>
            alert('Terjadi kesalahan, silahkan coba lagi!.'); 
            document.location = 'reg-users.php'; 
        </script>";
    }
}
?>
