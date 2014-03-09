/////////////////// Plug-in file for CalendarXP 9.0 /////////////////



// This file is totally configurable. You may remove all the comments in this file to minimize the download size.



/////////////////////////////////////////////////////////////////////







///////////// Calendar Onchange Handler ////////////////////////////



// It's triggered whenever the calendar gets changed to y(ear),m(onth),d(ay)



// d = 0 means the calendar is about to switch to the month of (y,m);



// d > 0 means a specific date [y,m,d] is about to be selected.



// e is a reference to the triggering event object



// Return a true value will cancel the change action.



// NOTE: DO NOT define this handler unless you really need to use it.



////////////////////////////////////////////////////////////////////



// function fOnChange(y,m,d,e) {}











///////////// Calendar AfterSelected Handler ///////////////////////



// It's triggered whenever a date gets fully selected.



// The selected date is passed in as y(ear),m(onth),d(ay)



// e is a reference to the triggering event object



// NOTE: DO NOT define this handler unless you really need to use it.



////////////////////////////////////////////////////////////////////



// function fAfterSelected(y,m,d,e) {}











///////////// Calendar Cell OnDrag Handler ///////////////////////



// It triggered when you try to drag a calendar cell. (y,m,d) is the cell date.



// aStat = 0 means a mousedown is detected (dragstart)



// aStat = 1 means a mouseover between dragstart and dragend is detected (dragover)



// aStat = 2 means a mouseup is detected (dragend)



// e is a reference to the triggering event object



// Return true (when aStat=0) to skip the set-date process, as well as any related event handlers (e.g. fAfterSelect).



// NOTE: DO NOT define this handler unless you really need to use it.



////////////////////////////////////////////////////////////////////



// function fOnDrag(y,m,d,aStat,e) {}















////////////////// Calendar OnResize Handler ///////////////////////



// It's triggered after the calendar panel has finished drawing.



// NOTE: DO NOT define this handler unless you really need to use it.



////////////////////////////////////////////////////////////////////



// function fOnResize() {}











////////////////// Calendar fOnWeekClick Handler ///////////////////////



// It's triggered when the week number is clicked.



// NOTE: DO NOT define this handler unless you really need to use it.



////////////////////////////////////////////////////////////////////



// function fOnWeekClick(year, weekNo) {}











////////////////// Calendar fIsSelected Callback ///////////////////////



// It's triggered for every date passed in as y(ear) m(onth) d(ay). And if



// the return value is true, that date will be rendered using the giMarkSelected,



// gcFGSelected, gcBGSelected and guSelectedBGImg theme options.



// NOTE: If NOT defined here, the engine will create one that checks the gdSelect only.



////////////////////////////////////////////////////////////////////



// function fIsSelected(y,m,d) {



//		return gdSelect[2]==d&&gdSelect[1]==m&&gdSelect[0]==y;



// }











////////////////// Calendar fParseInput Handler ///////////////////////



// Once defined, it'll be used to parse the input string stored in gdCtrl.value.



// It's expected to return an array of [y,m,d] to indicate the parsed date,



// or null if the input str can't be parsed as a date.



// NOTE: If NOT defined here, the engine will create one matching fParseDate().



////////////////////////////////////////////////////////////////////



// function fParseInput(str) {}











////////////////// Calendar fFormatInput Handler ///////////////////////



// Once defined, it'll be used to format the selected date - y(ear) m(onth) d(ay)



// into gdCtrl.value.



// It's expected to return a formated date string.



// NOTE: If NOT defined here, the engine will create one matching fFormatDate().



////////////////////////////////////////////////////////////////////



// function fFormatInput(y,m,d) {}







// ====== predefined utility functions for use with agendas. ========



// load an url in the window/frame designated by "framename".



function popup(url,framename) {



	var w=parent.open(url,framename,"top=200,left=200,width=400,height=200,scrollbars=1,resizable=1");



	if (w&&url.split(":")[0]=="mailto") w.close();



	else if (w&&!framename) w.focus();



}







// return the d(ate) of the q-th n-day of a specific m(onth) in a specific y(ear)



function getDateByDOW(y,m,q,n) {



// q: 1 - 5 ( 5 denotes the last n-day )



// n: 0 - Sunday, 1 - Monday ... 6 - Saturday



	var dom=new Date(y,m-1,1).getDay();



	var d=7*q-6+n-dom;



	if (dom>n) d+=7;



	if (d>fGetDays(y)[m]) d-=7;



	return d;	// ranged from 1 to 31



}







// ====== Following are self-defined and/or custom-built functions! =======







gsBottom=(NN4?"":"<DIV class='BottomDiv'>")+"<A class='BottomAnchor' href='javascript:void(0)' onclick='if(this.blur)this.blur();fClearSelected();return false;' onmouseover='return true;'>Clear All</A>"+(NN4?"":"</DIV>");











var _startc,_endc;



function fStartPop(startc,endc) {



  _startc=startc;



  _endc=endc;



  var sd=fParseDate(endc.value);



  if (!sd) sd=gEnd;



  fPopCalendar(startc, [gBegin,sd,sd]);



}







function fEndPop(startc,endc) {



  _startc=startc;



  _endc=endc;



  var sd=fParseDate(startc.value);



  if (!sd) sd=gBegin;



  fPopCalendar(endc, [sd,gEnd,sd]);



}







