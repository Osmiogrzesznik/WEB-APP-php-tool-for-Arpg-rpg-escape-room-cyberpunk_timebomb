<?php
if ($this->IsRegisteredDevice()){
    $password_length = strlen($_SESSION["device_password"]);
    $password_contains_only_digits = ctype_digit($_SESSION["device_password"]);
    $response = array(
        "device_id" => $_SESSION["device_id"],
        "device_status" => $_SESSION["device_status"],
        "time_set" => $_SESSION["time_set"],
        "password_length" => $password_length,
        "password_contains_only_digits" => $password_contains_only_digits,
        "feedback" => $this->feedback
    );
echo json_encode($response,JSON_PRETTY_PRINT);
}
else{?>
{error:"device is not logged in "}
<?php } ?>