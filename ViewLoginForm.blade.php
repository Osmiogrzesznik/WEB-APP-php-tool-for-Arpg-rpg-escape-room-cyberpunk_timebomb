<div class="centerpanel">
        <h2>Login</h2>

        <form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" name="loginform">
                <label for="login_input_username">Username</label>
                <input id="login_input_username" type="text" name="user_name" required />
                <label for="login_input_password">Password</label>
                <input id="login_input_password" type="password" name="user_password" required />
                <input type="submit" name="login" value="Log in" />

        </form>

        <div class="centerpanel">
                <h3>OR</h3>
                <a href="<?= $_SERVER['SCRIPT_NAME'] . '?action=registerForm' ?>"><button>Register new account</button></a>
                <h3>OR</h3>
                <pseudoform class="centerpanel">
                <label for="ipt">quick set this device as pre-set device pseudo ip:</label>
                <input id="ipt" type="text" name="ip_input" onchange="op(this)" placeholder="e.g. 1, 123 or name">
                </pseudoform>
                <script>
                        function op(ipt){
                                window.open("<?= $_SERVER['SCRIPT_NAME'] ?>?ip="+ ipt.value, "_self")
                        }
                        </script>
        </div>

</div>