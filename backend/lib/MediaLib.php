<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MediaLib
 *
 * @author Willap3.0
 */
class MediaLib {
///////////////////////////////////////////////////////////////////////////////////////////////
//
//Imagery stuff
//
///////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Upload a student image into the database, updating if necessary
     * @global type $CFG
     * @param type $recordID
     * @param type $file
     * @param type $update
     * @return type 
     */
    public function upload_image($recordID, $file, $update) {
        //print('uploading an image');
        global $CFG;

        //$myFile = "logfile.txt";
        $maxsize = 100000000;
        $filesize = 0;
        if (is_uploaded_file($file['tmp_name'])) {
           //  print('is an uploaded file, of size' . $file['size'] . 'and name ' . $file['name']);
// check the file is less than the maximum file size
            if ($file['size'] < $maxsize) {
                $filesize = $file['size'];
                // give it a unique name, based on the file contents.
                $raw_file_name = "../media/" . md5(file_get_contents($file['tmp_name']));
               // print('raw file name is:' . $file['tmp_name']);
                // resize image to 450 px high
                // get raw image data
                $imgdata = @imagecreatefromjpeg($file['tmp_name']);
                // resize it
                $width = imagesx($imgdata);
                $height = imagesy($imgdata);
                $new_height = 450;
                $new_width = floor($width * ( $new_height / $height));
                $img_jpg_data = imagecreatetruecolor($new_width, $new_height);
                imagecopyresized($img_jpg_data, $imgdata, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                if (!file_exists($raw_file_name)) {
                    if (!imagejpeg($img_jpg_data, $raw_file_name)) {
                        // print('imagejpeg failed');
                        return false;
                    }
                    imagedestroy($img_jpg_data);
                    //return false;
                }
// Insert a reference to the image into the database
                // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
                try {
                    $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
                } catch (PDOException $e) {
                    die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
                }
                // check to see if student image exists
                $checkQuery = "SELECT count(*) as count FROM student_images WHERE student_id = :recordID";
                $checkstmt = $conn->prepare($checkQuery);
                $checkstmt->bindValue(':recordID', $recordID, PDO::PARAM_STR);
                $checkstmt->execute() or die('<data><error>check student_images for update query failed</error><detail>' . $checkstmt->errorCode() . '</detail></data>');
                $row = $checkstmt->fetch(PDO::FETCH_ASSOC);
                //    print_r($row);
                //   $result = mysqli_query($conn, $checkQuery) or die(mysql_error());

                $update = ($row['count'] > 0);

                if ($update) {
                    //$sql = "UPDATE {$CFG->schema}.student_images SET path = :raw_file, filename = {$file['name']}' WHERE student_id = :$recordID";
                    $sql = "UPDATE student_images SET path = :raw_file, filename = :filename WHERE student_id = :recordID";
                //     print("Update SQL is: $sql");
                } else {
                    //$sql = "INSERT INTO {$CFG->schema}.student_images (path, filename, student_id) VALUES('{$raw_file}', '{$file['name']}', '{$recordID}')";
                    $sql = "INSERT INTO student_images (path, filename, student_id) VALUES(:raw_file, :filename, :recordID)";
                    // print("insert SQL is: $sql");
                }
                $updatestmt = $conn->prepare($sql);
                $updatestmt->bindValue(':recordID', $recordID, PDO::PARAM_INT);
                $updatestmt->bindValue(':raw_file', $raw_file_name, PDO::PARAM_STR);
                $updatestmt->bindValue(':filename', $file['name'], PDO::PARAM_STR);
                // $result = mysqli_query($conn, $sql) or die('<data><error>failed inserting image to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
                $updatestmt->execute() or die('<data><error>update student_images query failed</error><detail>' . $updatestmt->errorCode() . '</detail></data>');

                return true;
                //}
            } else {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * Deletes a student image
     * @global type $CFG
     * @param type $studentID
     * @return boolean
     */
    public function deleteStudentImage($studentID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "DELETE FROM student_images WHERE student_ID = :studentID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>deleteStudentImage query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @global type $CFG
     * @param type $mediaID
     * @param type $getbig
     * @param type $db 
     */
    public function displayStudentThumb($studentID, $getbig) {
        global $CFG;

        //$link = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>No connection to database</error><detail>' . mysql_error() . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// select the db
        $big = ($getbig == 'true');

        try {
            if (!isset($studentID)) {
                print ('<data><error>ID not specified</error><detail></detail></data>');
//throw new Exception('ID not specified');
            }
            $id = (int) $studentID;

            if ($id <= 0) {
                print ('<data><error>invalid ID specified</error><detail></detail></data>');
            }

            $query = "select * from student_images where student_ID = $id LIMIT 1";
            // $result = mysql_query($query, $link);
//            if (mysql_num_rows($result) == 0) {
//              
////throw new Exception('Image with specified ID not found');
//            }
            $stmt = $conn->prepare($query);

            $stmt->execute() or die('<data><error>displayStudentThumb query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            //$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            //while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            print ('<data><error>' . $ex->getMessage() . '</error><detail></detail></data>');
//   exit;
        }
//$fp = fopen('log.txt', 'w');
//  fwrite($fp, "default detected \n");

        if (isset($image['path'])&&file_exists($image['path'])) {
            $img = imagecreatefromjpeg($image['path']);
        } else {
            $img = imagecreatefromjpeg("resources/unknown-person.jpg");
        }
        $width = imagesx($img);
        $height = imagesy($img);
        $new_height = $big ? 450 : 75;
        $new_width = floor($width * ( $new_height / $height));

//fclose($fp);
        $thumb = imagecreatetruecolor($new_width, $new_height);

        imagecopyresized($thumb, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
//        header('Content-type: image/jpeg');
//        imagejpeg($thumb);
//        imagedestroy($thumb);
        return $thumb;
    }

    // get a raw image path
    public function displayRawStudentImage($studentID) {
        global $CFG;

        //$link = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>No connection to database</error><detail>' . mysql_error() . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        try {
            if (!isset($studentID)) {
                print ('<data><error>ID not specified</error><detail></detail></data>');
//throw new Exception('ID not specified');
            }
            $id = (int) $studentID;

            if ($id <= 0) {
                print ('<data><error>invalid ID specified</error><detail></detail></data>');
            }

            $query = "select * from student_images where student_ID = $id LIMIT 1";
            // $result = mysql_query($query, $link);
//            if (mysql_num_rows($result) == 0) {
//              
////throw new Exception('Image with specified ID not found');
//            }
            $stmt = $conn->prepare($query);

            $stmt->execute() or die('<data><error>displayRawStudentImage query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            //$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            //while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            print ('<data><error>' . $ex->getMessage() . '</error><detail></detail></data>');
//   exit;
        }
//$fp = fopen('log.txt', 'w');
//  fwrite($fp, "default detected \n");

        if (isset($image['path'])) {
            $path = $image['path'];
        } else {
            $path = "resources/unknown-person.jpg";
        }

//        imagejpeg($thumb);
//        imagedestroy($thumb);
        return $path;
    }

    /**
     * Returns a list of media associated with a record ID
     * @global type $CFG
     * @param type $recordID
     * @param type $userid
     * @param type $db
     * @return string 
     */
    public function get_assessment_media_by_record_id($recordID) {
        global $CFG;
        $returnStr = '<data>';

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");

        $query = "SELECT * FROM `media` WHERE recordID = '$recordID'";

// echo $query;

        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) < 1) {
            return $returnStr . '</data>';
        } else {
            while ($row = mysqli_fetch_array($result)) {
                $returnStr.="<image><id>{$row['ID']}</id><type>{$row['type']}</type><label>{$row['label']}</label><filename>{$row['name']}</filename><metadata>{$row['comments_data']}</metadata></image>";
            }
            $returnStr.='</data>';
        }

        return $returnStr;
    }

    /**
     * Uploads a new image
     * @global type $CFG
     * @param type $recordID
     * @param type $userID
     * @param type $file
     * @param type $label
     * @param type $tags
     * @param type $db
     * @return type 
     */
    public function upload_media_image($recordID, $file, $label, $tags = '') {

        global $CFG;
        //$myFile = "logfile.txt";
        $maxsize = 100000000;
        $filesize = 0;
        if (is_uploaded_file($file['tmp_name'])) {
// check the file is less than the maximum file size
            if ($file['size'] < $maxsize) {
                // $filesize = $file['size'];
                $filename = strtolower($file['name']);
                $filearr = explode(".",$filename);
                $filetype = end($filearr);
// prepare the image for insertion
// get the image info..
                //  $size = getimagesize($file['tmp_name']);
// put the image in the db...
// database connection
                $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot connect</error><detail>" . mysqli_error($conn) . "</detail></data>");
// move the file to a new place, using a hash. Check that this file hasn't been already uploaded- if so, simply refer to that instead
                $raw_file = $CFG->site_root . "media/" . md5(file_get_contents($file['tmp_name']));
                if (!file_exists($raw_file)) {
                    if (!move_uploaded_file($file['tmp_name'], $raw_file)) {
                        return "<data><error>problem moving file</error><detail/></data>";
                    }
                }


// our sql query
                $sql = "INSERT INTO `media`
                (recordID, type, size, label, name, file_path)
                VALUES
                ('{$recordID}', '" . $filetype . "', {$file['size']}, '{$label}',  '{$file['name']}', '{$raw_file}')";
            //    print($sql);

// insert the image
                if (!mysqli_query($conn, $sql)) {
// log error

                    return '<data><error>file entry into database failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
                }
            } else {
                return '<data><error>file too big</error><detail></detail></data>';
            }
        } else {
            return '<data><error>moving file to temp failed</error><detail></detail></data>';
        }
        return '<data><result>ok</result></data>';
    }

    /**
     * Uploads a new image
     * @global type $CFG
     * @param type $recordID
     * @param type $userID
     * @param type $file
     * @param type $label
     * @param type $tags
     * @param type $db
     * @return type 
     */
    public function upload_media_video($ffmpeg, $recordID, $file, $label, $tags = '') {
        global $CFG;
        //$myFile = "logfile.txt";
        $maxsize = 100000000;
        $filesize = 0;
        if (is_uploaded_file($file['tmp_name'])) {
// check the file is less than the maximum file size
            if ($file['size'] < $maxsize) {
                // $filesize = $file['size'];
                $extArr = explode(".", strtolower($file['name']));
                $filetype = end($extArr);

// database connection
                try {
                    $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
                } catch (PDOException $e) {
                    die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
                }
// move the file to a new place, using a hash. Check that this file hasn't been already uploaded- if so, simply refer to that instead
                $contenthash = md5(file_get_contents($file['tmp_name']));
                $raw_file = $CFG->site_root . "media/" . $contenthash;

                if (!file_exists($raw_file)) {
                    if (!move_uploaded_file($file['tmp_name'], $raw_file)) {
                        return "<data><error>problem moving file</error><detail/></data>";
                    }
                }

                // if the move is successful, make a screenshot
                $video = new PHPVideoToolkit\Video($raw_file);
                
                
                $process = $video->extractFrame(new \PHPVideoToolkit\Timecode(10))->save($CFG->site_root . "media/vidthumbs/" . $contenthash . ".jpg", null, \PHPVideoToolkit\Media::OVERWRITE_EXISTING);

                // make the screenshot smaller
                $img1 = imagecreatefromjpeg($CFG->site_root . "media/vidthumbs/" . $contenthash . ".jpg");
                $width = imagesx($img1);
                $height = imagesy($img1);
                $new_height =  75;
                $new_width = floor($width * ( $new_height / $height));
                $thumb = imagecreatetruecolor($new_width, $new_height);
                imagecopyresized($thumb, $img1, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                // get an overlay
                $img2 = imagecreatefrompng($CFG->site_root . "icons/video.png");

                // combine them
                imagecopyresampled($thumb, $img2, 0, 0, 0, 0, 48, 48, 48, 48);
                // write back to file
                imagejpeg($thumb, $CFG->site_root . "media/vidthumbs/" . $contenthash . ".jpg");


// our sql query
                $sql = "INSERT INTO `media` (`recordID`, `type`, `size`, `label`, `name`, `file_path`, `thumb_path`) VALUES (:recordID, :filetype, :filesize, :label, :filename, :raw_file, :thumb_path)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':recordID', $recordID, PDO::PARAM_INT);
                $stmt->bindValue(':filetype', $filetype, PDO::PARAM_STR);
                $stmt->bindValue(':filesize', $file['size'], PDO::PARAM_STR);
                $stmt->bindValue(':label', $label, PDO::PARAM_STR);
                $stmt->bindValue(':filename', $file['name'], PDO::PARAM_STR);
                $stmt->bindValue(':raw_file', $raw_file, PDO::PARAM_STR);
                $stmt->bindValue(':thumb_path', $CFG->site_root . "media/vidthumbs/" . $contenthash . ".jpg", PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    return '<data><error>file entry into database failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>';
                }
            } else {
                return '<data><error>file too big</error><detail></detail></data>';
            }
        } else {
            return '<data><error>moving file to temp failed</error><detail></detail></data>';
        }
        return '<data><result>ok</result></data>';
    }

    /**
     * 
     * @global type $CFG
     * @param type $urnum
     * @param type $recordID
     * @param type $file
     * @param type $label
     * @param type $tags
     * @return string
     */
    public function upload_document_v4($recordID, $file, $label, $tags = '') {
        global $CFG;
        $allowedExtensions = array("doc", "docx", "pdf", "ppt", "pptx");
        $fileArr = explode(".", strtolower($file['name']));
        $filetype = end($fileArr);
        if ($file['tmp_name'] > '') {
            
            if (!in_array($filetype, $allowedExtensions)) {
                die("<data><error>Invalid file</error><detail>Invalid document type</detail></data>");
            }
        }
        $maxsize = 100000000;
        if (is_uploaded_file($file['tmp_name'])) {

            // check the file is less than the maximum file size

            if ($file['size'] < $maxsize) {
                $filesize = $file['size'];
                // COntent Addressible Storage
                $raw_file = $CFG->site_root . "media/" . md5(file_get_contents($file['tmp_name']));
                if (!file_exists($raw_file)) {
                    if (!move_uploaded_file($file['tmp_name'], $raw_file)) {
                        return "<data><error>problem moving file</error><detail/></data>";
                    }
                }

                // put the file in the db...
// database connection
                try {
                    $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
                } catch (PDOException $e) {
                    die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
                }
//                $imgData = mysql_real_escape_string(file_get_contents($_FILES['userfile']['tmp_name']));

                $type_arr = explode('.', strtolower($file['name']));
                $type = $type_arr[1];
                //  fwrite($fp, "type is $type");
                // our sql query
                $sql = "INSERT INTO `media` (`recordID`, `type`, `size`, `label`, `name`, `file_path`) VALUES (:recordID, :filetype, :filesize, :label, :filename, :raw_file)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':recordID', $recordID, PDO::PARAM_INT);
                $stmt->bindValue(':filetype', $filetype, PDO::PARAM_STR);
                $stmt->bindValue(':filesize', $file['size'], PDO::PARAM_STR);
                $stmt->bindValue(':label', $label, PDO::PARAM_STR);
                $stmt->bindValue(':filename', $file['name'], PDO::PARAM_STR);
                $stmt->bindValue(':raw_file', $raw_file, PDO::PARAM_STR);

                if (!$stmt->execute()) {

                    // log error
                    return '<data><error>file upload failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>';
                }
            } else {
                return '<data><error>upload failed</error><detail>File too big</detail></data>';
                // if the file is not less than the maximum allowed, print an error
            }
        }

        return '<data>ok</data>';
    }

    /**
     *
     * @param type $recordID
     * @param type $userid
     * @param type $db
     * @return string 
     */
    public function deleteMediaItem($recordID) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");


        $query = "SELECT file_path FROM `media` WHERE ID = $recordID;";
//echo $query;

        $result = mysqli_query($conn, $query) or die(' <data><error>select file to delete failed</error><detail>' . mysqli_error($conn) . ', query was ' . $query . '</detail></data>');


        while ($row = mysqli_fetch_array($result)) {
            $path = $row['file_path'];
            unlink($path);
        }


        $query = "DELETE FROM `media` WHERE ID = $recordID";

        $result = mysqli_query($conn, $query) or die('<data><error>file entry delete failed</error><detail>' . mysqli_error($conn) . '</detail></data>');

//   print('num rows affected:'.mysql_affected_rows());

        return '<data><result>' . ((mysqli_affected_rows($conn) > 0) ? 'true' : 'false') . '</result></data>';
    }

    /**
     *
     * @global type $CFG
     * @param type $mediaID
     * @param type $getbig
     * @param type $db 
     */
    public function displayMediaThumb($mediaID, $getbig) {
        global $CFG;
        $thumb_size_x = $getbig ? 240 : 75;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// select the db

        $big = ($getbig == 'true');

        try {
            if (!isset($mediaID)) {
                print ('<data><error>ID not specified</error><detail></detail></data>');
//throw new Exception('ID not specified');
            }
            $id = (int) $mediaID;

            if ($id <= 0) {
                print ('<data><error>invalid ID specified</error><detail></detail></data>');
            }

            $query = "select * from media where ID = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                // log error
                return '<data><error>displayMediaThumb</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>';
            }
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            if (count($image) == 0) {
                print ('<data><error>Cannot get Image with specified ID ' . $mediaID . '</error><detail></detail></data>');
                return;
//throw new Exception('Image with specified ID not found');
            }
        } catch (Exception $ex) {
            print ('<data><error>' . $ex->getMessage() . '</error><detail></detail></data>');
//   exit;
        }
//$fp = fopen('log.txt', 'w');
        switch ($image['type']) {
            case 'jpeg':
//fwrite($fp, "image detected \n");
                $img = imagecreatefromjpeg($image['file_path']);

                break;
            case 'jpg':
//fwrite($fp, "image detected \n");
                $img = imagecreatefromjpeg($image['file_path']);

                break;
            case 'bmp':
//fwrite($fp, "image detected \n");
                $img = imagecreatefrombmp3($image['file_path']);
            case 'png':
//fwrite($fp, "image detected \n");
                $img = imagecreatefrompng($image['file_path']);
                break;
            case 'doc':
//fwrite($fp, "doc detected \n");
                $img = imagecreatefrompng($CFG->wwwroot . $CFG->basedir . '/icons/microsoft-word.png');
                break;
            case 'docx':
//fwrite($fp, "docx detected \n");
                $img = imagecreatefrompng($CFG->wwwroot . $CFG->basedir . '/icons/microsoft-word.png');
                break;
            case 'pdf':
//fwrite($fp, "pdf detected \n");
                $img = imagecreatefromgif($CFG->wwwroot . $CFG->basedir . '/icons/pdf.gif');
                break;
            case 'ppt':
                $img = imagecreatefromgif($CFG->wwwroot . $CFG->basedir . '/icons/powerpoint_logo.gif');
                break;
            case 'pptx':
                $img = imagecreatefromgif($CFG->wwwroot . $CFG->basedir . '/icons/powerpoint_logo.gif');
                break;
            // TODO movie files
            case 'mp4':
            case 'm4v':
            case 'mpg':
            case 'avi':
            case 'wmv':
                $img = imagecreatefromjpeg($image['thumb_path']);
                break;
            default:

                $img = imagecreatefrompng($CFG->wwwroot . $CFG->basedir . '/icons/unknown.png');
                break;
        }

        # define size of original image	
        $image_width = imagesx($img);
        $image_height = imagesy($img);
        $thumb_size_y = $thumb_size_x;


        # define images x AND y
        $thumb_width = $thumb_size_x;
        $factor = $image_width / $thumb_size_x;
        $thumb_height = intval($image_height / $factor);
        if ($thumb_height > $thumb_size_y) {
            $thumb_height = $thumb_size_y;
            $factor = $image_height / $thumb_size_y;
            $thumb_width = intval($image_width / $factor);
        }


//fclose($fp);
        $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

        imagecopyresized($thumb, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);
        header('Content-type: image/jpeg');
        imagejpeg($thumb);
        imagedestroy($thumb);
        return $thumb;
    }

    /**
     * 
     * @global type $CFG
     * @param type $media_id
     * @param type $user
     * @param type $dbversion
     * @param type $db
     * @return string
     */
    public function download_raw_media($media_id) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");
// select the db


        if ($media_id <= 0) {
            return "<data><error>Media with specified ID not found</error><detail>media id is invalid</detail></data>";
        }

        $query = sprintf("select * from media where ID = %d", $media_id);

        $result = mysqli_query($conn, $query);


        if (mysqli_num_rows($result) == 0) {
            return "<data><error>Media with specified ID not found</error><detail>no rows</detail></data>";
        }


        $file = mysqli_fetch_array($result);


        $using_ie6 = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') !== FALSE);

        //     print($file['name'] );
        //   print(file_exists($file['extra_path']) );
//        if (!$using_ie6) {
//            header("Pragma: no-cache");
//            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
//            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
//            // fwrite($fp, 'not in IE6');
//        } else {
        header("Pragma: ");
        header("Cache-Control: ");
//        }
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
//        header('Content-length: ' . $file['size']);
        return( readfile($file['file_path']));
    }

    private function imagecreatefrombmp3($filename) {
// version 1.00
        if (!($fh = fopen($filename, 'rb'))) {
            trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
            return false;
        }
// read file header
        $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
// check for bitmap
        if ($meta['type'] != 19778) {
            trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
            return false;
        }
// read image header
        $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
// read additional 16bit header
        if ($meta['bits'] == 16) {
            $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
        }
// set bytes and padding
        $meta['bytes'] = $meta['bits'] / 8;
        $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4) - floor($meta['width'] * $meta['bytes'] / 4)));
        if ($meta['decal'] == 4) {
            $meta['decal'] = 0;
        }
// obtain imagesize
        if ($meta['imagesize'] < 1) {
            $meta['imagesize'] = $meta['filesize'] - $meta['offset'];
// in rare cases filesize is equal to offset so we need to read physical size
            if ($meta['imagesize'] < 1) {
                $meta['imagesize'] = @filesize($filename) - $meta['offset'];
                if ($meta['imagesize'] < 1) {
                    trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
                    return false;
                }
            }
        }
// calculate colors
        $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
// read color palette
        $palette = array();
        if ($meta['bits'] < 16) {
            $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
// in rare cases the color value is signed
            if ($palette[1] < 0) {
                foreach ($palette as $i => $color) {
                    $palette[$i] = $color + 16777216;
                }
            }
        }
// create gd image
        $im = imagecreatetruecolor($meta['width'], $meta['height']);
        $data = fread($fh, $meta['imagesize']);
        $p = 0;
        $vide = chr(0);
        $y = $meta['height'] - 1;
        $error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';
// loop through the image data beginning with the lower left corner
        while ($y >= 0) {
            $x = 0;
            while ($x < $meta['width']) {
                switch ($meta['bits']) {
                    case 32:
                    case 24:
                        if (!($part = substr($data, $p, 3))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('V', $part . $vide);
                        break;
                    case 16:
                        if (!($part = substr($data, $p, 2))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('v', $part);
                        $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3);
                        break;
                    case 8:
                        $color = unpack('n', $vide . substr($data, $p, 1));
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 4:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 1:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        switch (($p * 8) % 8) {
                            case 0:
                                $color[1] = $color[1] >> 7;
                                break;
                            case 1:
                                $color[1] = ($color[1] & 0x40) >> 6;
                                break;
                            case 2:
                                $color[1] = ($color[1] & 0x20) >> 5;
                                break;
                            case 3:
                                $color[1] = ($color[1] & 0x10) >> 4;
                                break;
                            case 4:
                                $color[1] = ($color[1] & 0x8) >> 3;
                                break;
                            case 5:
                                $color[1] = ($color[1] & 0x4) >> 2;
                                break;
                            case 6:
                                $color[1] = ($color[1] & 0x2) >> 1;
                                break;
                            case 7:
                                $color[1] = ($color[1] & 0x1);
                                break;
                        }
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    default:
                        trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
                        return false;
                }
                imagesetpixel($im, $x, $y, $color[1]);
                $x++;
                $p += $meta['bytes'];
            }
            $y--;
            $p += $meta['decal'];
        }
        fclose($fh);
        return $im;
    }

}

?>
