function viewExposures(pointID)
{

    $('#expSection').empty();
    $(document).ready(
	function(){
	    $.post('viewExposures.php', {id: pointID},
			function(data){
				$('#expSection').append(data);
				console.log(data);
			}
		);
	}
    );
}

function editExposure(expID, pointID){
	// get a reference to the modal for editing
	var modal = $('#editModal');
	//
	modal.empty();
	$(document).ready(
		function(){
			// post the form and get response
			$.post('editExposure.php', {id: expID, pointID: pointID},
			function(data){
				// put response inside modal
				modal.html(data);
				// add close button
				modal.append(`<button class="close-button" data-close aria-label="Close modal" type="button">
				              <span aria-hidden="true">&times;</span>
                              </button>`);
				// open modal
				modal.foundation('open');
				// re-init foundation plugin
				$(document).foundation();
			});
		}
	);
}

function editPointing(pointID){
	// get a reference to the modal for editing
	var modal = $('#editModal');
	//
	modal.empty();
	$(document).ready(
		function(){
			// post the form and get response
			$.post('editPointing.php', {id: pointID},
			function(data){
				// put response inside modal
				modal.html(data);
				// add close button
				modal.append(`<button class="close-button" data-close aria-label="Close modal" type="button">
				              <span aria-hidden="true">&times;</span>
                              </button>`);
				// open modal
				modal.foundation('open');
				// re-init foundation plugin
				$(document).foundation();
			});

		}
	);
}

function showResults(data){
	hideFormDiv();
	$('#pTable').html(data);
	$('#pointing').DataTable({
		"ordering": false,
        "info":     false
	});
}

function hideFormDiv(){
    $('#form').hide('slow');
}

function showFormDiv(){
	$('#form').show('slow');
}

function respawnNoExp(){
	var options =
		{
		  url:  'viewQueue.php', // send form output to viewQueue.php
		  data: $('#submitform').serialize(),
		  success: showResults
		};

	$.ajax(options);
	$('#expSection').empty();
    return false;
}

function deletePointing(pointID){
    //delete pointing, then run original query
    $.post('deletePointing.php',{id:pointID},
	   function(data)
	   {

	       respawnNoExp();
	   }
	  );
}

function deleteExposure(expID, pointID){
    //delete exposure, then run original query
    $.post('deleteExposure.php',{id:expID,pID:pointID},
	   function(data)
	   {

	       respawnNoExp();
	       viewExposures(pointID);
	   }
	  );
}
