<?php 

/*********************DISCLAIMER***************************
Only use this script for importing new courses. 
It does not take into account existing course/video entries.
************************************************************/



	ini_set('display_errors', 1);


	

	function db() {	

		$xml = simplexml_load_file('videos.xml');

		//DB login details
		$DBusername = "root";
		$DBpassword = "my_101";
		$DBdatabase = "videoScheduler";
		$link = mysql_connect('localhost', $DBusername, $DBpassword);

		//Attempt to connect to DB
		if ($link) {
			$db_selected = mysql_select_db($DBdatabase, $link);

			if ($db_selected) {

				for($i = 0; $i < sizeof($xml); $i++){
					//get vars
					$course = $xml->video[$i]->course;
					$title = $xml->video[$i]->title;
					$path = $xml->video[$i]->path;

					//insert
					$query = mysql_query("INSERT INTO Video (course, title, path) VALUES ('$course', '$title', '$path')");

					//if successful insert, get new ID (don't have to link to existing course because these are all new courses)
					if($query){
					$queryMax = mysql_query("SELECT MAX(ID) FROM Video");

						if($queryMax){
							$row = mysql_fetch_array($queryMax, MYSQL_NUM);
							$id = $row[0];


							echo("sucessfully entered $course with an id of $id<br /><br />");

							foreach ($xml->video[0]->sessions->children() as $sess){

								//get vars
								$start = $sess->starttime;
								$end = $sess->endtime;

								//have id, now insert sessions
								$query2 = mysql_query("INSERT INTO Sessions (videoID, startTime, endTime) VALUES ('$id', '$start', '$end')");

								echo("sucessfully entered session: $start - $end <br /><br />");
							}
						}
					}

					echo("<br /><br /><br />");
				}
			}

			//close link
			mysql_close($link);
		}		
	}
	// echo "<pre>";
	// print_r($xml);
	// echo "</pre>";
	
	db();

?>