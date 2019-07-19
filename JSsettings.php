<?php
if ($this->IsRegisteredDevice()){
    $password_length = strlen($this->device_password);
    $password_contains_only_digits = ctype_digit($this->device_password);
    $response = array(
        "device_id" => $this->device_id,
        "device_status" => $this->device_status_new,//after check in isregistered
        "time_set" => $this->device_time_set,
        "password_length" => $password_length,
        "password_contains_only_digits" => $password_contains_only_digits,
        "feedback" => $this->feedback
    );
echo json_encode($response,JSON_PRETTY_PRINT);
}
else{?>
{error:"device is not logged in "}
<?php } ?>