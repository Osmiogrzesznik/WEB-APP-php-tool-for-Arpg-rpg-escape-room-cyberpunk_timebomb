<?php
if ($this->IsRegisteredDevice()){
    $password_length = strlen($this->timebomb_password);
    $password_contains_only_digits = ctype_digit($this->timebomb_password);
    $response = array(
        "device_id" => $this->device_id,
        "timebomb_status" => $this->timebomb_status_new,//after check in isregistered
        "timebomb_time_set" => $this->device_timebomb_time_set,
        "password_length" => $password_length,
        "password_contains_only_digits" => $password_contains_only_digits,
        "feedback" => getGlobalFeedback()
    );
echo json_encode($response,JSON_PRETTY_PRINT);
}
else{?>
{error:"device is not logged in "}
<?php } ?>