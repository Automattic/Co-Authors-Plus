/*
 * jQuery ContentEditable AC Autocompletion Plugin
 * 
 * A signifigant fork of the Original AutoComplete by Jörn Zaeffererrequest
 * 
 * Copyright (c) 2009 Jörn Zaeffererrequest and Aaron Raddon
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id: jquery.autocomplete.js 15 2009-08-22 10:30:27Z joern.zaefferer $
 */

(function($) {
  
  if (window['log'] == undefined){
    window['log'] = {
      toggle: function() {},
      move: function() {},
      resize: function() {},
      clear: function() {},
      debug: function() {},
      info: function() {},
      warn: function() {},
      error: function() {},
      profile: function() {}
    };
  }

$.fn.extend({
  autocomplete: function(urlOrData, options) {
    var isUrl = typeof urlOrData == "string";
    options = $.extend({
      formatEditableResult: function(row) { return '<a contenteditable="false" href="#" tabindex="-1" >@' + row[options.jsonterm] + '</a>&nbsp;';},
      formatResult: function(row) { return row[options.jsonterm];},
      formatItem: function(row) { return row[options.jsonterm]; }
      }, $.Autocompleter.defaults, {
        url: isUrl ? urlOrData : null,
        data: isUrl ? null : urlOrData,
        delay: isUrl ? $.Autocompleter.defaults.delay : 10,
        max: options && !options.scroll ? 10 : 150
    }, options);
    
    // if highlight is set to false, replace it with a do-nothing function
    options.highlight = options.highlight || function(value) { return value; };
    
    // if the formatMatch option is not specified, then use formatItem for backwards compatibility
    options.formatMatch = options.formatMatch || options.formatItem;
    
    return this.each(function() {
      new $.Autocompleter(this, options);
    });
  },
  result: function(handler) {
    return this.bind("result", handler);
  },
  search: function(handler) {
    return this.trigger("search", [handler]);
  },
  flushCache: function() {
    return this.trigger("flushCache");
  },
  setOptions: function(options){
    return this.trigger("setOptions", [options]);
  },
  unautocomplete: function() {
    return this.trigger("unautocomplete");
  },
  notfound: function() {
    return this.trigger("ace_notfound");
  }
});

$.Autocompleter = function(input, options) {

  var KEY = {
    UP: 38,
    DOWN: 40,
    DEL: 46,
    TAB: 9,
    RETURN: 13,
    SHIFT: 16,
    ESC: 27,
    COMMA: 188,
    PAGEUP: 33,
    PAGEDOWN: 34,
    BACKSPACE: 8,
    SPACE: 32,
    LEFT: 37,
    RIGHT: 39,
    AT: 50, 
    POUND:34,  
    DOLLAR:52,
    SEMIC:59
  };
  // Create $ object for input element
  var $input = $(input).attr("autocomplete", "off").addClass(options.inputClass);
  
  var timeout;
  var previousValue = "";
  var cache = $.Autocompleter.Cache(options);
  var hasFocus = 0;
  var cursorStart = 0;
  var editable = null;
  var editableSelection, editableRange;
  var lastKeyPressCode;
  var preKeyPressCode;
  var prePreKeyPressCode;
  var hotKey = "@";
  var autocActive = options.hotkeymode ? false : true;
  var config = {
    mouseDownOnSelect: false
  };
  var select = $.Autocompleter.Select(options, input, selectCurrent, config);
  
  var blockSubmit;
  
  if (options.hotkeymode) {
    options.multiple = false;
  }
  
  // prevent form submit in opera when selecting with return key
  $.browser.opera && $(input.form).bind("submit.autocomplete", function() {
    if (blockSubmit) {
      blockSubmit = false;
      return false;
    }
  });
  if (input.value == undefined && options.width < 1){
    editable = $(input)[0];
    options.width = $(input).parent().parent().width();
    options.left = $(input).parent().parent().offset().left;
  }
  
  // only opera doesn't trigger keydown multiple times while pressed, others don't work with keypress at all
  $input.bind(($.browser.opera ? "keypress" : "keydown") + ".autocomplete", function(event) {
    // a keypress means the input has focus
    // avoids issue where input had focus before the autocomplete was applied
    hasFocus = 1;
    var k=event.keyCode || event.which; // keyCode == 0 in Gecko/FF on keypress
    //log.debug("keypress: " + k);
    if (k == KEY.RETURN){
      log.debug("in k = " + k + ' options.supressReturn =' + options.supressReturn)
    }
    // track history, probably should push/pop an array
    prePreKeyPressCode = preKeyPressCode;
    preKeyPressCode = lastKeyPressCode;
    lastKeyPressCode = k;
    if (options.hotkeymode && autocActive === false){
      if (k == KEY.AT && event.shiftKey && (KEY.SPACE == prePreKeyPressCode || prePreKeyPressCode == undefined)){
        autocActive = true;
        log.debug("AutoComplete now active in Hotkey mode")
        clearTimeout(timeout);
        timeout = setTimeout(onChange, options.delay);
      }
      return;
    } 
    switch(k) {
    
      case KEY.UP:
        event.preventDefault();
        if ( select.visible() ) {
          select.prev();
        } else {
          onChange(0, true);
        }
        break;
        
      case KEY.DOWN:
        event.preventDefault();
        if ( select.visible() ) {
          select.next();
        } else {
          onChange(0, true);
        }
        break;
        
      case KEY.PAGEUP:
        event.preventDefault();
        if ( select.visible() ) {
          select.pageUp();
        } else {
          onChange(0, true);
        }
        break;
        
      case KEY.PAGEDOWN:
        event.preventDefault();
        if ( select.visible() ) {
          select.pageDown();
        } else {
          onChange(0, true);
        }
        break;
      
      // matches also semicolon
      case options.multiple && $.trim(options.multipleSeparator) == "," && KEY.COMMA:
      case KEY.TAB:
      case KEY.RETURN:
      case KEY.RIGHT:
      case KEY.SEMIC:
        if (k == KEY.RETURN)
          event.preventDefault();
        if( selectCurrent() ) {
          // stop default to prevent a form submit, Opera needs special handling
          log.debug("in k = " + k + ' options.supressReturn =' + options.supressReturn)
          if (k == KEY.RETURN && options.supressReturn)
            event.preventDefault();
          blockSubmit = true;
          hideResultsNow();
          if (options.hotkeymode || options.multiple === true){
            log.debug("in return false")
            return false;
          }
        } else {
          log.debug("nada found?  trigger notfound?");
          $input.trigger("ace_notfound", {});
          hideResultsNow();
          if (k == KEY.RETURN && options.supressReturn)
            event.preventDefault();
        }
        if (k == KEY.TAB || k == KEY.RETURN){
          log.debug("was tab")
        }
        break;
        
      case KEY.ESC:
        select.hide();
        break;
        
      default:
        if( autocActive === true ) {
          clearTimeout(timeout);
          timeout = setTimeout(onChange, options.delay);
        }
        break;
    }
  }).focus(function(){
    // track whether the field has focus, we shouldn't process any
    // results if the field no longer has focus
    log.debug("has focus")
    hasFocus++;
    if( autocActive === true && options.hotkeymode) {
      onChange(0, true);
    } else if (!options.hotkeymode) {
      if (!autocActive)
        autocActive = true;
      onChange(0, true);
    }
  }).blur(function() {
    hasFocus = 0;
    if (!config.mouseDownOnSelect) {
      hideResults();
    }
  }).click(function() {
    // show select when clicking in a focused field
    if ( hasFocus++ > 1 && !select.visible() ) {
      onChange(0, true);
    } else {
      log.debug('hasfocus = ' + hasFocus);
      log.debug('hasfocus = ' + (hasFocus > 1));
      log.debug('visible()' + (!select.visible()))
      log.debug('visible()' + select.visible())
    }
  }).bind('result',function(){
    hasFocus = 0;
    hideResults();
  }).bind("search", function() {
    // TODO why not just specifying both arguments?
    var fn = (arguments.length > 1) ? arguments[1] : null;
    function findValueCallback(q, data) {
      var result;
      if( data && data.length ) {
        for (var i=0; i < data.length; i++) {
          if( data[i].result.toLowerCase() == q.toLowerCase() ) {
            result = data[i];
            break;
          }
        }
      }
      if( typeof fn == "function" ) {
        fn(result);
      } else {
        $input.trigger("result", result && [result.data, result.value]);
      }
    }
    $.each(trimWords(smartVal()), function(i, value) {
      request(value, findValueCallback, findValueCallback);
    });
  }).bind("flushCache", function() {
    cache.flush();
  }).bind("setOptions", function() {
    $.extend(options, arguments[1]);
    // if we've updated the data, repopulate
    if ( "data" in arguments[1] )
      cache.populate();
  }).bind("unautocomplete", function() {
    select.unbind();
    $input.unbind();
    $(input.form).unbind(".autocomplete");
  });
  
  
  function selectCurrent() {
    var selected = select.selected();
    if( !selected )
      return false;
    
    var v = selected.result;
    previousValue = v;
    
    if ( options.multiple ) {
      var words = trimWords(smartVal());
      if ( words.length > 1 ) {
        var seperator = options.multipleSeparator.length;
        var cursorAt = $(input).selection().start;
        var wordAt, progress = 0;
        $.each(words, function(i, word) {
          progress += word.length;
          if (cursorAt <= progress) {
            wordAt = i;
            return false;
          }
          progress += seperator;
        });
        words[wordAt] = v;
        // TODO this should set the cursor to the right position, but it gets overriden somewhere
        //$.Autocompleter.Selection(input, progress + seperator, progress + seperator);
        v = words.join( options.multipleSeparator );
      }
      v += options.multipleSeparator;
      
    } else if ( options.hotkeymode) {
      autocActive = false;
      var cur = smartVal();
      cur = cur.substring(0,cursorStart -1);
      log.info("found Data! " + selected.data[0] + ' ' + selected.data[1])
      v = cur + options.formatResult(selected.data);
      if (input.value == undefined) {
        v = v + '<span id="cursorStart">—</span>';
      }
      
      //storeContentEditableCursor();
    }
    smartVal(v);
    hideResultsNow();
    $input.trigger("result", [selected.data, selected.value]);
    if ( options.hotkeymode && input.value == undefined)
      editableReturnCursor();
    return true;
  }
  
  function setupContentEditable(){
    if(editableSelection.getRangeAt !== undefined) {
        //ok
    // Get range (Safari 2)
    } else if(
        document.createRange &&
        editableSelection.anchorNode &&
        editableSelection.anchorOffset &&
        editableSelection.focusNode &&
        editableSelection.focusOffset
    ) {
        var temp = '';
    } 
  }
  
  function storeContentEditableCursor() {
      // editable is the contentEditable div
      
      // Don't capture selection outside editable region
      var isOrContainsAnchor = false,
          isOrContainsFocus = false,
          sel = window.getSelection(),
          parentAnchor = sel.anchorNode,
          parentFocus = sel.focusNode;
      
      while(parentAnchor && parentAnchor != document.documentElement) {
          if(parentAnchor == editable) {
              isOrContainsAnchor = true;
          }
          parentAnchor = parentAnchor.parentNode;
      }
      
      while(parentFocus && parentFocus != document.documentElement) {
          if(parentFocus == editable) {
              isOrContainsFocus = true;
          }
          parentFocus = parentFocus.parentNode;
      }
      
      if(!isOrContainsAnchor || !isOrContainsFocus) {
          return;
      }
      
      editableSelection = window.getSelection();
      
      //editableSelection, editableRange;
      // Get range (standards)
      if(editableSelection.getRangeAt !== undefined) {
          editableRange = editableSelection.getRangeAt(0);
          //log.debug("in contenteditable keyup")
      // Get range (Safari 2)
      } else if(
          document.createRange &&
          editableSelection.anchorNode &&
          editableSelection.anchorOffset &&
          editableSelection.focusNode &&
          editableSelection.focusOffset
      ) {
          editableRange = document.createRange();
          editableRange.setStart(selection.anchorNode, editableSelection.anchorOffset);
          editableRange.setEnd(selection.focusNode, editableSelection.focusOffset);
      } else {
          // Failure here, not handled by the rest of the script.
          // Probably IE or some older browser
          // TODO:  gracefully degrate to textarea?
      }
      
      var cursorStartSpan = document.createElement('span'),
          collapsed = !!editableRange.collapsed;
        
      cursorStartSpan.id = 'cursorStart';
      cursorStartSpan.appendChild(document.createTextNode('—'));
      
      // Insert beginning cursor marker
      editableRange.insertNode(cursorStartSpan);
      
      // Insert end cursor marker if any text is selected
      if(!collapsed) {
          var cursorEnd = document.createElement('span');
          cursorEnd.id = 'cursorEnd';
          editableRange.collapse();
          editableRange.insertNode(cursorEnd);
      }
  }
  
  function editableReturnCursor(){
      // Slight delay will avoid the initial selection
      // (at start or of contents depending on browser) being mistaken
      setTimeout(function() {
          var cursorStartSpan = document.getElementById('cursorStart'),
              cursorEnd = document.getElementById('cursorEnd');
          
          if (window.getSelection) {        // Firefox, Safari, Opera
            editableSelection = window.getSelection();
            var range = document.createRange();
            range.selectNode(cursorStartSpan);
            // Select range
            editableSelection.removeAllRanges();
            editableSelection.addRange(range);
            // Delete cursor marker
            document.execCommand('delete', false, null);
          } else {
              if (document.body.createTextRange) {    // Internet Explorer
                  var rangeToSelect = document.body.createTextRange();
                  rangeToSelect.moveToElementText(cursorStartSpan);
                  rangeToSelect.select();
                  document.selection.clear();
              }
          }


          // Register selection again
          //captureSelection();
      }, 10);
  }
  
  function onChange(crap, skipPrevCheck) {
    if( lastKeyPressCode == KEY.DEL ) {
      select.hide();
      return;
    }
    
    var currentValue = smartVal();
    if ( !skipPrevCheck && currentValue == previousValue ){
      return;
    }
    
    currentValue = findSearchTerm(currentValue);
    previousValue = currentValue;
    log.debug("onChange curVal = " + currentValue)
    
    if ( currentValue.length >= options.minChars) {
      $input.addClass(options.loadingClass);
      if (!options.matchCase)
        currentValue = currentValue.toLowerCase();
      request(currentValue, receiveData, hideResultsNow);
    } else {
      log.debug("in else")
      stopLoading();
      if (options.startmsg != null) {
        select.emptyList();
        select.display({}, null);
        select.show();
      } else {
        select.hide();
      }
    }
  };
  
  function trimWords(value) {
    if (!value)
      return [""];
    if (!options.multiple && !options.hotkeymode) {
      return [$.trim(value)];
    } else if (options.multiple) {
      return $.map(value.split(options.multipleSeparator), function(word) {
        return $.trim(value).length ? $.trim(word) : null;
      });
    } else if (options.hotkeymode) {
      log.error("should not get here, remove this section, not user")
      return fake.raise.error;
    }
  }
  
  // find word currently being searched for, anything after
  // previous results, or non query text
  function findSearchTerm(value) {
    if ( !options.multiple && !options.hotkeymode) {
      return value;
    } else if (options.multiple) {
      var words = trimWords(value);
      if (words.length == 1) 
        return words[0];
      var cursorAt = $(input).selection().start;
      if (cursorAt == value.length) {
        words = trimWords(value)
      } else {
        words = trimWords(value.replace(value.substring(cursorAt), ""));
      }
      return words[words.length - 1];
    // hotkeymode
    } else {
      if (value && value.lastIndexOf('@') >= 0){
        cursorStart = value.lastIndexOf('@') + 1;
      } else {
        log.error("found no @ in hotkeymode?" + value)
        cursorStart = value.length + 2;
      }
      value = value.substring(cursorStart);
      log.info("findSearchTerm: cursorStart,val " + cursorStart + ', ' + value)
      return value; //.trim();
    }
  }
  
  // fills in the input box w/the first match (assumed to be the best match)
  // q: the term entered
  // sValue: the first matching result
  function autoFill(q, sValue){
    // autofill in the complete box w/the first match as long as the user hasn't entered in more data
    // if the last user key pressed was backspace, don't autofill
    if( options.autoFill && (findSearchTerm(smartVal()).toLowerCase() == q.toLowerCase()) && lastKeyPressCode != KEY.BACKSPACE ) {
      // fill in the value (keep the case the user has typed)
      smartVal(smartVal() + sValue.substring(findSearchTerm(previousValue).length));
      // select the portion of the value not typed by the user (so the next character will erase)
      $(input).selection(previousValue.length, previousValue.length + sValue.length);
    }
  };
  
  // replace .val() with something to handle content-editable fields
  function smartVal(val) {
    var field = $input[0];
    //if form input field, vs contenteditable div
    if (field.value != undefined){
      if (val != undefined) {
        return $input.val(val);
      } else {
        return $input.val();
      }
    } else {
      if (val != undefined) {
        return $input.html(val);
      } else {
        val = $input.html();
        if (val != undefined && val.length != undefined && val.length > 0) {
          val = $.trim(val);
        } else {
          val = ''
        }
        var endval = '', li = 0;
        // replace <br> or &nbsp; BUT, only in last bit (10) or past hotkey
        // contenteditable appends <br> to end a lot, or the last space as &nbsp;
        if (val.lastIndexOf('@') > 0 || val.length > 10){
          li = val.lastIndexOf('@') > 0 ? val.lastIndexOf('@') + 1 : val.length - 10;
          endval = val.substring(li);
          val = val.substring(0,val.length - endval.length);
          //log.debug('used substring @ ' + val)
        } else {
          endval = val;
          val = '';
        }
        // only clean up end of markup, where they are doing insertion?
        endval = endval.replace("<br>",'').replace("<br/>",'').replace("&nbsp;",' ');
        val = val + endval;
        //log.debug("smartVal endval = " + escape(endval) + ' val= ' + escape(val))
        return val;
      }
    }
  }
  
  function hideResults() {
    clearTimeout(timeout);
    timeout = setTimeout(hideResultsNow, 200);
  };

  function hideResultsNow() {
    var wasVisible = select.visible();
    autocActive = false;
    select.hide();
    clearTimeout(timeout);
    stopLoading();
    if (options.mustMatch) {
      // call search and run callback
      $input.search(
        function (result){
          // if no value found, clear the input box
          if( !result ) {
            if (options.multiple) {
              var words = trimWords(smartVal()).slice(0, -1);
              smartVal( words.join(options.multipleSeparator) + (words.length ? options.multipleSeparator : "") );
            } else if (options.hotkeymode) {
              smartVal( "" );
              $input.trigger("result", null);
            } else {
              smartVal( "" );
              $input.trigger("result", null);
            }
          }
        }
      );
    }
  };

  function receiveData(q, data) {
    if ( data && data.length && hasFocus ) {
      stopLoading();
      select.display(data, q);
      autoFill(q, data[0].value);
      select.show();
    } else {
      hideResultsNow();
    }
  };

  function request(term, success, failure) {
    log.debug("in request term = " + term)
    if (!options.matchCase)
      term = term.toLowerCase();
    
    log.debug("in request term2 = " + term)
    var data = cache.load(term);
    log.debug("in request term3 = " + data)
    // recieve the cached data
    if (data && data.length) {
      log.debug('found cache, not loading ' + term)
      success(term, data);
      return;
    // if an AJAX url has been supplied, try loading the data now
    } else if( (typeof options.url == "string") && (options.url.length > 0) ){
      
      var extraParams = {
        timestamp: +new Date()
      };
      $.each(options.extraParams, function(key, param) {
        extraParams[key] = typeof param == "function" ? param() : param;
      });
      var found = false;
      log.debug("calling ajax, term = " + term, ' dataType = ' + options.dataType)
      $.ajax({
        // try to leverage ajaxQueue plugin to abort previous requests
        mode: "abort",
        // limit abortion to this input
        port: "autocomplete" + input.name,
        dataType: options.dataType,
        url: options.url,
        data: $.extend({
          q: term,
          limit: options.max
        }, extraParams),
        success: function(data) {
          if (data.length > 0){
            found = true;
            var parsed = options.parse && options.parse(data) || parse_json(data);
            cache.add(term, parsed);
            success(term, parsed);
          }
        }
      });
      if (found === true){
        log.debug("returning?")
        return;
      }
    } 
    log.debug("after load in request?")
    select.emptyList();
    if (options.noresultsmsg != null) {
      stopLoading();
      select.display({}, term);
      select.show();
    } else {
      // if we have a failure, we need to empty the list -- this prevents the the [TAB] key from selecting the last successful match
      failure(term);
    }
  };
  
  function parse_json(json){
    var parsed = [];
    log.debug("parsing json, len=" + json.length)
    for ( var i = 0, ol = json.length; i < ol; i++ ) {
        parsed[i] = {
          data: json[i],
          value: json[i][options.jsonterm],
          result: options.formatResult && options.formatResult(json[i]) || json[i][options.jsonterm]
        };
    }
    return parsed;
  }

  function stopLoading() {
    $input.removeClass(options.loadingClass);
  };

};

$.Autocompleter.defaults = {
  inputClass: "ac_input",
  resultsClass: "ac_results",
  loadingClass: "ac_loading",
  minChars: 1,
  live:false,
  startmsg: 'Start typing to get options...',
  msgonenter:false,
  endmsg: null,
  noresultsmsg: null,
  delay: 400,
  matchCase: false,
  matchSubset: true,
  matchContains: false,
  cacheLength: 10,
  supressReturn: false,
  max: 100,
  mustMatch: false,
  extraParams: {},
  jsonterm: 0, // 'name' ??
  formatResult: null,//placeholder, function in above options
  formatItem: null, //placeholder, function in above options
  dataType: 'json',
  selectFirst: true,
  formatMatch: null,
  autoFill: false,
  width: 0,
  left: 0,
  multiple: false,
  multipleSeparator: ", ",
  hotkeymode: false,
  highlight: function(value, term) {
    return value.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
  },
  scroll: true,
  scrollHeight: 180
};

$.Autocompleter.Cache = function(options) {

  var data = {};
  var length = 0;
  
  function matchSubset(s, sub) {
    if (!options.matchCase) 
      s = s.toLowerCase();
    var i = s.indexOf(sub);
    if (options.matchContains == "word"){
      i = s.toLowerCase().search("\\b" + sub.toLowerCase());
    }
    if (i == -1) return false;
    return i == 0 || options.matchContains;
  };
  
  function add(q, value) {
    if (length > options.cacheLength){
      flush();
    }
    if (!data[q]){ 
      length++;
    }
    data[q] = value;
  }
  
  function populate(){
    if( !options.data ) return false;
    // track the matches
    var stMatchSets = {},
      nullData = 0;

    // no url was specified, we need to adjust the cache length to make sure it fits the local data store
    if( !options.url ) options.cacheLength = 1;
    
    // track all options for minChars = 0
    stMatchSets[""] = [];
    
    // loop through the array and create a lookup structure
    for ( var i = 0, ol = options.data.length; i < ol; i++ ) {
      var rawValue = options.data[i];
      // if rawValue is a string, make an array otherwise just reference the array
      rawValue = (typeof rawValue == "string") ? [rawValue] : rawValue;
      
      var value = options.formatMatch(rawValue, i+1, options.data.length);
      if ( value === false || value === undefined)
        continue;
      
      var firstChar = value.charAt(0).toLowerCase();
      // if no lookup array for this character exists, look it up now
      if( !stMatchSets[firstChar] ) 
        stMatchSets[firstChar] = [];

      // if the match is a string
      var row = {
        value: value,
        data: rawValue,
        result: options.formatResult && options.formatResult(rawValue) || value
      };
      
      // push the current match into the set list
      stMatchSets[firstChar].push(row);

      // keep track of minChars zero items
      if ( nullData++ < options.max ) {
        stMatchSets[""].push(row);
      }
    };

    // add the data items to the cache
    $.each(stMatchSets, function(i, value) {
      // increase the cache size
      options.cacheLength++;
      // add to the cache
      add(i, value);
    });
  }
  
  // populate any existing data
  setTimeout(populate, 25);
  
  function flush(){
    data = {};
    length = 0;
  }
  
  return {
    flush: flush,
    add: add,
    populate: populate,
    load: function(q) {
      if (!options.cacheLength || !length)
        return null;
      /* 
       * if dealing w/local data and matchContains than we must make sure
       * to loop through all the data collections looking for matches
       */
      if( !options.url && options.matchContains ){
        // track all matches
        var csub = [];
        // loop through all the data grids for matches
        for( var k in data ){
          // don't search through the stMatchSets[""] (minChars: 0) cache
          // this prevents duplicates
          if( k.length > 0 ){
            var c = data[k];
            //log.debug("629 " + c + ' ' + k + ' ' + q)
            $.each(c, function(i, x) {
              // if we've got a match, add it to the array
              if (matchSubset(x.value, q)) {
                csub.push(x);
              }
            });
          }
        }       
        return csub;
      } else 
      // if the exact item exists, use it
      if (data[q]){
        return data[q];
      } else
      if (options.matchSubset) {
        for (var i = q.length - 1; i >= options.minChars; i--) {
          var c = data[q.substr(0, i)];
          if (c) {
            var csub = [];
            $.each(c, function(i, x) {
              if (matchSubset(x.value, q)) {
                csub[csub.length] = x;
              }
            });
            return csub;
          }
        }
      }
      return null;
    }
  };
};

$.Autocompleter.Select = function (options, input, select, config) {
  var CLASSES = {
    ACTIVE: "ac_over"
  };
  
  var listItems,
    active = -1,
    data,
    term = "",
    needsInit = true,
    element,
    list;
  
  // Create results
  function init() {
    if (!needsInit)
      return;
    element = $("<div/>")
    .hide()
    .addClass(options.resultsClass)
    .css("position", "absolute")
    .appendTo(document.body);
    list = $("<ul/>").appendTo(element).css({
      width: typeof options.width == "string" || options.width > 0 ? options.width -2 : $(input).width() -2
    }).mouseover( function(event) {
      if(target(event).nodeName && target(event).nodeName.toUpperCase() == 'LI') {
              active = $("li", list).removeClass(CLASSES.ACTIVE).index(target(event));
          $(target(event)).addClass(CLASSES.ACTIVE);            
          }
    }).click(function(event) {
      $(target(event)).addClass(CLASSES.ACTIVE);
      select();
      // TODO provide option to avoid setting focus again after selection? useful for cleanup-on-focus
      input.focus();
      return false;
    }).mousedown(function() {
      config.mouseDownOnSelect = true;
    }).mouseup(function() {
      config.mouseDownOnSelect = false;
    });
    
    if( options.width > 0 )
      element.css("width", options.width);
      
    needsInit = false;
  } 
  
  function target(event) {
    var element = event.target;
    while(element && element.tagName != "LI")
      element = element.parentNode;
    // more fun with IE, sometimes event.target is empty, just ignore it then
    if(!element)
      return [];
    return element;
  }

  function moveSelect(step) {
    listItems.slice(active, active + 1).removeClass(CLASSES.ACTIVE);
    movePosition(step);
        var activeItem = listItems.slice(active, active + 1).addClass(CLASSES.ACTIVE);
        if(options.scroll) {
            var offset = 0;
            listItems.slice(0, active).each(function() {
        offset += this.offsetHeight;
      });
            if((offset + activeItem[0].offsetHeight - list.scrollTop()) > list[0].clientHeight) {
                list.scrollTop(offset + activeItem[0].offsetHeight - list.innerHeight());
            } else if(offset < list.scrollTop()) {
                list.scrollTop(offset);
            }
        }
  };
  
  function movePosition(step) {
    active += step;
    if (active < 0) {
      active = listItems.size() - 1;
    } else if (active >= listItems.size()) {
      active = 0;
    }
  }
  
  function limitNumberOfItems(available) {
    return options.max && options.max < available
      ? options.max
      : available;
  }
  
  function fillList(q) {
    list.empty();
    var max = limitNumberOfItems(data.length);
    for (var i=0; i < max; i++) {
      if (!data[i])
        continue;
      var formatted = options.formatItem(data[i].data, i+1, max, data[i].value, term);
      if ( formatted === false )
        continue;
      var li = $("<li/>").html( options.highlight(formatted, term) ).addClass(i%2 == 0 ? "ac_even" : "ac_odd").appendTo(list)[0];
      $.data(li, "ac_data", data[i]);
    }
    listItems = list.find("li");
    if ( options.selectFirst ) {
      listItems.slice(0, 1).addClass(CLASSES.ACTIVE);
      active = 0;
    }
    if (options.startmsg && (max == 0 || max == undefined) && q == null) {
      var li = $("<li/>").html( options.startmsg )
        .click(function(){
          $(input).trigger("ac_start_msg_click");
        }).addClass("start_msg").appendTo(list)[0];
      $.data(li, "start_msg", data[max + 1]);
    }
    if (options.noresultsmsg && (max == 0 || max == undefined) && q != null) {
      var val_noresult = options.noresultsmsg;
      if( typeof options.noresultsmsg == "function" ) {
        val_noresult = fn(q);
      } else if (options.noresultsmsg.indexOf('{q}' > 0)) {
        val_noresult = val_noresult.replace('{q}',q);
      }
      var li = $("<li/>").html( val_noresult )
        .click(function(){
          $(input).trigger("ac_noresult_click",q);
        }).addClass("noresult_msg").appendTo(list)[0];
      $.data(li, "noresult_msg", data[max + 1]);
    }
    if (options.endmsg && (max != undefined && max > 0)) {
      var li = $("<li/>").html( options.endmsg )
        .click(function(){
          $(input).trigger("ac_end_message_click",q);
        }).addClass("end_msg").appendTo(list)[0];
      $.data(li, "end_msg", data[max + 1]);
    }
    // apply bgiframe if available
    if ( $.fn.bgiframe )
      list.bgiframe();
  }
  
  return {
    display: function(d, q) {
      init();
      data = d;
      term = q;
      fillList(q);
    },
    next: function() {
      moveSelect(1);
    },
    prev: function() {
      moveSelect(-1);
    },
    pageUp: function() {
      if (active != 0 && active - 8 < 0) {
        moveSelect( -active );
      } else {
        moveSelect(-8);
      }
    },
    pageDown: function() {
      if (active != listItems.size() - 1 && active + 8 > listItems.size()) {
        moveSelect( listItems.size() - 1 - active );
      } else {
        moveSelect(8);
      }
    },
    hide: function() {
      log.info("hiding?")
      element && element.hide();
      listItems && listItems.removeClass(CLASSES.ACTIVE);
      active = -1;
    },
    visible : function() {
      return element && element.is(":visible");
    },
    current: function() {
      return this.visible() && (listItems.filter("." + CLASSES.ACTIVE)[0] || options.selectFirst && listItems[0]);
    },
    show: function() {
      var offset = $(input).offset();
      element.css({
        width: typeof options.width == "string" || options.width > 0 ? options.width : $(input).width(),
        top: offset.top + input.offsetHeight,
        left: options.left > 0 ? options.left : offset.left
      }).show();
      
      if(options.scroll) {
        list.scrollTop(0);
        list.css({
          maxHeight: options.scrollHeight,
          overflow: 'auto'
        });
        
        if($.browser.msie && typeof document.body.style.maxHeight === "undefined") {
          var listHeight = 0;
          listItems.each(function() {
            listHeight += this.offsetHeight;
          });
          var scrollbarsVisible = listHeight > options.scrollHeight;
                    list.css('height', scrollbarsVisible ? options.scrollHeight : listHeight );
          if (!scrollbarsVisible) {
            // IE doesn't recalculate width when scrollbar disappears
            listItems.width( list.width() - parseInt(listItems.css("padding-left")) - parseInt(listItems.css("padding-right")) );
          }
        }
      }
    },
    selected: function() {
      var selected = listItems && listItems.filter("." + CLASSES.ACTIVE).removeClass(CLASSES.ACTIVE);
      return selected && selected.length && $.data(selected[0], "ac_data");
    },
    emptyList: function (){
      list && list.empty();
    },
    unbind: function() {
      element && element.remove();
    }
  };
};
$.fn.selection = function(start, end) {
  if (start !== undefined) {
    log.debug("in selection, no start/end")
    return this.each(function() {
      if( this.createTextRange ){
        var selRange = this.createTextRange();
        if (end === undefined || start == end) {
          selRange.move("character", start);
          selRange.select();
        } else {
          selRange.collapse(true);
          selRange.moveStart("character", start);
          selRange.moveEnd("character", end);
          selRange.select();
        }
      } else if( this.setSelectionRange ){
        this.setSelectionRange(start, end);
      } else if( this.selectionStart ){
        this.selectionStart = start;
        this.selectionEnd = end;
      }
    });
  }
  var field = this[0];
  if ( field.createTextRange ) {
    log.debug("in selection with createTextRange")
    var range = document.selection.createRange(),
      orig = field.value,
      teststring = "<->",
      textLength = range.text.length;
    range.text = teststring;
    var caretAt = field.value.indexOf(teststring);
    field.value = orig;
    this.selection(caretAt, caretAt + textLength);
    return {
      start: caretAt,
      end: caretAt + textLength
    }
  } else if( field.selectionStart !== undefined ){
    return {
      start: field.selectionStart,
      end: field.selectionEnd
    }
  } else {
    if(field.getRangeAt !== undefined) {
     log.error("ah, getRangeAt, ?")
    } else {
       log.error("ah, none of above, ?")
    }
   
  }
};

})(jQuery);