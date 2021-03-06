<?php
	
	// Getting POST data, performing some security checks
	$postdata = file_get_contents("php://input");
	$request = json_decode($postdata);
	$email = $request->user;
	$email2 = addslashes($request->user);
	if ($email != $email2) die("Invalid email");
	$log_author = $request->log_author;
	$log_author2 = addslashes($request->log_author);
	if ($log_author != $log_author2) die("Invalid author name");
	$pass = $request->password;
	$password2 = addslashes($request->password);
	if ($pass != $password2) die("Invalid password");
	$name = $request->name;
	$name2 = addslashes($request->name);
	if ($name != $name2) die("Invalid name");
	$version = $request->version;
	$version2 = addslashes($request->version);
	if ($version != $version2) die("Invalid version value");
	$author = $request->author;
	$author2 = addslashes($request->author);
	if ($author != $author2) die("Invalid author value");
	$url = $request->url;
	$url2 = addslashes($request->url);
	if ($url != $url2) die("Invalid url");
	$day = $request->date;
	$day2 = addslashes($request->date);
	if ($day != $day2) die("Invalid date");
	$type = 9;
	$description = $request->description;
	$long_description = $request->long_description;
	$sshot = $request->sshot;
	if (strlen($sshot) < 5) $sshot = "";
	$source = $request->source;
	$release_page = $request->release_page;
	$icon = "";
	$url3 = "";
	$titleid = "";
	
	// Creating connection
	include 'config.php';
	$con = mysqli_connect($servername, $username, $password, $dbname);
	
	// Checking connection
	if (mysqli_connect_errno()){
		die("Connection failed: " . mysqli_connect_error());
	}
	
	// Checking CSRF token
	include 'xsrf.php';
	$xsrf = $_COOKIE['XSRF-TOKEN'];
	$hdr_xsrf = $_SERVER['HTTP_X_XSRF_TOKEN'];
	if ((strcmp($xsrf,$hdr_xsrf) != 0) or (!checkXSRF($con, $xsrf))){
		mysqli_close($con);
		die("Unauthorized access.");
	}
	
	$sth = mysqli_prepare($con,"SELECT roles FROM vitadb_users WHERE email=? AND password=?");
	mysqli_stmt_bind_param($sth, "ss", $email, $pass);
	mysqli_stmt_execute($sth);
	$data = mysqli_stmt_get_result($sth);
	
	if (mysqli_num_rows($data)>0){
		while($r = mysqli_fetch_assoc($data)) {
			$roles = explode(";",$r['roles']);	
		}
		mysqli_stmt_close($sth);
		if ((strcmp($roles[0],"1") == 0) or (strcmp($roles[0],"2") == 0)){
			$sth2 = mysqli_prepare($con,"INSERT INTO vitadb (name, icon, version, author, url, type, description, data, date, titleid, long_description, screenshots, source, release_page) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			mysqli_stmt_bind_param($sth2, "sssssissssssss", $name, $icon, $version, $author, $url, $type, $description, $url3, $day, $titleid, $long_description, $sshot, $source, $release_page);
			mysqli_stmt_execute($sth2);
			mysqli_stmt_close($sth2);
			$sth3 = mysqli_prepare($con,"INSERT INTO vitadb_log(author,object,hb,date) VALUES(?,?,?,?)");
			$obj = "added";
			$date = date('Y-m-d H:i:s');
			mysqli_stmt_bind_param($sth3, "ssss", $log_author, $obj, $name, $date);
			mysqli_stmt_execute($sth3);
			mysqli_stmt_close($sth3);
			$sth4 = mysqli_query($con,"SELECT MAX(id) AS id FROM vitadb");
			$row = mysqli_fetch_array($sth4);
			$hb_id = $row['id'];
			require_once ('codebird.php');
			\Codebird\Codebird::setConsumerKey('', '');
			$cb = \Codebird\Codebird::getInstance();
			$cb->setToken('', '');
			if (strlen($sshot) > 5){
				$screenshots = explode(';', $sshot);
				$cb->setRemoteDownloadTimeout(10000);
				foreach ($screenshots as $screenshot) {
					$sshot_url = "https://vitadb.rinnegatamante.it/" . $screenshot;
					$reply = $cb->media_upload(array(
						'media' => $sshot_url
					));
					$media_ids[] = $reply->media_id_string;
				}
				$media_ids = implode(',', $media_ids);
				$tweet_text = "$name $version by $author can now be downloaded from VitaDB or VHBB! More info is available here: https://vitadb.rinnegatamante.it/#/info/$hb_id";
				$reply = $cb->statuses_update([
					'status' => $tweet_text,
					'media_ids' => $media_ids
				]);
				print_r($reply);
			} else {
				$tweet_text = "$name $version by $author can now be downloaded from VitaDB or VHBB! More info is available here: https://vitadb.rinnegatamante.it/#/info/$hb_id";
				$reply = $cb->statuses_update([
					'status' => $tweet_text
				]);
				print_r($reply);
			}
		}
	} else {		
		mysqli_stmt_close($sth);
		echo("An error occurred: " . mysqli_error($con));
	}

	mysqli_close($con);

?>