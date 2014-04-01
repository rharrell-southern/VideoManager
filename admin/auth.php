<?php

ini_set('display_errors', 0);
//ini_set("session.cookie_lifetime", "0"); //an hour

session_start();

if(!$_POST) {
	echo "<pre>";
	print_r($_SESSION);
	echo "</pre>";
}

//DB login details
$DBusername = "root";
$DBpassword = "my_101";
$DBdatabase = "videoScheduler";

//Define link to DB
$link = mysql_connect('localhost', $DBusername, $DBpassword);

function authenticate($user, $pass) {
	//echo($user . ":" . $pass);

	global $DBusername, $DBpassword, $DBdatabase, $auth, $link;

	//Attempt to connect to DB
	if ($link) {
		$db_selected = mysql_select_db($DBdatabase, $link);
		if ($db_selected) {

			$result = mysql_query("SELECT username, password FROM auth WHERE username = '" . $user . "' AND password = '" . $pass . "'");
			$row = mysql_fetch_array($result, MYSQL_NUM);

			if ( sizeof($row) > 1 ) {
				return true;
			} else {
				return false;
			}

		}
	}
}

function openSession() {
	if (!isset($_SESSION['isLoggedIn'])) {
 		$_SESSION['isLoggedIn'] = 1;
 	}

	//echo ("Session: " . $_SESSION['isLoggedIn']);
}

//print db data
function printTable() {

	global $DBdatabase, $auth, $link, $DBusername, $DBpassword;

	//Attempt to connect to DB
	if ($link) {
		$db_selected = mysql_select_db($DBdatabase, $link);
		if ($db_selected) {

			//get table: Video data
			$tableData = array();
			$result = mysql_query("SELECT * FROM Video");
			while($row = mysql_fetch_array($result, MYSQL_NUM)){
				$tableData[] = $row;
			}

			//get table: Sessions data
			$sessTableData = array();
			$result = mysql_query("SELECT * FROM Sessions");
			while($row = mysql_fetch_array($result, MYSQL_NUM)){
				$sessTableData[] = $row;
			}

			//Walk through both arrays, in order to find and connect all sessions to the corresponding videos in the 5th position ($tableData[4]) of $tableData
			$tmpArray = array();
			for($i = 0; $i < sizeof($tableData); $i++){

				for($j = 0; $j < sizeof($sessTableData); $j++){
					if($tableData[$i][0] == $sessTableData[$j][1]){
						$session = array($sessTableData[$j][0], $sessTableData[$j][2], $sessTableData[$j][3]);
						array_push($tmpArray, $session);
					}

				}

				//at the end of $j cycle, if sessions matching the ID of cylce $i push array of sessions onto the end, else push null
				if (sizeof($tmpArray != 0)){
						array_push($tableData[$i], $tmpArray);
						$tmpArray = array();
				} else {
					array_push($tableData[$i], "null");
				}
			}

 			echo json_encode($tableData);

			//Initial empty table structure, will populate/update via js functions		
		}
	}

	mysql_close($link);
}

if ($_SESSION['isLoggedIn'] == 1) {
	
	printTable();

} else if ($_POST) {

	if( authenticate($_POST['username'], $_POST['password']) ){
		openSession();
		printTable();
	}else {
	echo "{}";
	}

} else {
	echo "{}";
}
?>