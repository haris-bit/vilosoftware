<?
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 100);
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 100);
ini_set('session.save_path', '/tmp');

error_reporting(0);
ini_set('display_errors', 0);

session_start();

include("./_db.php");
include_once("../includes/core/dbmodel.php");
$host='localhost';
$username='anomozco_octcric';
$user_pass='rWg#M$vFYk]+';
$database_in_use='anomozco_octcric';

$con = mysqli_connect($host,$username,$user_pass,$database_in_use);
if (!$con)
    echo"not connected";
if (!mysqli_select_db($con,$database_in_use))
    echo"database not selected";

$calendarStartDate=$_SESSION['calendarStartDate'];
include_once("../roles.php");
if (isset($_SESSION['email']) && isset($_SESSION['password'])) {
    $session_password = $_SESSION['password'];
    $session_email =  $_SESSION['email'];
    $query = "SELECT *  FROM darlelJobber_users WHERE email='$session_email' AND password='$session_password'";
    $result = $con->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logged = 1;
            // var_dump($logged);
            $session_role = $row['role'];
            $session_id = $row['id'];
            $session_userId = $row['id'];
            $session_password = $row['password'];
            $session_name = $row['name'];
            $session_email = $row['email'];
            $session_phone = $row['phone'];
            $session_data = $row;
            $permission['edit_everyone_schedule']=0;
            $permission=$permissions[$session_role];
        }
    } else {
        $logged = 0;
    }
} else {
    $logged = 0;
}

$session_userId_filter = $session_userId;
if ($session_role == "admin") {
	$session_userId_filter = "";
}
if(isset($_SESSION['clientId'])){
    $id=$_SESSION['clientId'];
    $query="select * from darlelJobber_users where id='$id'";
    $result=$con->query($query);
    while($row=$result->fetch_assoc()){
        $logged=1;
        $session_id = $row['id'];
        $session_password = $row['password'];
        $session_name = $row['name'];
        $session_email = $row['email'];
        $session_role = $row['role'];
        $session_data = $row;
    }
}
?>