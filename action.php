<?php
//Определяем максимально допустимый размер загружаемого файла из настроек PHP
define ("MAX_FILE_SIZE", min(convertbytes(ini_get('upload_max_filesize')), convertbytes(ini_get('post_max_size'))));

//Подключаемся к БД
mysql_connect("сервер", "логин", "пароль");
mysql_select_db("имя базы");
mysql_query("SET NAMES 'UTF8'");

//Создаем и инициализируем экземпляр класса для работы с файлами
$album = new Album;
$album->init();

/**
* Класс для работы с файлами
*
*/
class Album {

	public $errors = array();
	public $field_name = 'my-pic'; //Ключ файла в $_FILES [нужно избавиться]
	public $upload_dir = 'uploads/'; //Папка для хранения изображений
	public $previews_dir = 'previews/'; //Папка для хранения превьюшек
	public $allowed_type = array('image/jpeg'=>'jpeg','image/png'=>'png','image/gif'=>'gif'); //Допустимые типы для загрузки и расширения, которые будут добавлены к именам файлов на сервере
	//Имя файла для Blob-данных может различаться в разных браузерах, поэтому расширения имен присваиваются в соответствие с MIME типом
	//IE image/pjpeg», «image/x-png»

	public function init()
	{
		$errors = array();
		
		switch ($_GET['type']) {
			case add:
				//Загружаем файл на сервер
				$file_name = $this->savefile();
				if (!$file_name) $this->error();

				//Подключаем графическую библиотеку и обрабатываем изображение
				include_once('lib/gd.php'); //или lib/magickwand.php, если она есть на хостинге и работает лучше.
				
				$preview_name = Image::makeavatar($this->upload_dir . $file_name, $this->previews_dir . $file_name, 164, 'png');
				$file_name = Image::trueresize($this->upload_dir . $file_name, null, 1000, 1000, 'jpg', 75, true);
				
				//Получаем дополнительные данные (нужно в случае, если отправка будет отложенной)
				$data = $_POST[$this->field_name];
				if(get_magic_quotes_gpc()) $data = stripslashes($data);		
				$data = json_decode($data, true);
								
				//Добавляем информацию в базу
				mysql_query("insert into `album` set `title` = '".$data['title']."', file = '".$file_name."', preview = '".$preview_name."'");
				
				//Возвращаем имя файла, превьюшки и id в базе
				$return_data = array('id'=>mysql_insert_id(), 'fileName'=>$this->upload_dir . $file_name, 'previewName'=>$this->previews_dir . $preview_name);
				echo json_encode($return_data);
				
				break;
							
			case delete:
				if (isset($_GET['id'])) {
					$res = mysql_query("select `file`, `preview` from `album` where `id` = '".$_GET['id']."'");
					$row = mysql_fetch_assoc($res);
					unlink($this->upload_dir . $row['file']);
					unlink($this->previews_dir . $row['preview']);
					mysql_query("delete from `album` where `id` = '".$_GET['id']."'");					
				}
				break;
				
			case update:
				if (isset($_GET['id'])) {
					mysql_query("update `album` set `title`='".$_POST['title']."' where `id` = '".$_GET['id']."'");				
				}
				break;
				
			case getlist:
				$res = mysql_query("select `id`, `position`, `title`, `file`, `preview` from `album` order by `position`" );
				$album = array();
				while ($row = mysql_fetch_assoc($res)) {
					$album[] = array('id'=>$row['id'], 'position'=>$row['position'], 'title'=>$row['title'], 'fileName'=>$this->upload_dir . $row['file'], 'previewName'=>$this->previews_dir . $row['preview']);
				}
				echo json_encode($album);
				break;
				
			case sort: //Сохранение порядка расположения элементов
				$data = $_POST['sort'];
				if (get_magic_quotes_gpc()) $data = stripslashes($data);		
				$data = json_decode($data, true);
				//Нужно придумать что-нибудь получше
				for ($i = 0, $i_max = count($data); $i < $i_max; $i++) {					
					mysql_query("update `album` set `position`='".$i."' where `id` = '".$data[$i]."'");
				}
				break;
		}
	}

	public function savefile() {
	
		if(!empty($_FILES)) {
			// Файл передан через обычный массив $_FILES
			//echo 'Contents of $_FILES:<br/><pre>'.print_r($_FILES, true).'</pre>';
			$file = $_FILES[$this->field_name];
			
			if ($file['error']) {			
				$this->errors['file'] = $file['error']; //Загружен с ошибкой...
				return false;				
			}
			if (!array_key_exists($file['type'], $this->allowed_type)){		
				$this->errors['file'] = 10; //Файл неразрешенного типа
				return false;	
			} 	
			
			$new_file_name = uniqid() . '.' . $this->allowed_type[$file['type']];

			if (!move_uploaded_file($file['tmp_name'], $this->upload_dir . $new_file_name)) {
				$this->errors['file'] = 9; //Не удалось переместить из временной директории
				return false;
			}

			return $new_file_name;	

		} else {		

			$headers = getallheaders();
			
			if($headers['Content-Length'] > MAX_FILE_SIZE) {
				$this->errors['file'] = 5; //Превышен размер файла
				return false;
			}
			
			$this->errors['file'] = 11; //Старые браузеры пока не поддерживаются
			return false;
			
			// Надо выцеплять файл из входного потока php
			// (такое встречается только в очень экзотических браузерах,
			//  поэтому можно не предусматривать этот способ вовсе)
			if(array_key_exists('Upload-Filename', $headers)) {
				$data = file_get_contents('php://input');
				echo 'File recieved: '.$headers['Upload-Filename'];
				echo '<br/>Size: '.$headers['Upload-Size'].' ('.strlen($data).' b)';
				echo '<br/>Type: '.$headers['Upload-Type'];
			}
		}
	}
	
	public function error() {
	
		$error_report = '';
		switch ($this->errors['file']) {		
			case 1: //Размер принятого файла превысил максимально допустимый размер, который задан директивой upload_max_filesize конфигурационного файла php.ini
			case 2: //Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.
			case 5: //Размер загружаемого файла превысил все мыслимые значения.
				$error_report = 'Файл ' . $_FILES['name'] . ' слишком большой. Размер загружаемого файла не должен превышать ' . round(MAX_FILE_SIZE/1048576, 2) . ' Мб';
				break;
			case 3: //Загружаемый файл был получен только частично.
			case 4: //Файл не был загружен.
			case 6: //Отсутствует временная папка. Добавлено в PHP 4.3.10 и PHP 5.0.3.
			case 7: //Не удалось записать файл на диск. Добавлено в PHP 5.1.0.
			case 8: //PHP-расширение остановило загрузку файла.
			case 9: //Не удалось переместить из временной директории
				$error_report = 'Загрузка не удалась';
				break;
			case 10: $error_report = 'Файл неразрешенного типа'; //Файл неразрешенного типа
				break;
			case 11: $error_report = 'Обновите браузер'; //Старые браузеры пока не поддерживаются
		}
		
		die($error_report);
	}
}

/**
* Convert a shorthand byte value from a PHP configuration directive to an integer value
* @param    string   $value
* @return   int
*/

function convertbytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // Модификатор 'G' доступен, начиная с PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
	
    return $val;
}
?>