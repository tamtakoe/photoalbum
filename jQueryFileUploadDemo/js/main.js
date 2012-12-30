$(document).ready(function() {

    var $thumbnailArea = $('#thumbnail-area'); // Место для показа миниатюр, а так же куда можно помещать файлы методом drag and drop   
    var $errorArea     = $('#error-area');
    var $fileInput     = $('#file-input'); // Стандарный input для файлов
    
	var actionUrl = 'action.php'

    // Сортировка миниатюр
	$thumbnailArea.sortable({
        'placeholder': 'span2 ui-state-highlight',
        'update': function () { saveSort() }
    }).on('click', 'textarea', function (e) { //for stupid Firefox and Opera
        e.target.focus();          
    });
    
    // Просмотр миниатюр
	$(".fancybox").attr('rel', 'gallery').fancybox();
       
    // Панель ошибок прикрепляется к верху окна, чтобы всегда быть видимой
    $errorArea.affix({ 'offset': { 'top': 120 } })
		
    // Шаблоны. Должны настраиваться вконце, т.к. многие плагины (sortable, fancybox, ...) работают некорректно с пустыми областями, даже если в них потом вставятся элементы
	var progressTpl  = $thumbnailArea.find('.progress-tpl').remove()[0].outerHTML
    var thumbnailTpl = $('.thumbnail-tpl').remove()[0].outerHTML
    var errorTpl     = $('.error-tpl').remove()[0].outerHTML
    
    // Инициализируем файлообменник
    var Album = new AlbumObj('action.php', {
        'progress': progressTpl,
        'thumbnail': thumbnailTpl,
        'error': errorTpl
    }, {
        '$thumbnail': $thumbnailArea,
        '$error': $errorArea
    })
	// Загружаем список элементов с сервера
    Album.getList()
	
    ////////////////////////////////////////////////////////////////////////////
    // Подключаем и настраиваем плагин загрузки
    $fileInput.fileupload({
        url: actionUrl,
        dataType: 'json',
        dropZone: $thumbnailArea,
        //read: function (e, data) {
        //    var img = data.files[0].preview // Вызывается, когда FileReader прочитал данные о файле
        //},
        //advanceDone: function (e, data) {
        //    var id =  result.id // Вызывается, когда предварительный запрос успешно завершился и вернул данные в result
        //    if (!id) return false // Загрузка файлов не происходит
        //},
        //advanceFail: function (e, data) {}, // Если предварительный запрос завершился неудачно
        add: function (e, data) {
            //data.url: 'advance.php' // Настройки предварительного запроса
            //data.type = 'get'       // если не указаны, что берутся из настроек основного запроса
            //data.formData = {
            //    'type': 'add' // Данные, передаваемые с предварительным запросом
            //} 

            $item = Album.newItem({
                'progress': true,
                'file': data.files[0],
                'prepend': true, //Элемент будет добавлен в начало списка
                'closeBt': function ($obj) {
                    if (typeof data.jqXHRitem !== 'undefined') data.jqXHRitem.abort();
                    Album.del($obj)
                }
            })
            data.$item = $item
            
            // Подгружаем миниатюру (нужно добавить в плагин загрузки)
            if (window.FileReader != null) {
                var reader = new FileReader();
                reader.onload = (function ($item) {
                    return function (event) {
                        if (event.target.result.indexOf('data:image') !== -1) {
                            Album.editItem($item, {
                                'img': event.target.result
                            })
                        }
                    };
                })($item);                        
                reader.readAsDataURL(data.files[0]);
            }
            
            // Отправляем предварительный запрос (нужно добавить в плагин загрузки)
            Album.add(data).done(function (result) {
                // Для будущего файла создана запись в БД и возвращен ее id
                Album.editItem(this.$item, {
                    'id': result.id
                });
                Album.saveSort()
                
                // Показываем иконку для файлов, не содержащих изображения
                var ext = this.files[0].name.split(".").pop()
                if ($.inArray(ext, ['jpeg', 'jpg', 'png', 'gif']) === -1) {
                    Album.editItem(this.$item, {
                        'img': result.previewsDir + '/' + ext + '.png'
                    })
                }                
                
                var errors = Album.validate(this.files[0], result)
                
                if (!errors) {
                    this.formData = {
                        'type': 'update',
                        'id': result.id
                    };
                    // Начинаем загрузку
                    this.jqXHRitem = this.submit();
                } else {
                    Album.showErrors(errors)
                    Album.del(this.$item)
                }
            }).fail(function () {
                Album.showErrors(['Невозможно загрузить файлы, проверьте соединение с интернетом'])
                Album.del(this.$item)
            })   
        },
        done: function (e, data) {
            if (typeof data.result.error === 'undefined') {
                Album.editItem(data.$item, {
                    'title': data.result.title,
                    'imgLink': data.result.fileName,
                    'img': data.result.previewName,
                    'progress': false,
                    'closeBt': function($obj) { Album.del($obj) }
                })
            } else {
                Album.showErrors([data.result.error])
                Album.del(data.$item)
            }
        },
        fail: function (e, data) {
            if (data.errorThrown !== 'abort') {
                Album.showErrors(['Невозможно загрузить файлы, нажмите F5 и попытайтесь еще раз'])
                Album.del(data.$item)
            }
        },
        progress: function (e, data) {
            Album.editItem(data.$item, {
                'progress': parseInt(data.loaded / data.total * 100, 10)
            })
        }
    });
    
    ////////////////////////////////////////////////////////////////////////////
    // Вспомогательные функции
    if ((window.File == null) || (window.FileList == null)) Album.showErrors(['Ваш браузер не поддерживает перетаскивание файлов'])
    if (window.FileReader == null) Album.showErrors(['Ваш браузер не поддерживает FileReader (миниатюрки не будут показаны)'])
    if (window.FormData == null) Album.showErrors(['Ваш браузер не поддерживает FormData (отправка с ручной формировкой запроса)'])
});

