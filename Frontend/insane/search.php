<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <script src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
        <meta charset="UTF-8">
        <title>IIS | Search results</title>
        <script type="text/javascript">
            document.title = "IIS | Search results"
        </script>
    </head>
    <body>
        <?php
            $rel_feedback = false;
            
            //Relevance search
            //Write to file every single relevant picture and then
            //execute the query
            if(isset($_GET['rel-search'])) {
                $rel_feedback = true;
                $myfile = fopen("relevance.TXT", "w") or die("Unable to open file!");
                foreach($_POST['checkbox'] as $checkbox) {
                    fwrite($myfile, $checkbox);
                }
                fclose($myfile);
                $limit = $_POST['limiter'];
                //Run relevance feedback command
                exec("3ISearcher.exe -j rel -l $limit");
            }
            
            //Upload a file for search
            function uploadFileForSearch() {
                $target_dir = "search/";
                $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
                $uploadOk = 1;
                $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                
                // Check if image file is a actual image or fake image
                if(isset($_POST["submit"])) {
                    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
                    if($check !== false) {
                        $uploadOk = 1;
                    } else {
                        $uploadOk = 0;
                    }
                }
                // Allow certain file formats
                if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif" && $imageFileType != "JPG" && $imageFileType != "PNG" && $imageFileType != "JPEG"
                && $imageFileType != "GIF") {
                    $uploadOk = 0;
                }
                
                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 0) {
                    $imageFileType = "jpg";
                    
                // if everything is ok, try to upload file
                } else {
                    $username = "search_image";
                    if (file_exists($target_dir.$username.".".$imageFileType)) { 
                        unlink ($target_dir.$username.".".$imageFileType);    
                    }
                    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_dir.$username.".jpg")) {
                        return $imageFileType;
                    } else {
                        return null;
                    }
                }
            }
            
            //Read the results stored in the results.txt file
            //and display them
            function readResults() {
                $array = array();
                $i = 0;
                $handle = fopen("results.txt", "r");
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        $pieces = explode(" ", $line);
                        $array[$i] = $pieces[1];
                        for($j=2; $j<sizeof($pieces); $j++) {
                            $array[$i] = $array[$i].' '.$pieces[$j];
                        }
                        
                        $i++;
                        $array[$i] = $pieces[0];
                        $i++;
                    }
                } else {
                    // error opening the file.
                } 
                fclose($handle);
                return $array;
            }
            
            //Simple search
            if(!$rel_feedback) {
                $type = uploadFileForSearch();
                $file = "search/search_image.jpg";
                if($file!=null) {
                    $limit = $_POST['limiter'];
                    
                    //Search for the image
                    exec("3ISearcher.exe -j search -p $file -l $limit");
                }
            }
            $files = array();
            $files = readResults();
            ?>
        <div id="title">Search results</div>
        <?php if(!$rel_feedback) { ?>
        <div id="details3">Your search for the image above, gave the following results. For relevance feedback, click up to three images and hit "Submit and search".</div>
        <br/>
        <div id="search-container"><img id="search-image" src="<?php echo $file; ?>" onload="imageResize()" border="1px"/><div id="search-image-details">
                <b>Width:</b> <script type="text/javascript">document.write(document.getElementById('search-image').clientWidth);</script> pixels<br/><br/>
                <b>Height:</b> <script type="text/javascript">document.write(document.getElementById('search-image').clientHeight);</script> pixels<br/><br/>
                <b>Time added:</b> <?php echo(date('d')."-".date('m')."-".date('Y').", ".date('G').":".date('i').":".date('s'));?><br/><br/>
                <b>Type:</b> <?php $type; echo strtoupper($type);?><br/><br/>
                <a href="index.php?page=statistics.php&search_image=true" title="Draw the histogram of the search image" target="_blank">Draw the image's histogram</a>
            </div>
        </div>
        <?php } else { ?>
        <div id="details3">These are the images that correspond more to the images you supplied before. You can still select up to three images for relevance feedback.</div>
        <br/>
        <?php } ?>
        <script type="text/javascript">
            
            //Resize the query image
            function imageResize() {
                var img = document.getElementById('search-image');
                var width = img.clientWidth;
                var height = img.clientHeight;
                if(width < 200) {
                    document.getElementById('search-image').style.width = width;
                }
                if(height > 200) {
                    document.getElementById('search-image').removeAttribute("width");
                    document.getElementById('search-image').style.height = '200px';
                }
            }
            
            //Resize the image results
            function imageResize2(i) {
                var img = document.getElementById('search-result-'+i);
                var width = img.clientWidth;
                var height = img.clientHeight;
                if(width < 150) {
                    document.getElementById('search-result-'+i).style.width = width;
                }
                if(height > 150) {
                    document.getElementById('search-result-'+i).removeAttribute("width");
                    document.getElementById('search-result-'+i).style.height = '150px';
                }
            }
            
            //JQuery scroll animation for the relevance feedback button
            $(document).ready(function () {
               var el = $('#rel-feedback');
               var originalelpos = el.offset().top; // take it where it originally is on the page

               //run on scroll
               $(window).scroll(function () {
                   var el = $('#rel-feedback'); // important! (local)
                   //var elpos = el.offset().top; // take current situation
                   var windowpos = $(window).scrollTop();
                   var finaldestination = windowpos + originalelpos;
                   el.stop().animate({ 'top': finaldestination }, 300);
               });
           });
           
           //JQuery scroll animation for the search image
           $(document).ready(function () {
               var el = $('#search-container');
               var originalelpos = el.offset().top; // take it where it originally is on the page

               //run on scroll
               $(window).scroll(function () {
                   var el = $('#search-container'); // important! (local)
                   //var elpos = el.offset().top; // take current situation
                   var windowpos = $(window).scrollTop();
                   var finaldestination = windowpos + originalelpos;
                   el.stop().animate({ 'top': finaldestination }, 300);
               });
           });
           
           //Expand or contract the table of results    
           function resizeTable() {
               var e = document.getElementById('image-results');
               var g = document.getElementById('search-container');
               if(e.style.width == "500px") {
                   g.setAttribute("hidden", "hidden");
                   e.style.width = "1050px";
               } else {
                   e.style.width = "500px";
                   g.removeAttribute("hidden");
               }
           }
        </script>
        <form id="image-results" method="post" enctype="multipart/form-data" action="index.php?page=search.php&rel-search=true" style="width: 500px;">
            <div id="rel-feedback"><input type="submit" value="Submit and search" id="submit"/></div>
            <div id="expand" style="float: right;"><img src="images/expand.png" title="Expand or contract the search results table" onclick="resizeTable()" style="cursor: pointer;"/></div>
            <select style="float:right;" id="limiter" name="limiter" required="required" title="Limit the number of results" style="margin-top: 5px;">
                <option value="10">Top 10 images</option>
                <option value="20" selected="selected">Top 20 images</option>
                <option value="50">Top 50 images</option>
                <option value="100">Top 100 images</option>
                <option value="200">Top 200 images</option>
                <option value="500">Top 500 images</option>
                
            </select>
        <br/>
        <br/>
        <br/>
        
        <?php
            if($rel_feedback) {
                ?> 
                <script type="text/javascript">
                    var e = document.getElementById('image-results');
                    
                    e.style.float = "none";
                    e.style.marginLeft = "50px";
                    e.style.width = "1100px";
                    document.getElementById('expand').setAttribute("hidden", "hidden");
                    
                </script>
                <?php
            }
            $iter = 0;
            $ii = 0;
            for($i=0;$i<count($files);$i+=2) {
                
                //$similarity = $files[$i+1];
                $filename = $files[$i];
                ?> 
            <div id="result">
                <a href="index.php?page=statistics.php&image=<?php echo $filename; ?>" target="_blank">
            <img style="border-color: black; border-width: thin; border-style: solid;" class="search-result" id="search-result-<?php echo $i ?>" src="<?php echo $filename?>" width="200px"/>
                </a>
            <input class="checkbox" id="checkbox[<?php echo $ii ?>]" name="checkbox[<?php echo $ii ?>]" onclick="changeColor(<?php echo $i ?>);" type="checkbox" value="<?php echo $filename ?>"/>
            
            </div>
                <?php
                $ii++;
                $iter++;
                if($iter == 4 && !$rel_feedback) {
                    $iter = 0;
                    ?> <br/><?php
                } elseif($iter == 4 && $rel_feedback) {
                    $iter = 0;
                    ?> <br/><?php
                }
            }
        ?>
                    
                </form>
        <script type="text/javascript">
            
            //Change the border color of the image, when the user clicks its checkbox
            function changeColor(i) {
                e = document.getElementById('search-result-'+i);
                if(e.getAttribute('style') === "border-color: black; border-width: thin; border-style: solid;" || e.getAttribute('style') === "border-color: black; border-width: thin; border-style: solid; transition: border-color .10s ease-in-out; transition: border-width .10s ease-in-out;") {
                    e.setAttribute('style', 'border-color: green; border-radius: 5px; border-width: medium; border-style: solid; transition: border-color .25s ease-in; transition: border-width .25s ease-in;'); 
                } 
                else {
                    e.setAttribute('style', 'border-color: black; border-width: thin; border-style: solid; transition: border-color .10s ease-in-out; transition: border-width .10s ease-in-out;');
                }
            }
            
            $("input[type=checkbox]").click(function() {
                var countchecked = $("input[type=checkbox]:checked").length;

                if(countchecked >= 3) {
                    $("input[type=checkbox]").not(':checked').attr("disabled", true);
                    document.getElementById("submit").removeAttribute("disabled");
                    //document.getElementById("submit").removeAttribute("disabled");
                }
                else {
                    $("input[type=checkbox]").not(':checked').attr("disabled", false);
                    //document.getElementById("submit").setAttribute("disabled", "disabled");
                    document.getElementById("submit").removeAttribute("disabled");
                }
            });
        </script>
    </body>
</html>
