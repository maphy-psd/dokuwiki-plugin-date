<?php
/**
 * Date: Run the php command strftime or date.
 * Usage:
 * {{date>format}}
 *
 * Replacers are handled in a simple key/value pair method.
 * {{date>format|key=val|key2=val|key3=val}}
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Marcel Pietschmann <mpietsch@astro.physik.uni-potsdam.de>
 * @version    0.9
 */
 
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_date extends DokuWiki_Syntax_Plugin {
    
    /**
     * What kind of syntax are we?
     */
     
     function getType() { return 'substition'; }
     
     // function getAllowedTypes(){ return array('container','substition','protected','disabled','formatting'); }
     
     /**
     * Paragraph Type
     */
    // function getPType(){ return 'normal'; } 
    // function getAllowedTypes() { return array('substition','protected','disabled','formatting','container'); }
    
    /**
     * Where to sort in?
     */
    function getSort(){ return 195; }
    
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) { 
        $this->Lexer->addSpecialPattern('{{date>.+?}}',$mode,'plugin_date');
        $this->Lexer->addSpecialPattern('<<.+?>>',$mode,'plugin_date');
    }
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){

        if (strpos($match,'date>',2) !== false) {
            // strip markup
            $match = substr($match,7,-2);
            // Get the key values pairs        
            $replacers = preg_split('/(?<!\\\\)\|/', $match);       
            
            // cut format key
            $dataformat = array_shift($replacers);
            
            // handle key=value
            $replacers = $this->_GetKeyValues($replacers);
            
            $cnst = null;
        } else {
            // strip markup
            // $cnst = array();
            $cnst = substr($match, 2, -2); 
        }
        return array($dataformat,$replacers,$cnst); 
    }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        // for XHTML output
        if ($mode == 'xhtml') {

            // format for the output
            $format = $data[0];

            // Check if some keys are set -> array has length greater than 0
            if (count($data[1]) > 0) {

                // search the keys in array and get position in array
                $timestamp_key = array_search("timestamp",$data[1]['keys']);
                $now_key = array_search("now",$data[1]['keys']);
                $locale_key = array_search("locale",$data[1]['keys']);
                $mode_key = array_search("mode",$data[1]['keys']);
                // $cnst = NULL;
            } else {
            
            // set null values for correct going
                $timestamp_key = null;
                $now_key = null;
                $locale_key = null;
                $mode_key = null;
                    if ($data[2] === '') {
                        $cnst = null;
                    } else {
                        $cnst = $data[2];
                    }              
            }
            
            // set locale LC_TIME setting, if some locale key is giving
            if (!empty($locale_key)) {
                // save actually settings
                $localestore = setlocale(LC_TIME,"0");
                // get values from array
                $locale_value = $data[1]['vals'][$locale_key];
                // set LC_TIME
                setlocale(LC_TIME,$locale_value);
            }    
            
            if ($mode_key !== false) {
                // get value from array
                $mode_value = $data[1]['vals'][$mode_key]; 
            } else {
                $mode_value = "strftime";
            }

            // check for the different possibilities
            if (is_null($timestamp_key) !== true and is_null($now_key) !== true and is_null($cnst) == true) {
                // timestamp and now keys are set
                
                // get values from array
                $timestamp_value = $data[1]['vals'][$timestamp_key];
                $now_value = $data[1]['vals'][$now_key];
                
                // replace " with ' for correct eval
                $timestamp_value = str_replace('"',"'",$timestamp_value);
                $now_value = str_replace('"',"'",$now_value);
                
                // check if in timestamp string the function strtotime is used
                $pos = strpos($timestamp_value, "strtotime");
                if ($pos === false) {
                    // strtotime is not used, so don't use now value
                    $timestamp_with_now_value = $timestamp_value;
                } else {
                    // // strtotime is used, so use now value
                    $timestamp_with_now_value = substr($timestamp_value,0,strlen($timestamp_value)-1).", ".$now_value.substr($timestamp_value,-1);
                }
                
                // eval the timestamp string
                eval ("\$timestamp_with_now_value = $timestamp_with_now_value;");
                
                // do the magic date function
                    if ($mode_value == "date") {
                        $xhtml = date($format, $timestamp_with_now_value);
                    } else {
                        $xhtml = strftime($format, $timestamp_with_now_value);
                    }
                
            } else if (is_null($timestamp_key) !== true and is_null($cnst) == true) {
                // only timestamp key is set
                
                // get values from array
                $timestamp_value = $data[1]['vals'][$timestamp_key];
                // replace " witch ' for correct eval
                $timestamp_value = str_replace('"',"'",$timestamp_value);
                // eval the timestamp string
                eval ("\$timestamp_value = $timestamp_value;");
                
                // do the magic date function
                if ($mode_value == "date") {
                        $xhtml = date($format, $timestamp_value);
                    } else {
                        $xhtml = strftime($format, $timestamp_value);
                    }
                
            } else if (is_null($cnst) == true) {
                // no keys are set
                // do the magic date function
                if ($mode_value == "date") {
                        $xhtml = date($format);
                    } else {
                        $xhtml = strftime($format);
                    }
                
            }
            
            if ($cnst !== null) {

                // handle with the constants
                switch ($cnst) {
                    case 'DATE':
                    case 'date':
                        $xhtml = strftime($conf['dformat']);
                        break;
                    case 'YEAR':
                    case 'year':
                        $xhtml = strftime('%Y');
                        break;
                    case 'MONTH':
                    case 'month':
                        $xhtml = strftime('%m'); 
                        break;
                    case 'WEEK':
                    case 'week':
                        $xhtml = strftime('%W'); 
                        break;
                    case 'DAY2':
                    case 'day2':
                    case 'DAY':
                    case 'day':
                        $xhtml = strftime('%d');
                        break;
                    case 'DAY1':
                    case 'day1':
                        $xhtml = strftime('%e');
                        break;
                    case 'DAYOFYEAR':
                    case 'dayofyear':
                        $xhtml = strftime('%j');
                        break;
                    case 'WEEKDAY':
                    case 'weekday':
                        $xhtml = strftime('%w');
                        break;  
                    case 'TIME':
                    case 'time':
                        $xhtml = strftime('%T');
                        break;
                    default:
                        // for unknown match render original
                        $xhtml = "@{$cnst}@";
                        break;
                }
            }
            
            // unset cache, so that the page is always up to date
            $renderer->info['cache'] = false;
            // print out the date
            $renderer->doc .= $xhtml;
            
            // set locale LC_TIME setting to stored settings
            if (isset($locale_key)) {
                setlocale(LC_TIME,$localestore);
            }
            
            return true;
        }
        return false;
    }
    
    
    /**
     * Handles the key=value array
     */
    function _GetKeyValues($replacers)
    {
            $r = array();
            if (is_null($replacers)) {
                $r['keys'] = null;
                $r['vals'] = null;
            } else if (is_string($replacers)) {
                    list ($k, $v) = explode('=', $replacers, 2);
                    // $r['keys'] = BEGIN_REPLACE_DELIMITER.trim($k).END_REPLACE_DELIMITER;
                    $r['keys'] = trim($k);
                    $r['vals'] = trim(str_replace('\|','|',$v));
            } else if (is_array($replacers)) {
                    foreach($replacers as $rep) {
                            list ($k, $v) = explode('=', $rep, 2);
                            // $r['keys'][] = BEGIN_REPLACE_DELIMITER.trim($k).END_REPLACE_DELIMITER;
                            $r['keys'][] = trim($k);
                            $r['vals'][] = trim(str_replace('\|','|',$v));
                    }
            } else {
                    // This is an assertion failure. We should NEVER get here.
                    //die("FATAL ERROR!  Unknown type passed to syntax_plugin_templater::massageReplaceMentArray() can't massage syntax_plugin_templater::\$replacers!  Type is:".gettype($r)." Value is:".$r);
                $r['keys'] = null;
                $r['vals'] = null;
            }
            return $r;
    }
 }
?>
