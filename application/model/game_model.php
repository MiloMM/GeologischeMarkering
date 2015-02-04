<?php

class GameModel
{
	public function getAllPhotos()
    {
        $sql = "SELECT * FROM photo";
        $query = $this->db->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }


	public function getOnePhoto($id)
	{

		$sql = "SELECT * FROM photo WHERE id = :id";

        $query = $this->db->prepare($sql);
        $parameter = array(':id' => $id);
        $query->execute($parameter);
       
        return $query->fetch();

	}

	public function getRandomPhoto()
	{

		$sql = "SELECT * FROM photo ORDER BY RAND() LIMIT 1 ";

        $query = $this->db->prepare($sql);
        $query->execute();

        return $query->fetch();

	}

    public function ajaxGetStats($id)
    {
        $this->model = $this->loadModel('game');
        $lngLat = $this->model->getLngLat($id);
        echo $lngLat->latitude . "," . $lngLat->longitude;
    }

    public function submitScore($points, $photo_id)
    {
        $sql = "INSERT INTO score (userid, photoid, distance) VALUES (:userid, :photoid, :points)";
        $query = $this->db->prepare($sql);
        $parameters = array(':user_id' => Session::get('userid'), ':points' => $points, 'photoid' => $photo_id);
        $query->execute($parameters);
    }

}