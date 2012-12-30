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
        <link href="css/jquery.fancybox.css" rel="stylesheet"/>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script src="js/jquery-ui-1.8.24.custom.min.js"></script>
        <script src="js/jquery.mousewheel-3.0.6.pack.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.autoGrowAndSaveArea.js"></script>		
        <script src="js/jquery.fancybox.js"></script>
        <script src="js/jquery.ui.widget.js"></script>
        <script src="js/jquery.iframe-transport.js"></script>
        <script src="js/jquery.fileupload.js"></script>
        <script src="js/main.js"></script>
        <!--[if lt IE 9]>
          <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <link rel="shortcut icon" href="/ico/favicon.ico">	
    </head>
	<body>
		<div class="container">
			<br/>
			<h1>Демонстрация загрузки картинок</h1>
			<br/>
			<br/>         
            <div div class="row">
                <div class="span2">
                    <form action="./action.php?add" method="POST" enctype="multipart/form-data">
                        <span class="btn fileinput-button">
                            <i class="icon-plus"></i>
                            <span>Добавить...</span>
                            <input type="file" name="files" name="my-pic" id="file-input" multiple="multiple">
                        </span>
                    </form>
                </div>
                <div id="error-area" class="span10">
                    <div class="alert alert-error error-tpl">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                </div>
            </div>         
			<div class="row-fluid thumbnailsContainer">
				<p class="thumbnailEmptyArea">Перетащите сюда свои фотографии</p>
				<ul id="thumbnail-area" class="thumbnails">
					<li class="span2 thumbnail-tpl">
						<div class="thumbnail">
							<button style="float: right; width: 25px; padding: 2px 0 0 2px;" type="button" class="close">×</button>
							<div class="imgTitle">
								<textarea rows="1" class="input-block-level" maxlength="50"></textarea>                              
							</div>
							<a class="img-link fancybox" href="#" title="">
								<img src="previews/preloader.gif" alt="">
							</a>
							<div class="progress progress-striped active progress-tpl">
								<div class="bar"></div>
							</div>                          
						</div>
					</li>
				</ul>
			</div>
		</div>
	</body>
</html>
<?php
?>