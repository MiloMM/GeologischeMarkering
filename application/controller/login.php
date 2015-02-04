<?php

class Login extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$this->view->render('login/index');
	}

	public function login()
	{
		$login_model = $this->loadModel('Login');
		$login_succesfull = $login_model->login();

		if ($login_succesfull) {
			header('location: ' . URL . 'dashboard/index');
		} else {
			header('location: ' . URL . 'login/index');
		}
	}

	public function logout()
	{
		$login_model = $this->loadModel('Login');
		$login_model->logout();

		header('location: ' . URL);
	}

	 /**
     * Login with cookie
     */
    function loginWithCookie()
    {
        // run the loginWithCookie() method in the login-model, put the result in $login_successful (true or false)
        $login_model = $this->loadModel('Login');
        $login_successful = $login_model->loginWithCookie();

        if ($login_successful) {
            header('location: ' . URL . 'dashboard/index');
        } else {
            // delete the invalid cookie to prevent infinite login loops
            $login_model->deleteCookie();
            // if NO, then move user to login/index (login form) (this is a browser-redirection, not a rendered view)
            header('location: ' . URL . 'login/index');
        }
    }

    /**
     * Show user's profile
     */
    function showProfile()
    {
        // Auth::handleLogin() makes sure that only logged in users can use this action/method and see that page
        Auth::handleLogin();
        $this->view->render('login/showprofile');
    }

    /**
     * Edit user name (show the view with the form)
     */
    function editUsername()
    {
        // Auth::handleLogin() makes sure that only logged in users can use this action/method and see that page
        Auth::handleLogin();
        $this->view->render('login/editusername');
    }

    /**
     * Edit user name (perform the real action after form has been submitted)
     */
    function editUsername_action()
    {
        // Auth::handleLogin() makes sure that only logged in users can use this action/method and see that page
        // Note: This line was missing in early version of the script, but it was never a real security issue as
        // it was not possible to read or edit anything in the database unless the user is really logged in and
        // has a valid session.
        Auth::handleLogin();
        $login_model = $this->loadModel('Login');
        $login_model->editUserName();
        $this->view->render('login/editusername');
    }

    /**
     * Edit user email (show the view with the form)
     */
    function editUserEmail()
    {
        // Auth::handleLogin() makes sure that only logged in users can use this action/method and see that page
        Auth::handleLogin();
        $this->view->render('login/edituseremail');
    }

    /**
     * Edit user email (perform the real action after form has been submitted)
     */
    function editUserEmail_action()
    {
        // Auth::handleLogin() makes sure that only logged in users can use this action/method and see that page
        // Note: This line was missing in early version of the script, but it was never a real security issue as
        // it was not possible to read or edit anything in the database unless the user is really logged in and
        // has a valid session.
        Auth::handleLogin();
        $login_model = $this->loadModel('Login');
        $login_model->editUserEmail();
        $this->view->render('login/edituseremail');
    }

	public function register()
	{
		$this->view->render('login/register');
	}

	public function register_action()
	{
		$login_model = $this->loadModel('Login');
		$registration_succesfull = $login_model->registerNewUser();

		if ($registration_succesfull) {
			header('location: ' . URL . 'login/index');
		} else {
			header('location: ' . URL . 'login/register');
		}
	}

	/**
     * Verify user after activation mail link opened
     * @param int $user_id user's id
     * @param string $user_activation_verification_code sser's verification token
     */
    function verify($user_id = null, $user_activation_verification_code = null)
    {
        if (isset($user_id) && isset($user_activation_verification_code)) {
            $login_model = $this->loadModel('Login');
            $login_model->verifyNewUser($user_id, $user_activation_verification_code);
            $this->view->render('login/verify');
        } else {
            header('location: ' . URL . 'login/index');
        }
    }

	/**
     * Request password reset page
     */
    function requestPasswordReset()
    {
        $this->view->render('login/requestpasswordreset');
    }

    /**
     * Request password reset action (after form submit)
     */
    function requestPasswordReset_action()
    {
        $login_model = $this->loadModel('Login');
        $login_model->requestPasswordReset();
        $this->view->render('login/requestpasswordreset');
    }

    /**
     * Verify the verification token of that user (to show the user the password editing view or not)
     * @param string $user_name username
     * @param string $verification_code password reset verification token
     */
    function verifyPasswordReset($user_name, $verification_code)
    {
        $login_model = $this->loadModel('Login');
        if ($login_model->verifyPasswordReset($user_name, $verification_code)) {
            // get variables for the view
            $this->view->user_name = $user_name;
            $this->view->user_password_reset_hash = $verification_code;
            $this->view->render('login/changepassword');
        } else {
            header('location: ' . URL . 'login/index');
        }
    }

    /**
     * Set the new password
     * Please note that this happens while the user is not logged in.
     * The user identifies via the data provided by the password reset link from the email.
     */
    function setNewPassword()
    {
        $login_model = $this->loadModel('Login');
        // try the password reset (user identified via hidden form inputs ($user_name, $verification_code)), see
        // verifyPasswordReset() for more
        $login_model->setNewPassword();
        // regardless of result: go to index page (user will get success/error result via feedback message)
        header('location: ' . URL . 'login/index');
    }
}