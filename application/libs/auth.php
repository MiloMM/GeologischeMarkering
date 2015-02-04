<?php

class Auth
{
	public static function handleLogin()
	{
		Session::init();

		if (!isset($_SESSION['user_logged_in'])) {
			Session::destroy();
			header('location: ' . URL . 'login');
			exit();
		}
	}
}