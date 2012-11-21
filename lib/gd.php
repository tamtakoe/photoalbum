<?php
/**
* Класс для обработки изображений на сервере
* Использует GD Graphics Library
*/
abstract class Image {

	/**
	* Пропорциональное масштабирование изображения
	*
	* Работает с JPEG, PNG, и GIF.
	* Масштабирование производится так, чтобы изображение влезло в заданный прямоугольник. Пропорции сторон не изменяются
	*
	* @param string Расположение исходного файла
	* @param string Расположение конечного файла, если установлено false, то исходный файл перезаписывается новым
	* @param integer Ширина прямоугольника в px, если 0, не учитывается
	* @param integer Высота прямоугольника в px, если 0, не учитывается
	* @param string Тип конечного файла jpeg, png или gif. При null тип определяется из имени конечного файла. В противном случае из типа исходного
	* @param integer Качество конечного файла. 1–100 для JPEG (рекомендуется 75), 0—9 для PNG (рекомендуется 9, если сервер выдержит дополнительную нагрузку)
	* @param boolean Будет ли наложен копирайт. Изображение берется из файла copyright.png
	* @return string Имя конечного файла или false в случае ошибки
	*/
	public function trueresize($file_input, $file_output, $max_w, $max_h, $ext, $quality, $copyright) {

		list($w, $h, $type) = getimagesize($file_input);
		if (!$w || !$h) return false; //Невозможно получить длину и ширину изображения

		//Вычисляем новые размеры изображения, если оно не вписывается
		$h1 = $h;
		if ($max_w && $w > $max_w) {
			$new_w = $max_w;
			$new_h = $h1 = $new_w/($w/$h);
		}	
		if ($max_h && $h1 > $max_h) {
			$new_h = $max_h;
			$new_w = $new_h/($h/$w);
		}
		
		//Если размеры не изменились и не надо ставить копирайт, просто копируем файл
		if (!$new_w && !$copyright) return self::convert($file_input, $file_output, null, $ext, $quality);
		
		//Если размеры не изменились, оставляем их старыми
		if (!$new_w) {
			$new_w = $w;
			$new_h = $h;
		}
		
		//Читаем данные из исходного изображения
		switch ($type) {
			case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($file_input); break;
			case IMAGETYPE_PNG:  $image = imagecreatefrompng($file_input); break;
			case IMAGETYPE_GIF:  $image = imagecreatefromgif($file_input); break;
			default: echo 'Некорректный формат файла'; return false; //Некорректный формат файла
		}
		
		//Создаем новое изображение	и задаем его размеры	
		$new_image = imagecreatetruecolor($new_w, $new_h);
		imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
		imagedestroy($image);

		//Ставим копирайт
		if ($copyright) {
			$file_copyright = 'copyright.png';
			list($cw, $ch) = getimagesize($file_copyright);
			
			$copyright_image = imagecreatefrompng($file_copyright);
			imagecopy($new_image, $copyright_image ,$new_w-$cw, $new_h-$ch, 0, 0, $cw, $ch);
			imagedestroy($copyright_image);
		}

		//Сохраняем
		return self::convert($file_input, $file_output, $new_image, $ext, $quality);
	}

	/**
	* Создание миниатюры
	*
	* Работает с JPEG, PNG, и GIF.
	* Обрезает до квадрата и масштабирует до нужного размера. Если область обрезки не задана, ей выступает все изображение.
	*
	* @param string Расположение исходного файла
	* @param string Расположение конечного файла
	* @param integer Размер стороны миниатюры в px
	* @param string Тип конечного файла jpeg, png или gif. При null тип определяется из имени конечного файла. В противном случае из типа исходного
	* @param integer Качество конечного файла 1–100. Иначе используются рекомендуемые значения 75 для JPEG и 100 для PNG (при 100 PNG-файлы весят меньше, но возрастает нагрузка на сервер)
	* @param integer Ширина области обрезки в %/100
	* @param integer Высота области обрезки в %/100
	* @param integer X-координата левого верхнего угла области обрезки в %/100. При null область обрезки расположится по-центру
	* @param integer Y-координата левого верхнего угла области обрезки в %/100. При null область обрезки расположится по-центру
	* @return string Имя конечного файла или false в случае ошибки
	*/
	public function makeavatar($file_input, $file_output, $new_size = 100, $ext, $quality, $w_pct = 1, $h_pct = 1, $x_pct, $y_pct) {
	
		//Читаем данные из исходного изображения
		list($w, $h, $type) = getimagesize($file_input);
		if (!$w || !$h) return false; //Невозможно получить длину и ширину изображения
		
		switch ($type) {
			case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($file_input); break;
			case IMAGETYPE_PNG:  $image = imagecreatefrompng($file_input); break;
			case IMAGETYPE_GIF:  $image = imagecreatefromgif($file_input); break;
			default: echo 'Некорректный формат файла'.$type; return;
		}
		
		//Проверяем корректность координат области обрезки
		if ($w_pct <= 0 || $w_pct > 1) $w_pct = 1;
		if ($h_pct <= 0 || $h_pct > 1) $h_pct = 1;
		if (!is_numeric($x_pct) || $x_pct < 0 || $x_pct >= 1) $x_pct = (1 - $w_pct) / 2;
		if (!is_numeric($y_pct) || $y_pct < 0 || $y_pct >= 1) $y_pct = (1 - $h_pct) / 2;
		
		//Переводим проценты в пиксели
		$src_w = $w*$w_pct;
		$src_h = $h*$h_pct;
		$src_x = min($w*$x_pct, $w-$src_w);
		$src_y = min($h*$y_pct, $h-$src_h);

		//Уменьшаем область до квадрата
		if ($src_w > $src_h) {
			$src_x += ($src_w - $src_h) / 2;
			$src_w = $src_h;
		} else {
			$src_y += ($src_h - $src_w) / 2;
			$src_h = $src_w;
		}

		//Создаем миниатюру
		$new_image = imagecreatetruecolor($new_size, $new_size);
		imagecopyresampled($new_image, $image, 0, 0, $src_x, $src_y, $new_size, $new_size, $src_w, $src_h);
		
		//Сохраняем
		return self::convert($file_input, $file_output, $new_image, $ext, $quality);
	}
	
