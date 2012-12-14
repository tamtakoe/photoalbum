<?php
//Подключаемся к БД
mysql_connect("хост", "логин", "пароль");
mysql_select_db("БД");
mysql_query("SET NAMES 'UTF8'");

//Создаем и инициализируем экземпляр класса для работы с файлами
try {
    $album = new Album($_REQUEST, array(
        'uploadDir' => 'uploads/',
        'previewsDir' => 'previews/'
    ));
} catch(Exception $e) {
    
    switch ($e->getCode()) {		
        case 1: //Размер принятого файла превысил максимально допустимый размер, который задан директивой upload_max_filesize конфигурационного файла php.ini
        case 2: //Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.
        case 5: //Размер загружаемого файла превысил все мыслимые значения.
        case 12: //Размер загружаемого файла превысил допустимое значение.
            $error_report = 'Размер файла не должен превышать' . round($album->maxFileSize/1048576, 2) . ' Мб';
            break;
        case 3: //Загружаемый файл был получен только частично.
        case 4: //Файл не был загружен.
        case 6: //Отсутствует временная папка. Добавлено в PHP 4.3.10 и PHP 5.0.3.
        case 7: //Не удалось записать файл на диск. Добавлено в PHP 5.1.0.
        case 8: //PHP-расширение остановило загрузку файла.
        case 9: //Не удалось переместить из временной директории
        case 15: //Не получен тип действия
            $error_report = 'Загрузка не удалась';
            break;
        case 10: $error_report = 'Разрешена загрузка только' . implode (', ', $album->allowedType) . 'типов';
            break;
        case 11: $error_report = 'Обновите браузер';
            break;
        case 13: $error_report = 'Превышено количество загружаемых файлов';
            break;
        case 14: $error_report = 'Превышено отведенное для хранения место';
    }
    echo ($error_report);
}

/**
* Класс для работы с файлами
*/
class Album {

	private $fieldName = 'my-pic'; //Ключ файла в $_FILES [нужно избавиться]
	private $uploadDir = '1uploads/'; //Папка для хранения изображений
	private $previewsDir = '1previews/'; //Папка для хранения превьюшек
	private $maxNumberOfFiles = 100; //Максимальное количество загруженных файлов (0 — без ограничений)
	private $maxSpace = '10M'; //Максимально допустимый вес всех загруженных файлов (0 — без ограничений)
	private $maxSize = '10M'; //Максимально допустимый вес загружаемого файла (0 — ограничен только настройками сервера или формы)
	private $allowedType = array('jpeg', 'jpg', 'png', 'gif', 'txt'); //Допустимые расширения для загрузки

	public function __construct($type, $param) {

		foreach ($param as $k => $v) {
			$this->$k = $v;
		}
        
        $this->init($type);
	}
	
	private function init($param) {

		switch ($param['type']) {
        
			case 'add':     $this->add(); break;
							
			case 'delete':  $this->delete($param['id']); break;
				
			case 'update':  $this->update($param['id'], $param); break;
				
			case 'getlist': $this->getList(); break;
				
			case 'sort': //Сохранение порядка расположения элементов
				$positions = get_magic_quotes_gpc() ? stripslashes($param['sort']) : $param['sort'];		

                $this->sort(json_decode($positions, true));
				break;
			
			default: throw new Exception('Не получен тип действия', 15);
		}
	}
    
    private function add() {

        //Создаем пустую запись и возвращаем ее id
        mysql_query("insert into `album1` set `id` = NULL");
        $return_data = array('id'=>mysql_insert_id(), 'maxSize'=>$this->toBytes($this->maxSize));
        echo json_encode($return_data);
	}
    
    private function delete($id) {
        if (isset($id)) {
            $res = mysql_query("select `file`, `preview` from `album1` where `id` = '".$id."'");
            $row = mysql_fetch_assoc($res);
            mysql_query("delete from `album1` where `id` = '".$id."'");
            
            if (!unlink($this->uploadDir . $row['file']) && file_exists($this->uploadDir . $row['file'])) {
                throw new Exception('Не удалось удалить файл', 16);
            }
            if (!unlink($this->previewsDir . $row['preview']) && file_exists($this->previewsDir . $row['preview'])) {
                throw new Exception('Не удалось удалить миниатюру', 17);
            }		
        }
    }
	
