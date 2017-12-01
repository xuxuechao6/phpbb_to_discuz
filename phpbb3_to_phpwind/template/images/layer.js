var prox;
var proy;
var proxc;
var proyc;
var width1;
var height1;
function show(id,width,height){/*--打开--*/
clearInterval(prox);
clearInterval(proy);
clearInterval(proxc);
clearInterval(proyc);
width1=width;
height1=height;
var o = document.getElementById(id);
o.style.display = "block";
o.style.width = "1px";
o.style.height = "1px"; 
prox = setInterval(function(){openx(o,width1)},10);
}
function openx(o,x){/*--打开x--*/
var cx = parseInt(o.style.width);
if(cx < x)
{
o.style.width = (cx + Math.ceil((x-cx)/5)) +"px";
}
else
{
clearInterval(prox);
proy = setInterval(function(){openy(o,height1)},10);
}
}
function openy(o,y){/*--打开y--*/
var cy = parseInt(o.style.height);
if(cy < y)
{
o.style.height = (cy + Math.ceil((y-cy)/5)) +"px";
}
else
{
clearInterval(proy);
}
}
function closeed(id){/*--关闭--*/
clearInterval(prox);
clearInterval(proy);
clearInterval(proxc);
clearInterval(proyc);
clearInterval(width1);
clearInterval(height1);
var o = document.getElementById(id);
if(o.style.display == "block")
{
o.style.display = "none";
}
else
{
clearInterval(proxc);
o.style.display = "none";
}
}


/*-------------------------鼠标拖动--------转换程序中表单不允许拖动---------------------*/
/*var od = document.getElementById("fd");
var dx,dy,mx,my,mouseD;
var odrag;
var isIE = document.all ? true : false;
document.onmousedown = function(e){
var e = e ? e : event;
if(e.button == (document.all ? 1 : 0))
{
mouseD = true;
}
}
document.onmouseup = function(){
mouseD = false;
odrag = "";
if(isIE)
{
od.releaseCapture();
od.filters.alpha.opacity = 100;
}
else
{
window.releaseEvents(od.MOUSEMOVE);
od.style.opacity = 1;
}
}


//function readyMove(e){
od.onmousedown = function(e){
odrag = this;
var e = e ? e : event;
if(e.button == (document.all ? 1 : 0))
{
mx = e.clientX;
my = e.clientY;
od.style.left = od.offsetLeft + "px";
od.style.top = od.offsetTop + "px";
if(isIE)
{
od.setCapture();
od.filters.alpha.opacity = 50;
}
else
{
window.captureEvents(Event.MOUSEMOVE);
od.style.opacity = 0.5;
}

//alert(mx);
//alert(my);

} 
}
document.onmousemove = function(e){
var e = e ? e : event;
//alert(mrx);
//alert(e.button);
if(mouseD==true && odrag)
{
var mrx = e.clientX - mx;
var mry = e.clientY - my;
od.style.left = parseInt(od.style.left) +mrx + "px";
od.style.top = parseInt(od.style.top) + mry + "px";
mx = e.clientX;
my = e.clientY;

}
}*/