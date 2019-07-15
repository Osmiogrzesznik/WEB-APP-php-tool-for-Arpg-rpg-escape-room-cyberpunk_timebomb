<div class="centerpanel"><h2>Login</h2>

<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" name="loginform">
        <label for="login_input_username">Username (or email)</label>
        <input id="login_input_username" type="text" name="user_name" required />
        <label for="login_input_password">Password</label>
        <input id="login_input_password" type="password" name="user_password" required />
        <input type="submit" name="login" value="Log in" />
        
</form>

<div class="centerpanel">
<h3>OR</h3>
        <a href="<?=$_SERVER['SCRIPT_NAME'] . '?action=registerForm' ?>"><button>Register new account</button></a>
</div>
</div>