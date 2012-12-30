<?php
//Подключаемся к БД
$link = mysql_connect("a50859.mysql.mchost.ru", "a50859_6", "aabbcc");
mysql_select_db("a50859_6");
mysql_query("SET NAMES 'UTF8'");

//Создаем и инициализируем экземпляр класса для работы с файлами
try {
    new Album($_REQUEST, array(
        'maxSize' => '2M',
        'allowedType' => array('jpeg', 'jpg', 'png', 'gif',
        'bmp', 'psd', 'psp', 'ai', 'eps', 'cdr',
        'mp3', 'mp4', 'wav', 'aac', 'aiff', 'midi',
        'avi', 'mov', 'mpg', 'flv', 'mpa',
        'pdf', 'txt', 'rtf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'bat', 'cmd', 'dll', 'inf', 'ini', 'ocx', 'sys',
        'htm', 'html', 'write', 'none',
        'zip', 'rar', 'dmg', 'sitx')
    ));
} catch(Exception $e) {
    echo json_encode(array('error' => 'Error ' . $e->getCode() . ': ' . $e->getMessage()));
}

/**
* Класс для работы с файлами
*/
class Album {

    // Параметры по умолчанию
    private $prm = array(
        'uploadDir'        => 'uploads/', //Папка для хранения изображений
        'previewsDir'      => 'previews/', //Папка для хранения миниатюр
        'maxNumberOfFiles' => 100, //Максимальное количество загруженных файлов (0 — без ограничений)
        'maxSpace'         => '100M', //Максимально допустимый вес всех загруженных файлов (0 — без ограничений)
        'maxSize'          => '10M', //Максимально допустимый вес загружаемого файла (0 — ограничен только настройками сервера или формы)
        'allowedType'      => array('jpeg', 'jpg', 'png', 'gif') //Допустимые расширения для загрузки
    );

	public function __construct($request, $param) {
        
        foreach ($param as $k => $v) $this->prm[$k] = $v;

        $this->prm['maxSpace'] = $this->toBytes($this->prm['maxSpace']);
        $this->prm['maxSize']  = $this->toBytes($this->prm['maxSize']);
        
        $this->init($request);
	}
	
	private function init($request) {

		switch ($request['type']) {       
			case 'add':      echo json_encode($this->add()); break;				
			case 'update':   echo json_encode($this->update($request['id'], $request)); break;
			case 'getlist':  echo json_encode($this->getList()); break;          
            case 'delete':                    $this->delete($request['id']); break;				
			case 'savesort':                  $this->savesort(json_decode(get_magic_quotes_gpc() ? stripslashes($request['sort']) : $request['sort'], true)); break;			
			default: $headers = getallheaders();				
                if ($headers['Content-Length'] > $this->prm['maxSize']) {
                    throw new Exception('Превышен размер файла, установленный на сервере', 5);
                }
                throw new Exception('Не получен тип действия', 15);
		}
	}
    
    /**
    * Создание пустой записи и возврат ее id, а так же ограничений и текстов ошибок
    */
    private function add() {
        mysql_query("insert into `photoalbum` set `id` = NULL");
        
        return array_merge($this->prm, array(
            'id'               => mysql_insert_id(),
            'quantity'         => $this->checkQuantity() - 1, //т.к. пустая запись уже создана
            'space'            => $this->checkSpace(),
            'errorBadType'     => sprintf('Можно загружать: %s', strtoupper(implode (', ', $this->prm['allowedType']))),
            'errorBigSize'     => sprintf('Вес не более %s Мб', round($this->prm['maxSize']/1048576, 2)),
            'errorMaxQuantity' => sprintf('Загружено максимальное количество файлов: %s', $this->prm['maxNumberOfFiles'])
        ));
	}
    
    /**
    * Удаление элемента с указанным id
    */    
    private function delete($id) {
        if (isset($id)) {
            $res = mysql_query("select `file`, `preview` from `photoalbum` where `id` = '" . intval($id) . "'");
            $row = mysql_fetch_assoc($res);

            mysql_query("delete from `photoalbum` where `id` = '" . intval($id) . "'");

            unlink($this->prm['uploadDir'] . $row['file']);           
            //Удаляем только миниатюры, созданные из картинок
            if (pathinfo($row['preview'], PATHINFO_FILENAME) !== pathinfo($row['file'], PATHINFO_EXTENSION)) {
                unlink($this->prm['previewsDir'] . $row['preview']);
            }
        }
	}

