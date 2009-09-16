function addBookmark(url, title)
{
 if (!url) url = location.href;
 if (!title) title = document.title;
 //Gecko
 if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) window.sidebar.addPanel (title, url, "");
 //IE4+
 else if (typeof window.external == "object") window.external.AddFavorite(url, title);
 //Opera7+
 else if (window.opera && document.createElement)
 {
   var a = document.createElement('A');
   if (!a) return false; //IF Opera 6
   a.setAttribute('rel','sidebar');
   a.setAttribute('href',url);
   a.setAttribute('title',title);
   a.click();
 }
 else return false; 
 return true;
}
