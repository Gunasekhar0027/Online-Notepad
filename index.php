<?php
$DIRS_AT_TOP=TRUE;
$DEFAULT_DIR='files/';
?>
<?php
require('util.php');
$PATH=isset($_REQUEST["p"])?$_REQUEST["p"]:'';
if($PATH==='' && $_SERVER['REQUEST_METHOD']==='GET'){if(!$DEFAULT_DIR)$DEFAULT_DIR=realpath('.');header('Location: ?p='.urlencodelite($DEFAULT_DIR));exit;}

header('Cache-Control: no-store');
require('commands.php');
$Recursive=FALSE;
if(isset($_REQUEST["r"]))$Recursive=TRUE;
$Grep="";
if(isset($_REQUEST["grep"]))$Grep=$_REQUEST["grep"];
$Find="";
if(isset($_REQUEST["find"]))$Find=$_REQUEST["find"];
$Locate="";
if(isset($_REQUEST["locate"]))$Locate=$_REQUEST["locate"];
$Title=substr($PATH,strrpos(str_replace('\\','/',$PATH),'/')+1);
if($Title=='')$Title='Code Editor';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, maximum-scale=1.0, minimum-scale=1.0, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Gunasekhar</title>
	<link rel="stylesheet" href="editor.css">
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
	<script src="//cdn.jsdelivr.net/ace/1.2.3/min/ace.js"></script>
	<script src="//cdn.jsdelivr.net/garlic.js/1.2.2/garlic.min.js"></script>
	<script src="//cdn.jsdelivr.net/dropzone/4.3.0/dropzone.min.js"></script>
</head>
<body>
<header class="list">
	<nav>
		<a href="javascript:void(0)" class="newButton">New</a>
		<a href="javascript:void(0)" class="uploadButton">Upload</a> 
		<a href="http://tutorialgo.in/">Back to Website</a>
	</nav>
</header>
<?php
	if (is_dir($PATH))
		echo renderFileList();
	elseif(file_exists($PATH))
		echo renderEditor();
	else
		die('Path does not exist: '.$PATH);
	
	function renderEditor()
	{
		global $PATH;
		$html="<header class=editor><nav>";
		$html.="&nbsp;&nbsp;<button onclick=editor.save(false);return(false);>Save</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$html.="<button onclick=editor.save(true);return(false);>Save &amp; Close</button>";
		$html.="</nav></header>";
		$c=file_get_contents($PATH);
		if(substr($c,0,3)==pack("CCC",0xef,0xbb,0xbf)) // Remove BOM
			$c=substr($c,3);
		$html.="<div id=editor ".(is_writable($PATH)?"":"data-readonly").">" . htmlentities($c,ENT_SUBSTITUTE) . "</div>";
		return $html;
	}
	function getFiles(){
		global $PATH,$Recursive,$Locate,$DIRS_AT_TOP;
		if($Locate)
			$r=getFilesUsingLocate($PATH,$Locate);
		elseif($Recursive)
			$r=scandir_recursive($PATH);
		else
			$r=scandir($PATH);
		sort($r,SORT_STRING|SORT_FLAG_CASE);
		
		if($DIRS_AT_TOP && !$Locate && !$Recursive){
			$files=array();
			$dirs=array();
			foreach($r as $f)
			{
				if(is_dir($PATH.'/'.$f))
					array_push($dirs,$f);
				else
					array_push($files,$f);
			}
			$r=array_merge($dirs,$files);
		}
		return $r;
	}
	function renderFileList()
	{
		global $PATH,$Grep,$Find,$Recursive;
		$editorPPath=realpath($_SERVER["DOCUMENT_ROOT"]);
		$files=getFiles();
		$html="";
		$html.="<div id=list>";
		
		foreach($files as $filePath)
		{
			$rFile=$filePath;
			$aFile=($PATH=='/'?'':$PATH).'/'.$rFile;
			$pFile=realpath($aFile);
			if($rFile=='.'||$rFile=='..')
				continue;
			$isDir=is_dir($aFile);
			
			
			if($Find){
				if(preg_match('/'.$Find.'/i',$rFile)!==1)
					continue;
			}
			if($Grep!=''){
				if($isDir)continue;
				$fileContents=file_get_contents($aFile,false,null,0,9242880); // Limit to 5MB
				if(strpos(strtolower($fileContents),strtolower($Grep))===FALSE)
					continue;
			}
				
			if($isDir){
				$size=get_dir_count($aFile);
				$friendlySize=$size;
			}
			else{
				$size=filesize($aFile);
				$friendlySize=human_readable_filesize(filesize($aFile));
			}
			$age=human_readable_timespan(time()-filemtime($aFile));
			$direct='';
			if(strpos($pFile,$editorPPath)===0){
				$direct=urlencodelite(str_replace('\\','/',substr($pFile,strlen($editorPPath))));
				if($direct=='')$direct='/';
			}
			$aFileEscaped=str_replace("'","\\'",$aFile);
			if($isDir)
				$dlAnchor='';
			else
				$dlAnchor='<a class=dl href="?d=&p='.$aFileEscaped.'"></a>';
			$delOnclick="onclick=\"editor.del('$aFileEscaped',this);return(false)\"";
			if($isDir&&$size!==0&&!is_link($aFile))$delOnclick='';
			
			$segAs='';
			
			// Anchor each path segment.
			$segs=explode('/',$rFile);
			$segsCount=count($segs);
			$segAppend='';
			for($i=0;$i<$segsCount;$i++){
				$segAppend.='/'.$segs[$i];
				if($i===$segsCount-1 && ($size>=1048576 || preg_match('/\.(mp3|aac|ogg|wav|mid|jpg|bmp|gif|png|webp|webm|mp4|mkv|m4v|avi|pdf|zip|rar|tar|gz|7z)$/i',$rFile)===1))
					// Don't allow editing if file is over 1MB or is a media type.
					$href=$direct;
				else
					$href='?p='.urlencodelite(($PATH==='/'?'':$PATH).$segAppend);
				$segIsDir=$i!==$segsCount-1;
				$segAs.='<span class=slash> / </span><a class="seg '.($segIsDir?'d':'').'" href="'.$href.'">'.$segs[$i].'</a>';
			}
			$segAs=substr($segAs,28); // Trim leading " <span class=slash>/</span> "
			
			$pasteAnchor='';
			if($isDir)
				$pasteAnchor="<a href=\"javascript:editor.paste(null,'$aFileEscaped')\" class=paste></a>";
				
			$html.='<div class="'.($isDir?'dir':'file').($size===FALSE?' bad':'').'"><div class=filepath>'.$segAs."</div><span class=size>$friendlySize</span><span class=age>$age</span><a href=# class=del $delOnclick></a></div>";
		}
		$html.="</div>";
		return $html;
	}
?>
<script src="editor.js"></script>
</body>
</html>
