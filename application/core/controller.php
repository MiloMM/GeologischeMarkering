<?php

class Controller
{
    /**
     * @var null Database Connection
     */
    public $db = null;

    /**
     * @var null Model
     */
    public $model = null;

    /**
     * Whenever controller is created, open a database connection too and load "the model".
     */
    function __construct()
    {
        Session::init();

        if (!isset($_SESSION['user_logged_in']) && isset($_COOKIE['rememberme'])) {
            header('location: ' . URL . 'login/loginWithCookie');
        }

        $this->openDatabaseConnection();
        
        try {
            $this->db;
        } catch (PDOException $e) {
            exit('Database connection could not be established.');
        }

        $this->view = new View();
    }

    /**
     * Open the database connection with the credentials from application/config/config.php
     */
    private function openDatabaseConnection()
    {
        // set the (optional) options of the PDO connection. in this case, we set the fetch mode to
        // "objects", which means all results will be objects, like this: $result->user_name !
        // For example, fetch mode FETCH_ASSOC would return results like this: $result["user_name] !
        // @see http://www.php.net/manual/en/pdostatement.fetch.php
        $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);

        // generate a database connection, using the PDO connector
        // @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
        $this->db = new PDO(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, $options);
    }

    /**
     * loads the model with the given name.
     * @param $name string name of the model
     */
    public function loadModel($name)
    {
        $path = MODEL . strtolower($name) . '_model.php';
        if (file_exists($path)) {
            require MODEL . strtolower($name) . '_model.php';
            // The "Model" has a capital letter as this is the second part of the model class name,
            // all models have names like "LoginModel"
            $modelName = $name . 'Model';
            // return the new model object while passing the database connection to the model
            $model = new $modelName();
            $model->db = $this->db;
            return $model;
        }
    }
}
