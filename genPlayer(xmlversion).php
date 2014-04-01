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
	global $displayVideos, $videoList, $enabled, $hasVideo;

	//Define link to DB
	$link = mysql_connect('localhost', $username, $password);
	@mysql_select_db($database) or die( "Unable to select database");

	//Attempt to connect to DB
	if ($link) {
		$db_selected = mysql_select_db($database, $link);
		if ($db_selected) {
		    
		}else{
			die ('Can\'t connect to'. $database . ': ' . mysql_error());
		}

	}else{
    	die('Unable to connect: ' . mysql_error());
	}
	

	// Check for XML data file
	if (file_exists('videos.xml')) {
		
		// Load XML data
		$videos = simplexml_load_file('videos.xml');
		$videoCount = 0;
		
		// Check through data for course videos
		foreach ($videos->video as $video) {
			if($video->course == $thisCourse) {
				$hasVideo = true;
				$filepath = $video->path;
				$title = $video->title;
				
				// Check current time against listed sessions
				foreach($video->sessions->session as $session) {
					
					// Convert text formatted time into unix time
					$starttime = date_create_from_format('Y-m-d H:i:sT', $session->starttime );
					$starttime = $starttime->format('U');
					$endtime = date_create_from_format('Y-m-d H:i:sT', $session->endtime );
					$endtime = $endtime->format('U');
					$now = getdate();
					
					// Debug variables
					if (DEBUG) {
						echo 'Checking session times: </br><pre>';
						echo 'Start time : '.$starttime;
						echo '</pre><pre>';
						echo 'Now        : '.$now[0];
						echo '</pre><pre>';
						echo 'End time   : '.$endtime;
						echo '</pre>';
					}
					
					if ($starttime < $now[0] && $endtime > $now[0]) {
						
						// Enable the player
						$enabled = true;
						
						// Add video to playlist
						$displayVideos[$videoCount]	= "{
										0:{src:'$filepath', type:'video/mp4'},
										config: {
											 title: '$title',
											 className: 'postad',
										}
										}";
						$videoList[$videoCount]['title'] = ($videoCount + 1).'. '.$title;
						$videoList[$videoCount]['link'] = 'javascript:projekktor(\'player_a\').setActiveItem('.($videoCount + 1).')';
						$videoCount++;
						
						if(DEBUG) {
							echo 'Session is Enabled<br />';
							echo '<pre>';
							print_r($displayVideos);
							print_r($videoList);
							echo '</pre>';
						}
					}
				}
			}
		}
		
	} else {
		// Fail gracefully
		echo 'Failed to open video occourances.';
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
        <form action="<?=$PHP_SELF?>" method="post" >
        <input type="password" name="pass" />
        <input type="submit" value="go" />
        </form>
    </div>
	<?php
}

// Display full debug information
if (DEBUG > 4) {
	echo '<pre>';
	$arr = get_defined_vars();
	print_r($arr);
	echo '</pre>';
}
?>