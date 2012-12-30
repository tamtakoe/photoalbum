/**
* autoGrowAndSaveArea
*
* @editor boolean             - Textarea show first (true)
* @toggle boolean or function - Textarea show after click and hide when blur (true). After blur callback start (function(event)).
*
* EXAMPLE. ONLY AUTOGROW
* $('textarea').autoGS()
*
* EXAMPLE. AUTOGROW AND CALLBACK
* $('textarea').autoGS({
*     'editor': false,
*     'toggle': function(e) {
*         $.ajax({
*             'url': 'save.php',
*             'data': {'id': e.target.id, 'title': e.target.value}
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
        if (!$textarea.next('.autogrow-textarea-mirror').length) {
            var $mirror = $('<div class="autogrow-textarea-mirror"></div>').css({
                    'wordWrap':          'break-word',
                    'borderColor':       'transparent',
                    'fontFamily':        $textarea.css('font-family'),
                    'fontSize':          $textarea.css('font-size'),
                    'lineHeight':        $textarea.css('line-height'),
                    'borderTopStyle':    $textarea.css('borderTopStyle'), //for cross-browser compatibility don't use shorthand properties
                    'borderBottomStyle': $textarea.css('borderBottomStyle'),
                    'borderLeftStyle':   $textarea.css('borderLeftStyle'),
                    'borderRightStyle':  $textarea.css('borderRightStyle'),
                    'borderTopWidth':    $textarea.css('borderTopWidth'),
                    'borderBottomWidth': $textarea.css('borderBottomWidth'),
                    'borderLeftWidth':   $textarea.css('borderLeftWidth'),
                    'borderRightWidth':  $textarea.css('borderRightWidth'),
                    'marginTop':         $textarea.css('marginTop'),
                    'marginBottom':      $textarea.css('marginBottom'),
                    'margingLeft':       $textarea.css('margingLeft'),
                    'marginRight':       $textarea.css('marginRight'),
                    'paddingTop':        $textarea.css('paddingTop'),
                    'paddingBottom':     $textarea.css('paddingBottom'),
                    'paddingLeft':       $textarea.css('paddingLeft'),
                    'paddingRight':      $textarea.css('paddingRight')})
                .width($textarea.width())
                .hide()
                .insertAfter($textarea);
        } else {
            var $mirror = $textarea.next('.autogrow-textarea-mirror').eq(0);
        }
        
        // send content to mirror every key up
        (this.onkeyup = function() {
            $mirror.html($textarea.val().replace(/\n/g, '<br/>') + '&nbsp').show(); //incorrect calculation of height if mirrow is hidden           
            if ($textarea.height() !== $mirror.height()) {
                $textarea.height($mirror.height());
            }
            $mirror.hide();
        })();
		
        // use settings
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
                        self.options.toggle(e);
                    } 
                }                            
            });
        }
        
        return this
    });
};