    /**
    * Обновление элемента с указанным id, в т.ч. сохранение файла и возврат его имени и имени миниатюры
    */ 	
    private function update($id, $param) {
        if (isset($id)) {    
            if (!empty($_FILES)) {
                $this->checkFreeSpace();
            
                //Загружаем файл на сервер (Обращаемся к первому элементу $_FILES[key($_FILES)], т.к. подразумевается, что в запросе присылается не более одного файла)
                try {
                    list($file_name, $ext, $file_size, $file_type) = $this->saveFile($_FILES[key($_FILES)], $this->prm['maxSize'], $this->prm['allowedType']); 
                } catch(Exception $e) {
                    $this->delete($id);
                    throw $e;
                }
                
                if (in_array($ext, array('jpeg', 'jpg', 'png', 'gif'))) {
                    //Подключаем графическую библиотеку и обрабатываем изображение
                    include_once('lib/gd.php'); //или lib/magickwand.php, если она есть на хостинге и работает лучше.
                    
                    $preview_name = Image::makeavatar($this->prm['uploadDir'] . $file_name, $this->prm['previewsDir'] . $file_name, 164, 'png');
                    $file_name = Image::trueresize($this->prm['uploadDir'] . $file_name, null, 1000, 1000, 'jpg', 75, true);
                } else {
                    $preview_name = $ext . '.png';
                }
            
                mysql_query("update `photoalbum` set `file` = '" . $file_name . "', `preview` = '" . $preview_name . "', `size` = '" . $file_size . "' where `id` = '" . intval($id) . "'");

                return array(
                    'fileName'    => $this->prm['uploadDir'] . $file_name,
                    'previewName' => $this->prm['previewsDir'] . $preview_name
                );               
            } 
            if (isset($param['title'])) {
                mysql_query("update `photoalbum` set `title` = '" . mysql_real_escape_string($param['title']) . "' where `id` = '" . intval($id) . "'");
            }
        }
	}

    /**
    * Получение списка элементов
    */ 	    
    private function getList() {
        $res = mysql_query("select `id`, `position`, `title`, `file`, `preview` from `photoalbum` order by `position`" );
        $album = array();
        while ($row = mysql_fetch_assoc($res)) {
            if ($row['file']) {
                $album[] = array(
                    'id'          => $row['id'],
                    'position'    => $row['position'],
                    'title'       => $row['title'],
                    'fileName'    => $this->prm['uploadDir'] . $row['file'],
                    'previewName' => $this->prm['previewsDir'] . $row['preview']
                );
            } else {
                //Удаляем записи без файлов, образовавшиеся в результате прошлых неудачных загрузок
                $this->delete($row['id']);
            }
        }
        return $album;
	}
    
    /**
    * Сохранение порядка расположения элементов
    */ 	
	private function savesort($positions) {
        $positionsTmp = array();
        foreach ($positions as $k => $v) {		
            $positionsTmp[] = "('" . intval($v) . "', '" . intval($k) . "')";
        }        
        $values = " values " . implode(", ", $positionsTmp);
        
        mysql_query("insert into `photoalbum` (`id`, `position`) " . $values . " on duplicate key update `position` = values(`position`)");
	}

    /**
    * Функции проверки количества загруженных файлов и занятого места
    */ 
    private function checkFreeSpace() {
        //Проверяем, не превышено ли количество загружаемых файлов (актуально, если файлы после загрузки уменьшаются до определенного размера)
        if ($this->checkQuantity() >= $this->prm['maxNumberOfFiles']) {
            throw new Exception('Превышено количество загружаемых файлов', 13);
        }      
        //Проверяем, не превышено ли отведенное для хранения место (актуально, если файлы сохраняются без обработки)
        if ($this->prm['maxSpace'] && $this->checkSpace() >= $this->prm['maxSpace']) {
            throw new Exception('Превышено отведенное для хранения место', 14);
        }
    }
    
    private function checkQuantity() {
        $res = mysql_query("select count(*) as quantity from `photoalbum`");
        $row = mysql_fetch_assoc($res);
        
        return $row['quantity'];
    }
    
    private function checkSpace() {
        $space = mysql_query("select sum(`size`) from `photoalbum`");
        list($space) = mysql_fetch_row($space);
        
        return $space;
    }
    
    /**
    * Загрузка файла на сервер
    */ 
	public function saveFile($file, $maxSize, $allowedType) {

        if ($file['error']) {			
            throw new Exception('Неудачная загрузка в $_FILES', $file['error']);
        }			
        if ($maxSize && $file['size'] > $maxSize){	
            throw new Exception('Превышен допустимый размер файла', 12);
        }

        //Если файл без расширения, узнаем его из MIME-типа
        if (!($ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)))) $ext = preg_replace('/^.*([^\/]*)$/U', '$1', $file['type']);
        if (!in_array($ext, $allowedType)){		
            throw new Exception('Файл неразрешенного типа', 10);
        }
        
        //Генерируем новое имя и сохраняем
        $new_file_name = uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $this->prm['uploadDir'] . $new_file_name)) {
            throw new Exception('Не удалось переместить из временной директории', 9);
        }
        
        return array($new_file_name, $ext, $file['size'], $file['type'], $file['name']);
	}
	
	public function toBytes($val) {
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