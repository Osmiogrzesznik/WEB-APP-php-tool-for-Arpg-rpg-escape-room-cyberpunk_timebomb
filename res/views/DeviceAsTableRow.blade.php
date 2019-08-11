<?php
// this supposed to be a View not include
// and in the end it will be invoked in class method
if (!isset($row, $displayedColumns, $column_counter, $nonDisplayed, $nonEditables, $index_link)) {
  $a = array(
    'row' => $row,
    'columns' => $displayedColumns,
    'column_counter' => $column_counter,
    'nonDisplayed' => $nonDisplayed,
    'nonEditables' => $nonEditables,
    'index_link' => $index_link
  );
  print_me($a);
  echo "ERROR no dvice data";
}

?>


<tr id="r<?= $row['device_id'] ?>">
  <?php for ($i = 0; $i < count($displayedColumns); $i++) :
    $column_name = $displayedColumns[$column_counter];
    if (in_array($column_name, $nonDisplayed)) {
      $column_counter++;
      continue;
    }

    if (in_array($column_name, $nonEditables)) {
      if ($column_name == "time_last_active") {

        date_default_timezone_set(DEFAULT_TIMEZONE_NAME_LONDON);
        $time_last_active = $row[$column_name];

        ?>
        <td class="field-non-editable time_last_active" data-column-name="time_last_active">
          <span id="r<?= $row['device_id'] . $column_name ?>" class="my_date_format"><?= $time_last_active ?></span>
          <br>
          <span class="ago"></span>
        <?php
        } elseif ($column_name == "device_location") { // any other noneditable
          ?>
        <td id="r<?= $row['device_id'] . $column_name ?>" class="field-non-editable squeezed" data-column-name="<?= $column_name ?>">
          <?= $row[$column_name]; ?>
        <?php
        } else { // any other noneditable
          ?>
        <td id="r<?= $row['device_id'] . $column_name ?>" class="field-non-editable" data-column-name="<?= $column_name ?>">
          <?php echo $row[$column_name];
        }
        //now outer if's elseif: editables

      } elseif ($column_name == "time_set") {
        ?>
      <td class="uuu">
        <span id="smallcounter" class="digits digits-small">

          <span id="counter_hour">00</span>
          <span id="counter_colon1">:</span>
          <span id="counter_min">00</span>
          <span id="counter_colon2" class="flash">:</span>
          <span id="counter_sec">00</span>
        </span>
        <label for="time_set">New:</label>
        <input name="time_set" class="field-editable time_set" data-column-name="time_set" value="<?= $row["time_set"]; ?>">
        <label for="r<?= $row['device_id'] . $column_name ?>">Old:</label><span id="r<?= $row['device_id'] . $column_name ?>"><?= $row[$column_name] ?><span>
          <?php
          } else {
            ?>
      <td id="r<?= $row['device_id'] . $column_name ?>" class="field-editable" data-column-name="<?= $column_name ?>">
        <?php
        echo $row[$column_name];
      };
      //IF COLUMN IS TIME UPDATED DISPLAY ADDITIONALY TIME IN AGO FORMAT
      // echo $column_name;
      ?>
    </td>
    <?php
    $column_counter++;
  endfor; ?>
  <td class="field-non-editable">
    <?= $row["device_id"] . " - " . $row["device_name"]; ?>
    <div class="flex-row">
      <button onclick="sendUpdate(this.dataset.id)" data-id="<?= $row["device_id"]; ?>">Save</button>
      <a href="<?= $index_link . "?action=delete&id=" . $row["device_id"]; ?>" onclick="if(!confirm('are you sure? deleting cannot be undone'))
  {event.stopPropagation();event.preventDefault()}else{}"><button>Delete</button></a>
    </div>
  </td>
</tr>