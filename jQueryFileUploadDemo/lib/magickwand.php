<?php
/**
* Класс для обработки изображений на сервере
* Использует библиотеку MagickWand
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

		//Читаем данные из исходного изображения
		list($w, $h) = getimagesize($file_input);
		if (!$w || !$h) return false; //Невозможно получить длину и ширину изображения
		
		$new_image = NewMagickWand();
		MagickReadImage($new_image, $file_input);
		
		//Уменьшаем изображение, если оно превысило допустимые размеры	
		if ($w > $max_w || $h > $max_h) {
			$new_image = MagickTransformImage($new_image, null, $max_w .'x'. $max_h);
			$w = MagickGetImageWidth($new_image);
			$h = MagickGetImageHeight($new_image);
			
		} elseif (!$copyright) {
			return self::convert($file_input, $file_output, null, $ext, $quality); //Просто копируем файл
		}
		
		//Ставим копирайт
		if ($copyright) {

			$file_copyright = 'copyright.png';			
			$watermark = NewMagickWand();		
			MagickReadImage($watermark, $file_copyright);
				
			$wc = MagickGetImageWidth($watermark);
			$hc = MagickGetImageHeight($watermark);
			MagickCompositeImage($new_image, $watermark, MW_OverCompositeOp, $w-$wc, $h-$hc);
			
			ClearMagickWand($watermark);
			DestroyMagickWand($watermark);			
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
	* @param integer Качество конечного файла. 1–100 для JPEG (рекомендуется 75), 0—9 для PNG (рекомендуется 9, если сервер выдержит дополнительную нагрузку)
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
		
		$new_image = NewMagickWand();
		MagickReadImage($new_image, $file_input);
		
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
		$new_image = MagickTransformImage($new_image, $src_w . 'x' . $src_h . '+' . $src_x . '+' . $src_y, $new_size . 'x' . $new_size);
		
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
	* @param integer Качество конечного файла 1–100. Иначе используются рекомендуемые значения 75 для JPEG и 100 для PNG (при 100 PNG-файлы весят меньше, но возрастает нагрузка на сервер)
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
			$image = NewMagickWand();
			if (!MagickReadImage($image, $file_input)) return false; //Файл некорректного типа
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
		MagickStripImage($image);//убираем комментарии
		switch ($ext) {
			case 'jpeg':
			case 'jpg':
				$ext = 'jpeg';
				if ($quality < 0 || $quality > 100) $quality = 75;
				MagickSetImageCompressionQuality($image, $quality);
				MagickSetImageFormat($image, 'jpeg');
				break;
				
			case 'gif':
				MagickSetImageFormat($image, 'gif');
				break;
				
			default:
				$ext = 'png';
				if ($quality < 0 || $quality > 99) $quality = 0;		
				MagickSetImageCompressionQuality($image, $quality);
				MagickSetImageFormat($image, 'png');		
		}

		if (!MagickWriteImage($image, $path.$name.$ext)) return false; //Не удалось сохранить файл
		
		ClearMagickWand($image);
		DestroyMagickWand($image);
		
		return $name.$ext;
	}
}
?>