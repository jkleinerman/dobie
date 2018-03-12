<?
$leavebodyopen=1;
include("header.php");
?>
<div id="page-wrapper">

<div class="row">
<div class="col-lg-12">
<h1 class="page-header">Visit Door Groups</h1>
</div>
</div>

<div class="row">
<div class="col-lg-12">

<div class="select-container">
<form action="javascript:void(0)">
<div class="select-container-title">Groups</div>
<div class="select-container-body">
<input type="text" name="filter" placeholder="Filter options..." class="form-control data-filter" data-filter="visit-door-groups-select">
<select id="visit-door-groups-select" class="select-options form-control" name="visit-door-groups-select" size="2"></select>
</div>
<div class="select-container-footer">
<button id="visit-door-groups-select-add" class="btn btn-success" type="button" data-toggle="modal" data-target="#modal-new">New</button>
<button id="visit-door-groups-select-edit" class="btn btn-primary" type="button" data-toggle="modal" data-target="#modal-new" disabled>Edit</button>
<button id="visit-door-groups-select-del" class="btn btn-danger" type="button" data-toggle="modal" data-target="#modal-delete" disabled>Delete</button>
</div>
</form>
</div>

</div>
</div>

</div>

<?
include("footer.php");
?>

<!-- MODALS -->
<!-- create modal -->
<div class="modal fade" id="modal-new" tabindex="-1" role="dialog" aria-labelledby="modal-new-label" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
<h4 class="modal-title" id="modal-new-label">New Visitor Group</h4>
</div>
<div class="modal-body">

<form class="form-horizontal" id="visit-door-groups-new-form" action="#">
<div class="form-group">
 <label class="control-label col-sm-2">Name:</label>
 <div class="col-sm-10">
      <input type="text" class="form-control" id="visit-door-groups-new-name" name="name" value="" required maxlength="64">
 </div>
</div>

 <div class="wrapper">
 <div class="row">
 <div class="col-sm-5">
<!--selects--> 
<div class="select-container">
<div class="select-container-title">Zones</div>
<div class="select-container-body">
<input type="text" name="filter" placeholder="Filter options..." class="form-control data-filter" data-filter="zones-select">
<select id="zones-select" class="select-options form-control select-options-small" name="zones-select" size="2"></select>
</div>
<div class="select-container-footer">
&nbsp;
</div>
</div>

<div class="select-container" id="select-container-doors" style="display:none">
<div class="select-container-title">Doors</div>
<div class="select-container-body">
<input type="text" name="filter" placeholder="Filter options..." class="form-control data-filter" data-filter="doors-select">
<select id="doors-select" class="select-options form-control select-options-small" name="doors-select[]" size="2" multiple></select>
</div>
<div class="select-container-footer">
<button type="button" class="btn btn-success" id="doors-group-selectall">Select all</button>
<br>
</div>
</div>
 
 </div>
 <div class="col-sm-2 center valignbottom">
 <div style="margin-top:290px;"> 
<button type="button" class="btn btn-primary" id="btn-item-add" disabled><span class="fa fa-chevron-right"></span><span class="fa fa-chevron-right"></span></button>
<button type="button" class="btn btn-primary" id="btn-item-delete" disabled><span class="fa fa-chevron-left"></span><span class="fa fa-chevron-left"></span></button><br>
</div>
 </div>
 <div class="col-sm-5">

<div class="select-container" id="select-container-doors-group-current">
<div class="select-container-title">Doors in the group</div>
<div class="select-container-body">
<input type="text" name="filter" placeholder="Filter options..." class="form-control data-filter" data-filter="doors-group-current-select">
<select id="doors-group-current-select" class="select-options form-control" name="doors-group-current-select" size="2" multiple></select>
</div>
<div class="select-container-footer">
</div>
</div>
 
 </div>

 </div>
</div>
<div class="modal-footer">
<button class="btn btn-success" id="visit-door-groups-new-submit">Save</button>
</div>
</form>
</div>
</div>
</div>
<!-- /.modal -->
</div>

<!-- delete modal -->
<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-body center">
Are you sure?
</div>
<div class="modal-footer center">
<form class="form-horizontal" id="visit-door-groups-delete-form" action="#">
<button class="btn btn-success">Ok</button>
<button type="button" class="btn btn-danger" onclick="$('#modal-delete').modal('hide');">Cancel</button>
</form>
</div>
</div>
</div>
<!-- /.modal -->
</div>

<!-- error modal -->
<div class="modal fade" id="modal-error" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
<h4 class="modal-title" id="modal-error-label">&nbsp;</h4>
</div>
<div class="modal-body center">
</div>
</div>
</div>
<!-- /.modal -->
</div>

<style>
@media(min-width:768px){
#modal-new .modal-dialog{
	width:670px;
}
}
</style>

<script type="text/javascript">
//init filters
setFilterAction();

//init vars for array values
var visitDoorGroupId;
var zoneId;
var arrDoors=[];
var arrGroupDoors=[];
var editId=0;

