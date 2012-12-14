/**
* autoGrowAndSaveArea
*
* @editor boolean             - Textarea show first (true)
* @toggle boolean or function - Textarea show after click and hide when blur (true). After blur callback start (function(event, value)).
*
* EXAMPLE. ONLY AUTOGROW
* $('textarea').autoGS()
*
* EXAMPLE. AUTOGROW AND CALLBACK
* $('textarea').autoGS({
*     'editor': false,
*     'toggle': function(e, value) {
*         $.ajax({
*             'url': 'save.php',
*             'data': {'id': e.target.id, 'title': value}
*         })
*     }
* })
*/

jQuery.fn.autoGS = function(options) {

    var self = this;
    
    // default settings
    self.options = $.extend({
        'editor': true,
        'toggle': false
    }, options || {});

    return self.each(function() {

        var $textarea = $(this)
            .css('overflow', 'hidden')
            .css('minHeight', this.rows+'em');
			
        // make mirror
        if ($textarea.next('.autogrow-textarea-mirror').length) {
            $mirror = $textarea.next('.autogrow-textarea-mirror').eq(0);
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
		
        //Send content to mirror every key up
        (this.onkeyup = function() {
            $mirror.html($textarea.val().replace(/\n/g, '<br/>') + '&nbsp').show(); //incorrect calculation of height if mirrow is hidden           
            if ($textarea.height() !== $mirror.height()) {
                $textarea.height($mirror.height());
            }
            $mirror.hide();
        })();
		
        // Use settings
        if (!self.options.editor) {
            $mirror.show();
            $textarea.hide();
        }
		
        if (self.options.toggle) {
            $mirror.on('click', function (e) {
                $textarea.show()[0].focus();
                $textarea[0].selectionStart = $textarea.val().length;
                $mirror.hide();               
            });

            $textarea.on('blur', function (e) {
                if(!self.options.editor || $textarea.val()){
                    $textarea.hide();
                    $mirror.show();                    
                    if (typeof self.options.toggle === 'function') {
                        self.options.toggle(e, $textarea.val());
                    } 
                }                            
            });
        }
    });
};
