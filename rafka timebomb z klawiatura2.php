<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" />
    <title>Test</title>
</head>
<body>
    <pre>
    <?php 
echo json_encode($_SESSION,JSON_PRETTY_PRINT) 
?>
    </pre>


<script type="text/javascript">
serverdata = 
<?php 
echo json_encode($_SESSION,JSON_PRETTY_PRINT) 
?>
</script>
    <script type="text/javascript" src="timebomb.js"></script>
</body>

</html>