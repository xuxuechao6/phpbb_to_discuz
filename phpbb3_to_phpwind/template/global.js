function get_lastchild(n)
{
	var x = n.lastChild;
	while (x.nodeType!=1)
	{
		x = x.previousSibling;
	}
	return x;
}
function get_firstchild(n)
{
	var x = n.firstChild;
	while (x.nodeType!=1)
	{
		x = x.nextSibling;
	}
	return x;
}

