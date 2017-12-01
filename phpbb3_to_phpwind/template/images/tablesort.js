
var dom = (document.getElementsByTagName) ? true : false;
var ie5 = (document.getElementsByTagName && document.all) ? true : false;
var arrowUp, arrowDown;

if (ie5 || dom)
	initSortTable();

function initSortTable() {
	arrowUp = document.createElement("SPAN");
	var tn = document.createTextNode("¡ø");
	arrowUp.appendChild(tn);
	arrowUp.className = "arrow";

	arrowDown = document.createElement("SPAN");
	var tn = document.createTextNode("¨‹");
	arrowDown.appendChild(tn);
	arrowDown.className = "arrow";
}

function SaveSortKeep(tableNode, nCol, bDesc, sType)
{
	if(tableNode.id == "")
		return;
	var strCookieValue = nCol + "," + bDesc + "," + sType;
	var strCookieName = tableNode.id + "SortKeep"
	document.cookie = strCookieName + "=" + escape(strCookieValue) + ";";
}

function sortTable(tableNode, nCol, bDesc, sType) {

	if(tableNode.getAttribute("sortkeep") == "true")
		SaveSortKeep(tableNode, nCol, bDesc, sType);
	var tBody = tableNode.tBodies[0];
	var trs = tBody.rows;
	var trl= trs.length;
	var a = new Array();
	var nosort = new Array();

	var index_a=0;
	var index_nosort=0;
	for (var i = 0; i < trl; i++) {
		if( trs[i].getAttribute('sort')=='nosort' )
		{
			nosort[index_nosort++] = trs[i];
		}
		else
			a[index_a++] = trs[i];
	}

	var start = new Date;
//	window.status = "Sorting data...";
	a.sort(compareByColumn(nCol,bDesc,sType));
//	window.status = "Sorting data done";
	
	for (var i = 0; i < a.length; i++) {
		a[i].cells[0].childNodes[0].nodeValue = i+1;
		tBody.appendChild(a[i]);
//alert(a[i].cells[0].childNodes[0].nodeValue);		
///		window.status = "Updating row " + (i + 1) + " of " + trl +
//						" (Time spent: " + (new Date - start) + "ms)";
	}
	for( i=0; i<nosort.length; i++ )
		tBody.appendChild(nosort[i]);
	
	// check for onsort
	if (typeof tableNode.onsort == "string")
		tableNode.onsort = new Function("", tableNode.onsort);
	if (typeof tableNode.onsort == "function")
		tableNode.onsort();
}

function SetSortColumnState(tableNode, nCol, bDesc) 
{
	var tHeadParent = tableNode.tHead;
	var el = tableNode.rows(0).cells(nCol);

	if (tHeadParent == null)
		return;
	
	if(el == null)
		return;


	if (tHeadParent.arrow != null) 
	{
		tHeadParent.arrow.parentNode.removeChild(tHeadParent.arrow);
	}

	el._descending = bDesc;

	if (bDesc)
		tHeadParent.arrow = arrowUp.cloneNode(true);
	else
		tHeadParent.arrow = arrowDown.cloneNode(true);

	el.appendChild(tHeadParent.arrow);
}

function LoadSortKeep(tableNode)
{
	if(tableNode.id == "")
		return;

	var strCookieValue = null;
	var strCookieName = tableNode.id + "SortKeep"

	// cookies are separated by semicolons
	var aCookie = document.cookie.split("; ");
	for (var i=0; i < aCookie.length; i++)
	{
		// a name/value pair (a crumb) is separated by an equal sign
		var aCrumb = aCookie[i].split("=");
		if (strCookieName == aCrumb[0]) 
		{
			strCookieValue = unescape(aCrumb[1]);
			break;
		}
	}

	if(strCookieValue == null)
		return;
	var aValue = strCookieValue.split(",");
	var nCol = parseInt(aValue[0]);
	var bDesc;
	if(aValue[1] == "true")
		bDesc = true;
	else
		bDesc = false;
	var sType = aValue[2];

	SetSortColumnState(tableNode, nCol, bDesc);
	sortTable(tableNode, nCol, bDesc, sType);
}

function CaseInsensitiveString(s) {
	return String(s).toUpperCase();
}

function Percent(s) 
{
	ss = parseFloat(s);
	if( isNaN(ss) )
		return -9999;
	return ss;
}

function parseDate(s) {
	return Date.parse(s.replace(/\-/g, '/'));
}

/* alternative to number function
 * This one is slower but can handle non numerical characters in
 * the string allow strings like the follow (as well as a lot more)
 * to be used:
 *    "1,000,000"
 *    "1 000 000"
 *    "100cm"
 */

function toNumber(s) {
    return Number(s.replace(/[^0-9\.]/g, ""));
}

function compareByColumn(nCol, bDescending, sType) {
	var c = nCol;
	var d = bDescending;
	
	var fTypeCast = String;
	
	if (sType == "n")
		fTypeCast = Number;
	else if (sType == "d")
		fTypeCast = parseDate;
	else if (sType == "cis")
		fTypeCast = CaseInsensitiveString;
	else if (sType == "p")
		fTypeCast = Percent;

	return function (n1, n2) {
		if (fTypeCast(getInnerText(n1.cells[c])) < fTypeCast(getInnerText(n2.cells[c])))
			return d ? -1 : +1;
		if (fTypeCast(getInnerText(n1.cells[c])) > fTypeCast(getInnerText(n2.cells[c])))
			return d ? +1 : -1;
		return 0;
	};
}

function sortColumnWithHold(e) {
	// find table element
	var el = ie5 ? e.srcElement : e.target;
	var table = getParent(el, "TABLE");
	
	// backup old cursor and onclick
	var oldCursor = table.style.cursor;
	var oldClick = table.onclick;
	
	// change cursor and onclick	
	table.style.cursor = "wait";
	table.onclick = null;
	
	// the event object is destroyed after this thread but we only need
	// the srcElement and/or the target
	var fakeEvent = {srcElement : e.srcElement, target : e.target};
	
	// call sortColumn in a new thread to allow the ui thread to be updated
	// with the cursor/onclick
	window.setTimeout(function () {
		sortColumn(fakeEvent);
		// once done resore cursor and onclick
		table.style.cursor = oldCursor;
		table.onclick = oldClick;
	}, 100);
}

function sortColumn(e) {
	var tmp = e.target ? e.target : e.srcElement;
	var tHeadParent = getParent(tmp, "THEAD");
	var el = getParent(tmp, "TD");
	if (tHeadParent == null)
		return;
		
	var nCol;
	var bDesc;

	if (el != null) {
		var p = el.parentNode;
		var i;

		// typecast to Boolean
		if( el._descending == null )
			bDesc = false;
		else
			bDesc = !Boolean(el._descending);

		// get the index of the td
		var cells = p.cells;
		var l = cells.length;
		for (i = 0; i < l; i++) {
			if (cells[i] == el) break;
		}
		nCol = i;

		var table = getParent(el, "TABLE");

		SetSortColumnState(table, nCol, bDesc);

		// can't fail
		sortTable(table, nCol, bDesc, el.getAttribute("type"));
	}
}


function getInnerText(el) {
	if (ie5) return el.innerText;	//Not needed but it is faster
	
	var str = "";
	
	var cs = el.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
		
	}
	
	return str;
}

function getParent(el, pTagName) {
	if (el == null) return null;
	else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())	// Gecko bug, supposed to be uppercase
		return el;
	else
		return getParent(el.parentNode, pTagName);
}