<html><head><title>Remote experiment</title></head>
<body>
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function startsWith($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}

function randomspooldir()
{
	for ($i=0; $i<500; $i++)
	{
		$nr=1000000+rand(1,32000);
		$success=mkdir("spool/$nr");
		if ($success) break;
	}
	if (!$success) exit("Server error, cannot create spool directory"); 
	return "spool/$nr";
}

function cleanupexit($spooldir,$msg)
{
	if (startsWith($spooldir,"spool/"))
	{
		$files = glob($spooldir.'/*', GLOB_MARK);
		foreach ($files as $file) {
			unlink($file);
			//echo "Cleanup file $file<br>";
		}
		rmdir($spooldir);
		//echo "Cleanup dir $spooldir<br>";
	}
	exit($msg);
}

function cleanvar($n)
{
	return preg_replace('/[^a-zA-Z0-9]/','',$_POST[$n]);
}

	$uloginid  =cleanvar("loginid");
	$upassw    =$_POST["passw"];
	$uboardtype=cleanvar("boardtype");
	$ubaudrate =cleanvar("baudrate");
	$loginok   =False;
	$passfile=file("admin/users.txt");
	foreach($passfile as $ul)
	{
		$un=explode(" ",$ul,2);
		$xpas=trim($un[1]);
		//echo "..$un[0]..$xpas..<br>";
		if ($un[0]==$uloginid && $xpas==$upassw)
			$loginok=True;
	}
	
	if (!$loginok)
		exit("Incorrect login $uloginid $upassw");
	
	if ($_FILES["file"]["error"] > 0)
	{
		exit("File upload failed.");
	}
	$filesize=$_FILES["file"]["size"]/1024;
	
	switch($uboardtype)
	{
	case "genesysv5":
		if ($filesize<400 || $filesize>1000)
			exit("File size: $filesize not matching this board type");
		break;
	default:
		exit("Unknown board type");
	}
	$spooldir=randomspooldir();
	$res=rename($_FILES["file"]["tmp_name"],"$spooldir/bit.bit");
	if (!$res) cleanupexit($spooldir,"Failed to move bit-file");
	
	$num=explode("/",$spooldir,2)[1];
	$key=bin2hex(openssl_random_pseudo_bytes(10));
	$info="USERID=$uloginid\n";
	$info.="BOARDTYPE=$uboardtype\n";
	$info.="BAUDRATE=$ubaudrate\n";
	file_put_contents("$spooldir/info",$info);
	file_put_contents("$spooldir/key",$key);
	$currenturl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	$newlink=str_replace("/dorun.php","/getresult.php?job=$num&key=$key",$currenturl);
	header( "Location: $newlink" ) ;
	echo "You should be automatically redirected to <a href='$newlink'>$newlink</a>";
?>
</body></html>
