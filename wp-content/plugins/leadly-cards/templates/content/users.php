<?php
/**
 * The default template for displaying users
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 */

?>

<article class="users-listing-wrapper">

	<header class="entry-header has-text-align-center header-footer-group">

		<div class="entry-header-inner section-inner medium">
			<h1 class="entry-title"><?= __('Users list from API', 'leadly'); ?></h1>
		</div><!-- .entry-header-inner -->

	</header>

	<div class="post-inner thin">

		<div class="entry-content">
			
			<div class="table-wrapper alignwide">
				<table id=data-table-users class="alignwide">
					<thead>
						<tr></tr>
					</thead>
					<?php

					$request = new WP_REST_Request( 'GET', '/leadly/plugin/users' );
					$response = rest_do_request( $request );
					$server = rest_get_server();
					$data = $server->response_to_data( $response, false );
					$json = wp_json_encode( $data );

					?>
				</table>

			</div>

		</div><!-- .entry-content -->

	</div><!-- .post-inner -->

</article><!-- .post -->

<script id="hidden-template" type="text/x-custom-template">
	<tr role="row" class="user-details">
		<td colspan="20">
			<table>
				<thead>
					<tr></tr>
				</thead>
			</table>
		</td>
	</tr>
</script>

<script type="text/javascript">
	$ = jQuery;

	// Recursive function to view all json data, with key and value, in console.
	function showData(key, value) {
		if(typeof value =='object') {
			console.log(key + ":");
			$.each(value, function (key2, value2) {
				showData(key2, value2);
			}); 
		} else {
			console.log(key, value);
		}
	}

	// build dynamic table header
	function dynamicTableHeader(columns, target) {
		var tableHeaders = '';
		
		$.each(columns, function(i, val){
			tableHeaders += "<th>" + val.toUpperCase() + "</th>";
		});
		
		target.find("thead tr").append(tableHeaders);  

		// showData(key, value);
	}

	$(document).ready(function () {
		let jsonData = <?= $json ?>;
		var tableColumn = new Array(),
			tableColumnDetails = new Array(),
			mandatoryColumns = ['id', 'name', 'username'],
			targetedTableID = $('#data-table-users'),
			detailsTemplate = $('#hidden-template').html();

		dynamicTableHeader(mandatoryColumns,targetedTableID);

		$.each(jsonData[0], function (key, value) {
			if($.inArray(key, mandatoryColumns) !== -1) {
				if(typeof value =='object') {
					tableColumn.push({
						'data' : value
					});
				} else {
					tableColumn.push({
						'data' : key
					});
				}
			}

			tableColumnDetails.push({
				'data' : key
			});

			showData(key, value);
		});

		var tableColumnDetailsHeader = new Array();
		$.each(tableColumnDetails, function (key, value) {
			tableColumnDetailsHeader.push(value.data);
		});

		table = targetedTableID.DataTable({
			data: jsonData,
			columns: tableColumn
		});

		var lastDisplayedTable = '';

		targetedTableID.find('tbody').on('click', '> tr', function () {
			if($(this).hasClass('user-details')) {
				return;
			}

			if(lastDisplayedTable.length > 0) {
				$('#' + lastDisplayedTable).remove();
			}

			var data = table.row( this ).data();
			
			$(detailsTemplate).insertAfter(this);
			var detailsTable = targetedTableID.find('.user-details');
			detailsTable.attr('id', 'for-user-' + data['id'] );
			lastDisplayedTable = 'for-user-' + data['id'];

			$.ajax({
				type: 'GET',
				url: leadlyAjax.restURL + 'leadly/plugin/user?id=' + data['id'],
				beforeSend: function (xhr) {
					xhr.setRequestHeader ('X-WP-Nounce', leadlyAjax.restNounce);
				},
				success: function (response) {
					var responseArray = new Array(response);

					dynamicTableHeader(tableColumnDetailsHeader, detailsTable);
					
					detailsTable.find('table').DataTable({
						data: responseArray,
						columns: [
							{ "data": "id"},
							{ "data": "name"},
							{ "data": "username"},
							{ "data": "email"},
							{
								data: null,
								render: function ( data, type, row ) {
									var address = "City: " + data.address.city + "</br>";
									address += "Street: " + data.address.street + "</br>";
									address += "Suite: " + data.address.suite + "</br>";
									address += "Geo location:</br> Lat " +  data.address.geo.lat + "</br>Lng " + data.address.geo.lng;
									return address;
								}
							},
							{ "data": "phone"},
							{ "data": "website"},
							{
								data: null,
								render: function ( data, type, row ) {
									var company = data.company.name + "</br>";
									company += "Catch phrase: " + data.company.catchPhrase;
									return company;
								}
							}
						],
						ordering: false,
						searching: false,
						paging: false,
						info: false
					});
				}
			});
			
		});
	});
</script>
