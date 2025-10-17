<?php
require_once __DIR__.'/../models/Librarian.php';
require_once __DIR__.'/../helpers/Response.php';

class LibrarianController {
    private $librarian;

    public function __construct($db){
        $this->librarian = new Librarian($db);
    }

    // Get librarian info by ID
    public function get($id){
        $info = $this->librarian->getById($id);
        if($info) Response::json($info, 200);
        else Response::json(['message'=>'Not found'], 404);
    }

    // Update librarian info
    public function update($id, $data){
        // Optional: handle profile image upload
        if(isset($_FILES['profile_image'])){
            $target_dir = __DIR__."/../uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir,0777,true);
            $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
            move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
            $data['profile_image'] = "uploads/".basename($_FILES["profile_image"]["name"]);
        }

        if($this->librarian->updateProfile($id, $data)){
            Response::json(['message'=>'Profile updated'],200);
        } else {
            Response::json(['message'=>'Update failed'],400);
        }
    }
}
?>