//populate select list
populateList("visit-door-groups-select","visit_door_groups");

//onchange action on groups select
$("#visit-door-groups-select").change(function(){
	updateButtons($(this).prop("id"));
});

//onchange action on zones select
$("#zones-select").change(function(){
	zoneId=$("#zones-select").val();
	if(!isNaN(zoneId) && zoneId!="undefined"){
		//populate list
		populateList("doors-select","doors",zoneId);
		//show list
		$("#select-container-doors").fadeIn(function(){
			//populate arrays initially
			fillArrayDoors();
			fillArrayDoorGroup();
		});
		//disable add button
		$("#btn-item-add").prop("disabled",true);
	}
});

//on change door select > enable / disable right arrow button
$("#doors-select").change(function(){
	var doorId=$("#doors-select").val();
	if(doorId!="undefined") $("#btn-item-add").prop("disabled",false);
	else $("#btn-item-add").prop("disabled",true);
});

//on change current door select > enable / disable right arrow button
$("#doors-group-current-select").change(function(){
	var groupDoorId=$("#doors-group-current-select").val();
	if(groupDoorId!="undefined") $("#btn-item-delete").prop("disabled",false);
	else $("#btn-item-delete").prop("disabled",true);
});

//item add button click action
$("#btn-item-add").click(function(){
	addRight();
	//clear door selection
	$("#doors-select").val([]);
	//disable add button
	$("#btn-item-add").prop("disabled",true);
});

//item delete button click action
$("#btn-item-delete").click(function(){
	//disable del button
	addLeft();
	//clear current selection
	$("#doors-group-current-select").val([]);
	//disable del button
	$("#btn-item-delete").prop("disabled",true);
});

//select all doors action
$("#doors-group-selectall").click(function(){
	$('#doors-select option').not('.toadd').not('.todel').not('.toupd').not(":disabled").prop('selected',true);
	//if no items have been selected, disable add button
	if($("#doors-select option:selected").length>0) $("#btn-item-add").prop("disabled",false);
});

//add element from left to right
function addRight(){
	var boxOptions=$("#doors-select option:selected");
	$.each(boxOptions,function(){
		elemid=$(this).val();
		elemname=$(this).html();
		//add into new array
		arrGroupDoors[elemid]=elemname;
		//remove from old array
		arrDoors[elemid]="";
		//show in new box
		if($("#doors-group-current-select option[value='"+elemid+"']").length>0){
			//toggle if exists
			$("#doors-group-current-select option[value='"+elemid+"']").show();
			$("#doors-group-current-select option[value='"+elemid+"']").prop("disabled",false);
		} else {
			//if not, append html
			$("#doors-group-current-select").append("<option value='"+elemid+"'>"+elemname+"</option>");
		}
		//hide from older box
		$("#doors-select option[value='"+elemid+"']").hide();
		$("#doors-select option[value='"+elemid+"']").prop("disabled",true);
	});
}

//remove element from right to left
function addLeft(){
	var boxOptions=$("#doors-group-current-select option:selected");
	$.each(boxOptions,function(){
		elemid=$(this).val();
		elemname=$(this).html();
		//add into new array
		arrDoors[elemid]=elemname;
		//remove from old array
		arrGroupDoors[elemid]="";
		//show in new box
		if($("#doors-select option[value='"+elemid+"']").length>0){
			//toggle if exists
			$("#doors-select option[value='"+elemid+"']").show();
			$("#doors-select option[value='"+elemid+"']").prop("disabled",false);
		} else {
			//if not, append html
			$("#doors-select").append("<option value='"+elemid+"'>"+elemname+"</option>");
		}
		//hide from older box
		$("#doors-group-current-select option[value='"+elemid+"']").hide();
		$("#doors-group-current-select option[value='"+elemid+"']").prop("disabled",true);
	});
}

//init array doors
function fillArrayDoors(){
	arrDoors=[];
	var boxOptions=document.querySelectorAll("#doors-select option");
	if(boxOptions.length>0){
		boxOptionsArr = boxOptions;
		for(var i=0;i<boxOptionsArr.length;i++){
			if(boxOptionsArr[i].className==""){
				var doorid=boxOptionsArr[i].value;
				var doorname=boxOptionsArr[i].innerHTML;
				arrDoors[doorid]=doorname;
			}
		}
	}
}

//init array door group
function fillArrayDoorGroup(){
	arrGroupDoors=[];
	var boxOptions=document.querySelectorAll("#doors-group-current-select option");
	if(boxOptions.length>0){
		boxOptionsArr = boxOptions;
		for(var i=0;i<boxOptionsArr.length;i++){
			var doorid=boxOptionsArr[i].value;
			var doorname=boxOptionsArr[i].innerHTML;
			arrGroupDoors[doorid]=doorname;
		}
	}
}

