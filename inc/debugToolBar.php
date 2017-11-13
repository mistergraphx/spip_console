<?php
/**
 * Cette classe permet de sécuriser le debugage PHP dans vos scripts (locaux
 * et distant).
 * 
 * A l'utilisation il vous suffit de l'inclure dans vos script.
 *
 * @source https://github.com/jerrywham/DebugToolBar
 * @author Jacksay<studio@jacksay.com>
 * @author Cyril MAGUIRE<contact@ecyseo.net>
 */
class Debug {

  /****************************************************************************/
  /** CONFIGURATION **/
 
  // Vous pouvez ajouter votre ip pour un debuggage distant
  // attention cependant
  public static $allow_IP = array('::1','127.0.0.1');
  /* array('::1','127.0.0.1','88.161.204.85'); */
 
  /****************************************************************************/
 
  /**
   * Equivalent à un var_dump mais en version sécurisée et en couleur.
   *
   * @author  Cyril MAGUIRE<contact@ecyseo.net>
   * @version 1.0
   */
  private static function _trac( $mixedvar, $comment='',  $sub = 0, $index = false )
  {
    $type = htmlentities(gettype($mixedvar));
    $debug = debug_backtrace();
    //var_dump($debug);
    $r ='';
    switch ($type) {
      case 'NULL':$r .= '<em style="color: #0000a0; font-weight: bold;">NULL</em>';break;
      case 'boolean':if($mixedvar) $r .= '<span style="color: #327333; font-weight: bold;">TRUE</span>';
      else $r .= '<span style="color: #327333; font-weight: bold;">FALSE</span>';break;
      case 'integer':$r .= '<span style="color: red; font-weight: bold;">'.$mixedvar.'</span>';break;
      case 'double':$r .= '<span style="color: #e8008d; font-weight: bold;">'.$mixedvar.'</span>';break;
      case 'string':$r .= '<span style="color: '.($index === true ? '#e84a00':'#000').';">\''.$mixedvar.'\'</span>';break;
      case 'array':$r .= 'Tableau('.count($mixedvar).') &nbsp;{'."\r\n\n";
        foreach($mixedvar AS $k => $e) $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub+1).'['.self::_trac($k, $comment, $sub+1, true).'] =&gt; '.($k === 'GLOBALS' ? '* RECURSION *':self::_trac($e, $comment, $sub+1)).",\r\n";
        $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub).'}';
      break;
      case 'object':$r .= 'Objet «<strong>'.htmlentities(get_class($mixedvar)).'</strong>»&nbsp;{'."\r\n\n";
        
