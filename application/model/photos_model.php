<?php

class PhotosModel
{

    //This method is not working yet. But you will need to write it.
    public function getAllPhotos()
    {
        $sql = "SELECT * FROM photo";
        $query = $this->db->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    public function uploadPhoto($file)
    {

      $echo ='';
      $allowedExts = array( "jpeg", "jpg", "pjpeg");
      $temp = explode(".", $file["name"]); 
      $extension = end($temp);

      if ((($file["type"] == "image/jpeg")
      || ($file["type"] == "image/jpg")
      || ($file["type"] == "image/pjpeg"))
      && ($file["size"] < 2000000000)
      && in_array($extension, $allowedExts)) {
        if ($file["error"] > 0) {
          $echo .= "Return Code: " . $file["error"] . "<br>";
        } else {
          $echo .= "Upload: " . $file["name"] . "<br>";
          $echo .= "Type: " . $file["type"] . "<br>";
          $echo .= "Size: " . ($file["size"] / 1024) . " kB br>";
          $echo .= "Temp file: " . $file["tmp_name"] . "<br>";
            $location = $this->getGPSlocation($file);
            if($location == false){
                echo "De location is  false";
                return false;
            } else {
              if (file_exists( ROOT . "public/img/" . $file["name"])) {
                $echo .=$file["name"] . " already exists. ";
              } else {
                move_uploaded_file($file["tmp_name"],
                ROOT . "public/img/" . $file["name"]);
                $echo .="Stored in: " . "public/img/" . $file["name"];
                $photo = ROOT . "public/img/" . $file["name"];
                var_dump($location);
                $this->uploadPhotoToDb($file ,$location);
                return true;
                echo "De move_uploaded_file is bereikt";
               }
             }
        }
       return false; 
     }
 
         
}
  
        function getGPSlocation($file)
        {
            $exif = exif_read_data($file["tmp_name"]);
            $latitude = $this->gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
            $longitude = $this->gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
            $longlat = array($longitude, $latitude);
            echo "getGPSlocation wordt bereikt" . $longitude . $latitude;
            return $longlat;
        }


        function gps($coordinate, $hemisphere) {
         for ($i = 0; $i < 3; $i++) {
           $part = explode('/', $coordinate[$i]);
             if (count($part) == 1) {
                $coordinate[$i] = $part[0];
             } else if (count($part) == 2) {
                $coordinate[$i] = floatval($part[0])/floatval($part[1]);
             } else {
                $coordinate[$i] = 0;
             }
         }
        
        list($degrees, $minutes, $seconds) = $coordinate;
        $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
        return $sign * ($degrees + $minutes/60 + $seconds/3600);
     }
        private function uploadPhotoToDB($file, $longlat){
            $sql = "INSERT INTO photo (filename, lon, lat) VALUES (:filename, :lon, :lat)";
            $query = $this->db->prepare($sql);
            $parameters = array(':filename' => $file["name"], ':lon' => $longlat[0], ':lat' => $longlat[1]);

            $query->execute($parameters);
            echo $sql;
            echo "De waarde is" . $longlat[0]; 
            var_dump($longlat);
        }
                  


}

         
    

