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
 * @version    1.20
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
        $this->Lexer->addSpecialPattern('{{date=.+?}}',$mode,'plugin_date');
    }
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
		//strip strings for non-constant
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
            // strip markup for constanst
            $cnst = substr($match, 7, -2); 
        }
        return array($dataformat,$replacers,$cnst); 
    }
 
    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
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
                $cnst = FALSE;
            } else {
            // set FALSE values for correct going
                $timestamp_key = FALSE;
                $now_key = FALSE;
                $locale_key = FALSE;
                $mode_key = FALSE;
                    if ($data[2] === '') {
                        $cnst = FALSE;
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
            if ( $timestamp_key !== FALSE and $now_key !== FALSE and $cnst == FALSE ) {
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
                
            } else if ( $timestamp_key !== FALSE and $cnst == FALSE ) {
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
                
            } else if ($cnst == FALSE) {
                // no keys are set
                // do the magic date function
                if ($mode_value == "date") {
                        $xhtml = date($format);
                    } else {
                        $xhtml = strftime($format);
                    }
            }
            
            if ($cnst != FALSE) {
                $dformat = $this->getConf('dpformat');
                // handle with the constants
				if ( strcasecmp($cnst,'today') == 0 ) {
					$xhtml = strftime($dformat);
				} else if ( strcasecmp($cnst,'tomorrow') == 0 ) {
					$xhtml = strftime($dformat, strtotime("+1 day"));
				} else if ( strcasecmp($cnst,'yesterday') == 0 ) {
					$xhtml = strftime($dformat, strtotime("-1 day"));
				} else if ( strcasecmp($cnst,'year') == 0 ) {
					$xhtml = strftime('%Y');
				} else if ( strcasecmp($cnst,'nyear') == 0 ) {
					$xhtml = strftime('%Y',strtotime("+1 year"));;
				} else if ( strcasecmp($cnst,'pyear') == 0 ) {
					$xhtml = strftime('%Y',strtotime("-1 year"));
				} else if ( strcasecmp($cnst,'month') == 0 ) {
					$xhtml = strftime('%m');
				} else if ( strcasecmp($cnst,'nmonth') == 0 ) {
					$xhtml = strftime('%m',strtotime("+1 month"));
				} else if ( strcasecmp($cnst,'pmonth') == 0 ) {
					$xhtml = strftime('%m',strtotime("-1 month"));
				} else if ( strcasecmp($cnst,'week') == 0 ) {
					$xhtml = strftime('%W'); 
				} else if ( strcasecmp($cnst,'nweek') == 0 ) {
					$xhtml = strftime('%W',strtotime("+1 week"));
				} else if ( strcasecmp($cnst,'pweek') == 0 ) {
					$xhtml = strftime('%W',strtotime("-1 week"));
				} else if ( strcasecmp($cnst,'dayofmonth') == 0 ) {
					$xhtml = strftime('%d');
				} else if ( strcasecmp($cnst,'dayofmonth2') == 0 ) {
					$xhtml = strftime('%e');
				} else if ( strcasecmp($cnst,'dayofyear') == 0 ) {
					$xhtml = strftime('%j');
				} else if ( strcasecmp($cnst,'weekday') == 0 ) {
					$xhtml = strftime('%w');
				} else if ( strcasecmp($cnst,'time') == 0 ) {
					$xhtml = strftime('%T');
				} else {
					// for unknown match render original
                    $xhtml = "{date={$cnst}}";
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