    private function update($id, $param) {
        if (isset($id)) {    
            if (isset($param['file'])) {
                $this->checkFreeSpace();
            
                //Загружаем файл на сервер
                list($file_name, $file_size) = $this->saveFile($this->maxSize, $this->allowedType);

                //Подключаем графическую библиотеку и обрабатываем изображение
                //use lib\gd as Image;
                include_once('lib/gd.php'); //или lib/magickwand.php, если она есть на хостинге и работает лучше.
                
                $preview_name = Image::makeavatar($this->uploadDir . $file_name, $this->previewsDir . $file_name, 164, 'png');
                $file_name = Image::trueresize($this->uploadDir . $file_name, null, 1000, 1000, 'jpg', 75, true);
            
                mysql_query("update `album1` set `file` = '".$file_name."', `preview` = '".$preview_name."', `size` = '".$file_size."' where `id` = '" . $id . "'");
                //Возвращаем имя файла, превьюшки и id в базе
                $return_data = array('id'=>$id, 'fileName'=>$this->uploadDir . $file_name, 'previewName'=>$this->previewsDir . $preview_name);
                echo json_encode($return_data);
                
            } elseif (isset($param['title'])) {
                mysql_query("update `album1` set `title` = '".$param['title']."' where `id` = '" . $id . "'");
            }
        }
    }
    
    private function getList() {
        $res = mysql_query("select `id`, `position`, `title`, `file`, `preview` from `album1` order by `position`" );
        $album = array();
        while ($row = mysql_fetch_assoc($res)) {
            $album[] = array('id'=>$row['id'], 'position'=>$row['position'], 'title'=>$row['title'], 'fileName'=>$this->uploadDir . $row['file'], 'previewName'=>$this->previewsDir . $row['preview']);
        }
        echo json_encode($album);
	}
    
	private function sort($positions) {

        $positionsTmp = array();
        foreach ($positions as $k => $v) {		
            $positionsTmp[] = "('" . $v . "', '" . $k . "')";
        }        
        $values = " values " . implode(", ", $positionsTmp);
        
        mysql_query("insert into `album1` (`id`, `position`) " . $values . " on duplicate key update `position` = values(`position`)");
    }
    
    private function checkFreeSpace() {
        //Проверяем, не превышено ли количество загружаемых файлов (актуально, если файлы после загрузки обрабатываются и имеют на выходе определенный размер)
        $res = mysql_query("select count(*) as quantity from `album1`");
        $row = mysql_fetch_assoc($res);
        if ($row['quantity'] >= $this->maxNumberOfFiles) {
            throw new Exception('Превышено количество загружаемых файлов', 13);
        }
        
        //Проверяем, не превышено ли отведенное для хранения место (актуально, если файлы сохраняются без обработки)
        $space = mysql_query("select sum(`size`) from `album1`");
        list($space) = mysql_fetch_row($space);
        
        if ($this->maxSpace && $space >= $this->toBytes($this->maxSpace)) {
            throw new Exception('Превышено отведенное для хранения место', 14);
        }
    }

    public function saveFile($maxSize, $allowedType) {

        if(!empty($_FILES)) {
            //echo 'Contents of $_FILES:<br/><pre>'.print_r($_FILES, true).'</pre>';
            $file = $_FILES[$this->fieldName];

            if ($file['error']) {			
                throw new Exception('Загружен с ошибкой...', $file['error']);
            }			
            if ($maxSize && $file['size'] > $this->toBytes($maxSize)){	
                throw new Exception('Превышен допустимый размер файла', 12);
            }

            //Если файл без расширения, узнаем его из MIME-типа
            if (!($ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)))) $ext = preg_replace('/^.*([^\/]*)$/U', '$1', $file['type']);
            
            if (!in_array($ext, $allowedType)){		
                throw new Exception('Файл неразрешенного типа', 10);
            }
            
            //Генерируем новое имя и сохраняем
            $new_file_name = uniqid() . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $new_file_name)) {
                throw new Exception('Не удалось переместить из временной директории', 9);
            }
            
            return array($new_file_name, $file['size'], $file['type']);

        } else {		

            $headers = getallheaders();				
            if($headers['Content-Length'] > $this->toBytes($maxSize)) {
                throw new Exception('Превышен размер файла, установленный на сервере', 5);
            }
            
            throw new Exception('Загрузка из старых браузеров не поддерживаются', 11);
        }
    }
	
    public function toBytes($val)
	{
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		switch ($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}
}
?>
