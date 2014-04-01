<!DOCTYPE HTML>
<html>
<head>
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