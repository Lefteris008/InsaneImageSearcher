<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>IIS | Upload to server</title>
        <script type="text/javascript">
            document.title = "IIS | Upload to server"
        </script>
    </head>
    <body>
        <?php if(isset($_GET['action'])) {
            if($_GET['action'] == "submit") {
                
                //Function that deletes the image-db/ old folder
                function deleteDir($dirPath) {
                    if (! is_dir($dirPath)) {
                        throw new InvalidArgumentException("$dirPath must be a directory");
                    }
                    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                        $dirPath .= '/';
                    }
                    $files = glob($dirPath . '*', GLOB_MARK);
                    foreach ($files as $file) {
                        if (is_dir($file)) {
                            self::deleteDir($file);
                        } else {
                            unlink($file);
                        }
                    }
                    rmdir($dirPath);
                }
                
                //Function that upload images on the server
                function uploadFilesToDB($delete) {
                    
                    //Scan folder /image-db/ for already existing folders
                    $results = scandir('image-db/');
                    $target_dir = null;
                    foreach ($results as $result) {
                        if ($result === '.' or $result === '..') {
                            continue;
                        }
                        if (is_dir('image-db/' . $result)) {
                            $target_dir = 'image-db/' . $result;
                        }
                    }
                    
                    if($target_dir == null) {
                        $date = date('Y-m-d-H-i-s');
                        $target_dir = "image-db/".$date."/";
                        if(!file_exists($target_dir)) {
                            mkdir($target_dir, 0700);
                        }
                    }
                    //Delete already existing folders if the user has chosen so
                    //and create a new one
                    if($delete) {
                        deleteDir($target_dir.'/');
                        $date = date('Y-m-d-H-i-s');
                        $target_dir = "image-db/".$date."/";
                        if(!file_exists($target_dir)) {
                            mkdir($target_dir, 0700);
                        }
                    }
                    $target_file = $target_dir . '/'. basename($_FILES["fileToUpload"]["name"]);
                    $uploadOk = 1;
                    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                    // Check if image file is a actual image or fake image
                    if(isset($_POST["submit"])) {
                        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
                        if($check !== false) {
                            $uploadOk = 1;
                        } else {
                            $name = explode(".", $target_file);
                            $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
                            foreach($accepted_types as $mime_type) {
                                if($mime_type == $_FILES["fileToUpload"]["type"]) {
                                    $okay = true;
                                    break;
                                } 
                            }

                            $continue = strtolower($name[1]) == 'zip' ? true : false;
                            if(!$continue) {
                                return "fail-no-zip";
                            }
                            if(move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_dir.$_FILES["fileToUpload"]["name"])) {
                                
                                //Unzip
                                $zip = new ZipArchive();
                                $x = $zip->open($target_dir.$_FILES["fileToUpload"]["name"]);
                                if ($x === true) {	
                                    $zip->extractTo($target_dir);
                                    $zip->close();
                                    unlink($target_dir.$_FILES["fileToUpload"]["name"]);
                                    return "true";
                                }
                            } else {
                                return "fail-zip";
                            }
                        }
                        $uploadOk = 0;
                    }
                    // Allow certain file formats
                    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                    && $imageFileType != "gif" ) {
                        return "fail-no-photo";
                    }
                    if (file_exists($target_dir.'/'.$_FILES["fileToUpload"]["name"])) { 
                        unlink ($target_dir.'/'.$_FILES["fileToUpload"]["name"]);    
                    }
                    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_dir.'/'.$_FILES["fileToUpload"]["name"])) {
                        return "true";
                    } else {
                        return "fail-photo";
                    }
                    
                }
                
                //Function that indexes the images on the DB
                function indexToDB($delete) {
                    $target_dir = "image-db/";
                    $results = scandir($target_dir);
                    foreach ($results as $result) {
                        if ($result === '.' or $result === '..') {
                            continue;
                        }
                        if (is_dir($target_dir . '/' . $result)) {
                            //Index the files
                            if(!$delete) {
                                
                                exec("3ISearcher.exe -j index -p image-db\\$result");
                                //echo "<pre>$output</pre>";
                                //echo dirname(__FILE__).'\\insane\\image-db\\'. $result;
                                //exit();
                            } else { //$delete == true
                                exec("3ISearcher.exe -j index -p image-db\\$result -d yes");
                                //echo "<pre>$output</pre>";
                                //echo dirname(__FILE__).'\\insane\\image-db\\'. $result;
                                //exit();
                            }
                        }
                    }
                }
                $delete = $_POST['update-type'];
                if($delete == "new") {
                    $delete = true;
                } else {
                    $delete = false;
                }
                $success = uploadFilesToDB($delete);
                indexToDB($delete);
                header("Location: index.php?page=upload.php&action=upload&success=".$success);
            } elseif($_GET['action'] == "upload") {
                if(isset($_GET['success'])) {
                    if($_GET['success'] == "true") {
                        ?>
                        <div id="title-success">The files were successfully uploaded to server.</div>
                        <script type="text/javascript">
                            setTimeout(function() {
                                document.getElementById("title-success").style.visibility = "hidden"; 
                                document.getElementById("title-success").style.marginTop = "0px";
                            }, 2500);
                        </script>
                    <?php
                    } elseif($_GET['success'] == "fail-no-zip") {
                        ?> 
                        <div id="title-fail">The file you uploaded was not a zip file. Try again.</div>    
                        <?php
                    } elseif($_GET['success'] == "fail-zip") {
                        ?> 
                        <div id="title-fail">There was a problem uploading your zip file. Try again.</div>    
                        <?php
                    } elseif($_GET['success'] == "fail-no-photo") {
                        ?> 
                        <div id="title-fail">The file you uploaded was not an image file. Try again.</div>    
                        <?php
                    } elseif($_GET['success'] == "fail-photo") {
                        ?> 
                        <div id="title-fail">There was a problem uploading your image file. Try again.</div>    
                        <?php
                    }
                }
        ?>
                        <div style="display: none;" id="loader"><img id="loader_image" src="images/loader.gif"/>
                            <br/>
                            Uploading and indexing, please wait...
                        </div>
                        
        <script type="text/javascript">
            setTimeout(function() {
                document.getElementById("title-fail").style.visibility = "hidden"; 
                document.getElementById("title-fail").style.marginTop = "0px";
            }, 3000);
        </script>
        <div id="title">Upload data to server</div>
        <div id="upload-form-div">
            <form id="upload-form" action="upload.php?action=submit" method="post" enctype="multipart/form-data">
                <div id="details2">Upload a single picture or a set of pictures to the server.
                    Note that if you want to upload a set of pictures,<br/>
                    you need to compress them under no parent folder, into a single .zip file. Please be advised that the process<br/>
                    of uploading and indexing would take some time, depending on the size of the files.</div>
                <input accept="image*/" type="file" name="fileToUpload" id="filesToUpload" required="required"/>
                <br/>
                Select type of update
                <select onchange="warnUser()" id="update-type" name="update-type" required="required">
                    <option value="update" title="Updates existing dataset by adding and indexing only the new images">Update existing dataset</option>
                    <option value="new" title="Deletes previous dataset and creates a new one with new images">Delete everything and start fresh</option>
                </select> 
                <br/>
                <br/>
                <div id="warning" hidden="hidden"><b>WARNING:</b> You are about to drop the existing database.
                <br/>
                <br/></div>
                
                <script type="text/javascript">
                    
                    //Warn user that he is going to delete the DB
                    function warnUser() {
                        var e = document.getElementById("update-type");
                        var strUser = e.options[e.selectedIndex].value;
                        if(strUser=="new") {
                            document.getElementById("warning").removeAttribute("hidden");
                        } else {
                            document.getElementById("warning").setAttribute("hidden", "hidden");
                        }
                    }
                    
                    function displayLoader() {
                        var e = document.getElementById("loader");
                        e.style.display = "initial";
                    }
                </script>
                
                <input type="submit" value="Upload to server" name="submit" id="uploadButton" onclick="displayLoader()"/>
                <br/>
                <br/>
                <div>Note that you can only upload PNG and JPEG (JPG) with up to 1GB file limit <b>per file</b> (image or zip).
                    <br/>The server running the front-end must be configured to allow max execution time up to 1 hour (60 minutes).</div>
            </form>
        </div>
        <?php } 
            }
        ?>
    </body>
</html>