//Файлообменник
function AlbumObj (url, tpl, area) {   
    this.actionUrl = url
    this.tpl = tpl
    this.area = area
};
AlbumObj.prototype = {
    
    actionUrl: 'action.php',
    tpl: {'progress':null, 'thumbnail':null, 'error':null},
    area: {'$thumbnail':null, '$error':null},
    
    // Проверка выбранных файлов
    validate: function (file, permit) {
        var errors = []
        if ($.inArray(file.name.split(".").pop(), permit.allowedType) === -1) {
            errors.push(permit.errorBadType)
        }
        if (file.size > Math.min((+permit.maxSize ? permit.maxSize : Infinity), (+permit.maxSpace ? permit.maxSpace: Infinity) - permit.space)) {
            errors.push(permit.errorBigSize)
        }
        if (permit.maxNumberOfFiles && permit.quantity >= permit.maxNumberOfFiles) {
            errors.push(permit.errorMaxQuantity)
        }
        return errors.length ? errors : false
    },
    
    // Вывод ошибок на экран
    showErrors: function (errors) {
        var a = this
        if (!a.area.$error.find('.error-tpl').length) {
            var $error = $(a.tpl.error).appendTo(a.area.$error)
        } else {
            var $error = a.area.$error.find('.error-tpl').eq(0)
            //Удаляем дубликаты ошибок
            $error.find('span').each(function () {
                var errDel = $.inArray(this.innerHTML, errors)
                if (errDel > -1) errors.splice(errDel,1) }
            )
        }
        for (var i in errors) $error.append('<span>' + errors[i] + '</span><br/>')
    },
    
    // Добавление записи на сервере для нового файла, возврат ее id и списка ограничений
    add: function (data) {
        var a = this
        return $.ajax({
            'url': a.actionUrl,
            'type': 'post',
            'dataType': 'json',
            'data': {'type': 'add'},
            'context': data
        })
    },

    // Обновление данных на сервере
    update: function ($item, options) {
        var a = this
        $.ajax({
            'url': a.actionUrl,
            'type': 'post',
            'dataType': 'json',
            'data': $.extend({
                    'type': 'update',
                    'id': $item.data('id')
                }, options || {}),
            'error': function () {
                    a.showErrors(['Невозможно обновить данные, проверьте соединение с интернетом'])
                }
        })
    },
    
    // Удаление с сервера
    del: function ($item) {
        var a = this
        
        if ($item.data('id')) {
            var id = $item.data('id')
            
            $item.hide()
            $.ajax({
                'url': a.actionUrl,
                'type': 'post',
                'dataType': 'json',
                'data': {'type': 'delete', 'id': id},
                'success': function (data) {
                    $item.remove();
                    a.saveSort();
                },
                'error': function () {
                    a.showErrors(['Невозможно удалить с сервера, проверьте соединение с интернетом'])
                    $item.show()               
                    a.editItem($item, {
                        'id': id //Восстанавливаем id
                    })
                }
            })
            $item.data('id','') //Удаляем id, чтобы отметить начало процесса удаления
            
        } else {
            $item.remove()
        }
    },
    
    // Чтение списка элементов с сервера
    getList: function () {
        var a = this
        $.ajax({
            'url': a.actionUrl,
            'type': 'post',
            'dataType': 'json',
            'data': {type: 'getlist'},
            'success': function(data) {
                if (typeof data !== 'undefined') {
                    $.each(data, function (i, item) {
                        a.newItem({
                            'id': item.id,
                            'title': item.title,
                            'note': item.note,
                            'imgLink': item.fileName,
                            'img': item.previewName,
                            'closeBt': function ($obj) { a.del($obj) }
                        })
                    })
                }
            },		
            'error': function () {
                a.showErrors(['Невозможно вывести список элементов, проверьте соединение с интернетом'])
            }
        })
    },
    
    // Сохранение на сервере порядка расположения миниатюр
    saveSort: function () {
        var a = this
        try {
            var sortArr = []
            a.area.$thumbnail.children().each( function () {
                var id = $(this).data('id')
                if (!id) throw 'Невозможно определить позиции, т.к. некоторые элементы находятся в процессе добавления/удаления';
                sortArr.push(id)
            })
            $.ajax({
                'url': a.actionUrl,
                'type': 'post',
                'data': {type: 'savesort', 'sort': JSON.stringify(sortArr)},
                'error': function () {
                        a.showErrors(['Невозможно сохранить расположение, проверьте соединение с интернетом'])
                    }
                })
        } catch (e) {}
    },
    
    // Создание миниатюры
    newItem: function (options) {
        var a = this        
        var $item = a.editItem($(a.tpl.thumbnail), options || {})
        options['prepend'] ? $item.prependTo(a.area.$thumbnail) : $item.appendTo(a.area.$thumbnail)
        $item.find('textarea').autoGS({
                'editor': false,
                'toggle': function (e) { a.update($item, {
                            'title': e.target.value
                        })
                        a.editItem($item, {
                            'imgTitle': e.target.value
                        })
                    }
            });
        return $item
    },
    
    // Изменение свойств миниатюры
    editItem: function ($item, options) {
        var a = this
        var $imgLink = $item.find('.img-link'),
            $img = $imgLink.find('img')

        if (typeof options.title === 'string') {
            var $title = $item.find('textarea')
            $title.val(options.title)
            $imgLink.attr('title', options.title)
            $img.attr('alt', options.title)
        }
        if (typeof options.imgLink  === 'string') $imgLink.attr('href', options.imgLink)
        if (typeof options.imgTitle === 'string') $imgLink.attr('title', options.imgTitle)
        if (typeof options.img      === 'string') $img.attr('src', options.img)
        if (typeof options.id    !== 'undefined') $item.data('id', options.id)
        
        // Кнопка отмены/удаления
        if (typeof options.closeBt === 'function') {
            var $closeBt = $item.find('.close')
            $closeBt.off().click(function () { options.closeBt($item) })
        }
        
        // Прогресс-бар
        if (typeof options.progress === 'number') {
            $item.find('div.progress-tpl .bar').css('width', options.progress+'%');

        } else if (options.progress === true) {
            $(a.tpl.progress).appendTo($item.find('> div'));	
            
        } else if (options.progress === false) {
            $item.find('div.progress-tpl').remove();
        }
        
        return $item
    }
}


