var hT, sT;  // hide() Show()

function getNewSortedList(field,mode)
{
    field = field || 'Name';
    mode = mode || "DESC"

    req = new XMLHttpRequest();
    req.onreadystatechange = function()
    {
        if(req.readyState==4 && req.status==200)
        {
            if(mode=="ASC" || mode==null)
                mode="\'DESC\'";
            else
                mode="\'ASC\'";

            posts = JSON.parse(req.responseText);

            table =  document.getElementById('table');
            table.innerHTML = '<tr>'+
                '<td class="top td clickable" OnClick="getNewSortedList(\'Date\','+mode+')"> Date </td>'+
                '<td class="top td clickable" OnClick="getNewSortedList(\'Name\','+mode+')"> User Name </td>'+
                '<td class="top td clickable" OnClick="getNewSortedList(\'Email\','+mode+')"> E-mail </td>'+
                '<td class="top td"> Homepage </td>'+
                '<td class="top td" > Ip </td>'+
                '<td class="top td"> Browser </td>'+
                '<td class="top td" width="300px" > Message </td>'+
                '<td class="top td "> Attachment</td>'+
                '</tr>';
            for(i=0;i<posts.length;i++)
            {

               tr = document.createElement('tr');
               tr.innerHTML = "<td class='td' > "+ escapeHTML(posts[i].date) +" </td>"+
                    "<td class='td'>"+ escapeHTML(posts[i].name) +"</td>" +
                    "<td class='td' > "+ escapeHTML(posts[i].email) +" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].url) +" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].ip) +" </td>"+
                    "<td class='td' > "+ escapeHTML(posts[i].user_agent)+" </td>"+
                    "<td class='td' width='300px' > "+ posts[i].text +" </td>"+
                    "<td class='td clickable'> <span onclick=\"   showFile('/files/"+escapeHTML (posts[i].attachment)+"' )     \"  > "+ escapeHTML (posts[i].attachment) +"</span></td>";

               table.appendChild(tr);
            }
        }
    }
    req.open('POST','/Order'+field+'/'+mode, true);
    req.send();
}

function preview(form)
{
    var req = new XMLHttpRequest();
    req.onreadystatechange = function()
    {
        if(req.readyState == 4 && req.status == 200)
        {
            var data = JSON.parse(req.responseText);
            var TA = document.getElementById('form_text');
            moveTextToTA(TA);

            var table = document.querySelector('#table');
            var file = document.querySelector('#form_attachment').files[0];

            var tr = document.querySelector('#preview');
            if(tr === null)
            {
               tr = document.createElement('tr');
            }
            tr.id='preview';
            var strHTML = "<td class='td' > "+ escapeHTML(data['date']) +" </td>"+
                "<td class='td'>"+ escapeHTML(form_name.value) +"</td>" +
                "<td class='td' > "+ escapeHTML(form_email.value) +" </td>"+
                "<td class='td' > "+ escapeHTML(form_url.value) +" </td>"+
                "<td class='td' > "+ escapeHTML(data['ip']) +" </td>"+
                "<td class='td' > "+ escapeHTML(data['browser'])+" </td>"+
                "<td class='td' width='300px' > "+ escapeHTML(form_text.value) +" </td>";

            if(typeof file == "undefined")
                strHTML += "<td class='td'> </td>";
            else
                strHTML += "<td class='td'> "+ escapeHTML (file.name) +"</td>";

            tr.innerHTML = strHTML;
            table.appendChild(tr);

        }
    }

    req.open('POST','/getIpDateBrowser', true);
    req.send();

}
function escapeHTML(html)
{
    if(html==null)
        return '';

    var html = html.replace("<b>","||b||").replace("<i>","||i||").replace("<a>","||a||").replace("<u>","||u||");
    html = html.replace("<code>","||code||").replace("<s>","||s||").replace("<strong>","||strong||").replace("<del>","||del||");
    html = html.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    var tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    html = tmp.innerText;
    html =  html.replace("||b||","<b>").replace("||i||","<i>").replace("||a||","<a>").replace("||u||","<u>");
    html = html.replace("||code||","<code>").replace("||s||","<s>").replace("||strong||","<strong>").replace("||del||","<del>");
    return html;
}

