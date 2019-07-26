<a href="<?= $_SERVER['SCRIPT_NAME'] ?>">Homepage</a>
<div class = "centerpanel"><h2>Registration</h2>

        <form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>?action=registerUser" name="registerform">
        <label for="login_input_username">Username (only letters and numbers, 2 to 64 characters)</label>
        <input id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />
        <label for="login_input_password_new">Password (min. 6 characters)</label>
        <input id="login_input_password_new" class="login_input" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" />
        <label for="login_input_password_repeat">Repeat password</label>
        <input id="login_input_password_repeat" class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" />
        <label for="timezone">Your Location(Timezone Offset)</label>
        <select id="timezone" name="timezone" title="required to calculate time " required>
                <option selected="selected" value="">Choose one</option>
                <?php
include_once(PROJ ."res/timezoneListGenerator.php");
$timezones = timezone_list();
                foreach ($timezones as $name => $val) { ?>
                        <option value="<?php echo $name ?>"><?= $val ?></option>
                <?php
                } ?>
        </select>
        <input type="submit" name="register" value="Register" />
       
        </form>
        <div class="centerpanel">
<h2>OR</h2>
        <a href="<?= $_SERVER['SCRIPT_NAME'] ?>"><button>Log in</button></a>
</div>

        </div>
        </body>