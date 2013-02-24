<html><head><title>Remote experiment</title>
<style type="text/css">
body { font-family:sans; }
</style>
</head>
<body>
<form action="dorun.php" method="POST" enctype="multipart/form-data">
<div style="width:160px; display:inline-block">Login: </div><input type="text" name="loginid"><br>
<div style="width:160px; display:inline-block">Password: </div><input type="password" name="passw"><br><br>

<div style="width:160px; display:inline-block">Board type: </div><select name="boardtype">
<option value="genesysv5">Genesys Virtex-5</option>
</select><br>

<div style="width:160px; display:inline-block">TTY Baud Rate: </div><select name="baudrate">
<option value="9600">9600</option>
<option value="14400">14400</option>
<option value="19200">19200</option>
<option value="28800">28800</option>
<option value="38400">38400</option>
<option value="57600">57600</option>
<option value="115200">115200</option>
<option value="230400">230400</option>
<option value="460800">460800</option>
<option value="921600">921600</option>
<option value="1000000">1000000</option>
<option value="2000000">2000000</option>
</select><br><br> 

<div style="width:160px; display:inline-block">Bit-file:</div><input type="file" name="file" id="file"><br><br>
<input type="submit" value="Submit">
</form>
</body>
</html>
