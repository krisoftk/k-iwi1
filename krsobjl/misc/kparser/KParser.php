<?php
class KParser {
	private $file = null;                  //file name
	private $istruzioni = null;            //instructions
	
	private $input = array();              //input array, each line of the $file attribute is an element of this array
	
	private $output = array();             //array of the results
	
	// fills the input attribute with the given file. Each line of the file is a record of the array
	
	private function create_input() {
	    $ind=0;
	    if (file_exists($this->file)) {
	        if (($handle = fopen($this->file, "r")) !== FALSE) {
	            while (($data = fgets($handle)) !== FALSE) {
	               $ind++;
	               $this->input[$ind]=str_replace(PHP_EOL, '', $data);
	            }
	            fclose($handle);
	        }
	    }
	}
	
	// A line is extracted from the text and processed with these parameters:
	// Field 1:
	//     -Identifier of the line to extract
	//     -0 for a line number, 1 for the name of a variable holding the line number
	// Field 2:
	// If the second parameter is an array of two values:
	//     -the first is the starting column number
	//     -the second is the number of characters
	// If the second parameter isn't null, a substring of the chosen line is taken
	// If the line number is 0 it must return false
	
	private function get_riga($c1,$c2){
	    if ($c1[1]==1) { $nrig=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $nrig=$c1[0];}
	    
	    if ($nrig>0) {
	       if ($c2!=null) {return substr($this->input[$nrig],$c2[0],$c2[1]);}
	           else { return $this->input[$nrig];}
	    } else {return false;}
	}
	
	// n lines are extracted and returned as requested by these parameters:
	// Field 1:
	//     -Identifier of the line to start extracting from
	//     -0 for a line number, 1 for the name of a variable holding the line number
	// Field 2:
	// Array with two values:
	//     -Number of lines to extract, including the starting one identified by field 1
	//      This value can also be negative, meaning extract n lines above the one given by field 1
	//     -0 for a number of lines to extract, 1 for the name of a variable holding the number of lines to extract
	//     -An array with two values: start and length of the substring to consider for ALL the lines. If = null the whole line is considered
	
	private function get_n_righe($c1,$c2){
	    //Find the number of the starting line to extract
	    if ($c1[1]==1) { $nrigi=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $nrigi=$c1[0];}
	    //Find the number of lines to extract
	    if ($c2[1]==1) { $nrige=$this->output[$c2[0]]; }
	    if ($c2[1]==0) { $nrige=$c2[0];}
	    
	    //Define the output array
	    $out=array();
	    
	    if ($nrigi>0) {
	        $nrigf=$nrigi+$nrige;
	    
	        //Handle the negative parameter
	       if ($nrigi>$nrigf) {
	           $nrigf=$nrigi;
	           $nrigi=$nrigf+$nrige;
	       }
	       //Scan the input and, if the second parameter of C2<>null, extract the substring
	       for ($ind = $nrigi; $ind <= $nrigf; ++$ind) {
	            if ($c2[2]==null) { $riga["text"]=$this->input[$ind];}
	               else {$riga["text"]=substr($this->input[$ind],$c2[2][0],$c2[2][1]);}	               
	           $riga["array"]=$this->var_get_array($riga," ");
	           $out[]=$riga;
	       }
	    }
	    return $out;
	}
	
	// Extracts from the text the line containing the n-th occurrence of the given string:
	// Field 1:
	//     - String to search for
	//     - 0 string to search for, 1 name of a variable holding the string to search for
	// Field 2, array of one value:
	//     - Number of the occurrence to extract (integer)
	//     - 0 number of the occurrence to search for, 1 name of a variable holding the number of the occurrence
	//     - An array with two values: start and length of the substring to consider for ALL the lines. If = null the whole line is considered
	
	private function get_riga_num_str($c1,$c2){
	    //Find the string to search for
	    if ($c1[1]==1) { $str=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $str=$c1[0];}
	    //Find the number of the occurrence
	    if ($c2[1]==1) { $ric=$this->output[$c2[0]]; }
	    if ($c2[1]==0) { $ric=$c2[0];}
	    $n     =0;
	    $numr  =0;
	    foreach ($this->input as $riga) {
	        $numr++;
	        $pos=0;
	        if (isset($c2[2])) {$riga=substr($riga,$c2[2][0],$c2[2][1]);}
	        while (strpos($riga,$str,$pos)!==false) {
	           $pos=strpos($riga,$str,$pos)+strlen($str);
	           $n++;
	           if ($n==$ric) {return $numr;}
	       }
	    }
	    return 0;
	}

	// Processes a variable of the output with these parameters:
	// Field 1:
	//     -name of the variable to process
	// Field 2:
	// The second parameter is an array with two values:
	//     -a string to search for
	//     -one of the two strings: "before","after".
	// The substring is made by searching the first parameter of field 2 in the value of the variable given in field 1.
	// if the second value of field 2 is before, it keeps from the start of the variable up to the start of the given string
	// if the second value of field 2 is after, it keeps from the given string to the end of the variable
	// the string given in the first value of field 2 stays in the result.
	
