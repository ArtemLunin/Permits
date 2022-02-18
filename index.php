<?php
//session_start();
?>
<!DOCTYPE html>
<html>
<head>
	<title>Building Permit Status</title>
	<script src="./js/jquery-3.4.1.min.js"></script>
<script src="./js/popper.min.js"></script>
<link rel="stylesheet" href="./css/bootstrap.min.css">
<script src="./js/bootstrap.min.js"></script>
<script type="text/javascript" src="./library.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<div  class="container-fluid">
	<div class="row">
	<div class="col-sm-7">
		<div id="upload_status"></div>
		<div>
			<select id="wards_select">Select ward</select>
		</div>
	</div>
	<div class="col-sm-1">
		<div><button type="button" class="btn btn-primary" id="start_upload" style="visibility: hidden;" title="Start upload data">Run</button></div>
		<div><button type="button" class="btn btn-warning" id="stop_upload" style="visibility: hidden;" title="Stop upload data">Stop</button></div>
	</div>
    <div class="col-sm-4">
	<a href="#" id="showWards" class="card-link" title="show wards info">Building Permit Status&nbsp;</a>
	</div>
	</div>
</div>
<div class="col-sm-12">
<table class="table table-bordered table-sm compact stripe nowrap" id="appTable">
	<thead>
		<tr>
			<td>Hide</td>
			<td>Application#</td>
			<td>Ward</td>
			<td>Address</td>
			<td>Application Type</td>
			<td>Description</td>
			<td>Date</td>
			<!--<td>Status Code</td>-->
			<td>Status</td>
		</tr>
	</thead>
	<tfoot></tfoot>
</table>
</div>
</body>
</html>

<div id="wardInfo" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Wards Info</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <div class="row justify-content-md-center px-5 py-2">
      <div class="row container-fluid" style=" overflow-y: scroll;">
      <table class="table table-hover table-sm table-striped table-bordered">
        <thead>
      <tr>
        <th>Ward</th>
        <th>Update date</th>
        <th>Done</th>
      </tr>
      </thead>
      <tbody id="wardsTable">
      </tbody>
      </table>
    </div>
    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<div id="dialogModal" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="titleDialogModal">Question</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      	<p id="questionDialogModal"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
        <button type="button" class="btn btn-primary" modal-command="" id="btnDialogModal">Yes</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_signIn" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document" >
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Login to the protected area</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group">
            <label for="signIn_username" class="col-form-label">username:</label>
            <input type="text" required class="form-control" id="signIn_username">
          </div>
          <div class="form-group">
            <label for="signIn_password" class="col-form-label">password:</label>
            <input type="password" required class="form-control" id="signIn_password">
          </div>
        </form>
                  <div class="bg-warning text-dark" id="errMsg" style="visibility: hidden;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button id="btn_signIn" type="button" class="btn btn-primary">SignIn</button>
      </div>
    </div>
  </div>
</div>