function fClearSelected() { // clear the date field and reset the dynamic range.



  _startc.value="";



  _endc.value="";



  fUpdSelect(0,0,0);



  gRange=[gBegin,gEnd];



  gdBegin=new Date(gBegin[0],gBegin[1]-1,gBegin[2]);



  gdEnd=new Date(gEnd[0],gEnd[1]-1,gEnd[2]);



  fRepaint();



}



















// ======= the following plugin is coded for the artificial internal dropdown seletors ========



// You may change the left,top in the fPopMenu() to adjust the popup position.



// Other Settings



var _highlite_background="black";	// highlight background color



var _highlite_fontColor="white";	// highlight font color



var _pop_length=7;	// how many months to be shown



var _pop_width=80;	// pixels of the popup width







// Override the gsCalTitle option to popup a date-selector layer. Remember to keep it as an expression or a function returning a string.



gsCalTitle="\"<a class='PopAnchor' href='javascript:void(0);' onclick='if(this.blur)this.blur();fPopMenu(this,event);return false;'>\"+gMonths[gCurMonth[1]-1]+' '+gCurMonth[0]+\"</a>\"";















function fPopMenu(dc,e) {



	var lyr=NN4?document.freeDiv0:fGetById(document,"freeDiv0");



	var bv=NN4?lyr.visibility=="show":lyr.style.visibility=="visible";



	if (bv) { fToggleLayer(0,false); return; }



	fSetDPop(gCurMonth[0],gCurMonth[1]);



	if (NN4) with (lyr) {



		left=25;



		top=4;



	} else with (lyr.style) {



		left=25+"px";



		top=4+"px";



	}



	fToggleLayer(0,true);



}







var _tmid=null;



function fSetDPop(y,m) {



	var mi=_pop_length;



	var wd=_pop_width;



	var sME=NN4||IE4?"":" onmouseover='fToggleColor(this,0)' onmouseout='fToggleColor(this,1)' ";	// menu-item focus background-color



	var padstr="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";



	var cm=fCalibrate(y,m);



	var a=[NN4||IE4||IE&&MAC?"<table border=1 cellspacing=0 cellpadding=0><tr><td>":"","<div onmouseover='clearTimeout(_tmid)' onmouseout='_tmid=setTimeout(\"fToggleLayer(0,false)\",100)'><table class='PopMenu' border=0 cellspacing=0 cellpadding=0>"];



	if(!fBfRange(cm[0],cm[1]))a.push("<tr><td align='center' class='PopMenuItem' nowrap width=",wd,sME," onclick='fSetDPop(",cm[0],",",cm[1]-mi,")'><a class='PopMenuItem' href='javascript:void(0)' onclick='if(NN4)fSetDPop(",cm[0],",",cm[1]-mi,");return false;'>",padstr,"-",padstr,"</a></td></tr>");



	for (var i=0;i<mi;i++) {



		var lm=fCalibrate(cm[0],cm[1]+i);



		if (!fIsOutRange(lm[0],lm[1]))



			a.push("<tr><td align='center' class='PopMenuItem' nowrap width=",wd,sME," onclick='fToggleLayer(0,false);fSetCal(",lm[0],",",lm[1],",0,true,event);'><a class='PopMenuItem' href='javascript:void(0)' onclick='if(NN4)fSetCal(",lm[0],",",lm[1],",0,true,event);return false;'>",gMonths[lm[1]-1]," ",lm[0],"</a></td></tr>");



	}



	if(!fAfRange(lm[0],lm[1]))a.push("<tr><td align='center' class='PopMenuItem' nowrap width=",wd,sME," onclick='fSetDPop(",cm[0],",",cm[1]+mi,")'><a class='PopMenuItem' href='javascript:void(0)' onclick='if(NN4)fSetDPop(",cm[0],",",cm[1]+mi,");return false;'>",padstr,"+",padstr,"</a></td></tr>")



	a.push("</table></div>",NN4||IE4||IE&&MAC?"</td></tr></table>":"");



	fDrawLayer(a.join(''));



}







var _cPair=[];



function fToggleColor(obj,n) {



	if (NN4||IE4) return;



	if (n==0) { // mouseover



		_cPair[0]=obj.style.backgroundColor;



		obj.style.backgroundColor=_highlite_background;



		_cPair[1]=obj.firstChild.style.color;



		obj.firstChild.style.color=_highlite_fontColor;



	} else {



		obj.style.backgroundColor=_cPair[0];



		obj.firstChild.style.color=_cPair[1];



	}



}







function fToggleLayer(id,bShow) {



	var lyr=NN4?eval("document.freeDiv"+id):fGetById(document,"freeDiv"+id);



	if (NN4) lyr.visibility=bShow?"show":"hide";



	else lyr.style.visibility=bShow?"visible":"hidden";



}







function fDrawLayer(html) {



	var lyr=NN4?document.freeDiv0:fGetById(document,"freeDiv0");



	if (IE4||IE&&MAC) lyr.style.border="0px";



	if (NN4) with (lyr.document) {



		clear(); open();



		write(html);



		close();



	} else {



		lyr.innerHTML=html+"\n";



	}



}











// ======= end of dropdown plugin ========



