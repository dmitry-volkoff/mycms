function check_checkboxes(name)
{
    var i;
    var state = document.getElementsByName(name+'_all')[0].checked;
    elset=document.getElementsByTagName('input');
    
    for (i=0;i<elset.length;i++)
    {
	if(elset[i].type.toLowerCase()=="checkbox" && elset[i].name.indexOf(name) != -1)
	{
	    elset[i].checked=state;
	}
    }
}

function clear_all()
{
    var i;
    elset=document.getElementsByTagName('input');
    
    for (i=0;i<elset.length;i++)
    {
	eltype = elset[i].type.toLowerCase();
	if(eltype == "checkbox")
	{
	    elset[i].checked=false;
	} else if (eltype == "text") {
	    elset[i].value="";
	}
    }
    
    var form = document.getElementById('search_form');
    if (form.elements['_metro_stations[]'])
    {
	moveSelections(form.elements['__metro_stations[]'], form.elements['_metro_stations[]'], form.elements['metro_stations[]'], 'none');
    }
}
