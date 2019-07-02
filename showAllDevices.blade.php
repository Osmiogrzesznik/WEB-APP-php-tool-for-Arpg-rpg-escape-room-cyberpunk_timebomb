<?php
$conn = $this->db_connection;
$sql = "SELECT * FROM device";// WHERE class = '$class'"; later  -> WHERE user_creator_id = :logged_user_id



$query = $conn->prepare($sql);
$result = $query->execute();
$result->setFetchMode(PDO::FETCH_ASSOC);


$columns = array();
$resultset = array();

# Set columns and results array
while($row = $result->fetch()) {
	if (empty($columns)) {
		$columns = array_keys($row);
	}
	$resultset[] = $row;
}



# If records found
if( count($resultset > 0 )) {
?>
	<table class="table table-bordered" >
		<thead>
			<tr class='info';>
				<?php foreach ($columns as $k => $column_name ) : ?>
					<th> <?php echo $column_name;?> </th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>

			<?php

				// output data of each row
				foreach($resultset as $index => $row) {
				$column_counter =0;
			?>
				<tr class='success';>
					<?php for ($i=0; $i < count($columns); $i++):?>
						<td> <?php echo $row[$columns[$column_counter++]]; ?>   </td>
					<?php endfor;?>
				</tr>
			<?php } ?>

		</tbody>
	</table>

<?php }else{ ?>
<h4> Information Not Available </h4>
<?php } ?>