	/**
	* Сохранение изображения в JPEG, PNG или GIF.
	*
	* Примеры:
	* convert('img/flower.png', 'thumb/astra.png')                   //Скопирует img/flower.png под именем thumb/astra.png
	* convert('img/flower.png', 'thumb/astra.jpeg')                  //Скопирует с преобразованием в jpeg с качеством по умолчанию
	* convert('img/flower.png', 'thumb/',          null,   jpeg)     //Скопирует с преобразованием в jpeg под именем thumb/flower.jpeg
	* convert('img/flower.png',  null,             null,   jpeg, 70) //Преобразует в jpeg с качеством 70
	* convert('img/flower.png',  null,             $image, jpeg)     //Сохранит $image под именем img/flower.jpeg, удалив перед этим img/flower.png 
	* convert( null,            'thumb/astra.png', $image)           //Сохранит $image под именем 'thumb/astra.png'
	*
	* @param string Имя исходного изображения
	* @param string Имя конечного изображения
	* @param object Источник конечного изображения
	* @param string Тип конечного файла jpeg, png или gif. При null тип определяется из имени конечного файла. В противном случае из типа исходного
	* @param integer Качество конечного файла. 1–100 для JPEG (рекомендуется 75), 0—9 для PNG (рекомендуется 9, если сервер выдержит дополнительную нагрузку)
	* @return string Имя конечного файла или false в случае ошибки
	*/
	public function convert($file_input, $file_output, $image, $ext, $quality) {

		//Определяем тип конечного файла.
		//Если тип файла не указан, он будет взят из имени конечного файла, если же его нет, то из типа исходного
		list($w, $h, $type) = getimagesize($file_input);
		$file_input_ext = image_type_to_extension($type, false);
		$file_output_ext = pathinfo($file_output, PATHINFO_EXTENSION);
		
		if (!$ext && !$file_output_ext) {
			$ext = $file_input_ext;
		} elseif (!$ext) {
			$ext = $file_output_ext;
		}
		
		$ext = strtolower($ext);
		
		//Если источник изображения пуст, но требуется преобразование в другой формат, загружаем в источник исходный файл 
		if (!$image && $file_input_ext != $ext) {
			switch ($type) {
				case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($file_input); break;
				case IMAGETYPE_PNG:  $image = imagecreatefrompng($file_input); break;
				case IMAGETYPE_GIF:  $image = imagecreatefromgif($file_input); break;
				default: return false; //Файл некорректного типа
			}
		}
	
		//Если перемещение не требуется и в источнике есть изображение, удаляем старый файл
		if (!$file_output) {
			if ($image) unlink($file_input);
			$file_output = $file_input;
			$fixed = true;
		}
		
		//Определяем имя и путь для нового файла
		$path = pathinfo($file_output, PATHINFO_DIRNAME).'/';
		$name = pathinfo($file_output, PATHINFO_FILENAME).'.';

		//Если преобразование не требуется, просто копируем файл
		if (!$image) {
			if (!$fixed) {
				if (!copy($file_input, $path.$name.$ext)) return false; //Не удалось скопировать
			}
			return $name.$ext;
		}
		
		//Преобразуем и сохраняем
		switch ($ext) {
			case 'jpeg':
			case 'jpg':
				$ext = 'jpeg';
				if ($quality < 1 || $quality > 100) $quality = 75;
				if (!imagejpeg($image, $path.$name.$ext, $quality)) return false; //Не удалось сохранить в jpeg
				break;
				
			case 'gif':
				if (!imagegif($image, $path.$name.$ext)) return false; //Не удалось сохранить в gif
				break;
				
			default:
				$ext = 'png';
				if ($quality < 1 || $quality > 100) $quality = 9;
				$quality = round($quality / 11.111111);
				if (!imagepng($image, $path.$name.$ext, $quality)) return false; //Не удалось сохранить в png
		}
		imagedestroy($image);
		
		return $name.$ext;
	}
}
?>