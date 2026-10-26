<?php
	/*function to write a number in the standard format*/
	function knum($input,$num_dec) {
		return number_format($input, $num_dec, ',', '.');
	}

	/*function to write a number in the standard format for printing with right align*/
    /* this function format a number with no significative blanks at its left side to reach the desired length*/
	function knump($input,$num_dec,$num_tot) {
		if ($input!=0) {
						$num=knum($input, $num_dec);
						$lung=strlen($num);
						while ($lung<$num_tot) {
								$num=' '.$num;
								$lung++;
						}
						if (strlen($num)>$num_tot) {$num=substr($num,strlen($num)-1-$num_tot,$num_tot);}
		}
		else {
				$num='';
				for ($k=1;$k<=$num_tot;$k++){ 
				    $num=' '.$num;    
				}
		}
		return $num;
	}

	/*function to write a string in the standard format for printing with left align*/
    /* this function format a string with no significative blanks at its right side to reach the desired length*/
	function kstrp($input,$num_tot) {
		$lung=strlen($input);
		while ($lung<$num_tot) {
			$input=$input.' ';
			$lung++;
		}
		if (strlen($input)>$num_tot) {$input=substr($input,0,$num_tot-1);}
		return $input;
	}
	
	/* function to verify data format             */ 
	/*    - Control that GG an AA are 2 char long */
	
	function kdatactrl($input) {
		$archivio=explode('/',$input);
		if (strlen($archivio[0])<2){
			$input='0'.$input;
		}
		if (strlen($archivio[1])<2){
			$archivio2=explode('/',$input);
			$input=$archivio2[0].'/0'.$archivio2[1].'/'.$archivio2[2];
		}
		return $input;
	}
	
	/* function to convert data from video to program: GG/MM/AAAA -----> AAAAMMGG */
	function kdatavp($input) {
		$input=kdatactrl($input);
	    return substr($input,6,4).substr($input,3,2).substr($input,0,2);
	}

	/* function to convert data from program to video: AAAAMMGG -----> GG/MM/AAAA */
	function kdatapv($input) {
	    return $input != '' ? substr($input,6,2).'/'.substr($input,4,2).'/'.substr($input,0,4) : '';
	}
	
	
?>