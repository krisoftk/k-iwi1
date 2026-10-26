<?php
class KParser {
	private $file = null;                  //file name
	private $istruzioni = null;            //instructions
	
	private $input = array();              //input array, each line of the $file attribute is an element of this array
	
	private $output = array();             //array of the results
	
	// popola l'attributo input con il file passato in ingesso. Ogni riga del file è un record dell'array 
	
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
	
	// Viene estrata una riga dal testo e manipolata con i seguenti parametri:
	// Campo 1:
	//     -Identificativo riga da estrarre
	//     -0 per numero riga, 1 per nome variabile che contiene il numero riga  
	// Campo 2:
	// Se il secondo parametro è un array a due valori:
	//     -il primo è il numero di colonna di partenza
	//     -il secondo è il numero di caratteri
	// Se il secondo parametro è diverso da null allora viene eseguita una substring della riga scelta
	// Se il numero riga è 0 deve restituire false
	
	private function get_riga($c1,$c2){
	    if ($c1[1]==1) { $nrig=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $nrig=$c1[0];}
	    
	    if ($nrig>0) {
	       if ($c2!=null) {return substr($this->input[$nrig],$c2[0],$c2[1]);}
	           else { return $this->input[$nrig];}
	    } else {return false;}
	}
	
	// Vengono estratte e passate n righe come richeisto dai seguenti parametri:
	// Campo 1:
	//     -Identificativo riga dalla quale iniziare ad estrarre
	//     -0 per numero riga, 1 per nome variabile che contiene il numero riga
	// Campo 2:
	// Array con due valori:
	//     -Numero di righe da estrarre compresa quella di partenza identificata dal campo 1
	//      Questo valore può essere anche negativo e significa estrarre nrighe sopra quella segnalata dal campi 1
	//     -0 per numero di righe da estrarre, 1 per nome variabile che contiene il numero di righe da estrarre
	//     -E' un array con due valori: inizio e lunghezza della sottostringa da considerare per TUTTE le righe. Se = null viene considerata tutta la riga
	
	private function get_n_righe($c1,$c2){
	    //Trovo il numero della riga di partenza da estrarre
	    if ($c1[1]==1) { $nrigi=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $nrigi=$c1[0];}
	    //Trovo il numero di righe da estrarre
	    if ($c2[1]==1) { $nrige=$this->output[$c2[0]]; }
	    if ($c2[1]==0) { $nrige=$c2[0];}
	    
	    //Definisco array di uscita
	    $out=array();
	    
	    if ($nrigi>0) {
	        $nrigf=$nrigi+$nrige;
	    
	        //Gesstisco il parametro negativo
	       if ($nrigi>$nrigf) {
	           $nrigf=$nrigi;
	           $nrigi=$nrigf+$nrige;
	       }
	       //Scandisco l'input e se il secondpo parametro di C2<>null allora esetraggo la sottostringa 
	       for ($ind = $nrigi; $ind <= $nrigf; ++$ind) {
	            if ($c2[2]==null) { $riga["text"]=$this->input[$ind];}
	               else {$riga["text"]=substr($this->input[$ind],$c2[2][0],$c2[2][1]);}	               
	           $riga["array"]=$this->var_get_array($riga," ");
	           $out[]=$riga;
	       }
	    }
	    return $out;
	}
	
	// Viene estratta la riga dal testo che contiene l'ennesima stringa specificata:
	// Campo 1:
	//     - Stringa da cercare
	//     - 0 stringa da cercare, 1 nome variabile che contiene la stringa da cercare
	// Campo 2 array di un valore: 
	//     - Numero di ricorrenza da estrarre (intero)
	//     - 0 numero della ricorrenza da cercare, 1 nome variabile che contiene in numero della ricorrenza da cercare
	//     - E' un array con due valori: inizio e lunghezza della sottostringa da considerare per TUTTE le righe. Se = null viene considerata tutta la riga
	