function resetForm(){
	//empty both arrays
	arrGroupDoors=[];
	arrDoors=[];
	//group name
	$("#visit-door-groups-new-name").val("");
	//clear all selections
	$("#doors-select,#doors-group-current-select").val([]);
	//disable all buttons
	$("#btn-item-add,#btn-item-delete").prop("disabled",true);
	//hide doors container
	$("#select-container-doors").hide();
	//empty current door group select
	$("#doors-group-current-select").empty();
	//clear group id value if edit
	editId=0;
	//modal title
	$("#modal-new-label").text("New Visitor Group");
}

//fetch info for new
$('#modal-new').on('show.bs.modal', function (event){
	//populate zones select list
	populateList("zones-select","zones");
	//clear all previous values
	resetForm();
});

//fetch info for edit
$('#visit-door-groups-select-edit').click(function (event){
	//clear all previous values
	resetForm();
	var visitDoorGroupId = $("#visit-door-groups-select").val();
	$.ajax({
		type: "POST",
		url: "process",
		data: "action=get_visit_door_group&id=" + visitDoorGroupId,
		success: function(resp){
			if(resp[0]=='1'){
				//populate fields with rec info
				var values = resp[1];
				editId=values.id;
				$('#visit-door-groups-new-name').val(values.name);
				//modal title
				$("#modal-new-label").text("Edit Visitor Group");
				//fetch group doors
				$.ajax({
					type: "POST",
					url: "process",
					data: "action=get_visit_door_group_doors&id=" + visitDoorGroupId,
					success: function(doors_resp){
						if(doors_resp[0]=='1'){
							//fill select with door data
							var doors_values = doors_resp[1];
							for(var i=0;i<doors_values.length;i++){
								$("#doors-group-current-select").append("<option value='"+doors_values[i].id+"'>"+doors_values[i].name+"</option>");
							}
							//fill group door array
							fillArrayDoorGroup();
						}//else skip > door group can have zero doors
					},
					failure: function(){
						//show modal error
						$('#modal-error .modal-body').text("Operation failed, please try again");
						$("#modal-error").modal("show");
					}
				});
			} else {
				//show modal error
				$('#modal-error .modal-body').text(resp[1]);
				$("#modal-error").modal("show");
			}
		},
		failure: function(){
			//show modal error
			$('#modal-error .modal-body').text("Operation failed, please try again");
			$("#modal-error").modal("show");
		}
	});
});

/*
TODO:
- ver de hacer que al popular la doors list, que muestre todas las puertas como opcion que no estén todavia en el door group select
- ver que al remover una puerta del door group, la agrege al door select sólo si es parte del mismo
*/

//new/edit action
$("#visit-door-groups-new-form").submit(function(){

	var visitDoorGroupName = $("#visit-door-groups-new-name").val();
	//build door id array with all the values that have a non empty name
	var visitDoorGroupDoors = [];
	for (var k in arrGroupDoors){
		if (typeof arrGroupDoors[k] !== 'function' && arrGroupDoors[k]!=""){
			visitDoorGroupDoors.push(k);
		}
	}
	//build action string if its create or edit
	if(editId!=0 && !isNaN(editId)) action_str = "action=edit_visit_door_group&id=" + editId +"&name=" + visitDoorGroupName + "&doorids=" + visitDoorGroupDoors.join("|");//edit
	else action_str = "action=add_visit_door_group&name=" + visitDoorGroupName + "&doorids=" + visitDoorGroupDoors.join("|");//create

	$.ajax({
		type: "POST",
		url: "process",
		data: action_str,
		success: function(resp){
			if(resp[0]=='1'){
				//close modal
				$("#modal-new").modal("hide");
				//repopulate select box
				populateList("visit-door-groups-select","visit_door_groups");
			} else {
				//show modal error
				$('#modal-error .modal-body').text(resp[1]);
				$("#modal-error").modal("show");
			}
		},
		failure: function(){
			//show modal error
			$('#modal-error .modal-body').text("Operation failed, please try again");
			$("#modal-error").modal("show");
		}
	});
	return false;
});

//delete action
$("#visit-door-groups-delete-form").submit(function(){
	var visitDoorGroupId = $("#visit-door-groups-select").val();

	if(!isNaN(visitDoorGroupId)){
		$.ajax({
			type: "POST",
			url: "process",
			data: "action=delete_visit_door_group&id=" + visitDoorGroupId,
			success: function(resp){
				if(resp[0]=='1'){
					//close modal
					$("#modal-delete").modal("hide");
					//repopulate select box
					populateList("visit-door-groups-select","visit_door_groups");
				} else {
					//show modal error
					$('#modal-error .modal-body').text(resp[1]);
					$("#modal-error").modal("show");
				}
			},
			failure: function(){
				//show modal error
				$('#modal-error .modal-body').text("Operation failed, please try again");
				$("#modal-error").modal("show");
			}
		});
	} else {
		//invalid values sent
		$('#modal-error .modal-body').text("Invalid values sent");
		$("#modal-error").modal("show");
	}
	return false;
});
</script>

</body>
</html>