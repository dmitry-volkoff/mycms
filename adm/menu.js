function minimize_menu()
{
    document.getElementById("actmenu").style.visibility = "hidden";
    document.getElementById("actmenu").style.display = "none";
    document.getElementById("menu_act_img").src = "img/menu_max.gif";
    document.getElementById("menu_act_href").href = "javascript:maximize_menu();";
}

function maximize_menu()
{
    document.getElementById("actmenu").style.visibility = "visible";
    document.getElementById("actmenu").style.display = "inline";
    document.getElementById("menu_act_img").src = "img/menu_min.gif";
    document.getElementById("menu_act_href").href = "javascript:minimize_menu();";
}