function checkFileType(fileInput)
{
    var file = fileInput.files[0] ;
    var extension = file.name.split('.')[1];
    if(extension == 'txt')
    {
        if(file.size > 100 * 1024)
        {
            alert('размер файла не должен привышать 100кб');
            file.value='';
        }
    }
    else if(extension == 'jpg' || extension =='gif' || extension == 'png')
    {

    }
    else
    {
        alert ('недопустимый формат файла');
        file.value='';

    }
}
function showFile(fileName )
{
    var req = new XMLHttpRequest();
    req.onreadystatechange = function ()
    {
        if(req.readyState == 4 && req.status==200)
        {
            var div = document.querySelector('#fileView');
            var response = JSON.parse(req.responseText);
            div.innerHTML = '';
            if(response['error'] == "false")
            {
                if (response['isText'] == "true")
                {
                    div.innerHTML = response['fileContent'];
                }
                if (response['isText'] == "false")
                {
                    var img = document.createElement('img');
                    img.src = fileName;
                    div.appendChild(img);
                }
                div.style.display = 'block';
                Show();
            }
            else
            {
                div.style.display = 'none';
                alert('ошибка, файл не найден');
                return;
            }
        }
    }

    req.open('POST', '/get'+fileName, true);
    req.send();
}
function hide()
{
    var obj = document.getElementById('fileView');
    var op = (obj.style.opacity)?parseFloat(obj.style.opacity):parseInt(obj.style.filter)/100;
    if(op > 0 ) {
        clearTimeout(sT);
        op -= 0.1;
        obj.style.opacity = op;
        obj.style.filter='alpha(opacity='+op*100+')';
        hT=setTimeout('hide()',30);
    }
    else
    {
        document.querySelector('#fileView').style.display='none';
    }
}
function Show()
{

    var obj = document.getElementById('fileView');
    op = (obj.style.opacity)?parseFloat(obj.style.opacity):parseInt(obj.style.filter)/100;
    if(op < 1) {

        clearTimeout(hT);
        op += 0.1;
        obj.style.opacity = op;
        obj.style.filter='alpha(opacity='+op*100+')';
        sT=setTimeout('Show()',30);
    }
}
function checkData(form)
{
    var elements = document.getElementsByTagName('input');
    var errors = document.getElementsByClassName('error');

    var textarea = (document.getElementById('form_text'));
    var files = document.getElementById('form_attachment');
    var regulas = [/^[a-zA-Z0-9]+$/, // name field
        /@+./,// email
        /(^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.-]*)*\/?$)|(^$)/ //url
    ]
    moveTextToTA(textarea);
    var formData = new FormData();
    var hasErrors = false;
    for(var i=0; i<elements.length-2; i++)
    {
        if(checkField(elements[i].value,regulas[i]) == false)
        {
            errors[i].innerHTML = 'Не верное значение поля';
            hasErrors = true;
        }
        else
        {
            formData.append(elements[i].name, elements[i].value);
            errors[i].innerHTML ='';
        }
    }

    var captcha = document.getElementById('form_captcha').value;
    formData.append('attachment', files.files[0]);
    formData.append('captcha', captcha);
    formData.append(textarea.name, textarea.value);


    if(hasErrors==false)
    {
        sendData(formData);
    }
}
function checkField(value,pattern)
{
    return new RegExp(pattern).test(value);
}
function moveTextToTA(textarea)
{
    var div = document.getElementsByClassName(' nicEdit-main  ')[0];
    textarea.value = div.innerHTML;
}
function sendData(formData)
{
    var req = new XMLHttpRequest();
    req.onreadystatechange = function ()
    {
        if (req.readyState == 4 && req.status == 200)
        {
            if (req.responseText == '1')
            {
                getNewSortedList(); // если предпросмотра не было то просто обновляем список
                alert('запись успешно добавлена');
            }
            else if (req.responseText == '0') {
                alert('не верно введена капча');
            }
            reloadCaptcha();
        }
    }
    req.open("post", "/addFormData");
    req.send(formData);
}
function reloadCaptcha()
{
    var captcha = document.getElementById('captcha');
    captcha.src = '/captcha'+'?'+Math.random();
}