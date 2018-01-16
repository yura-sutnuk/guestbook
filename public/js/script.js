function getNewSortedList(field,mode)
{
    req = new XMLHttpRequest();
    req.onreadystatechange = function()
    {
        if(req.readyState==4 && req.status==200)
        {
            if(mode=="ASC")
                mode="\'DESC\'";
            else
                mode="\'ASC\'";

            posts = JSON.parse(req.responseText);
            table =  document.getElementsByTagName('table')[0];
            table.innerHTML = '<tr>'+
                '<td class="top td sortable" OnClick="getNewSortedList(\'Date\','+mode+')"> Date </td>'+
                '<td class="top td sortable" OnClick="getNewSortedList(\'Name\','+mode+')"> User Name </td>'+
                '<td class="top td sortable" OnClick="getNewSortedList(\'Email\','+mode+')"> E-mail </td>'+
                '<td class="top td"> Homepage </td>'+
                '<td class="top td" > Ip </td>'+
                '<td class="top td"> Browser </td>'+
                '<td class="top td" > Message </td>'+
                '</tr>';
               // alert(posts[0].text);
            for(i=0;i<posts.length;i++)
            {

               tr = document.createElement('tr');
               tr.innerHTML = "<td class='td' > "+ escapeHTML(posts[i].date) +" </td>"+
                    "<td class='td'>"+ escapeHTML(posts[i].name) +"</td>" +
                    "<td class='td' > "+ escapeHTML(posts[i].email) +" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].url) +" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].ip) +" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].user_agent)+" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].text) +" </td>";

               table.appendChild(tr);
            }
        }
    }
    req.open('POST','/Order'+field+'/'+mode, true);
    req.send();
}



function escapeHTML(text) {
    if(text==null)
        return '';

    return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}