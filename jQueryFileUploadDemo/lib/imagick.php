<?php
/**
* Класс для обработки изображений на сервере
* Использует библиотеку Imagick
*
* ВНИМАНИЕ! ФУНКЦИИ НЕ НАПИСАНЫ
*/
class Image {

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
		
		//return $name.$ext;
	}
}
/* Для замера производительности
	$start_time = microtime(true);

	//Действия

	$exec_time = microtime(true) - $start_time;
	file_put_contents('log.txt', $exec_time."\n", FILE_APPEND);
*/
?>