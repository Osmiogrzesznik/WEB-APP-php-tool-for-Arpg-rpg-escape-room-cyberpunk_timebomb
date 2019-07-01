<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Device registration</title>
    <link rel="stylesheet" href="halfdigi.css">
</head>
<style>
input{
    display:block;
}

input:invalid+span:after {
    content: 'value NOT OK';
    padding-left: 5px;
}

input:valid+span:after {
    content: 'value OK';
    padding-left: 5px;
}
</style>

<body>
    <h2>Registration</h2>
    <form method="post" action="index.php?action=registerDevice" name="registerform">
        <label for="device_name">
            device_name:
        </label>
            <input id="device_name" type="text" pattern="[a-zA-Z0-9]{2,64}" name="device_name" required />
        <label for="device_description">
            device_description:
        </label>
            <input id="device_description" type="text" name="device_description"/>
        <label for="device_password_new">
            Device Password (min. 4 characters, only lowercase letters and numbers) used to stop the timer/unlock the doors
        </label>
            <input id="device_password_new" class="login_input" type="password" name="device_password_new"
            pattern="[a-z0-9]{4,32}" required autocomplete="off" />
        <label for="device_password_repeat">
            Repeat password
        </label>
            <input id="device_password_repeat" class="login_input" type="password" name="device_password_repeat"
            pattern="[a-z0-9]{4,32}" required autocomplete="off" />
        <label for="device_ip">
                Device IP(default this one):
        </label>
            <input id="device_ip" type="text" name="device_ip"
                title="IP address of device" required autocomplete="off" 
                <?php echo 'value='. $this->getIP(DEBUG_MODE) . ''?> />
        <label for="time_set">
                time_set(set the time for device to stop accepting valid answer):
        </label>
                <input id="time_set" type="datetime-local" name="time_set" 
                pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"
                <?php
                date_default_timezone_set('Europe/London');
                echo 'min="'. date('Y-m-d\TH:i') . '"'?> required>
        <span class="validity"></span>

            <input type="submit" name="register" value="Register" />
    </form>
    <pre>
            //     CREATE TABLE IF NOT EXISTS device (
                -----------//         'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
                -----------//         'device_name' TEXT NOT NULL,
                -----------//         'device_description' TEXT,
                -----------//         'device_ip' TEXT NOT NULL,
                //         'device_http_user_agent' TEXT NOT NULL, 
                -----------//         'device_password' TEXT NOT NULL ,
                -----------//         'device_status' TEXT,
                //         'time_set' INTEGER,
                -----------//         'time_last_uppdated' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                //                 );
                
                // CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
                // CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
            </pre>
        
    <a href="index.php">Homepage</a>

</body>

</html>