            $prop = get_object_vars($mixedvar);
            //
            foreach($prop AS $name => $val){
                if($name == 'privates_variables'){ # Hack (PS: il existe des biblio interne permettant de tuer une classe)
                    for($i = 0, $count = count($mixedvar->privates_variables); $i < $count; $i++) {
                        $r .=   str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub+1).'<strong>'.
                                htmlentities($get = $mixedvar->privates_variables[$i]).
                                '</strong> =&gt; '.self::_trac($mixedvar->$get, $comment, $sub+1).
                                "\r\n\n";
                    }
                    continue;
                }
            //
              $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub+1).'<strong>'.htmlentities($name).'</strong> =&gt; '.self::_trac($val, $comment, $sub+1)."\r\n\n";
            }
            
            //$r .= '<pre>'.print_r($mixedvar,TRUE).'</pre>';
            
            $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub).'}';
        break;
      default:$r .= 'Variable de type <strong>'.$type.'</strong>.';break;
    }

    $r = preg_replace('/\[(.*)\]/', '[<span class="jcktraker-id">$1</span>]', $r);

    return $r;
  }
	/**
	 * Pour tracer une variable
	 *
	 * @author  Jacksay<studio@jacksay.com>
	 * @author  Cyril MAGUIRE<contact@ecyseo.net>
	 * @version 2.0
	 */
	public static function trac( $mixedvar, $comment='',  $sub = 0 ) {
	  $debug = debug_backtrace();
	  //DEBUG :
	  //var_dump($debug);
	  $r = self::_trac( $mixedvar, $comment, $sub);
	  $r .= "\n\n\n"; 
	  self::getInstance()->OUTPUT .= '<pre>'."\n\n".'<p class="jcktraker-backtrace">'."\n".'&nbsp;Appel du debug ligne '.
									  $debug[2]['line']. ' du fichier'."\n\n".'&nbsp;<strong><em>'.
									  $debug[2]['file'].'</em></strong>'."\n\n".'</p>'."\n\n".'<strong class="jcktraker-blue">'.
									  $comment.'</strong> = '. $r ."</pre>\n";
	  self::getInstance()->TRAC_NUM++;
	}
	/**
	 * Pour décomposer une variable globale
	 * @author  Cyril MAGUIRE<contact@ecyseo.net>
	 * @version 1.0
	 */
	private static function _color($value) {
		return "\n\n".self::_trac($value)."\n\n\n";
	}
    
	/**
	 * Affiche une petite ligne pour suivre le fil de l'exécution.
	 * A utiliser dans un foreach par exemple pour savoir quel valeur prend une variable
	 *
	 * @author  Jacksay<studio@jacksay.com>
	 * @version 1.0
	 */
	public static function flow( $message, $type=1 )
	{
	  if(is_array($message)){
		  $message = print_r($message,TRUE);
	  }
	  self::getInstance()->OUTPUT .= '<p class="jcktraker-flow-'.$type.'">'.$message."</p>\n";
	  self::getInstance()->TRAC_NUM++;
	}
    
 
	private $OUTPUT = "";
	private $TRAC_NUM = 0;
  
	private static $instance;
	private $debug = false;
 
 
	/**
	 * Cette méthode est automatiquement appelée lorsque vous importez le fichier
	 * JckTraker.php dans votre script.
	 *
	 * @author  Jacksay<studio@jacksay.com>
	 * @author  Cyril MAGUIRE<contact@ecyseo.net>
	 * @version 2.0
	 */
	public static function init()
	{
	  if(in_array($_SERVER['REMOTE_ADDR'], self::$allow_IP)){
		self::getInstance()->debug = true;
		error_reporting(E_ALL);
	  } else {
		self::getInstance()->debug = false;
		error_reporting(0);
	  }
	}
 
 
	/**
	 * Accesseur
	 *
	 * @author  Jacksay<studio@jacksay.com>
	 * @author  Cyril MAGUIRE<contact@ecyseo.net>
	 * @version 2.0
	 */
	public static function getInstance(){
	  if(!isset (self::$instance) ){
		self::$instance = new Debug();
		self::init();
	  }
	  return self::$instance;
	}
    
	private function session_is(){
      if(isset($_SESSION))
          return self::_color($_SESSION);
  }
	
	private function session_count(){
		if(isset ($_SESSION)) {
			return 'SESSION('.count($_SESSION).')';
		} else {
			return '<del>SESSION</del>';}
	}
    /**
     * Elément clef, va renvoyer au template mustache les variables
     *
     * @author  Jacksay<studio@jacksay.com>
     * @author  Cyril MAGUIRE<contact@ecyseo.net>
     * @version 2.0
     */
    function debugBar() {
        if( !$this->debug ) return;
        $render =  array(
            'POST'=>self::_color($_POST),
            'POST_COUNT'=>count($_POST),
            'FILES'=>self::_color($_FILES),
            'FILES_COUNT'=>count($_FILES),
            'REQUEST'=>self::_color($_REQUEST),
            'REQUEST_COUNT'=>count($_REQUEST),
            '$_GET'=>self::_color($_GET),
            '$_GET_COUNT'=>count($_GET),
            'COOKIE'=>self::_color($_COOKIE),
            'COOKIE_COUNT'=>count($_COOKIE),
            'TRAC'=> $this->OUTPUT,
            'TRAC_NUM'=>$this->TRAC_NUM,
            'SESSION'=> self::session_is(),
            'SESSION_COUNT'=> self::session_count(),
            'SERVER'=>self::_color($_SERVER),
            'SERVER_COUNT'=>count($_SERVER)
        );
        if(!empty ($this->OUTPUT) ):
            $render['OUTPUT'] ='<script type="text/javascript">jcktraker_toogle(\'jcktraker-own\', document.getElementById(\'jacktraker_own_button\'));</script>';
        endif;
        return $render;
    }
}
// AUTO RUN
//if (DEBUG == 1) {
	/**
	* Dump variable
	* Alias of Debug::trac()
	*/
	if ( !function_exists( 'd' ) ) {
	  function d() {
		  call_user_func_array( array( 'Debug', 'trac' ), func_get_args() );
	  }
	}
/**
* Dump variable
* Alias of Debug::flow()
*/
if ( !function_exists( 'f' ) ) {
	function f() {
		call_user_func_array( array( 'Debug', 'flow' ), func_get_args() );
	}
}
# Initialisation du débugage
Debug::init();



//}
?>
	