	private function var_str_strip($c1,$c2){
	    if ($c1!==false) {
	                           if ($c2[1]=='before') {return substr($this->output[$c1],0,strpos($this->output[$c1],$c2[0])+strlen($c2[0]));}
	                           if ($c2[1]=='after')  {return substr($this->output[$c1],strpos($this->output[$c1],$c2[0]),strlen($this->output[$c1]));}
	    } else {return false;}
	}
	
	// Returns an array by splitting the string $c1 with the separator $c2
	
	private function var_get_array($c1,$c2) {
	    return explode(" ",preg_replace('/'.$c2.'+/', $c2, $c1["text"]));
	}
	
	// Processes a variable of the output with these parameters:
	// Field 1:
	//     -name of the variable to process
	// Field 2:
	// If the second parameter is an array with two values
	//     -the first is the string to search for
	//     -the second is the replacement string

	private function var_str_replace($c1,$c2){
	    return str_replace($c2[0],$c2[1],$this->output[$c1]);
	}

	// Extracts an array with the list of the line numbers where the rows start.
	// For each row it creates a variable called rigaXXXXXX holding an array with
	// the lines of text that make up that single row.
	// Field 1: array with two elements
	//     -Name of the variable with the items per page
	//     -Name of the variable with the total of pages
	//     -Name of the variable with the number of text lines that make up a sales row
	// Field 2: array with two elements
	//     -Step of the row numbers (e.g. step 10 gives the rows 10,20,30,40......
	//     -Total width of the number, including leading zeros
	
	private function var_array_rows_create($c1,$c2){
	    $out=array();
	    $numeromaxarticoli=$this->output[$c1[0]]*$this->output[$c1[1]];
	    for ($ind = 1; $ind <= $numeromaxarticoli; ++$ind) {
	        $numriga=str_pad($ind*$c2[0], $c2[1], "0", STR_PAD_LEFT);
	        $valore=array($numriga,$this->get_riga_num_str(array($numriga,0),array(1,0)));
	        $out[]=$valore;
	        if ($valore[1]!=0) {
	                $this->output["riga".$numriga]=$this->get_n_righe(array($valore[1],0),array($this->output[$c1[2]],0,null));
	        }
	    }
	    return $out;
	}
	
	// Extracts an array with the list of the line numbers where the rows start.
	// The function scans all the integer values between 1 and n
	// Field 1: array with two elements
	//     -n=maximum number of rows to search for
	//     -Total width of the number, including leading zeros
	//     -Name of the variable with the number of text lines that make up a sales row
	// Field 2: array with one element
	//     -An array with two values: start and length of the substring to consider for ALL the lines.
	
	private function var_array_n_rows_create($c1,$c2){
	    $out=array();
	    $numeromaxarticoli=$c1[0];
	    for ($ind = 1; $ind <= $numeromaxarticoli; ++$ind) {
	        $numriga=str_pad($ind, $c1[1], "0", STR_PAD_LEFT);
	        $valore=array($numriga,$this->get_riga_num_str(array($numriga,0),array(1,0,$c2[0])));
	        if ($valore[1]!=0) {
	            $out[]=$valore;
	            $this->output["riga".$numriga]=$this->get_n_righe(array($valore[1],0),array($this->output[$c1[2]],0,null));
	        }
	    }
	    return $out;
	}

	// Creates a new variable;
	
	private function var_add($c1,$c2) {
	    return $c1[0];
	}
	
	// arithmetic between variables;
	// Field 1:
	//     -Constant integer value or name of the variable holding it
	//     -0 constant, 1 variable name
	//     -operation '+','-','*','/'
	// Field 1:
	//     -Constant integer value or name of the variable holding it
	//     -0 constant, 1 variable name

	
	private function var_op($c1,$c2) {
	    //Find the first number
	    if ($c1[1]==1) { $num1=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $num1=$c1[0];}
	    
	    //Find the second number
	    if ($c2[1]==1) { $num2=$this->output[$c2[0]]; }
	    if ($c2[1]==0) { $num2=$c2[0];}
	    
	    if ($c1[2]=="+") {return $num1+$num2;}
	    if ($c1[2]=="-") {return $num1-$num2;}
	    if ($c1[2]=="*") {return $num1*$num2;}
	    if ($c1[2]=="/") {return $num1+$num2;}
	}
	
	public function __construct($file, $istruzioni) {
	    $this->file = $file;
	    $this->istruzioni = $istruzioni;
	    $this->create_input();
	}
	
	public function addIstruzione($istruzione) {
	   $this->istruzioni[] = $istruzione; 
	}
	
	public function start() {
	    foreach ($this->istruzioni as $istruzione) {
	        $operazione = $istruzione[0];
	        $campo1 = $istruzione [1];
	        $campo2 = $istruzione[2];
	        $risultato = $istruzione[3];
	        
	        try {
	            $this->output[$risultato] = $this->{$operazione}($campo1, $campo2);
	        } catch (Exception $e) {
	            // method not found
	        }
	    }
	    
	    return $this->output;
	}
	
	
}
?>