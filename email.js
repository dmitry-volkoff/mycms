function email(address,domain,description)
{
	var at = String.fromCharCode(64);
	if (!description) { description = address+at+domain; }
	document.writeln("<a href='mailto:"+address+at+domain+"'>"+description+"</a>");
}
