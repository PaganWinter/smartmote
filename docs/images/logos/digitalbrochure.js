var req;
function processBrochureRequest(url) 
{    
      
    if(document.digitalBrochureForm.email.value == "")
        {
           
           document.getElementById("brochureMsg").innerHTML = "Please enter your e-mail ID";
           return false;
        } 
        else {             
          var url1 = url+'?emailId='+escape(document.digitalBrochureForm.email.value);
         
            retrieveEDigitalAction(url1);
            document.digitalBrochureForm.email.value = ""; 
            return false;
         }    
    
}

function retrieveEDigitalAction(url) {      
   if (window.XMLHttpRequest) {
       // Non-IE browsers
       var reqM = new XMLHttpRequest();
        try {
            url = url+"&ts="+new Date().getTime() ;
           // alert(url);
            reqM.open("GET", url, true);
        } catch (e) {
            alert(e);
        }
        reqM.onreadystatechange = function(aEvt){
		if (reqM.readyState == 4) 
		{ // Complete
		    if (reqM.status == 200) 
		    { // OK response
			var digiResponse = reqM.responseText;
			var digiValue = digiResponse.indexOf("Thank");
			if (digiValue > -1)
			{
			    document.digitalBrochureForm.email.focus();
			}
			document.getElementById("brochureMsg").innerHTML = digiResponse;

		     } else {
                        // alert("here="+reqM.statusText);
			document.getElementById("brochureMsg").innerHTML = reqM.statusText;
		    }
			document.digitalBrochureForm.email.focus();

		}
	}
        reqM.send(null);
    } else if (window.ActiveXObject) { // IE
      req = new ActiveXObject("Microsoft.XMLHTTP");
      if (req) {
        req.onreadystatechange = processEDigitalComplete;
        req.open("POST", url, true);
        req.send();
      }
    }
}

function processEDigitalComplete() {
    if (req.readyState == 4) 
    { // Complete
        if (req.status == 200) 
            { // OK response
            var digiResponse = req.responseText;
            var digiValue = digiResponse.indexOf("Thank");
                if (digiValue > -1)
                {
                    document.digitalBrochureForm.email.focus();
                }
                document.getElementById("brochureMsg").innerHTML = digiResponse;

            } else 
            {
                document.getElementById("brochureMsg").innerHTML = req.statusText;
            }
      document.digitalBrochureForm.email.focus();
    }
  }

function doFirst() {
    document.getElementById("brochureMsg").innerHTML = "Please leave your e-mail ID to<br>receive a digital brochure";
    document.digitalBrochureForm.email.value = "";
}

