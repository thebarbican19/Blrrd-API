<?php

function generate_key() { 
$chars = "abcdefghijklmopqrstuvwxyzABCDEFGHIJKLMOPQRSTUVWXYZ023456789"; 
	srand((double)microtime()*1000000); 
	$i = 0; 
	$key = '' ; 

    while ($i <= 35) { 
       	$num = rand() % 59; 
        $tmp = substr($chars, $num, 1); 
        $key = $key . $tmp; 
        $i++; 

    } 

    return $key; 

} 

function generate_password() { 
	$chars = "abcdefghijklmopqrstuvwxyzABCDEFGHIJKLMOPQRSTUVWXYZ023456789*&@/%"; 
	srand((double)microtime()*1000000); 
	$i = 0; 
	$key = '' ; 

    while ($i <= 7) { 
       	$num = rand() % 64; 
        $tmp = substr($chars, $num, 1); 
        $key = $key . $tmp; 
        $i++; 

    } 

    return $key; 
	
}

?>