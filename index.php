<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Демонстрация плагина загрузки файлов на сервер</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <link href="css/bootstrap.css" rel="stylesheet">
        <link href="css/bootstrap-responsive.css" rel="stylesheet">	
        <link href="css/main.css" rel="stylesheet">
        <link href="css/jquery.fancybox.css" rel="stylesheet" />
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script src="js/jquery-ui-1.8.24.custom.min.js"></script>
        <script src="js/jquery.mousewheel-3.0.6.pack.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.damnUploader.js"></script>
        <script src="js/jquery.autogrowtextarea.js"></script>		
        <script type="text/javascript" src="js/jquery.fancybox.js"></script>		
        <script src="js/main.js"></script>
        <!--[if lt IE 9]>
          <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <link rel="shortcut icon" href="/ico/favicon.ico">	
    </head>
	<body>	
	
	<canvas id="canvas" width="150" height="120" title="Кликнуть для отправки изображения в канве" style="cursor:pointer; display: none" ></canvas>
	<div id="console" style="display: none"></div>
		<div class="container">
			<br/>
			<h1>Демонстрация загрузки картинок</h1>
			<br/>
			<div div class="row">
				<div class="span8">					
					<p>Для загрузки используется плагин jquery.damnUploader, позволяющий загружать файлы различными способами</p>
					<p><a href="https://github.com/safronizator/damnUploader" >Страница плагина на GitHub</a></p>
					<p><a href="#" >Страница демонстрации на GitHub</a></p>
				</div>
				<div class="span4">
					<p><a class="btn btn-primary btn-large">Скачать демонстрацию</a></p>
				</div>
			</div>
			<br/>
			<br/>
			<p>
				<form action="./action.php?add" method="POST" enctype="multipart/form-data">
					<span class="btn fileinput-button">
						<i class="icon-plus"></i>
						<span>Добавить файлы...</span>
						<input type="file" name="files" name="my-pic" id="file-field" multiple="multiple">
					</span>
				</form>
			</p>
			<div id="albumContainer" class="row-fluid">
				<p class="emptyAlbumText">Перетащите сюда свои фотографии</p>
				<ul id="album" class="thumbnails">
					<li class="span2">
						<div class="thumbnail">
							<button style="float: right; width: 25px; padding: 2px 0 0 2px;" type="button" class="close">×</button>
							<div class="imgTitle">
								<textarea rows="1" class="input-block-level" maxlength="50"></textarea>	
							</div>
							<a class="img-link fancybox" href="#" title="">
								<img src="" alt="">
							</a>
							<div class="imgNote"></div>
							<div class="progress progress-striped active">
								<div class="bar"></div>
							</div>
						</div>
					</li>
				</ul>
			</div>	
			<footer>
			</footer>

		</div> <!-- /container -->
		
	</body>
</html>
<?php
?>