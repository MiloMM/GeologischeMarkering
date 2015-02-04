<?php

class Photos extends Controller
{

    
    public function index()
    {
        $this->model = $this->loadModel('Photos');
        $this->view->photos = $this->model->getAllPhotos();
        $this->view->render('photos/index');

    }

    public function succes()
    {
        $this->view->render('photos/succes');
    }

    public function fail()
    {
        $this->view->render('photos/fail');
    }

    
    public function addPhoto()
    {
        
        $this->model = $this->loadModel('Photos');
 
        if ($_POST["submit"]) {
            $upload_succesfull = $this->model->uploadPhoto($_FILES["fileToUpload"]);


          if ($upload_succesfull) {
               header('location: ' . URL . 'game/');
           } else {
                header('location: ' . URL . 'photos/fail');
           }
           
        }
    }
}

