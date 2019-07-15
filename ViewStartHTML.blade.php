<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="halfdigi.css">
</head>
<body>
<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>">Homepage</a>
    <pre id="feedback">
<?php
if ($this->feedback) {          
            echo "FEEDBACK : \n" . $this->feedback ;
}?>
</pre><br/>