	private function get_riga_num_str($c1,$c2){
	    //Trovo la tringa da cercare
	    if ($c1[1]==1) { $str=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $str=$c1[0];}
	    //Trovo il numero della ricorrenza
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

	// Viene manipolta una variabile presente nell'ouput con i seguenti parametri:
	// Campo 1:
	//     -nome variabile da manipolare
	// Campo 2:
	// Il secondo parametro è un array con due valori:
	//     -una stringa da cercare
	//     -una delle due stringhe: "before","after".
	// Viene creata la sottostringa cercando il dal primo parametro del campo 2 dal valore della variabile indicata nel campo 1.
	// se il secondo valore del campo 2 è before si tene dall'inizio della variabile fino all'inizio della stringa data
	// se il secondo valore del campo 2 è after si tiene dalla stringa data alla fine della variabile
	// la stringa data nel primo valore del campo 2 rimane nel risultato.
	
	private function var_str_strip($c1,$c2){
	    if ($c1!==false) {
	                           if ($c2[1]=='before') {return substr($this->output[$c1],0,strpos($this->output[$c1],$c2[0])+strlen($c2[0]));}
	                           if ($c2[1]=='after')  {return substr($this->output[$c1],strpos($this->output[$c1],$c2[0]),strlen($this->output[$c1]));}
	    } else {return false;}
	}
	
	// Restituisce un array dividendo la stringa $c1 usando il separatore $c2
	
	private function var_get_array($c1,$c2) {
	    return explode(" ",preg_replace('/'.$c2.'+/', $c2, $c1["text"]));
	}
	
	// Viene manipolta una variabile presente nell'ouput con i seguenti parametri:
	// Campo 1:
	//     -nome variabile da manipolare
	// Campo 2:
	// Se il secondo parametro è un array con due valori
	//     -il primo è la stringa da cercare
	//     -il secondo è la stringa da sotituire

	private function var_str_replace($c1,$c2){
	    return str_replace($c2[0],$c2[1],$this->output[$c1]);
	}

	// Estrae un array con l'elenco dei numeri di riga dove iniziano le righe.
	// Per ogni riga crea una variabile chiamara rigaXXXXXX con dentro un array
	// righe del testo che compongono quella riga singola.
	// Campo 1: array con due elementi
	//     -Nome variabile articoli per pagina
	//     -Nome variabile totale pagine
	//     -Nome variabile numero righe di testo che compongono una riga di vendita
	// Campo 2: array con due elementi
	//     -Step dei numeri di riga (es: step 10 le righe saranno:10,20,30.40......
	//     -Numero totale occupazione numero, compresi zero non significativi
	
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
	
	// Estrae un array con l'elenco dei numeri di riga dove iniziano le righe.
	// La funzione esegue una scansione completa dei valori interi tra 1 ed n
	// Campo 1: array con due elementi
	//     -n=numero massimo di righe da cercare
	//     -Numero totale occupazione numero, compresi zero non significativi
	//     -Nome variabile numero righe di testo che compongono una riga di vendita
	// Campo 2: array con un elemento
	//     -E' un array con due valori: inizio e lunghezza della sottostringa da considerare per TUTTE le righe.
	
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

	// Crea nuova variabile;
	
	private function var_add($c1,$c2) {
	    return $c1[0];
	}
	
	// addizione tra variabili;
	// Campo 1:
	//     -Valore costante algebrico intero oppure nome di variabile che lo contiene 
	//     -0 costante, 1 nome di variabile
	//     -operazione '+','-','*','/'
	// Campo 1:
	//     -Valore costante algebrico intero oppure nome di variabile che lo contiene
	//     -0 costante, 1 nome di variabile

	
	private function var_op($c1,$c2) {
	    //Trovo il primo numero
	    if ($c1[1]==1) { $num1=$this->output[$c1[0]]; }
	    if ($c1[1]==0) { $num1=$c1[0];}
	    
	    //Trovo il secondo numero
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
	            // metodo non trovato
	        }
	    }
	    
	    return $this->output;
	}
	
	
}
?>