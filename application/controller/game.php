<?php

class Game extends Controller{


	public function index()
    {
      $this->model = $this->loadModel('game');
      $this->view->game = $this->model->getAllPhotos();
      $this->view->render('game/index');

    }

    public function play($id = null)
    {
    	if ($id != null) 
    	{
    		$this->model = $this->loadModel('game');
     		$this->view->game = $this->model->getOnePhoto($id);
    	} else {
    		$this->model = $this->loadModel('game');
     		$this->view->game = $this->model->getRandomPhoto();
    	}
    	
        $this->view->render('game/play');
    }

    public function score(){
        $this->model = $this->loadModel('game');
        $this->view->game = $this->model->submitScore($_POST['distance'], $_POST['photo_id']);

        $this->view->render('game/score');
    }

}