<?php include('hashcash_server.php'); ?>
<html>
	<head>
	<title>hashcash test form</title>
	<script type="text/javascript" src="hashcash_client.js"></script>
	<script>
		setTimeout(hc_findHash, 2000); // start PoW after a few seconds
	</script>
	</head>
	<body>
		<span style="color:red"><?= $formError; ?></span><br/>
		<form id="stampform" action="form.php" method="post">
			<?php hc_CreateStamp(); ?>
	        <input type="text" id="name" name="name" placeholder="Your name..." value="<?=$_POST["name"]?>"><span style="color:red"><?= $nameError; ?></span><br/><br/>
	        <input type="text" id="email" name="email" placeholder="Your email address..." value="<?=$_POST["email"]?>"><span style="color:red"><?= $emailError; ?></span><br/><br/>
	        <input type="text" id="subject" name="subject" placeholder="Subject..." value="<?=$_POST["subject"]?>"><span style="color:red"><?= $subjectError; ?></span><br/><br/>
	        <textarea id="emailBody" name="emailBody" placeholder="Write your message here." style="height:200px"><?=$_POST["emailBody"]?></textarea><span style="color:red"><?= $messageError; ?></span><br/><br/>
			<input type="submit" name="submit" value="Submit" id="submitbutton" disabled>
			<span id="countdown" style="color:red"></span>
		</form>
	</body>
</html>