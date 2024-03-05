<?php
	function random($length = 10) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
	function runQuery($query){
        global $con;
        $result=$con->query($query);
        if(!$result){
            echo $query."<br>";
            echo $con->error."<br>";
            exit();
        }
    }

    function getAll($con,$sql){
        runQuery($sql);
    	$result = $con->query($sql);
        $list = array();
        while ($row = mysqli_fetch_assoc($result)){
            $list[] = $row;
        }
        return $list;
    }
    function getRow($con,$sql){
    	runQuery($sql);
    	if ($result = $con->query($sql)) {
            $row = mysqli_fetch_assoc($result);
            return $row;
        } else {
            return false;
        }
    }
?>