let descriptionFormatted="";
let tDataTable=0;
let tbodyNode=0;
var entityMap = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
  '/': '&#x2F;',
  '`': '&#x60;',
  '=': '&#x3D;'
};

function escapeHtml (string) {
  return String(string).replace(/[&<>"'`=\/]/g, function (s) {
    return entityMap[s];
  });
}

document.addEventListener("DOMContentLoaded", ready);
function ready() {
	// for local use - disable check user rights
	//if (getCookie('isLogined')==1)
	//{

		const btnStart = document.querySelector('#start_upload'),
			dateStart = document.querySelector('#startDate'),
			dateEnd = document.querySelector('#endDate');

		const today = new Date();
		const d = new Date(today);
		d.setMonth(d.getMonth() - 2);
		d.setDate(1);
  		dateStart.value = d.toISOString().split('T')[0];
		dateEnd.value = today.toISOString().split('T')[0];;
		
		showAppTable('W01');
		setTimeout(function () {showWardsList()},3000);
		setTimeout(function () {getPos()},5000);
	//}
	//else
	//{
	//	showSignIn();
	//}
	document.getElementById('start_upload').onclick = (function ()
	{
		document.getElementById('upload_status').innerHTML='<span class=\"bg-secondary\">'+'Please wait...'+'</span>';
		document.getElementById('start_upload').style.visibility="hidden";
		document.getElementById('stop_upload').style.visibility="hidden";
		uploadData(dateStart.value, dateEnd.value);
		setTimeout(function () {getPos()}, 40000);
	});
	document.getElementById('stop_upload').onclick = (function ()
	{
		document.getElementById('titleDialogModal').innerText="Stop upload";
			document.getElementById('questionDialogModal').innerText="Stop the upload process from the server? Data for this ward will not be fully downloaded.";
			var modalCommand=document.getElementById('btnDialogModal');
			modalCommand.setAttribute('modal-command','stop_upload');
			$('#dialogModal').modal({
		  		keyboard: true
			});
	});
	document.getElementById('showWards').onclick = (function ()
	{
		showWardsTable();
	});
	document.getElementById('btnDialogModal').onclick = (function()
	{
		var modalCommand=this.getAttribute('modal-command');
		switch(modalCommand)
		{
			case 'stop_upload':
				stopUploadData();
				setTimeout(function () {getPos()},40000);
				$('#dialogModal').modal('hide');
				return true;
				break;
			default:
				return false;
		}
	});
	document.getElementById('wards_select').onchange =(function()
	{
		changeWard(this.value);
	});

	dateStart.addEventListener('change', () => {
		const dateStartObj = new Date(dateStart.value);
		const dateEndObj = new Date(dateEnd.value);
		if (dateStartObj > dateEndObj) {
			dateEnd.value = dateStart.value;
		}
	});

	dateEnd.addEventListener('change', () => {
		const dateStartObj = new Date(dateStart.value);
		const dateEndObj = new Date(dateEnd.value);
		if (dateStartObj > dateEndObj) {
			dateStart.value = dateEnd.value;
		}
	});

	document.getElementById('btn_signIn').onclick = (function ()
	{
		var requestURL='utils.php';
		var data = new FormData();
		data.append('sign_in', 1);
		data.append('username', document.getElementById('signIn_username').value);
		data.append('password', document.getElementById('signIn_password').value);
		rSignIn = new XMLHttpRequest();
		rSignIn.open("POST",requestURL,true);
		rSignIn.send(data);   
		rSignIn.onload = function() {   
			if (rSignIn.status == 200) {
				ans_str=JSON.parse(rSignIn.response);
				if (window.ans_str.answer && ans_str.answer==1){
					setCookie('isLogined', 1 , 1);
					//$('#modal_signIn').modal('hide');
					//	setTimeout(function () {showAppTable()},500);
					document.location.href = "index.php";
				}
				else
				{
					console.log(ans_str);
				}
			}
			else if (rSignIn.status == 401)
			{
				document.getElementById('errMsg').innerText="wrong password or username";
				document.getElementById('errMsg').style.visibility="visible";
			}
		}
	});
}
function showAppTable(ward='W01')
{
	var requestURL='utils.php';
	var data = new FormData();
	data.append( 'doShowAppTable', ward );
	rshowAppTable = new XMLHttpRequest();
	rshowAppTable.open("POST",requestURL,true);
	rshowAppTable.send(data); 
	rshowAppTable.onload = function() { 
		if (rshowAppTable.status == 200) {
			ans_str=JSON.parse(rshowAppTable.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				tableNode=document.getElementById('appTable');
				if (tbodyNode!=0)
				{
					tableNode.removeChild(tbodyNode);
				}

				tbodyNode=document.createElement('tbody');
				ans_str.appTableData.forEach((row_data, index, array) => {
					var trNode=document.createElement('tr');
					let row_id=0;
					let folderRsn=0;
					for (cell_idx in row_data)
					{
						var tdNode=document.createElement('td');
						if (cell_idx=='id')
						{
							row_id=row_data[cell_idx];
							let aDelete=document.createElement('a');
							aDelete.classList.add('text-center', 'material-icons', 'md-24');
							aDelete.innerText='delete';
							aDelete.setAttribute('title', 'Delete this application');
							aDelete.setAttribute('href', '#');
							aDelete.onclick = (function()
							{
								hideApplication(row_id);
							});
							tdNode.appendChild(aDelete);
						}
						else if (cell_idx=='folderRsn')
						{
							folderRsn=row_data[cell_idx];
							continue;
						}
						else if (cell_idx=='address')
						{
								descriptionFormatted="";
								let descr_out=getDescrWithLineBreak(row_data[cell_idx], 25);
								tdNode.innerHTML=descriptionFormatted;
						}
						else if (cell_idx=='description')
						{
							descriptionFormatted="";
							if ((row_data[cell_idx])=='no_data') 
							{
								adescr=document.createElement('a');
								adescr.innerText='Update description';
								adescr.classList.add('card-link');
								adescr.setAttribute('href','#');
								adescr.setAttribute('title', 'Update description');
								adescr.onclick = (function()
								{
									updDescription(folderRsn);
								});
								tdNode.setAttribute('id','descr_'+folderRsn);
								tdNode.appendChild(adescr);
							}
							else
							{
								let descr_out=getDescrWithLineBreak(escapeHtml(row_data[cell_idx]), 80);
								tdNode.innerHTML=descriptionFormatted;
							}
						}
						else
						{
							tdNode.innerText=row_data[cell_idx];	
						}
						trNode.appendChild(tdNode);
					}
					trNode.setAttribute('id',row_id);
					tbodyNode.appendChild(trNode);
				});
				tableNode.appendChild(tbodyNode);

					//tDataTable=$('#appTable').DataTable( {
						$('#appTable').DataTable( {
						destroy: true,
					    paging: true,
					    searching: true,
						pageLength: 15,
						//"columnDefs": [
	   					//		{ "orderable": false, "targets": 0 },],
					    "order": [[ 2, 'asc' ]],
					} );
			}
			else
			{
				console.log(window.ans_str);
			}
		}
		else if (rshowAppTable.status == 401)
		{
			showSignIn();
		}
	}
}
function showWardsTable()
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doShowWards', 1 );
	rshowWardsTable = new XMLHttpRequest();
	rshowWardsTable.open("POST",requestURL,true);
	rshowWardsTable.send(data); 
	rshowWardsTable.onload = function() { 
		if (rshowWardsTable.status == 200) {
			ans_str=JSON.parse(rshowWardsTable.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				let tbodyNode=document.getElementById('wardsTable');
				while (tbodyNode.hasChildNodes()) {   
				  tbodyNode.removeChild(tbodyNode.firstChild);
				}
				ans_str.wardsTableData.forEach((row_data, index, array) => {
					var trNode=document.createElement('tr');
					let row_id=0;
					for (cell_idx in row_data)
					{
						if (cell_idx=='id')
						{
							row_id=row_data[cell_idx];
							continue;
						}
						else
						{
							var tdNode=document.createElement('td');
							if (cell_idx=='complete_update')
							{
								let aCron=document.createElement('a');
								aCron.classList.add('text-center');
								if (row_data[cell_idx]=='1')
									aCron.innerText='Y';
								else
									aCron.innerText='N';
								aCron.setAttribute('title', 'Upload it first');
								aCron.setAttribute('href', '#');
								aCron.onclick = (function()
								{
									cronWar(row_id);
								});
								tdNode.appendChild(aCron);
							}
							else if (cell_idx=='last_update')
							{
								tdNode.setAttribute('id','idDate_'+row_id);
								tdNode.innerText=row_data[cell_idx];	
							}
							else
							{
								tdNode.innerText=row_data[cell_idx];	
							}
						}
						trNode.appendChild(tdNode);
					}
					trNode.setAttribute('id',row_id);
					tbodyNode.appendChild(trNode);
				});
				$('#wardInfo').modal({
						keyboard: true
					});
			}
			else
			{
				console.log(window.ans_str);
			}
		}
	}
}
function hideApplication(id) {
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doHideApplication', 1 );
	data.append( 'row_id', id );
	rHideApplication = new XMLHttpRequest();
	rHideApplication.open("POST",requestURL,true);
	rHideApplication.send(data); 
	rHideApplication.onload = function() { 
		if (rHideApplication.status == 200) {
			ans_str=JSON.parse(rHideApplication.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				document.getElementById(ans_str.row_id).remove();
			}
			else
			{
				console.log(window.ans_str);
			}
		}
		
	}

}
function getDescrWithLineBreak(str, max_str_length=90)
{
	// let space_pos=0;
	// let arg_max_length=max_str_length;
	// if (str.length<=max_str_length)
	// {
	// 	descriptionFormatted=descriptionFormatted+str;
	// 	return 1;
	// }
	// else
	// {
	// 	space_pos=str.lastIndexOf(' ', max_str_length);
	// 	if (space_pos==-1)
	// 	{
	// 		descriptionFormatted=descriptionFormatted+str.substring(0, max_str_length)+'<br>';
	// 		space_pos=max_str_length;
	// 	}
	// 	else
	// 	{
	// 		descriptionFormatted=descriptionFormatted+str.substring(0, space_pos)+'<br>';
	// 	}
	// 	getDescrWithLineBreak(str.substring(space_pos), arg_max_length);
	// }
	let regex = new RegExp(`(.{1,${max_str_length}})(\\s|$)`,'g');
	let arr_str = str.match(regex).map(chunk => chunk.trim());
	descriptionFormatted = arr_str.join('<br>');
}
function getPos()
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doGetPos', 1 );
	rGetPos = new XMLHttpRequest();
	rGetPos.open("POST",requestURL,true);
	rGetPos.send(data); 
	rGetPos.onload = function() { 
		if (rGetPos.status == 200) {
			ans_str=JSON.parse(rGetPos.response);
			if (window.ans_str.answer)
			{
				if(ans_str.answer==1)
				{
					document.getElementById('upload_status').innerHTML='<span class=\"bg-info\">Ward: '+ans_str.ward+', '+ans_str.pos+' of '+ans_str.amount+' ('+Math.round((ans_str.pos/ans_str.amount)*100)+'%)'+', date start: '+ans_str.date_start+', loading is in progress: '+getUploadDur(ans_str.time_work)+'...</span>';
					document.getElementById('start_upload').style.visibility="hidden";
					document.getElementById('stop_upload').style.visibility="visible";
					setTimeout(function () {getPos()},30000);

				}
				else
				{
					document.getElementById('upload_status').innerHTML='<span class=\"bg-warning\">'+'No current uploads'+'</span>';
					document.getElementById('start_upload').style.visibility="visible";
					document.getElementById('stop_upload').style.visibility="hidden";
					setTimeout(function () {getPos()},600000);
				}
			}
			else
			{
				console.log(window.ans_str);
			}
		}
		
	}
}
function getUploadDur(time_work)
{
	let dur_min=time_work/60>>0;
	let result_dur="";
	if (dur_min<5)
	{
		result_dur=dur_min+" min "+(time_work%60)+" sec";
	}
	else if(dur_min<60)
	{
		result_dur=dur_min+" min";
	}
	else
	{
		let dur_hr=time_work/3600>>0;
		dur_min=(time_work-dur_hr*3600)/60>>0;
		result_dur=dur_hr+" hr "+dur_min+" min";
	}
	return result_dur;
}
function uploadData(dateStart, dateEnd)
{
	let requestURL='upload_data_start.php';
	let data = new FormData();
	data.append('startUpload', 1);
	data.append('dateStart', dateStart);
	data.append('dateEnd', dateEnd);
	rUploadData = new XMLHttpRequest();
	rUploadData.open("POST",requestURL,true);
	rUploadData.send(data); 
	rUploadData.onload = function() { 
		if (rUploadData.status == 200) {
			ans_str=JSON.parse(rUploadData.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				setTimeout(function () {showAppTable()},5000);
				//document.location.href = "index.php";
			}
			else if(window.ans_str.text)
			{
				document.getElementById('upload_status').innerHTML='<span class=\"bg-warning\">'+ans_str.text+'</span>';
				document.getElementById('start_upload').style.visibility="visible";
				document.getElementById('stop_upload').style.visibility="hidden";
				return false;
			}
		}
		else
			return false;
	}
	return true;
}
function stopUploadData()
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'stopUpload', 1 );
	rStopUploadData = new XMLHttpRequest();
	rStopUploadData.open("POST",requestURL,true);
	rStopUploadData.send(data); 
	rStopUploadData.onload = function() { 
		if (rStopUploadData.status == 200) {
			ans_str=JSON.parse(rStopUploadData.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				document.getElementById('start_upload').style.visibility="visible";
				document.getElementById('stop_upload').style.visibility="hidden";
			}
		}
	}
}
function updDescription(folderRsn)
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doUpdDescr', folderRsn );
	rUpdDescription = new XMLHttpRequest();
	rUpdDescription.open("POST",requestURL,true);
	rUpdDescription.send(data); 
	rUpdDescription.onload = function() { 
		if (rUpdDescription.status == 200) {
			ans_str=JSON.parse(rUpdDescription.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				let tdNode=document.getElementById('descr_'+folderRsn);
				while (tdNode.hasChildNodes()) {   
					  tdNode.removeChild(tdNode.firstChild);
				}
				descriptionFormatted="";
				getDescrWithLineBreak(ans_str.description);
				tdNode.innerHTML=descriptionFormatted;
			}
		}
	}
}
function cronWar(war_id)
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doSetNextWard', war_id );
	rCronWar = new XMLHttpRequest();
	rCronWar.open("POST",requestURL,true);
	rCronWar.send(data); 
	rCronWar.onload = function() { 
		if (rCronWar.status == 200) {
			ans_str=JSON.parse(rCronWar.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				let tdNode=document.getElementById('idDate_'+war_id);
				tdNode.innerText=ans_str.new_date;
				setTimeout(function () {showWardsTable()},500);
			}
		}
	}
}
function showWardsList()
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doShowWardsList', 1 );
	rShowWardsList = new XMLHttpRequest();
	rShowWardsList.open("POST",requestURL,true);
	rShowWardsList.send(data); 
	rShowWardsList.onload = function() { 
		if (rShowWardsList.status == 200) {
			ans_str=JSON.parse(rShowWardsList.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				selectNode=document.getElementById('wards_select');
				while (selectNode.hasChildNodes()) {   
				  selectNode.removeChild(selectNode.firstChild);
				}
				let war_id=1;
				ans_str.wardsTableData.forEach((row_data, index, array) => {
					optionNode=document.createElement('option');
					for (cell_idx in row_data)
					{
						if (cell_idx=='ward')
						{
							optionNode.setAttribute('value', row_data[cell_idx]);
							continue;
						}
						else
						{
							optionNode.innerText=war_id+'. '+row_data[cell_idx];
						}
					}
					war_id++;
					selectNode.appendChild(optionNode);
				});
			}
			else
			{
				console.log(window.ans_str);
			}
		}
	}
}
function changeWard(wards_select)
{
	showAppTable(wards_select);
}
function showSignIn()
{
	document.getElementById('errMsg').style.visibility="hidden";
	document.getElementById('errMsg').innerText="";
	$('#modal_signIn').modal('show');

}
function getCookie(cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  var expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}