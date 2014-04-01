<?php 
// Set debug mode
define('DEBUG', false);

// Display errors on debug mode
if (DEBUG) {
	ini_set('display_errors', 1);
}

// Set config variables
$poster = 'videos/intro.png';
$height = '450';
$width = '800';

//Initialize global variables
$hasVideo = false;
$enabled = false;
$displayVideos = '';
$videoList = '';

//DB login details
$username = "root";
$password = "my_101";
$database = "videoScheduler";

// Set up functions
function getVideoData($thisCourse) {
	
	// Set global variables
	global $displayVideos, $videoList, $enabled, $hasVideo, $username, $password, $database;

	//Define link to DB
	$link = mysql_connect('localhost', $username, $password);

	//Define videocount
	$videoCount = 0;

	//Attempt to connect to DB
	if ($link) {
		$db_selected = mysql_select_db($database, $link);
		if ($db_selected) {

			$videoData = array();
			$today = date("Y-m-d H:i:s"); 

			//Select rows
			$videos = mysql_query("SELECT ID, course, title, path FROM Video WHERE course = '" . $thisCourse . "'");
			while($videoRow = mysql_fetch_array($videos, MYSQL_NUM)){
				$videoData[] = $videoRow;
			}

			//print row data from result
			//echo("<pre>");
			//print_r($videoData);
			//("</pre>");

			for($i = 0; $i < sizeof($videoData); $i++){
				//Fetch row data from result

				$sessData = array();

				$sessions = mysql_query("SELECT ID, videoID, startTime, endTime FROM Sessions WHERE videoID = '" . $videoData[$i][0] . "'");
				while($sessRow = mysql_fetch_array($sessions, MYSQL_NUM)){
					$sessData[] = $sessRow;
				}

				//print row data from result
				//echo("<pre>");
				//print_r($sessData);
				//echo("</pre>");

				//Check if current date is within a range in $result
				for($j = 0; $j < sizeof($sessData); $j++){
					if($today >= $sessData[$j][2] && $today <= $sessData[$j][3]){
						//echo("Date  (" . $today . ") in range: " . $sessData[$j][2] . " - " . $sessData[$j][3] . "<br />");
						
						// Add video to playlist
						$displayVideos[$videoCount]	= "{
								0:{src:'".$videoData[$i][3]."', type:'video/mp4'},
								config: {
									 title: '".$videoData[$i][2]."',
									 className: 'postad',
								}
								}";
						$videoList[$videoCount]['title'] = ($videoCount + 1).'. '.$videoData[$i][2];
						$videoList[$videoCount]['link'] = 'javascript:projekktor(\'player_a\').setActiveItem('.($videoCount + 1).')';
						$videoCount++;

					}else{
						//echo("Date (" . $today . ") not in range: " . $sessData[$j][2] . " - " . $sessData[$j][3] . "<br />");
						$hasVideo = true;
					}
				}
			}

			//Close connection
		    mysql_close($link);

		}else{ //Else unsuccessful connection
			echo('Could not connect to '. $database . ': ' . mysql_error());

			//Close connection
		    mysql_close($link);
		}

	}else{
    	echo('Could not connect: ' . mysql_error());
	}

	if(	$videoCount > 0){		
		// Enable the player
		$enabled = true;
	}
}

//Fetch POST data
if ($_POST['course']) {
	getVideoData($_POST['course']);
}

// Generate player
if ($enabled) {
?>
	<video id="player_a" class="projekktor" poster="<?php echo $poster; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" controls>
	</video>
	<script type="text/javascript">
	$(document).ready(function() {
	  projekktor('#player_a', {
					/* path to the MP4 Flash-player fallback component */
					playerFlashMP4:		'/projekktor/jarisplayer.swf',
			
					/* path to the MP3 Flash-player fallback component */
					playerFlashMP3:		'/projekktor/jarisplayer.swf',
					
					playlist: [
						{
						0:{src:'videos/intro.mp4', type:"video/mp4"},
						config: {
							title: 'Online Campus',
							className: 'intro',
							disallowSkip: true,
							plugin_controlbar: {
							showOnStart: true,
							disableFade: true
							},
							plugin_display: {
							staticControls: false,
							displayPlayingClick: function() {
								alert("Display Clicked, tracked that!")
							}
							}            
						}
						},
						<?php
						foreach($displayVideos as $displayVideo) {
							echo ','.$displayVideo;
						}
						?>
						
					]              
				});
	});
	</script>
	<?php if(count($videoList) > 1) { ?>
		<div id="playlist">
            <h2>Available Videos</h2>
            <div id="items">
            	<ul>
				<?php
                foreach($videoList as $listItem) {
                    echo '<li><a href="'.$listItem['link'].'">'.$listItem['title'].'</a></li>';
                }
                ?>
                </ul>
            </div>
		</div>
		<?php
	}
	?>
    <div id="copyright">
        <h4>Copyright Compliance Statement</h4>
        <p>This video is available for time-limited access in compliance with the copyright 
        procedures and policies of Southern Adventist University.  This display falls within educational fair use 
        (17 USC § 107) and/or the TEACH Act (H.R.2215).</p>
        
        <h4>Fair Use</h4>
        <a href="http://www.law.cornell.edu/uscode/text/17/107?quicktabs_8=1#quicktabs-8" target="_blank">http://www.law.cornell.edu/uscode/text/17/107?quicktabs_8=1#quicktabs-8</a>
        
        <h4>TEACH Act</h4>
        <a href="http://www.copyright.com/media/pdfs/CR-Teach-Act.pdf" target="_blank">http://www.copyright.com/media/pdfs/CR-Teach-Act.pdf</a><br>
        <a href="http://beta.congress.gov/bill/107th-congress/senate-bill/487?q=S.%20487%20\\\(107\\\)" target="_blank">http://beta.congress.gov/bill/107th-congress/senate-bill/487?q=S.%20487%20\\\(107\\\)</a>
    </div>
    <?php
} else if($hasVideo) {
	// Warn for expired Videos
	?>
    <div id="login">
        <h2>Time Limit Expired</h2>
        <p>Please return during the time periods scheduled for viewing this video by your professor.</p>
    </div>
	<?php	
} else {
	// Warn if no videos are currently scheduled for a course
	?>
    <div id="login">
        <h2>No Video Scheduled</h2>
        <p>There is currently no video scheduled for your course.  If you believe this is an error, double check you have entered the access information correctly.  If there is still an error, please contact your instructor.</p>
        <p><strong>Course:</strong> <?=$_POST['course']?></p>
        <h2>
        Please enter the password to access the video resource.
        </h2>
        <form action="" method="post" >
        <input type="password" name="pass" />
        <input type="submit" value="go" />
        </form>
    </div>
	<?php
}
?>