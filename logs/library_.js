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
	showAppTable();
	setTimeout(function () {getPos()},5000);
	document.getElementById('start_upload').onclick = (function ()
	{
		document.getElementById('upload_status').innerHTML='<span class=\"bg-secondary\">'+'Please wait...'+'</span>';
		document.getElementById('start_upload').style.visibility="hidden";
		document.getElementById('stop_upload').style.visibility="hidden";
		uploadData();
		setTimeout(function () {getPos()},40000);
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
}
function showAppTable()
{
	var requestURL='utils.php';
	var data = new FormData();
	data.append( 'doShowAppTable', 1 );
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				tableNode=document.getElementById('appTable');
				if (tbodyNode)
				{
					if (tDataTable)
					{
						tDataTable.destroy();
					}
					while (tbodyNode.hasChildNodes()) {   
					  tbodyNode.removeChild(tbodyNode.firstChild);
					}
					tbodyNode.parentNode.removeChild(tbodyNode);
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
							//console.log('td:'+folderRsn);
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

					tDataTable=$('#appTable').DataTable( {
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
	}
}
function showWardsTable()
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doShowWards', 1 );
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				tbodyNode=document.getElementById('wardsTable');
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
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
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
	let space_pos=0;
	let arg_max_length=max_str_length;
	/*
	if (str.length>60)
	{
		let check_str=str.substring(str.length-20,str.length);
		let upper_check_str=check_str.toUpperCase();
		if (check_str==upper_check_str)
		{
			console.log(check_str);
			max_str_length=max_str_length-20;
		}
	}
	*/
	if (str.length<=max_str_length)
	{
		descriptionFormatted=descriptionFormatted+str;
		return 1;
	}
	else
	{
		space_pos=str.lastIndexOf(' ', max_str_length);
		if (space_pos==-1)
		{
			descriptionFormatted=descriptionFormatted+str.substring(0, max_str_length)+'<br>';
			space_pos=max_str_length;
		}
		else
		{
			descriptionFormatted=descriptionFormatted+str.substring(0, space_pos)+'<br>';
		}
		getDescrWithLineBreak(str.substring(space_pos), arg_max_length);
	}
}
function getPos()
{
	let requestURL='utils.php';
	let data = new FormData();
	data.append( 'doGetPos', 1 );
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
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
function uploadData()
{
	let requestURL='upload_data_start.php';
	let data = new FormData();
	data.append( 'startUpload', 1 );
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
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
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
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
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				let tdNode=document.getElementById('descr_'+folderRsn);
				while (tdNode.hasChildNodes()) {   
					  tdNode.removeChild(tdNode.firstChild);
				}
				descriptionFormatted="";
				//console.log(descriptionFormatted);
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
	request = new XMLHttpRequest();
	request.open("POST",requestURL,true);
	request.send(data); 
	request.onload = function() { 
		if (request.status == 200) {
			ans_str=JSON.parse(request.response);
			if (window.ans_str.answer && ans_str.answer==1)
			{
				let tdNode=document.getElementById('idDate_'+war_id);
				tdNode.innerText=ans_str.new_date;
				setTimeout(function () {showWardsTable()},500);
			}
		}
	}
}