<?php
if(isset($_POST["rm"])){
	header('Content-Type: application/json');
	if(is_dir($PATH)&&!is_link($PATH))
		echo '{"success":'.(rmdir($PATH)===true?'true':'false').'}';
	else
		echo '{"success":'.(unlink($PATH)===true?'true':'false').'}';
	exit;
}
elseif(isset($_POST["content"])){
	header('Content-Type: application/json');
	$content=$_POST["content"];
	file_put_contents($PATH,$content);
	echo '{"msg":"Your changes have been saved."}';
	exit;
}
elseif(isset($_POST["new"])){
	header('Content-Type: application/json');
	$type=$_POST["type"];
	if(file_exists($PATH))
		//echo '{"success":false,"message":""}';
		echo json_encode(array("success"=>false,"message"=>"File already exists."));
	else{
		if($type==='dir'){
			$r=mkdir($PATH);
		}
		elseif($type==='file'){
			$r=file_put_contents($PATH,'');
		}
		//echo '{"success":'.($r!==false?'true':'false').'}';
		echo json_encode(array("success"=>$r!==false,"message"=>"Error writing ".$PATH));
	}
	exit;
}
elseif(isset($_FILES['file'])){
	$dest=$PATH.'/'.$_FILES['file']['name'];
	$tempFile=$_FILES['file']['tmp_name'];
	header('Content-Type: application/json');
	echo json_encode(array("success"=>move_uploaded_file($_FILES['file']['tmp_name'],$dest)===true));
	exit;
}
?>