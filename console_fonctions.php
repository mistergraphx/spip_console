<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function console_affichage_final($page){

	$debugtoolbar = recuperer_fond('inclure/debugtoolbar');
	    
    $pos_body = strpos($page, '</body>');

    return substr_replace($page, $debugtoolbar, $pos_body, 0);

}



function balise_CONSOLE_dist($p){
    $p->code = "calculer_balise_CONSOLE()";
	$p->interdire_scripts = false;
    return $p;
}
function calculer_balise_CONSOLE(){
    $debug = Debug::getInstance()->debugBar();
    return $debug;
}