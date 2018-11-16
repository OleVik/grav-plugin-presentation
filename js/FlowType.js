/*jshint laxcomma:true */
/*
* FlowType.JS without jQuery.
* Ported by Christian Dannie Storgaard. Based on:
*
* FlowType.JS 1.0
* Copyright (c) 2013, Simple Focus http://simplefocus.com/
*
* FlowType.JS by Simple Focus (http://simplefocus.com/)
* is licensed under the MIT License. Read a copy of the
* license in the LICENSE.txt file or at
* http://choosealicense.com/licenses/mit
*
* Thanks to Giovanni Difeterici (http://www.gdifeterici.com/)
*/

(function(global) {
    
    var addEvent = null;
    if ( document.addEventListener ) {
        addEvent = function(element, eventName, callback) {
            return element.addEventListener( eventName, callback, false );
        };
    } else if ( document.attachEvent ) {
        addEvent = function(element, eventName, callback) {
            element.attachEvent( 'on'+eventName, callback );
        };
    } else {
        addEvent = function(element, eventName, callback) {
            element['on'+eventName] = callback;
        };
    }
    
    global.flowtype = function(element, options) {
        
        // Establish default settings/variables
        // ====================================
        options.maximum   = options.maximum   || 9999;
        options.minimum   = options.minimum   || 1;
        options.maxFont   = options.maxFont   || 9999;
        options.minFont   = options.minFont   || 1;
        options.fontRatio = options.fontRatio || 35;
        options.lineRatio = options.lineRatio || 1.45;
            
        // Do the magic math
        // =================
        changes = function(el) {
            var  elw = el.clientWidth
                ,width = elw > options.maximum ? options.maximum : elw < options.minimum ? options.minimum : elw
                ,fontBase = width / options.fontRatio
                ,fontSize = fontBase > options.maxFont ? options.maxFont : fontBase < options.minFont ? options.minFont : fontBase
            ;
                
            el.style.fontSize   = fontSize + 'px';
            el.style.lineHeight = fontSize * options.lineRatio + 'px';
        };
        
        // Make the magic visible
        // ======================
        element.updateFlowType = function(){changes(element);};
        
        // Make changes upon resize
        addEvent( global, 'resize', element.updateFlowType );
            
        // Set changes on load
        element.updateFlowType();
        
        // addEvent visiblity for demo purposes - REMOVE IN PRODUCTION
        global.flowtype.addEvent = addEvent;
    };
}(window));