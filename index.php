<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />

    <title>Online Learning - Video Player</title>
    <!-- Load page style -->
    <link rel="stylesheet" href="style.css" type="text/css" media="screen" />
    
    <!-- Load player theme -->
    <link rel="stylesheet" href="/projekktor/theme/style.css" type="text/css" media="screen" />

    <!-- Load jquery -->
    <script type="text/javascript" src="/projekktor/jquery-1.7.2.min.js"></script>

    <!-- load projekktor -->
    <script type="text/javascript" src="/projekktor/projekktor-1.2.22r204.min.js"></script>
</head>

<body>
<div id="container">
<?php
if ($_POST) {
?>
<div id="player"></div>
<script>
$.ajax({
  type: "POST",
  url: "genPlayer.php",
  cache: false,
  data: { course: '<?=$_POST['pass']?>' }
}).done(function( html ) {
  $("#player").append(html);
});
</script>
<?php
} else {
?>
<div id="login">
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
</div>
</body>
</html>