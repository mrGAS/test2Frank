<?php
/********************************
Simple PHP File Manager
Copyright John Campbell (jcampbell1)

Liscense: MIT
********************************/

//Disable error report for undefined superglobals
error_reporting( error_reporting() & ~E_NOTICE );

//тащим текущий путь
$fileIn = $_REQUEST['file'] ?: '.';

//отладка
function debug2File($text) {
    $fw = fopen(dirname(__FILE__). '/log.txt', "a");
    fwrite($fw, date("m.d.Y") .' '. json_encode($text)."\n");
    fclose($fw);
}

debug2File($_POST);

//перерисовка
if($_GET['act'] == 'list') {
	if (is_dir($fileIn)) {
		$directory = $fileIn;
		$result = [];
		//оставил php, ведь вам интересен код xD
		$files = array_diff(scandir($directory), ['.']);
		//готовим дерево
		foreach ($files as $file) {
		    $i              = $directory . '/' . $file;
		    $fileStat       = stat($i);
		    $path           = realpath($i);
		    $downloadLink   = '';
		    if (!is_dir($i)) {$downloadLink   = '?act=download&file='.$path;}
		    $moveLink       = '';
		    if (is_dir($i)) {$moveLink        = '#'.urldecode($path);}
		    
    		if (basename($i) != '..') {
    			$result[] = [
    				'mtime'     => $fileStat['mtime'],
    				'size'      => $fileStat['size'],
    				'name'      => basename($i),
    				'path'      => realpath($i),
    				'is_dir'    => is_dir($i),
    				'download'  => $downloadLink,
    				'move'      => $moveLink,
    			];
    		} 
    		//не строит пускать выше
    		else if (realpath($directory) != realpath(dirname(__FILE__))){
    		    $result[] = [
    				'name'      => basename($i),
    				'path'      => realpath($i),
    				'is_dir'    => is_dir($i),
    				'move'      => $moveLink,
    			];
    		}
		}
		echo json_encode(['results' =>$result]);
		exit;
	}
//загрузка
} elseif ($_GET['act'] == 'download') {
    $file = $fileIn;
	$filename = basename($file);
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	header('Content-Type: ' . finfo_file($finfo, $file));
	header('Content-Length: '. filesize($file));
	header(sprintf('Content-Disposition: attachment; filename=%s',
		strpos('MSIE',$_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\"" ));
	ob_flush();
	readfile($file);
	exit;
//создание директории
} elseif ($_POST['act'] == 'mkdir') {
    $file = $fileIn;
	$dir = $_POST['name'];
	$dir = str_replace('/', '', $dir);
	if(substr($dir, 0, 2) === '..')
	    exit;
	chdir($file);
	@mkdir($_POST['name']);
	exit;
//создание файла, просто проверяю на чтобы не плодили исполняемые
} elseif ($_POST['act'] == 'fileCreate') {
    $file = $fileIn;
    $newFile = $file. '/'.$_POST['name'];
	if  (!fnmatch('*.php', $newFile,FNM_CASEFOLD)) {
    	@fopen($newFile, "a");
    	@fclose($newFile);
	}
    exit;
} elseif ($_POST['act'] == 'uploadFile') {
    $file = $fileIn;
	if  (!fnmatch('*.php', $_FILES['file_data']['name'],FNM_CASEFOLD)) {
    	$res = $res = move_uploaded_file($_FILES['file_data']['tmp_name'], $file.'/'.$_FILES['file_data']['name']);
	}
	exit;
} elseif ($_POST['act'] == 'delete') {
    $file = $fileIn;
    //запрещаем удаление исполняемых файлов
	if  (!fnmatch('*.php', $file,FNM_CASEFOLD)) {
	    @rmrf($file);
	}
	exit;
} elseif ($_POST['act'] == 'rename') {
    $file = $fileIn;
    $directory = dirname($file);
	$name = $_POST['name'];
	// мы же не хотим чтобы нам снесли индекс
    if (!fnmatch('*.php', $file,FNM_CASEFOLD) && ($name != '')) { 
        rename($file,$directory.'/'.$name);
    }
    exit;
}


//рекурсивно зачищаем
function rmrf($dir) {
	if(is_dir($dir)) {
		$files = array_diff(scandir($dir), ['.','..']);
		foreach ($files as $file)
			rmrf("$dir/$file");
		rmdir($dir);
	} else {
		unlink($dir);
	}
}
?>






<!DOCTYPE html>
<html><head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>

<!--можно было обойтись, но хочу без перезагрузки страницы-->
<script>
    $(function(){
        var XSRF = (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)')||0)[2];
        //создание директории
        $('#mkdir').submit(function(e) {
    		var hashval = decodeURIComponent(window.location.hash.substr(1)),
    			$dir = $(this).find('[name=name]');
    		e.preventDefault();
    		$dir.val().length && $.post('?',{'act':'mkdir',name:$dir.val(),xsrf:XSRF,file:hashval},function(data){
    			list();
    		},'json');
    		$dir.val('');
    		return false;
    	});
    	
    	//создание файла
    	$('#fileCreate').submit(function(e) {
    		var hashval = decodeURIComponent(window.location.hash.substr(1)),
    			$dir = $(this).find('[name=name]');
    		e.preventDefault();
    		$dir.val().length && $.post('?',{'act':'fileCreate',name:$dir.val(),xsrf:XSRF,file:hashval},function(data){
    			list();
    		},'json');
    		$dir.val('');
    		return false;
    	});

        //обработка загрузки uploadFiles
    	$('#uploadFiles').change(function(e) {
    		e.preventDefault();
    		$.each(this.files,function(k,file) {
    			uploadFile(file);
    		});
    	});
	    
	    
	    $('#table').on('click','.delete',function(data) {
    		$.post("",{'act':'delete',file:$(this).attr('data-file'),xsrf:XSRF},function(response){
    			list();
    		},'json');
    		return false;
    	});
	    
	    //запаковка 
    	function uploadFile(file) {
    		var folder = decodeURIComponent(window.location.hash.substr(1));
    		var fd = new FormData();
    		fd.append('file_data',file);
    		fd.append('file',folder);
    		fd.append('xsrf',XSRF);
    		fd.append('act','uploadFile');
    		var xhr = new XMLHttpRequest();
    		xhr.open('POST', '?');
    		xhr.onload = function() {
    			list();
      		};
      		xhr.send(fd);
    	}
    	
    	$('#table').on('click','.rename',function(data) {
    	    window.alert('qweqw');
    	    $newName = $(this.parentNode).find('.rename-name');
    	    $.post("",{'act':'rename',file:$(this).attr('data-file'),name:$newName.val(),xsrf:XSRF},function(response){
    			list();
    		},'json');
    		return false;
    	});
    	
        var $tbody = $('#list');
        /*реагируем на изменение кэша*/
        $(window).on('hashchange',list).trigger('hashchange');
    	function list() {
    		var hashval = window.location.hash.substr(1);
    		$.get('?act=list&file='+ hashval,function(data) {
    		    /*зачистили и перерисовали*/
    		    $tbody.empty();
    			$.each(data.results,function(key, value) {
    			    $tbody.append(renderListRow(value));
    			});
    		},'json');
    	}
    	/*перерисовка строк таблицы*/
    	function renderListRow(data) {
    	    var $link;
    	    (data.move != '') ? $link = $('<a class="name" />').attr('href', data.move).text(data.name) : $link = $('<p class="name" />').text(data.name);

    		var $linkDown = $('<a class="col-2 download" />');
    		(data.move == '') ? $linkDown.attr('href', data.download).text('download') : null;
    		
    		var $linkDelete = $('<a class="col-2 delete" href="#" />');
    		(data.size >= 0) ? $linkDelete.attr('data-file', data.path).text('delete') : null;
    	
    		var $inputRename = $('<input type="text" name="name" class="col-6 rename-name"/>')
    		var $linkRename = $('<a class="col-6 rename" href="#" />').attr('data-file', data.path).text('rename');
    		var $blockRename = $('<div class="col-6 block-rename"/>');
    		(data.size >= 0) ? $blockRename.append($inputRename).append($linkRename) : null;
    		
    		var $html = $('<tr />')
    	        .append($('<td />').append($link))
    	        .append($('<td />').text(data.size))
    	        .append($('<td />').text(data.mtime))
    	        .append($('<td />').append($('<div class="row"/>').append($linkDown).append($linkDelete).append($blockRename))/*.append($linkRename)*/)
    	    return $html;
    	}
    	
	})
</script>

<!--вообще не нужное, но лень добавлять свои стили-->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head><body>
<div class="row" id="top">
   	<div class="col-5">
   	    <div id="breadcrumb">&nbsp;</div>
   	</div>
   	<div class="col-4">
       	<input id=uploadFiles type=file name=name multiple />
    </div>
    <div class="col-3">
    	<div class="col-12">
        	<form action="?" method="post" id="mkdir">
        		<label for=dirname>Create New Folder</label><input id=dirname type=text name=name value="" />
        		<input type=submit value="create" />
        	</form>
        </div>
        <div class="col-12">
            <form action="?" method="post" id="fileCreate">
        		<label for=filename>Create New File</label><input id=newFilename type=text name=name value="" />
        		<input type=submit value="create" />
        	</form>
        </div>
    </div>
</div>

<div id="upload_progress"></div>
<table class="table" id="table">
    <thead>
        <tr>
        	<th>Name</th>
        	<th>Size</th>
        	<th>Modified</th>
        	<th>Actions</th>
        </tr>
    </thead>
    <tbody id="list">
        <tr>
            <th>Name</th>
        </tr>
    </tbody>
</table>
<footer class="text-center">test by MRGAS</footer>
</body></html>
