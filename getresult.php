<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function cleanvar($n)
{
	if (!isset($_GET[$n])) return "";
	return preg_replace('/[^a-zA-Z0-9]/','',$_GET[$n]);
}

$job =cleanvar("job");
$ukey =cleanvar("key");
$auto =cleanvar("auto");
$retried=cleanvar("retried");

if ($retried=="") $retried=1;
else $retried=$retried+1;

if (!file_exists("spool/".$job."/key"))
{
	header("HTTP/1.1 403 Forbidden");
	exit("Invalid job id or key.");
}

$dkey=trim(file_get_contents("spool/".$job."/key"));

if ($ukey!=$dkey)
{
	header("HTTP/1.1 403 Forbidden");
	exit("Invalid job id or key.");
}

if (file_exists("spool/".$job."/finished"))
{
	//header('Content-type: text/plain');
	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename="ttylog.txt"');
	readfile("spool/".$job."/ttyout");
	exit();
}

$currenturl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
$newlink=$currenturl."?job=$job&key=$ukey";
	
if ($auto=="yes")
{
	sleep(3);
	$newlink="$newlink&auto=$auto&retried=$retried";
	header( "Location: $newlink" ) ;
	echo "You should be automatically redirected to <a href='$newlink'>$newlink</a>";
	exit();
}



?>
<html>
<meta http-equiv="refresh" content="2;url=<?php echo "$newlink"; ?>">
<body>
<?php
	if (!file_exists("spool/".$job."/ttyout"))
	{
		echo "Job queued.";
		if (file_exists("spool/".$job."/inqueue"))
		{
			$inqueue=trim(file_get_contents("spool/".$job."/inqueue"));
			echo "<br>$inqueue other jobs in queue before this one.";
		}
	} else {
		echo "Job started.";
	}
?>
</body>
</html>
