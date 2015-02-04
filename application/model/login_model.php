<?php

class LoginModel
{

	public function login()
	{
		if (!isset($_POST['user_name']) OR empty($_POST['user_name'])) {
			$_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_FIELD_EMPTY;
			return false;
		}
		if (!isset($_POST['user_password']) OR empty($_POST['user_password'])) {
			$_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_FIELD_EMPTY;
			return false;
		}

		$sth = $this->db->prepare("SELECT user_id,
										  user_name,
										  user_email,
										  user_password_hash,
										  user_active,
										  user_failed_logins,
										  user_last_failed_login
								   FROM users
								   WHERE (user_name = :user_name OR user_email = :user_name)");
		$sth->execute(array(':user_name' => $_POST['user_name']));
		$count = $sth->rowCount();

		if ($count != 1) {
			$_SESSION["feedback_negative"][] = FEEDBACK_LOGIN_FAILED;
			return false; 
		}

		$result = $sth->fetch();

		if (($result->user_failed_logins >= 3) AND ($result->user_last_failed_login > (time()-30))) {
			$_SESSION['feedback_negative'][] = FEEDBACK_PASSWORD_WRONG_3_TIMES;
			return false;
		}

		if (password_verify($_POST['user_password'], $result->user_password_hash)) {
			if ($result->user_active != 1) {
                $_SESSION["feedback_negative"][] = FEEDBACK_ACCOUNT_NOT_ACTIVATED_YET;
                return false;
            }

            // login process, write the user data into session
            Session::init();
            Session::set('user_logged_in', true);
            Session::set('user_id', $result->user_id);
            Session::set('user_name', $result->user_name);
            Session::set('user_email', $result->user_email);

            // reset the failed login counter for that user (if necessary)
            if ($result->user_last_failed_login > 0) {
                $sql = "UPDATE users SET user_failed_logins = 0, user_last_failed_login = NULL
                        WHERE user_id = :user_id AND user_failed_logins != 0";
                $sth = $this->db->prepare($sql);
                $sth->execute(array(':user_id' => $result->user_id));
            }

            // generate integer-timestamp for saving of last-login date
            $user_last_login_timestamp = time();
            // write timestamp of this login into database (we only write "real" logins via login form into the
            // database, not the session-login on every page request
            $sql = "UPDATE users SET user_last_login_timestamp = :user_last_login_timestamp WHERE user_id = :user_id";
            $sth = $this->db->prepare($sql);
            $sth->execute(array(':user_id' => $result->user_id, ':user_last_login_timestamp' => $user_last_login_timestamp));

            // if user has checked the "remember me" checkbox, then write cookie
            if (isset($_POST['user_rememberme'])) {

                // generate 64 char random string
                $random_token_string = hash('sha256', mt_rand());

                // write that token into database
                $sql = "UPDATE users SET user_rememberme_token = :user_rememberme_token WHERE user_id = :user_id";
                $sth = $this->db->prepare($sql);
                $sth->execute(array(':user_rememberme_token' => $random_token_string, ':user_id' => $result->user_id));

                // generate cookie string that consists of user id, random string and combined hash of both
                $cookie_string_first_part = $result->user_id . ':' . $random_token_string;
                $cookie_string_hash = hash('sha256', $cookie_string_first_part);
                $cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;

                // set cookie
                setcookie('rememberme', $cookie_string, time() + COOKIE_RUNTIME, "/", COOKIE_DOMAIN);
            }

            // return true to make clear the login was successful
            return true;
		} else {
            // increment the failed login counter for that user
            $sql = "UPDATE users
                    SET user_failed_logins = user_failed_logins+1, user_last_failed_login = :user_last_failed_login
                    WHERE user_name = :user_name OR user_email = :user_name";
            $sth = $this->db->prepare($sql);
            $sth->execute(array(':user_name' => $_POST['user_name'], ':user_last_failed_login' => time() ));
            // feedback message
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_WRONG;
            return false;
        }

        // default return
        return false;
	}

	/**
     * performs the login via cookie (for DEFAULT user account, FACEBOOK-accounts are handled differently)
     * @return bool success state
     */
    public function loginWithCookie()
    {
        $cookie = isset($_COOKIE['rememberme']) ? $_COOKIE['rememberme'] : '';

        // do we have a cookie var ?
        if (!$cookie) {
            $_SESSION["feedback_negative"][] = FEEDBACK_COOKIE_INVALID;
            return false;
        }

        // check cookie's contents, check if cookie contents belong together
        list ($user_id, $token, $hash) = explode(':', $cookie);
        if ($hash !== hash('sha256', $user_id . ':' . $token)) {
            $_SESSION["feedback_negative"][] = FEEDBACK_COOKIE_INVALID;
            return false;
        }

        // do not log in when token is empty
        if (empty($token)) {
            $_SESSION["feedback_negative"][] = FEEDBACK_COOKIE_INVALID;
            return false;
        }

        // get real token from database (and all other data)
        $query = $this->db->prepare("SELECT user_id, user_name, user_email, user_password_hash, user_active,
                                          user_failed_logins, user_last_failed_login
                                     FROM users
                                     WHERE user_id = :user_id
                                       AND user_rememberme_token = :user_rememberme_token
                                       AND user_rememberme_token IS NOT NULL");
        $query->execute(array(':user_id' => $user_id, ':user_rememberme_token' => $token));
        $count =  $query->rowCount();
        if ($count == 1) {
            // fetch one row (we only have one result)
            $result = $query->fetch();
            // TODO: this block is same/similar to the one from login(), maybe we should put this in a method
            // write data into session
            Session::init();
            Session::set('user_logged_in', true);
            Session::set('user_id', $result->user_id);
            Session::set('user_name', $result->user_name);
            Session::set('user_email', $result->user_email);

            // generate integer-timestamp for saving of last-login date
            $user_last_login_timestamp = time();
            // write timestamp of this login into database (we only write "real" logins via login form into the
            // database, not the session-login on every page request
            $sql = "UPDATE users SET user_last_login_timestamp = :user_last_login_timestamp WHERE user_id = :user_id";
            $sth = $this->db->prepare($sql);
            $sth->execute(array(':user_id' => $user_id, ':user_last_login_timestamp' => $user_last_login_timestamp));

            // NOTE: we don't set another rememberme-cookie here as the current cookie should always
            // be invalid after a certain amount of time, so the user has to login with username/password
            // again from time to time. This is good and safe ! ;)
            $_SESSION["feedback_positive"][] = FEEDBACK_COOKIE_LOGIN_SUCCESSFUL;
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_COOKIE_INVALID;
            return false;
        }
    }

    /**
     * Log out process, deletes cookie, deletes session
     */
    public function logout()
    {
        // set the remember-me-cookie to ten years ago (3600sec * 365 days * 10).
        // that's obviously the best practice to kill a cookie via php
        // @see http://stackoverflow.com/a/686166/1114320
        setcookie('rememberme', false, time() - (3600 * 3650), '/', COOKIE_DOMAIN);

        // delete the session
        Session::destroy();
    }

    /**
     * Deletes the (invalid) remember-cookie to prevent infinitive login loops
     */
    public function deleteCookie()
    {
        // set the rememberme-cookie to ten years ago (3600sec * 365 days * 10).
        // that's obviously the best practice to kill a cookie via php
        // @see http://stackoverflow.com/a/686166/1114320
        setcookie('rememberme', false, time() - (3600 * 3650), '/', COOKIE_DOMAIN);
    }

    /**
     * Returns the current state of the user's login
     * @return bool user's login status
     */
    public function isUserLoggedIn()
    {
        return Session::get('user_logged_in');
    }

    /**
     * Edit the user's name, provided in the editing form
     * @return bool success status
     */
    public function editUserName()
    {
        // new username provided ?
        if (!isset($_POST['user_name']) OR empty($_POST['user_name'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_FIELD_EMPTY;
            return false;
        }

        // new username same as old one ?
        if ($_POST['user_name'] == $_SESSION["user_name"]) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_SAME_AS_OLD_ONE;
            return false;
        }

        // username cannot be empty and must be azAZ09 and 2-64 characters
        if (!preg_match("/^(?=.{2,64}$)[a-zA-Z][a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/", $_POST['user_name'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_DOES_NOT_FIT_PATTERN;
            return false;
        }

        // clean the input
        $user_name = substr(strip_tags($_POST['user_name']), 0, 64);

        // check if new username already exists
        $query = $this->db->prepare("SELECT user_id FROM users WHERE user_name = :user_name");
        $query->execute(array(':user_name' => $user_name));
        $count =  $query->rowCount();
        if ($count == 1) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_ALREADY_TAKEN;
            return false;
        }

        $query = $this->db->prepare("UPDATE users SET user_name = :user_name WHERE user_id = :user_id");
        $query->execute(array(':user_name' => $user_name, ':user_id' => $_SESSION['user_id']));
        $count =  $query->rowCount();
        if ($count == 1) {
            Session::set('user_name', $user_name);
            $_SESSION["feedback_positive"][] = FEEDBACK_USERNAME_CHANGE_SUCCESSFUL;
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_UNKNOWN_ERROR;
            return false;
        }
    }

    /**
     * Edit the user's email, provided in the editing form
     * @return bool success status
     */
    public function editUserEmail()
    {
        // email provided ?
        if (!isset($_POST['user_email']) OR empty($_POST['user_email'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_FIELD_EMPTY;
            return false;
        }

        // check if new email is same like the old one
        if ($_POST['user_email'] == $_SESSION["user_email"]) {
            $_SESSION["feedback_negative"][] = FEEDBACK_EMAIL_SAME_AS_OLD_ONE;
            return false;
        }

        // user's email must be in valid email format
        if (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION["feedback_negative"][] = FEEDBACK_EMAIL_DOES_NOT_FIT_PATTERN;
            return false;
        }

        // check if user's email already exists
        $query = $this->db->prepare("SELECT * FROM users WHERE user_email = :user_email");
        $query->execute(array(':user_email' => $_POST['user_email']));
        $count =  $query->rowCount();
        if ($count == 1) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USER_EMAIL_ALREADY_TAKEN;
            return false;
        }

        // cleaning and write new email to database
        $user_email = substr(strip_tags($_POST['user_email']), 0, 64);
        $query = $this->db->prepare("UPDATE users SET user_email = :user_email WHERE user_id = :user_id");
        $query->execute(array(':user_email' => $user_email, ':user_id' => $_SESSION['user_id']));
        $count =  $query->rowCount();
        if ($count != 1) {
            $_SESSION["feedback_negative"][] = FEEDBACK_UNKNOWN_ERROR;
            return false;
        }

        Session::set('user_email', $user_email);
        // call the setGravatarImageUrl() method which writes gravatar URLs into the session
        $_SESSION["feedback_positive"][] = FEEDBACK_EMAIL_CHANGE_SUCCESSFUL;
        return false;
    }

	public function registerNewUser()
	{
		if (empty($_POST['user_name'])) {
			$_SESSION['feedback_negative'][] = FEEDBACK_USERNAME_FIELD_EMPTY;
		} elseif (empty($_POST['user_password_new']) OR empty($_POST['user_password_repeat'])) {
			$_SESSION['feedback_negative'][] = FEEDBACK_PASSWORD_FIELD_EMPTY;
		} elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
			$_SESSION['feedback_negative'][] = FEEDBACK_PASSWORD_REPEAT_WRONG;
		} elseif (strlen($_POST['user_password_new']) < 6) {
			$_SESSION['feedback_negative'][] = FEEDBACK_PASSWORD_TOO_SHORT;
		} elseif (strlen($_POST['user_name']) > 64 OR strlen($_POST['user_name']) < 2 ) {
			$_SESSION['feedback_negative'][] = FEEDBACK_USERNAME_TOO_SHORT_OR_TOO_LONG;
		} elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
			$_SESSION['feedback_negative'][] = FEEDBACK_USERNAME_DOES_NOT_FIT_PATTERN;
		} elseif (empty($_POST['user_email'])) {
			$_SESSION['feedback_negative'][] = FEEDBACK_EMAIL_FIELD_EMPTY;
		} elseif (strlen($_POST['user_email']) > 64) {
			$_SESSION['feedback_negative'][] = FEEDBACK_EMAIL_TOO_LONG;
		} elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
			$_SESSION['feedback_negative'][] = FEEDBACK_EMAIL_DOES_NOT_FIT_PATTERN;
		} elseif (!empty($_POST['user_name'])
			AND strlen($_POST['user_name']) <= 64
			AND strlen($_POST['user_name']) >= 2
			AND preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
			AND !empty($_POST['user_email'])
			AND strlen($_POST['user_email']) <= 64
			AND filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
			AND !empty($_POST['user_password_new'])
			AND !empty ($_POST['user_password_repeat'])
			AND ($_POST['user_password_new'] === $_POST['user_password_repeat'])) {
			
			$user_name = strip_tags($_POST['user_name']);
			$user_email = strip_tags($_POST['user_email']);

			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
			$user_password_hash = password_hash($_POST['user_password_new'], PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

			$query = $this->db->prepare("SELECT * FROM users WHERE user_name = :user_name");
			$query->execute(array(':user_name' => $user_name));
			$count = $query->rowCount();
			if ($count == 1) {
				$_SESSION['feedback_negative'][] = FEEDBACK_USERNAME_ALREADY_TAKEN;
			}

			$query = $this->db->prepare("SELECT * FROM users WHERE user_email = :user_email");
			$query->execute(array(':user_email' => $user_email));
			$count = $query->rowCount();
			if ($count == 1) {
				$_SESSION['feedback_negative'][] = FEEDBACK_EMAIL_ALREADY_TAKEN;
			}

			$user_activation_hash = sha1(uniqid(mt_rand(), true));
			$user_creation_timestamp = time();

			$sql = "INSERT INTO users (user_name, user_password_hash, user_email, user_creation_timestamp, user_activation_hash)
					VALUES (:user_name, :user_password_hash, :user_email, :user_creation_timestamp, :user_activation_hash)";
			$query = $this->db->prepare($sql);
			$query->execute(array(':user_name' => $user_name,
								  ':user_password_hash' => $user_password_hash,
								  ':user_email' => $user_email,
								  ':user_creation_timestamp' => $user_creation_timestamp,
								  ':user_activation_hash' => $user_activation_hash));
			$count = $query->rowCount();
			if ($count != 1) {
				$_SESSION['feedback_negative'][] = FEEDBACK_ACCOUNT_CREATION_FAILED;
				return false;
			}

			$query = $this->db->prepare("SELECT user_id FROM users WHERE user_name = :user_name");
			$query->execute(array(':user_name' => $user_name));
			if ($query->rowCount() != 1) {
				$_SESSION['feedback_negative'][] = FEEDBACK_UNKOWN_ERROR;
				return false;
			}
			$result_user_row = $query->fetch();
			$user_id = $result_user_row->user_id;

			if ($this->sendVerificationEmail($user_id, $user_email, $user_activation_hash)) {
				$_SESSION['feedback_positive'][] = FEEDBACK_ACCOUNT_SUCCESFULLY_CREATED;
				return true;
			} else {
				$query = $this->db->prepare("DELETE FROM users WHERE user_id = :last_inserted_id");
				$query->execute(array(':last_inserted_id' => $user_id));
				$_SESSION['feedback_negative'][] = FEEDBACK_VERIFICATION_EMAIL_SENDING_FAILED;
				return false;
			}

		} else {
			$_SESSION['feedback_negative'][] = FEEDBACK_UNKOWN_ERROR;
		}
		return false;
	}

	/**
     * sends an email to the provided email address
     * @param int $user_id user's id
     * @param string $user_email user's email
     * @param string $user_activation_hash user's mail verification hash string
     * @return boolean gives back true if mail has been sent, gives back false if no mail could been sent
     */
    private function sendVerificationEmail($user_id, $user_email, $user_activation_hash)
    {
        // create PHPMailer object (this is easily possible as we auto-load the according class(es) via composer)
        $mail = new PHPMailer;

        // please look into the config/config.php for much more info on how to use this!
        if (EMAIL_USE_SMTP) {
            // set PHPMailer to use SMTP
            $mail->IsSMTP();
            // useful for debugging, shows full SMTP errors, config this in config/config.php
            $mail->SMTPDebug = PHPMAILER_DEBUG_MODE;
            // enable SMTP authentication
            $mail->SMTPAuth = EMAIL_SMTP_AUTH;
            // enable encryption, usually SSL/TLS
            
            /* if (defined('EMAIL_SMTP_ENCRYPTION')) {
                $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
            } */

            // set SMTP provider's credentials
            $mail->Host = EMAIL_SMTP_HOST;
            $mail->Username = EMAIL_SMTP_USERNAME;
            $mail->Password = EMAIL_SMTP_PASSWORD;
            $mail->Port = EMAIL_SMTP_PORT;
        } else {
            $mail->IsMail();
        }

        // fill mail with data
        $mail->From = EMAIL_VERIFICATION_FROM_EMAIL;
        $mail->FromName = EMAIL_VERIFICATION_FROM_NAME;
        $mail->AddAddress($user_email);
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;
        $mail->Body = EMAIL_VERIFICATION_CONTENT . EMAIL_VERIFICATION_URL . '/' . urlencode($user_id) . '/' . urlencode($user_activation_hash);

        // final sending and check
        if($mail->Send()) {
            $_SESSION["feedback_positive"][] = FEEDBACK_VERIFICATION_MAIL_SENDING_SUCCESSFUL;
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_VERIFICATION_MAIL_SENDING_ERROR . $mail->ErrorInfo;
            return false;
        }
    }

    /**
     * checks the email/verification code combination and set the user's activation status to true in the database
     * @param int $user_id user id
     * @param string $user_activation_verification_code verification token
     * @return bool success status
     */
    public function verifyNewUser($user_id, $user_activation_verification_code)
    {
        $sth = $this->db->prepare("UPDATE users
                                   SET user_active = 1, user_activation_hash = NULL
                                   WHERE user_id = :user_id AND user_activation_hash = :user_activation_hash");
        $sth->execute(array(':user_id' => $user_id, ':user_activation_hash' => $user_activation_verification_code));

        if ($sth->rowCount() == 1) {
            $_SESSION["feedback_positive"][] = FEEDBACK_ACCOUNT_ACTIVATION_SUCCESSFUL;
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_ACCOUNT_ACTIVATION_FAILED;
            return false;
        }
    }

        /**
     * Perform the necessary actions to send a password reset mail
     * @return bool success status
     */
    public function requestPasswordReset()
    {
        if (!isset($_POST['user_name']) OR empty($_POST['user_name'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_FIELD_EMPTY;
            return false;
        }

        // generate integer-timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
        $temporary_timestamp = time();
        // generate random hash for email password reset verification (40 char string)
        $user_password_reset_hash = sha1(uniqid(mt_rand(), true));
        // clean user input
        $user_name = strip_tags($_POST['user_name']);

        // check if that username exists
        $query = $this->db->prepare("SELECT user_id, user_email FROM users
                                     WHERE user_name = :user_name AND user_provider_type = :provider_type");
        $query->execute(array(':user_name' => $user_name, ':provider_type' => 'DEFAULT'));
        $count = $query->rowCount();
        if ($count != 1) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USER_DOES_NOT_EXIST;
            return false;
        }

        // get result
        $result_user_row = $result = $query->fetch();
        $user_email = $result_user_row->user_email;

        // set token (= a random hash string and a timestamp) into database
        if ($this->setPasswordResetDatabaseToken($user_name, $user_password_reset_hash, $temporary_timestamp) == true) {
            // send a mail to the user, containing a link with username and token hash string
            if ($this->sendPasswordResetMail($user_name, $user_password_reset_hash, $user_email)) {
                return true;
            }
        }
        // default return
        return false;
    }

    /**
     * Set password reset token in database (for DEFAULT user accounts)
     * @param string $user_name username
     * @param string $user_password_reset_hash password reset hash
     * @param int $temporary_timestamp timestamp
     * @return bool success status
     */
    public function setPasswordResetDatabaseToken($user_name, $user_password_reset_hash, $temporary_timestamp)
    {
        $query_two = $this->db->prepare("UPDATE users
                                            SET user_password_reset_hash = :user_password_reset_hash,
                                                user_password_reset_timestamp = :user_password_reset_timestamp
                                          WHERE user_name = :user_name AND user_provider_type = :provider_type");
        $query_two->execute(array(':user_password_reset_hash' => $user_password_reset_hash,
                                  ':user_password_reset_timestamp' => $temporary_timestamp,
                                  ':user_name' => $user_name,
                                  ':provider_type' => 'DEFAULT'));

        // check if exactly one row was successfully changed
        $count =  $query_two->rowCount();
        if ($count == 1) {
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_RESET_TOKEN_FAIL;
            return false;
        }
    }

    /**
     * send the password reset mail
     * @param string $user_name username
     * @param string $user_password_reset_hash password reset hash
     * @param string $user_email user email
     * @return bool success status
     */
    public function sendPasswordResetMail($user_name, $user_password_reset_hash, $user_email)
    {
        // create PHPMailer object here. This is easily possible as we auto-load the according class(es) via composer
        $mail = new PHPMailer;

        // please look into the config/config.php for much more info on how to use this!
        if (EMAIL_USE_SMTP) {
            // Set mailer to use SMTP
            $mail->IsSMTP();
            //useful for debugging, shows full SMTP errors, config this in config/config.php
            $mail->SMTPDebug = PHPMAILER_DEBUG_MODE;
            // Enable SMTP authentication
            $mail->SMTPAuth = EMAIL_SMTP_AUTH;
            // Enable encryption, usually SSL/TLS
            /*
            if (defined('EMAIL_SMTP_ENCRYPTION')) {
                $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
            } */

            // Specify host server
            $mail->Host = EMAIL_SMTP_HOST;
            $mail->Username = EMAIL_SMTP_USERNAME;
            $mail->Password = EMAIL_SMTP_PASSWORD;
            $mail->Port = EMAIL_SMTP_PORT;
        } else {
            $mail->IsMail();
        }

        // build the email
        $mail->From = EMAIL_PASSWORD_RESET_FROM_EMAIL;
        $mail->FromName = EMAIL_PASSWORD_RESET_FROM_NAME;
        $mail->AddAddress($user_email);
        $mail->Subject = EMAIL_PASSWORD_RESET_SUBJECT;
        $link = EMAIL_PASSWORD_RESET_URL . '/' . urlencode($user_name) . '/' . urlencode($user_password_reset_hash);
        $mail->Body = EMAIL_PASSWORD_RESET_CONTENT . ' ' . $link;

        // send the mail
        if($mail->Send()) {
            $_SESSION["feedback_positive"][] = FEEDBACK_PASSWORD_RESET_MAIL_SENDING_SUCCESSFUL;
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_RESET_MAIL_SENDING_ERROR . $mail->ErrorInfo;
            return false;
        }
    }

    /**
     * Verifies the password reset request via the verification hash token (that's only valid for one hour)
     * @param string $user_name Username
     * @param string $verification_code Hash token
     * @return bool Success status
     */
    public function verifyPasswordReset($user_name, $verification_code)
    {
        // check if user-provided username + verification code combination exists
        $query = $this->db->prepare("SELECT user_id, user_password_reset_timestamp
                                       FROM users
                                      WHERE user_name = :user_name
                                        AND user_password_reset_hash = :user_password_reset_hash
                                        AND user_provider_type = :user_provider_type");
        $query->execute(array(':user_password_reset_hash' => $verification_code,
                              ':user_name' => $user_name,
                              ':user_provider_type' => 'DEFAULT'));

        // if this user with exactly this verification hash code exists
        if ($query->rowCount() != 1) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_RESET_COMBINATION_DOES_NOT_EXIST;
            return false;
        }

        // get result row (as an object)
        $result_user_row = $query->fetch();
        // 3600 seconds are 1 hour
        $timestamp_one_hour_ago = time() - 3600;
        // if password reset request was sent within the last hour (this timeout is for security reasons)
        if ($result_user_row->user_password_reset_timestamp > $timestamp_one_hour_ago) {
            // verification was successful
            $_SESSION["feedback_positive"][] = FEEDBACK_PASSWORD_RESET_LINK_VALID;
            return true;
        } else {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_RESET_LINK_EXPIRED;
            return false;
        }
    }

    /**
     * Set the new password (for DEFAULT user, FACEBOOK-users don't have a password)
     * Please note: At this point the user has already pre-verified via verifyPasswordReset() (within one hour),
     * so we don't need to check again for the 60min-limit here. In this method we authenticate
     * via username & password-reset-hash from (hidden) form fields.
     * @return bool success state of the password reset
     */
    public function setNewPassword()
    {
        // basic checks
        if (!isset($_POST['user_name']) OR empty($_POST['user_name'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_USERNAME_FIELD_EMPTY;
            return false;
        }
        if (!isset($_POST['user_password_reset_hash']) OR empty($_POST['user_password_reset_hash'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_RESET_TOKEN_MISSING;
            return false;
        }
        if (!isset($_POST['user_password_new']) OR empty($_POST['user_password_new'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_FIELD_EMPTY;
            return false;
        }
        if (!isset($_POST['user_password_repeat']) OR empty($_POST['user_password_repeat'])) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_FIELD_EMPTY;
            return false;
        }
        // password does not match password repeat
        if ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_REPEAT_WRONG;
            return false;
        }
        // password too short
        if (strlen($_POST['user_password_new']) < 6) {
            $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_TOO_SHORT;
            return false;
        }

        // check if we have a constant HASH_COST_FACTOR defined
        // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
        $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
        // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
        // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
        // want the parameter: as an array with, currently only used with 'cost' => XX.
        $user_password_hash = password_hash($_POST['user_password_new'], PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

        // write users new password hash into database, reset user_password_reset_hash
        $query = $this->db->prepare("UPDATE users
                                        SET user_password_hash = :user_password_hash,
                                            user_password_reset_hash = NULL,
                                            user_password_reset_timestamp = NULL
                                      WHERE user_name = :user_name
                                        AND user_password_reset_hash = :user_password_reset_hash
                                        AND user_provider_type = :user_provider_type");

        $query->execute(array(':user_password_hash' => $user_password_hash,
                              ':user_name' => $_POST['user_name'],
                              ':user_password_reset_hash' => $_POST['user_password_reset_hash'],
                              ':user_provider_type' => 'DEFAULT'));

        // check if exactly one row was successfully changed:
        if ($query->rowCount() == 1) {
            // successful password change!
            $_SESSION["feedback_positive"][] = FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL;
            return true;
        }

        // default return
        $_SESSION["feedback_negative"][] = FEEDBACK_PASSWORD_CHANGE_FAILED;
        return false;
    }
}