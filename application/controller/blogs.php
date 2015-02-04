<?php

class Blogs extends Controller
{
	public function index()
	{
		//show view
		$this->view->render('blogs/index');
	}
}