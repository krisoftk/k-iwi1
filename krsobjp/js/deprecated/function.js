// funzione per contare quante checkbox sono cliccate.

function checkTT(f) {
	var i = 0;
	var chk = 0;
	var modulo = f.elements;
	for (i = 0; i < modulo.length; i++) {
		if (modulo[i].type == "checkbox") {
			if (modulo[i].checked) {
				chk++;
			}
		}
	}
	return chk;
}

// funzione per estrarre l'ennesimo valore tra le righe con check selezionato.

function getTT(f, num) {
	var i = 0;
	var c = 0;
	var modulo = f.elements;
	for (i = 0; i < modulo.length; i++) {
		if (modulo[i].type == "checkbox") {
			if (modulo[i].checked) {
				c++;
				if (c == num) {
					return modulo[i].value;
				}
			}
		}
	}
	return 0;
}

//funzione per mettere il check a tutte le check box della pagina.

function SelezTT(f) {
  var i = 0;
  var modulo = f.elements;
  for (i=0; i<modulo.length; i++) {
      if (modulo[i].type == "checkbox"){
          modulo[i].checked = !(modulo[i].checked);
      }
  }
}