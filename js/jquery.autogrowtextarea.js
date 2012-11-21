/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <jevin9@gmail.com> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return. Jevin O. Sewaruth
 * ----------------------------------------------------------------------------
 *
 * Autogrow Textarea Plugin Version v3.0
 * http://www.technoreply.com/autogrow-textarea-plugin-3-0
 * 
 * THIS PLUGIN IS DELIVERD ON A PAY WHAT YOU WHANT BASIS. IF THE PLUGIN WAS USEFUL TO YOU, PLEASE CONSIDER BUYING THE PLUGIN HERE :
 * https://sites.fastspring.com/technoreply/instant/autogrowtextareaplugin
 *
 * Date: October 15, 2012
 */

jQuery.fn.autoGrow = function(options) {

	// context
    var self = this;
		
	// default settings
	self.options = $.extend({
		editor: true,
		toggle: false
	}, options || {});
	
	return self.each(function() {

		var $textarea = $(this)
			.css('overflow','hidden')
			.css('minHeight',this.rows+'em');
			
		if ($textarea.next('.autogrow-textarea-mirror').length) {
			$mirror = $textarea.next('.autogrow-textarea-mirror').eq(0)
		} else {
			var $mirror = $('<div class="autogrow-textarea-mirror"></div>')
				.css('wordWrap', 'break-word')
				.css('borderColor', 'transparent')
				.css('fontFamily',        $textarea.css('font-family'))
				.css('fontSize',          $textarea.css('font-size'))
				.css('lineHeight',        $textarea.css('line-height'))
				.css('borderTopStyle',    $textarea.css('borderTopStyle')) //for cross-browser compatibility don't use shorthand properties
				.css('borderBottomStyle', $textarea.css('borderBottomStyle'))
				.css('borderLeftStyle',   $textarea.css('borderLeftStyle'))
				.css('borderRightStyle',  $textarea.css('borderRightStyle'))
				.css('borderTopWidth',    $textarea.css('borderTopWidth'))
				.css('borderBottomWidth', $textarea.css('borderBottomWidth'))
				.css('borderLeftWidth',   $textarea.css('borderLeftWidth'))
				.css('borderRightWidth',  $textarea.css('borderRightWidth'))
				.css('marginTop',         $textarea.css('marginTop'))
				.css('marginBottom',      $textarea.css('marginBottom'))
				.css('margingLeft',       $textarea.css('margingLeft'))
				.css('marginRight',       $textarea.css('marginRight'))
				.css('paddingTop',        $textarea.css('paddingTop'))
				.css('paddingBottom',     $textarea.css('paddingBottom'))
				.css('paddingLeft',       $textarea.css('paddingLeft'))
				.css('paddingRight',      $textarea.css('paddingRight'))
				.hide().insertAfter($textarea);
		}
			
		var sendContentToMirror = function () {
			$mirror.html($textarea.val().replace(/\n/g, '<br/>') + '&nbsp').show() //incorrect calculation of height if mirrow is hidden
			if ($textarea.height() !== $mirror.height())
				$textarea.height($mirror.height());
			$mirror.hide();
		}

		var growTextarea = function () {
			sendContentToMirror();
		}
		
				
		// Fire the event for text already present
		sendContentToMirror();
				
		// Bind the textarea's event
		this.onkeyup = growTextarea;
		
		// Use settings
		if (!self.options.editor) {
			$mirror.show()
			$textarea.hide()
		}
		
		if (self.options.toggle) {
			$mirror.on('click', function (e) {
				$textarea.show()[0].focus()
				$mirror.hide()				
			});
			//Должна быть колбек функция
			$textarea.on('blur', function (e) {
				if(!self.options.editor || $textarea.val()){
					$textarea.hide()
					$mirror.show()
					
					//----							
					$.ajax({
						'url': 'action.php?type=update&id='+$(e.target).parent().parent().parent().data('id')	,
						'type': 'post',
						'dataType': 'json',
						'data': {title: $textarea.val()},
						'success': function(data) {
							$(e.target).parent().parent().find('.img-link').attr('title',$textarea.val())
						}
					})
				}				
			});
		}
	});
};

// !function ($) {

  /* APPLY TO STANDARD DROPDOWN ELEMENTS
   * =================================== */

 // $(document)
 //   .on('click.dropdown.data-api touchstart.dropdown.data-api', clearMenus)
 //   .on('click.dropdown touchstart.dropdown.data-api', '.dropdown form', function (e) { e.stopPropagation() })
 //   .on('click.dropdown.data-api touchstart.dropdown.data-api'  , toggle, Dropdown.prototype.toggle)
 //   .on('keydown.dropdown.data-api touchstart.dropdown.data-api', toggle + ', [role=menu]' , Dropdown.prototype.keydown)

//}(window.jQuery);